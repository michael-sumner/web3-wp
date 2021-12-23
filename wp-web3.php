<?php

/**
 * @wordpress-plugin
 * Plugin Name: WP Web 3.0
 * Description: Allow your users to log into your WordPress site using Web 3.0 sign-on.
 * Version: 1.0.0
 * Requires at least: 4.0
 * Requires PHP: 5.6
 * Author: Michael Bryan Sumner
 * Author URI: https://smnr.co/wp-web3
 * License: GPL-2.0+
 * Text Domain: wp-web3
 * Domain Path: /languages
 *
 * @link https://smnr.co/wp-web3
 * @since 1.0.0
 * @package WP_Web3_Login
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die('Hey there...');
}

// todo allow plugin option for password fallback, if user doesn't have metamask installed.
// options: disable password sign-in, allow password sign-in fallback for users without metamask (default).

function wp_web3_enqueue_scripts()
{
    wp_enqueue_style('wp-web3', plugin_dir_url(__FILE__) . 'static/css/app.css', array(), '1.0.0');
    wp_enqueue_script('wp-web3', plugin_dir_url(__FILE__) . 'static/js/web3.min.js', array(), '1.2.84', true);
    wp_enqueue_script('wp-web3-app', plugin_dir_url(__FILE__) . 'static/js/app.js', array('jquery'), '1.0.0', true);
    wp_localize_script('wp-web3', 'wp_web3', array(
        'nonce'     => wp_create_nonce('wp_web3_nonce'),
        'ajaxurl'   => admin_url('admin-ajax.php'),
        'pluginurl' => plugin_dir_url(__FILE__),
    ));
}
add_action('login_enqueue_scripts', 'wp_web3_enqueue_scripts');

function wp_web3()
{
    check_ajax_referer('wp_web3_nonce');

    if (!isset($_POST['data'])) {
        wp_send_json_error();
    }

    // retrieve values and sanitize.
    $post_data = $_POST['data'];

    $public_address = sanitize_post($post_data['public_address'], 'raw');
    $user_login     = sanitize_user($post_data['user_login']);
    $_ajax_nonce    = sanitize_post($_POST['_ajax_nonce']);

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
    update_user_meta($user_id, 'wp_web3_public_address', $public_address);

    // log the user in.
    wp_set_auth_cookie($user_id);

    $redirect_url = wp_nonce_url(admin_url(), $_ajax_nonce);

    // return values
    $data = array(
        'redirect_url' => esc_url($redirect_url),
    );
    wp_send_json_success($data);
}
add_action('wp_ajax_wp_web3', 'wp_web3');
add_action('wp_ajax_nopriv_wp_web3', 'wp_web3');

/**
 * On user post meta update, if it contains an eth address, then sign the user out, for security.
 *
 * @param int    $meta_id     ID of updated metadata entry.
 * @param int    $object_id   ID of the object metadata is for.
 * @param string $meta_key    Metadata key.
 * @param mixed  $_meta_value Metadata value. Serialized if non-scalar.
 * @return void
 */
function wp_web3_user_public_address_updated($meta_id, $object_id, $meta_key, $_meta_value)
{
    if ($meta_key !== 'wp_web3_public_address') {
        return;
    }

    // if meta_value has become invalid eth address, then sign the user out.
    if (!preg_match('/^0x[a-fA-F0-9]{40}$/', $_meta_value)) {

        $user = get_userdata((int) $object_id);

        if (!$user) {
            return;
        }

        $sessions = WP_Session_Tokens::get_instance($user->ID);

        $sessions->destroy_all();
    }
}
add_action('updated_user_meta', 'wp_web3_user_public_address_updated', 10, 4);
