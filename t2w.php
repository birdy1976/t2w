<?php
/**
 * @package T2W
 * @version 0.2
 */
/*
Plugin Name: T2W
Plugin URI: https://github.com/birdy1976/t2w/
Description: T2W (twitter2wordpress) posts tweets of a public Twitter account automatically to a WordPress blog.
Author: birdy1976
Version: 0.2
Author URI: https://twitter.com/#!/birdy1976/
*/

// http://codex.wordpress.org/Function_Reference/fetch_feed
include_once(ABSPATH . WPINC . '/feed.php');
include_once("oauth.php");

// http://www.slideshare.net/ronalfy/wordpress-plugin-localization
add_action('init', 't2w_init');

function t2w_init(){
	load_plugin_textdomain('t2w', false, dirname(plugin_basename(__FILE__)).'/languages/');
}

// http://codex.wordpress.org/Function_Reference/wp_schedule_event
register_activation_hook(__FILE__, 't2w_cron_activate');
register_deactivation_hook(__FILE__, 't2w_cron_deactivate');
add_action('t2w_cron_event', 't2w_cron_hourly');

function t2w_cron_activate(){
	if(!wp_next_scheduled('t2w_cron_event')){
		wp_schedule_event(time(), 'hourly', 't2w_cron_event');
	}
}

function t2w_cron_deactivate(){
	wp_clear_scheduled_hook('t2w_cron_event');
}

// http://codex.wordpress.org/Writing_a_Plugin#WordPress_Options_Mechanism
add_option('t2w_latest_status', '219716066530689024');

// Get hashtags (initial #) and usernames (initial @) as tags
function t2w_get_tags($string, $initial_char){
	// http://stackoverflow.com/questions/3060601/retrieve-all-hashtags-from-a-tweet-in-a-php-function
	preg_match_all('/('.$initial_char.'\w+)/', $string, $tags);
	array_walk($tags[0], create_function('&$tag', '$tag = substr($tag, 1);'));
	return $tags[0];
}

function t2w_convert_encoding($m){
	return mb_convert_encoding($m[1], 'UTF-8', 'HTML-ENTITIES');
}

// http://stackoverflow.com/questions/2758736/multibyte-strtr-mb-strtr
function t2w_unaccent($string){
	// http://www.php.net/manual/en/function.html-entity-decode.php#104617
	$string = preg_replace_callback('/(&#[0-9]+;)/', 't2w_convert_encoding', $string);
    if(strpos($string = htmlentities($string, ENT_QUOTES, 'UTF-8'), '&') !== false){
		// Changed "lig" to "zlig" because of "ß" aka &szlig;
        $string = html_entity_decode(
			preg_replace('~&([a-z]{1,2})(?:acute|cedil|circ|grave|orn|ring|slash|tilde|uml|zlig);~i', '$1', $string),
			ENT_QUOTES, 'UTF-8');
	}
	return $string;
}

function t2w_cron_hourly(){
	$o = get_option('t2w_options');
	// https://dev.twitter.com/docs/api/1/get/statuses/user_timeline
	$status = get_option('t2w_latest_status');
	// functions provided by <oauth.php>
	$bearer_token = get_bearer_token();
	// http://stackoverflow.com/questions/6815520/cannot-use-object-of-type-stdclass-as-array
	$json_items = json_decode(search_for_a_user($bearer_token, $o['username']), true);
	invalidate_bearer_token($bearer_token);
	if(count($json_items) != 0 && !isset($json_items["error"])){
		$tags = get_tags(); $slugs = array(); foreach ($tags as $tag){array_push($slugs, $tag->slug);}
		$status1; $status2 = $status; $status3 = $status;
	    foreach ($json_items as $item){
			$status1 = $item["id_str"];
			$tweet = esc_html($item["text"]);
			if(bccomp($status1, $status2) == 1){
				// http://codex.wordpress.org/Function_Reference/wp_insert_post
				$post = array();
				$post['post_title'] = $tweet;
				// e.g. https://twitter.com/caro__b/status/350249279908089856
				$post['post_content'] = 'https://twitter.com/'.$o['username']."/status/".$status1;
				// http://stackoverflow.com/questions/7223436/parsing-the-twitter-api-created-at
				$post['post_date'] = date('Y-m-d H:i:s', strtotime($item["created_at"]));
				$post['post_status'] = 'publish';
				$post['post_author'] = 1;
				$post['post_category'] = array($o['category']);
				// Collect as many tags as possible
				$tweet = strtolower(t2w_unaccent($tweet));
				$tweet = preg_replace('/[^a-z0-9@#\s]/', '', $tweet);
				$replace = array($o['username']);
				$tweet = str_replace($replace, ' ', $tweet);
				$words = split(' ', $tweet);
				$tags = array_intersect($slugs, $words);
				// Get new tags from hashtags and usernames but skip personal username
				$tags = array_merge($tags, t2w_get_tags($tweet, '#'), t2w_get_tags($tweet, '@'));
				$post['tags_input'] = implode(',', $tags);
				wp_insert_post($post);
				if(bccomp($status1, $status3) == 1){$status3 = $status1;}
			}
			if(bccomp($status3, $status2) == 1){update_option('t2w_latest_status', $status3);}
		}
	}
}

