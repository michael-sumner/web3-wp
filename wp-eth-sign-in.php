<?php

/**
 * @wordpress-plugin
 * Plugin Name: WP Ethereum Login
 * Description: Allow your users to log into your WordPress site using Ethereum sign-on.
 * Version: 1.0.0
 * Requires at least: 4.0
 * Requires PHP: 5.6
 * Author: Michael Bryan Sumner
 * Author URI: https://smnr.co/wp-eth-login
 * License: GPL-2.0+
 * Text Domain: wp-eth-login
 * Domain Path: /languages
 *
 * @link https://smnr.co/wp-eth-login
 * @since 1.0.0
 * @package WP_Ethereum_Login
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die('Hey there...');
}

function wp_eth_enqueue_scripts()
{
    wp_enqueue_style('wp-eth', plugins_url() . '/' . basename(__FILE__, '.php') . '/assets/css/app.css', array(), '1.0.0');
    wp_enqueue_script('wp-eth-web3', plugins_url() . '/' . basename(__FILE__, '.php') . '/assets/js/web3.min.js', array(), '1.2.84', true);
    wp_enqueue_script('wp-eth', plugins_url() . '/' . basename(__FILE__, '.php') . '/assets/js/app.js', array('jquery'), '1.0.0', true);
    wp_localize_script('wp-eth', 'wp_eth_login', array('nonce' => wp_create_nonce('wp_eth_login_nonce'), 'ajaxurl' => admin_url('admin-ajax.php')));
}
add_action('login_enqueue_scripts', 'wp_eth_enqueue_scripts');

function wp_eth_login()
{
    check_ajax_referer('wp_eth_login_nonce');

    if (!isset($_POST['data'])) {
        wp_send_json_error();
    }

    // retrieve values and sanitize.
    $post_data = $_POST['data'];

    $public_address = sanitize_post($post_data['public_address'], 'raw');
    $user_login     = sanitize_user($post_data['user_login']);

    if (empty($user_login)) {
        wp_send_json_error('Username / email not specified.');
    }

    // check $user_login
    if (strpos($user_login, '@')) {
        // sanitize user email
        $user_login = sanitize_email($user_login);
        $user_data  = get_user_by('email', trim(wp_unslash($user_login)));
    } else {
        // sanitize username
        $user_login = sanitize_user($user_login);
        $user_data  = get_user_by('login', trim(wp_unslash($user_login)));
    }

    $user_id = $user_data->ID;

    if (!$user_id) {
        wp_send_json_error('User does not exist');
    }

    // update user meta.
    update_user_meta($user_id, 'wp_eth_public_address', $public_address);

    // log the user in.
    wp_set_auth_cookie($user_id);

    $redirect_url = admin_url();

    // return values
    $data = array(
        'redirect_url' => $redirect_url,
    );
    wp_send_json_success($data);
}
add_action('wp_ajax_wp_eth_login', 'wp_eth_login');
add_action('wp_ajax_nopriv_wp_eth_login', 'wp_eth_login');
