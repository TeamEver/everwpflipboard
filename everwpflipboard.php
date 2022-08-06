<?php
/* @wordpress-plugin
 * Plugin Name:       Flipboard RSS plugin
 * Plugin URI:        http://www.team-ever.com
 * Description:       Flipboard RSS plugin for WordPress
 * Version:           1.0.1
 * Author:            Team Ever
 * Author URI:        https://www.team-ever.com
 * Text Domain:       everwpflipboard
 * Domain Path: /languages
 * License:           Tous droits réservés / Le droit d'auteur s'applique (All rights reserved / French copyright law applies)
 * Copyright:       Cyril CHALAMON - Team Ever
 * Author:       Cyril CHALAMON - Team Ever
 */
if (!defined('ABSPATH')) {
  exit; // Exit if accessed directly
}
function flipBoardFeed() {
    $postNumbers = (int)get_option( 'everwpflipboard_qty' );
    if ($postNumbers <= 0) {
        $postNumbers = 50;
    }
    $fields = array('name', 'description', 'wpurl', 'url', 'admin_email', 'charset', 'version', 'html_type', 'text_direction', 'language');
    $blogInfos = array();
    foreach($fields as $field) {
        $blogInfos[$field] = get_bloginfo($field);
    }
    $posts = wp_get_recent_posts(array(
        'numberposts' => 50, // Number of recent posts thumbnails to display
        'post_status' => 'publish' // Show only the published posts
    ));
    $flipBoardFile = 'flipboard.xml';
    $rss = '<rss xmlns:atom="http://www.w3.org/2005/Atom" xmlns:media="http://search.yahoo.com/mrss/" xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd" xmlns:creativeCommons="http://backend.userland.com/creativeCommonsRssModule" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:sy="http://purl.org/rss/1.0/modules/syndication/" version="2.0">
    <channel>
    <title>'.$blogInfos['name'].'</title>
    <link>'.$blogInfos['url'].'</link>
    <description>
    '.$blogInfos['description'].'
    </description>
    <language>'.$blogInfos['language'].'</language>';
    foreach ($posts as $post) {
        $author = get_user_by( 'id', $post['post_author'] );
        if (empty($post['post_excerpt'])) {
            $postDescription = wp_trim_excerpt($post['post_content']);
        } else {
            $postDescription = $post['post_excerpt'];
        }
        $postThumbnail = get_the_post_thumbnail_url( $post['ID'], 'medium' );
        $post_categories = wp_get_post_categories( $post['ID'] );
        $defaultCategory = '';
             
        foreach($post_categories as $c){
            $cat = get_category( $c );
            $defaultCategory .= $cat->name;
        }
        $rss .= '<item>
        <title>'.sanitize_text_field($post['post_title']).'</title>
        <link>'.get_permalink($post['ID']).'</link>
        <guid>'.get_permalink($post['ID']).'</guid>
        <pubDate>'.sanitize_text_field($post['post_date_gmt']).'</pubDate>
        <dc:creator xmlns:dc="creator">'.$author->display_name.'</dc:creator>
        <description><![CDATA[
        '.strip_tags(sanitize_text_field($postDescription)).'
        ]]>
        </description>';
        if ($postThumbnail) {
            $rss .= '<enclosure url="'.sanitize_url($postThumbnail).'" length="1000" type="image/jpeg" />';
        }
        if ($defaultCategory) {
            $rss .= '<category>'.sanitize_text_field($defaultCategory).'</category>';
        }
        $rss .= '</item>';
    }
    $rss.= '</channel>
    </rss>
    ';
    file_put_contents(ABSPATH . '/' .$flipBoardFile, $rss);
}
add_action( 'save_post', 'flipBoardFeed', 10, 3 );
add_action( 'admin_init', 'everwpflipboard_register_settings' );

/* 
 * Register settings 
 */
function everwpflipboard_register_settings() {
    register_setting( 
        'general', 
        'everwpflipboard_qty',
        'esc_html' // <--- Customize this if there are multiple fields
    );
    add_settings_section( 
        'site-everwpflipboard-qty', 
        'FlipBoard XML file generation', 
        '__return_false', 
        'general' 
    );
    add_settings_field( 
        'everwpflipboard_qty', 
        'Select how many posts to be add on the Flipboard XML file', 
        'everwpflipboard_print_input_number', 
        'general', 
        'site-everwpflipboard-qty' 
    );
}    

/* 
 * Print settings field content 
 */
function everwpflipboard_print_input_number() 
{
    $everwpflipboard_qty = html_entity_decode( get_option( 'everwpflipboard_qty' ) );
    echo '<input type="number" id="everwpflipboard_qty" name="everwpflipboard_qty" value="' . $everwpflipboard_qty . '" />';
}