<?php
/* @wordpress-plugin
 * Plugin Name:       Flipboard RSS plugin
 * Plugin URI:        http://www.team-ever.com
 * Description:       Flipboard RSS plugin for WordPress
 * Version:           2.0.1
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
    $everwpflipboardQty = (int)get_option( 'everwpflipboard_qty' );
    if ($everwpflipboardQty <= 0) {
        $everwpflipboardQty = 50;
    }
    $fields = array('name', 'description', 'wpurl', 'url', 'admin_email', 'charset', 'version', 'html_type', 'text_direction', 'language');
    $blogInfos = array();
    foreach($fields as $field) {
        $blogInfos[$field] = get_bloginfo($field);
    }
    $posts = wp_get_recent_posts(array(
        'numberposts' => (int)$everwpflipboardQty, // Number of recent posts thumbnails to display
        'post_status' => 'publish' // Show only the published posts
    ));
    $flipBoardFile = 'flipboard.xml';
    $dom = new DOMDocument();
    $dom->encoding = 'UTF-8';
    $dom->xmlVersion = '1.0';
    $dom->formatOutput = true;

    // Add elements
    $nodeDocument = $dom->createElement('channel');
    $dom->appendChild($nodeDocument);

    $titleNode = $dom->createElement('title', $blogInfos['name']);
    $nodeDocument->appendChild($titleNode);

    $linkNode = $dom->createElement('link', $blogInfos['url'].'/flipboard.xml');
    $nodeDocument->appendChild($linkNode);

    $descriptionNode = $dom->createElement('description', $blogInfos['name']);
    $nodeDocument->appendChild($descriptionNode);

    $languageNode = $dom->createElement('language', strtolower($blogInfos['language']));
    $nodeDocument->appendChild($languageNode);

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
            $defaultCategory = $cat->name;
        }
        $datetime = new DateTime($post['post_date_gmt']);
        $postDate = $datetime->format('c');

        $itemNode = $dom->createElement('item');
        $nodeDocument->appendChild($itemNode);

        $titlePostNode = $dom->createElement('title', sanitize_text_field($post['post_title']));
        $itemNode->appendChild($titlePostNode);

        $linkPostNode = $dom->createElement('link', get_permalink($post['ID']));
        $itemNode->appendChild($linkPostNode);

        $guidPostNode = $dom->createElement('guid', get_permalink($post['ID']));
        $itemNode->appendChild($guidPostNode);

        $pubDatePostNode = $dom->createElement('pubDate', sanitize_text_field($postDate));
        $itemNode->appendChild($pubDatePostNode);

        // $dcCreatorPostNode = $dom->createElement('dc:creator', sanitize_text_field($author->display_name));
        $dcCreatorPostNode = $dom->createElement('dc', sanitize_text_field($author->display_name));
        $itemNode->appendChild($dcCreatorPostNode);

        $descriptionPostNode = $dom->createElement(
            'description',
            html_entity_decode(
                strip_tags(
                    sanitize_text_field($postDescription)
                )
            )
        );
        $itemNode->appendChild(
            $descriptionPostNode
        );

        if ($postThumbnail) {
            $enclosurePostNode = $dom->createElement('enclosure');
            $urlAttr = new DOMAttr('url', sanitize_url($postThumbnail));
            $enclosurePostNode->setAttributeNode($urlAttr);
            $lengthAttr = new DOMAttr('length', '1000');
            $enclosurePostNode->setAttributeNode($lengthAttr);
            $enclosurePostNode->setAttributeNode($urlAttr);
            $typeAttr = new DOMAttr('type', 'image/jpeg');
            $enclosurePostNode->setAttributeNode($typeAttr);
            $itemNode->appendChild($enclosurePostNode);
        }
        if ($defaultCategory) {
            $categoryPostNode = $dom->createElement('category', sanitize_text_field($defaultCategory));
            $itemNode->appendChild($categoryPostNode);
        }
    }

    $dom->save(ABSPATH . '/' .$flipBoardFile);
}
/* 
 * Register settings 
 */
function everwpflipboard_register_settings() {
    register_setting( 
        'general', 
        'everwpflipboard_qty',
        'esc_html'
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
function everwpflipboard_print_input_number() {
    $everwpflipboardQty = get_option( 'everwpflipboard_qty' );
    echo '<input type="number" id="everwpflipboard_qty" name="everwpflipboard_qty" value="' . $everwpflipboardQty . '" />';
}
add_action( 'save_post', 'flipBoardFeed', 10, 3 );
add_action( 'admin_init', 'everwpflipboard_register_settings' );
