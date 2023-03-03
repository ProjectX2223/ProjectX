<?php
/**
 * Plugin Name: ChatGPT Plugin
 * Plugin URI: https://www.example.com/
 * Description: Adds a ChatGPT chat window to the bottom of each post.
 * Version: 1.0
 * Author: chang
 * Author URI: https://www.example.com/
 */

// Add the necessary CSS and JavaScript files.
function chatgpt_add_scripts() {
    wp_enqueue_style( 'chatgpt-style', plugins_url( 'css/chatgpt.css', __FILE__ ) );
    wp_enqueue_script( 'chatgpt-script', plugins_url( 'js/chatgpt.js', __FILE__ ), array(), '1.0.0', true );
}
add_action( 'wp_enqueue_scripts', 'chatgpt_add_scripts' );

// Add the ChatGPT chat window to the bottom of each post.
function chatgpt_add_chat_window( $content ) {
    $chat_window = '<div id="chatgpt-chat-window"></div>';
    return $content . $chat_window;
}
add_filter( 'the_content', 'chatgpt_add_chat_window' );
