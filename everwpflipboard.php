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
function everwpflipboard_install() {
    $recent_posts = wp_get_recent_posts(array(
        'numberposts' => 10, // Number of recent posts thumbnails to display
        'post_status' => 'publish' // Show only the published posts
    ));
    foreach ( $recent_posts as $post_item ) {
        update_post_meta( $post_item['ID'], 'everwpflipboard', 1 );
    }
}
function everwpflipboard_uninstall() {
    delete_post_meta_by_key('everwpflipboard');
}
// Meta Box Class: FlipboardXMLMetaBox
// Get the field value: $metavalue = get_post_meta( $post_id, $field_id, true );
class FlipboardXMLMetaBox{
    private $screen = array(
        'post',
        'page',                
    );
    private $meta_fields = array(
                array(
                    'label' => 'Add to Flipboard XML',
                    'id' => 'everwpflipboard',
                    'type' => 'checkbox',
                )

    );
    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_action( 'save_post', array( $this, 'save_fields' ) );
    }

    public function add_meta_boxes() {
        foreach ( $this->screen as $single_screen ) {
            add_meta_box(
                'FlipboardXML',
                __( 'FlipboardXML', 'textdomain' ),
                array( $this, 'meta_box_callback' ),
                $single_screen,
                'normal',
                'default'
            );
        }
    }
    public function meta_box_callback( $post ) {
        wp_nonce_field( 'FlipboardXML_data', 'FlipboardXML_nonce' );
        $this->field_generator( $post );
    }
    public function field_generator( $post ) {
        $output = '';
        foreach ( $this->meta_fields as $meta_field ) {
            $label = '<label for="' . $meta_field['id'] . '">' . $meta_field['label'] . '</label>';
            $meta_value = get_post_meta( $post->ID, $meta_field['id'], true );
            if ( empty( $meta_value ) ) {
                if ( isset( $meta_field['default'] ) ) {
                    $meta_value = $meta_field['default'];
                }
            }
            switch ( $meta_field['type'] ) {
                                case 'checkbox':
                                    $input = sprintf(
                                        '<input %s id=" %s" name="%s" type="checkbox" value="1">',
                                        $meta_value === '1' ? 'checked' : '',
                                        $meta_field['id'],
                                        $meta_field['id']
                                        );
                                    break;

                default:
                                    $input = sprintf(
                                        '<input %s id="%s" name="%s" type="%s" value="%s">',
                                        $meta_field['type'] !== 'color' ? 'style="width: 100%"' : '',
                                        $meta_field['id'],
                                        $meta_field['id'],
                                        $meta_field['type'],
                                        $meta_value
                                    );
            }
            $output .= $this->format_rows( $label, $input );
        }
        echo '<table class="form-table"><tbody>' . $output . '</tbody></table>';
    }
    public function format_rows( $label, $input ) {
        return '<tr><th>'.$label.'</th><td>'.$input.'</td></tr>';
    }
    public function save_fields( $post_id ) {
        if ( ! isset( $_POST['FlipboardXML_nonce'] ) )
            return $post_id;
        $nonce = $_POST['FlipboardXML_nonce'];
        if ( !wp_verify_nonce( $nonce, 'FlipboardXML_data' ) )
            return $post_id;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
            return $post_id;
        foreach ( $this->meta_fields as $meta_field ) {
            if ( isset( $_POST[ $meta_field['id'] ] ) ) {
                switch ( $meta_field['type'] ) {
                    case 'email':
                        $_POST[ $meta_field['id'] ] = sanitize_email( $_POST[ $meta_field['id'] ] );
                        break;
                    case 'text':
                        $_POST[ $meta_field['id'] ] = sanitize_text_field( $_POST[ $meta_field['id'] ] );
                        break;
                }
                update_post_meta( $post_id, $meta_field['id'], $_POST[ $meta_field['id'] ] );
            } else if ( $meta_field['type'] === 'checkbox' ) {
                update_post_meta( $post_id, $meta_field['id'], '0' );
            }
        }
        flipBoardFeed();
    }
}
if (class_exists('FlipboardXMLMetabox')) {
    new FlipboardXMLMetabox;
};
function flipBoardFeed() {
    $fields = array('name', 'description', 'wpurl', 'url', 'admin_email', 'charset', 'version', 'html_type', 'text_direction', 'language');
    $blogInfos = array();
    foreach($fields as $field) {
        $blogInfos[$field] = get_bloginfo($field);
    }
    $args = array(
        'meta_key' => 'everwpflipboard',
        'posts_per_page' => -1
    );
    $posts = get_posts($args);
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
        $author = get_user_by( 'id', $post->post_author );
        if (empty($post->post_excerpt)) {
            $postDescription = wp_trim_excerpt($post->post_content);
        } else {
            $postDescription = $post->post_excerpt;
        }
        $postThumbnail = get_the_post_thumbnail_url( $post->ID, 'medium' );
        $post_categories = wp_get_post_categories( $post->ID );
        $defaultCategory = '';
             
        foreach($post_categories as $c){
            $cat = get_category( $c );
            $defaultCategory = $cat->name;
        }
        $rss .= '<item>
        <title>'.$post->post_title.'</title>
        <link>'.get_permalink($post).'</link>
        <guid>'.get_permalink($post).'</guid>
        <pubDate>'.$post->post_date.'</pubDate>
        <dc:creator xmlns:dc="creator">'.$author->display_name.'</dc:creator>
        <description><![CDATA[
        '.strip_tags($postDescription).'
        ]]></description>';
        if ($postThumbnail) {
            $rss .= '<enclosure url="'.get_the_post_thumbnail_url( $post->ID, 'medium' ).'" length="1000" type="image/jpeg" />';
        }
        $rss .= '<category>'.$defaultCategory.'</category>
        </item>';
    }
    $rss.= '</channel>
    </rss>
    ';
    file_put_contents($flipBoardFile, $rss);
}
add_action('wp_head', 'flipBoardFeed');
register_activation_hook(__FILE__,'everwpflipboard_install');
register_deactivation_hook( __FILE__, 'everwpflipboard_uninstall' );
