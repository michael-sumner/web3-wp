<?php

/**
 * @wordpress-plugin
 * Plugin Name: Web3 WP
 * Description: Allow your users to log into your WordPress site using Web3 sign-on.
 * Version: 1.0.0
 * Requires at least: 4.0
 * Requires PHP: 5.6
 * Author: Bioneer Ltd
 * Author URI: https://bioneer.ai/
 * License: GPL-2.0+
 * Text Domain: web3-wp
 * Domain Path: /languages
 *
 * @link https://bioneer.ai/
 * @since 1.0.0
 * @package Web3_WP
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die('Hey there...');
}

define('WEB3_WP_VERSION', '1.0.0');

if (!class_exists('Web3_WP')) {
    class Web3_WP
    {
        /**
         * The current version of the plugin.
         *
         * @since 1.0.0 Web3 WP
         * @access protected
         * @var string $version The current version of the plugin.
         */
        protected $version;

        /**
         * Define the core functionality of the plugin.
         *
         * Set the plugin version that can be used throughout the plugin.
         * Set the hooks.
         *
         * @since 1.0.0 Web3 WP
         */
        public function __construct()
        {
            if (defined('WEB3_WP_VERSION')) {
                $this->version = WEB3_WP_VERSION;
            } else {
                $this->version = '1.0.0';
            }

            // Activate hooks
            $this->activate_actions();
            $this->activate_filters();
        }

        /**
         * Register listeners for actions.
         *
         * @since 1.0.0 Web3 WP
         * @return void
         */
        private function activate_actions()
        {
            add_action('login_enqueue_scripts', array($this, 'enqueue_scripts'));
            add_action('login_form', array($this, 'login_form_button'));
            add_action('wp_ajax_web3_wp', array($this, 'login'));
            add_action('wp_ajax_nopriv_web3_wp', array($this, 'login'));
            add_action('updated_user_meta', array($this, 'user_public_address_updated'), 10, 4);

            // add_action user_profile_fields
            add_action('show_user_profile', array($this, 'user_profile_fields'));
            add_action('edit_user_profile', array($this, 'user_profile_fields'));

            // save user_profile_fields
            add_action('personal_options_update', array($this, 'update_profile_fields'));
            add_action('edit_user_profile_update', array($this, 'update_profile_fields'));

            // error messages
            add_action( 'user_profile_update_errors', array($this, 'user_profile_update_errors'), 10, 3 );
        }

        /**
         * Register listeners for filters.
         *
         * @since 1.0.0 Web3 WP
         * @return void
         */
        private function activate_filters()
        {
        }

        /**
         * Retrieve the version number of the plugin.
         *
         * @since 1.0.0 Web3 WP
         * @return string The version number of the plugin.
         */
        public function get_version()
        {
            return $this->version;
        }


        /**
         * Enqueue admin scripts.
         *
         * @since 1.0.0 Web3 WP
         * @return void
         */
        public function enqueue_scripts()
        {
            wp_enqueue_style('web3-wp', plugin_dir_url(__FILE__) . 'public/css/loginform.css', array(), $this->get_version());
            wp_enqueue_script('web3', plugin_dir_url(__FILE__) . 'public/js/web3.min.js', array(), '1.2.11', true);
            wp_enqueue_script('web3modal', plugin_dir_url(__FILE__) . 'public/js/web3modal/dist/index.js', array(), '1.9.0', true);
            wp_enqueue_script('evm-chains', plugin_dir_url(__FILE__) . 'public/js/evm-chains/dist/umd/index.min.js', array(), '0.2.0', true);
            wp_enqueue_script('web3-wp', plugin_dir_url(__FILE__) . 'public/js/loginform.js', array(), $this->get_version(), true);

            wp_localize_script('web3-wp', 'web3_wp_login', array(
                'nonce'               => wp_create_nonce('web3_wp_login_nonce'),
                'ajaxurl'             => admin_url('admin-ajax.php'),
                'pluginurl'           => plugin_dir_url(__FILE__),
            ));
        }

        /**
         * Login Form Button.
         *
         * @since 1.0.0 Web3 WP
         * @return void
         */
        public function login_form_button()
        {
            ?>
            <div style="display: block; clear: both;"></div>
            <div>
                <div id="alert-error-metamask" style="display: none;">Metamask is not installed</div>
            </div>
            <div id="prepare" style="text-align: center; margin-top: 1rem; display: none;">
                <button id="btn-connect" type="button" class="button button-secondary button-hero hide-if-no-js js-c-web3_wp-signIn">Connect wallet</button>
            </div>

            <div id="connected" style="text-align: center; margin-top: 1rem; display: none;">
                <button id="btn-disconnect" type="button" class="button button-secondary button-hero hide-if-no-js js-c-web3_wp-signIn">Disconnect wallet</button>
            </div>
            <?php
        }

        /**
         * Login functionality.
         *
         * @since 1.0.0 Web3 WP
         * @return void
         */
        public function login()
        {
            check_ajax_referer('web3_wp_login_nonce');

            if (!isset($_POST['data'])) {
                wp_send_json_error();
            }

            // retrieve values and sanitize $_POST.
            $post_data = sanitize_post($_POST['data'], 'raw');
            $public_address = sanitize_post($post_data['public_address'], 'raw');

            if (empty($public_address)) {
                wp_send_json_error(
                    __('Address does not exist.', 'web3-wp'),
                );
            }

            // search for user within usermeta.
            global $wpdb;
            $like = '%' . $wpdb->esc_like($public_address) . '%';
            $user = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT *
                    FROM $wpdb->usermeta
                    WHERE `meta_key` = 'web3_wp_public_address' AND
                    `meta_value` LIKE %s
                    LIMIT 1",
                    $like
                )
            );

            if (!$user) {
                if (!empty($public_address)) {
                    wp_send_json_error(
                        wp_sprintf(
                            __('WordPress user with Web3 address %s does not exist.', 'web3-wp'),
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
            update_user_meta($user_id, 'web3_wp_public_address', $public_address);

            // log the user in.
            wp_set_auth_cookie($user_id);

            $redirect_url = wp_nonce_url(admin_url(), $_ajax_nonce);

            // return values
            $data = array(
                'redirect_url' => esc_url($redirect_url),
            );

            wp_send_json_success($data);
        }

        /**
         * On user post meta update, if it contains an eth address, then sign the user out, for security.
         *
         * @param int    $meta_id     ID of updated metadata entry.
         * @param int    $object_id   ID of the object metadata is for.
         * @param string $meta_key    Metadata key.
         * @param mixed  $_meta_value Metadata value. Serialized if non-scalar.
         * @return void
         */
        public function user_public_address_updated($meta_id, $object_id, $meta_key, $_meta_value)
        {
            if ($meta_key !== 'web3_wp_public_address') {
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

        // add a custom user field in the user page
        public function user_profile_fields($user)
        {
            $public_address = get_user_meta($user->ID, 'web3_wp_public_address', true);
            ?>
            <h3><?php _e('Web3', 'web3-wp'); ?></h3>
            <table class="form-table">
                <tr>
                    <th><label for="web3_wp_public_address"><?php _e('Web3 Address', 'web3-wp'); ?></label></th>
                    <td>
                        <input type="text" name="web3_wp_public_address" id="web3_wp_public_address" value="<?php echo esc_attr($public_address); ?>" class="regular-text" />
                        <p class="description"><?php _e('Enter your Web3 address here.', 'web3-wp'); ?></p>
                    </td>
                </tr>
            </table>
            <?php
        }

        public function update_profile_fields($user_id)
        {
            if (!current_user_can('edit_user', $user_id)) {
                return false;
            }

            $public_address = (string) sanitize_post($_POST['web3_wp_public_address'], 'raw');

            if (!empty($public_address) && preg_match('/^0x[a-fA-F\d]{40}$/', $public_address)) {
                update_user_meta($user_id, 'web3_wp_public_address', $public_address);
            }
        }

        public function user_profile_update_errors($errors, $update, $user)
        {
            $public_address = (string) sanitize_post($_POST['web3_wp_public_address'], 'raw');

            if (!empty($public_address) && !preg_match('/^0x[a-fA-F\d]{40}$/', $public_address)) {
                $errors->add('web3_wp_public_address_error', __('<strong>Error</strong>: Please enter a valid Web3 address.', 'web3-wp'));
            }
        }
    }
}
$Web3_WP = new Web3_WP();