// http://planetozh.com/blog/2009/05/handling-plugins-options-in-wordpress-28-with-register_setting/
add_action('admin_init', 't2w_options_init');
add_action('admin_menu', 't2w_options_add_pages');

function t2w_options_init(){
	register_setting('t2w_set_of_options', 't2w_options', 't2w_sanitize_options');
}

function t2w_plugin_action_links($links, $file){
	// Add link to T2W settings only to T2W action links
	if($file == dirname(plugin_basename(__FILE__).'/t2w.php')){
		$links[] = '<a href="plugins.php?page='.$file.'">'.__('Settings', 't2w').'</a>';
	}
	return $links;
}

function t2w_options_add_pages(){
	add_options_page(__('T2W Settings', 't2w'), __('T2W Settings', 't2w'), 'administrator', __FILE__, 't2w_options_do_page');
	add_plugins_page(__('T2W Settings', 't2w'), __('T2W Settings', 't2w'), 'administrator', __FILE__, 't2w_options_do_page');
	add_filter('plugin_action_links', 't2w_plugin_action_links', 10, 2);
}

function t2w_options_do_page(){
	?>
	<div class="wrap">
		<div id="icon-plugins" class="icon32"></div>
		<h2><?php _e('T2W Settings', 't2w'); ?></h2>
		<?php if($_GET['settings-updated']){
			// http://wordpress.org/support/topic/how-to-display-the-settings-updated-message#post-2097438
			echo '<div id="message" class="updated"><p>'.__('Settings saved', 't2w').'</p></div>';
		} ?>
		<form method="post" action="options.php">
			<?php settings_fields('t2w_set_of_options'); ?>
			<?php $o = get_option('t2w_options'); ?>
			<table class="form-table">
				<tr valign="top"><th scope="row"><?php _e('Twitter username', 't2w'); ?></th>
					<td><input type="text" name="t2w_options[username]" value="<?php echo $o['username']; ?>" /></td>
				</tr>
				<tr valign="top"><th scope="row"><?php _e('WordPress category', 't2w'); ?></th>
					<td><?php wp_dropdown_categories('name=t2w_options%5Bcategory%5D&show_count=1&hide_empty=0&selected='.$o['category']); ?></td>
				</tr>
			</table>
			<p class="submit">
			<input type="submit" class="button-primary" value="<?php _e('Save changes', 't2w') ?>" />
			</p>
		</form>
	</div>
	<?php	
}

function t2w_sanitize_options($input){	
	$input['username'] =  preg_replace('/[^a-z0-9]/', '', strtolower($input['username']));
	$input['category'] = intval($input['category']);
	// Execute cron as soon as possible
	wp_schedule_single_event(time(), 't2w_cron_event');	
	return $input;
}

// http://jacobsantos.com/2008/general/wordpress-27-plugin-uninstall-methods/
register_uninstall_hook(__FILE__, 't2w_uninstall');

function t2w_uninstall(){
	delete_option('t2w_latest_status');
	delete_option('t2w_options');
}

?>
