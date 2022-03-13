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
 * @package WP_Web3
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die('Hey there...');
}

// todo allow plugin option for password fallback, if user doesn't have metamask installed.
// options: disable password sign-in, allow password sign-in fallback for users without metamask (default).

function wp_web3_enqueue_scripts()
{
    wp_enqueue_style('wp-web3', plugin_dir_url(__FILE__) . 'public/css/loginform.css', array(), '1.0.0');
    wp_enqueue_script('web3', 'https://unpkg.com/web3@1.2.11/dist/web3.min.js', array(), '1.0.0', true);
    wp_enqueue_script('web3modal', 'https://unpkg.com/web3modal@1.9.0/dist/index.js', array(), '1.0.0', true);
    wp_enqueue_script('evm-chains', 'https://unpkg.com/evm-chains@0.2.0/dist/umd/index.min.js', array(), '1.0.0', true);
    wp_enqueue_script('@walletconnect', 'https://unpkg.com/@walletconnect/web3-provider@1.2.1/dist/umd/index.min.js', array(), '1.0.0', true);
    wp_enqueue_script('fortmatic', 'https://unpkg.com/fortmatic@2.0.6/dist/fortmatic.js', array(), '1.0.0', true);
    wp_enqueue_script('wp-web3', plugin_dir_url(__FILE__) . 'public/js/loginform.js', array(), '1.0.0', true);
    wp_localize_script('wp-web3', 'wp_web3_login', array(
        'nonce'     => wp_create_nonce('wp_web3_login_nonce'),
        'ajaxurl'   => admin_url('admin-ajax.php'),
        'pluginurl' => plugin_dir_url(__FILE__),
    ));
}
add_action('login_enqueue_scripts', 'wp_web3_enqueue_scripts');

function wp_web3_login_form_button()
{
?>
    <div style="display: block; clear: both;"></div>
    <div id="prepare" style="text-align: center; margin-top: 1rem; display: none;">
        <button id="btn-connect" type="button" class="button button-secondary button-hero hide-if-no-js js-c-wp_web3-signIn">Connect wallet</button>
    </div>

    <div id="connected" style="text-align: center; margin-top: 1rem; display: none;">
        <button id="btn-disconnect" type="button" class="button button-secondary button-hero hide-if-no-js js-c-wp_web3-signIn">Disconnect wallet</button>
    </div>
<?php
}
add_action('login_form', 'wp_web3_login_form_button');

function wp_web3_login()
{
    check_ajax_referer('wp_web3_login_nonce');

    if (!isset($_POST['data'])) {
        wp_send_json_error();
    }

    // retrieve values and sanitize.
    $post_data      = $_POST['data'];
    $public_address = sanitize_post($post_data['public_address'], 'raw');

    if (empty($public_address)) {
        wp_send_json_error(
            __('Address does not exist.', 'wp-web3'),
        );
    }

    // search for user within usermeta.
    global $wpdb;
    $like = '%' . $wpdb->esc_like($public_address) . '%';
    $user = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT *
            FROM $wpdb->usermeta
            WHERE `meta_key` = 'wp_web3_public_address' AND
            `meta_value` LIKE %s
            LIMIT 1",
            $like
        )
    );

    if (!$user) {
        if (!empty($public_address)) {
            wp_send_json_error(
                wp_sprintf(
                    __('WordPress user with Web3 address %s does not exist.', 'wp-web3'),
                    $public_address
                )
            );
        }
    }

    update_user_caches($user);

    $_ajax_nonce = sanitize_post($_POST['_ajax_nonce']);
    $user_id     = absint($user->user_id);

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
add_action('wp_ajax_wp_web3', 'wp_web3_login');
add_action('wp_ajax_nopriv_wp_web3', 'wp_web3_login');

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
    if (!preg_match('/^0x[a-fA-F\d]{40}$/', $_meta_value)) {

        $user = get_userdata(absint($object_id));

        if (!$user) {
            return;
        }

        $sessions = WP_Session_Tokens::get_instance($user->ID);

        $sessions->destroy_all();
    }
}
add_action('updated_user_meta', 'wp_web3_user_public_address_updated', 10, 4);
