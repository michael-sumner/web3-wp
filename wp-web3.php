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

define('WP_WEB3_VERSION', '1.0.0');

if (!class_exists('WPWeb3_0')) {
    class WPWeb3_0
    {
        /**
         * The current version of the plugin.
         *
         * @since 1.0.0 WP Web 3.0
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
         * @since 1.0.0 WP Web 3.0
         */
        public function __construct()
        {
            if (defined('WP_WEB3_VERSION')) {
                $this->version = WP_WEB3_VERSION;
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
         * @since 1.0.0 WP Web 3.0
         * @return void
         */
        private function activate_actions()
        {
            $options = get_option('wp_web3');

            if ($options['enable_web3_wallet_login'] === 'enable') {
                add_action('login_enqueue_scripts', array($this, 'enqueue_scripts'));
                add_action('login_form', array($this, 'login_form_button'));
                add_action('wp_ajax_wp_web3', array($this, 'login'));
                add_action('wp_ajax_nopriv_wp_web3', array($this, 'login'));
                add_action('updated_user_meta', array($this, 'user_public_address_updated'), 10, 4);
            }
            // todo
            // add_action('admin_head', array($this, 'plugin_settings'));
        }

        /**
         * Register listeners for filters.
         *
         * @since 1.0.0 WP Web 3.0
         * @return void
         */
        private function activate_filters()
        {
            // add_filter('gform_confirmation_settings_fields', array($this, 'confirmation_setting'), 10, 3);
            // add_filter('gform_pre_confirmation_save', array($this, 'confirmation_save'), 10, 2);
            add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_settings_link'));
        }

        /**
         * Retrieve the version number of the plugin.
         *
         * @since 1.0.0 WP Web 3.0
         * @return string The version number of the plugin.
         */
        public function get_version()
        {
            return $this->version;
        }


        /**
         * Enqueue admin scripts.
         *
         * @since 1.0.0 WP Web 3.0
         * @return void
         */
        public function enqueue_scripts()
        {
            wp_enqueue_style('wp-web3', plugin_dir_url(__FILE__) . 'public/css/loginform.css', array(), $this->get_version());
            wp_enqueue_script('web3', 'https://unpkg.com/web3@1.2.11/dist/web3.min.js', array(), $this->get_version(), true);
            wp_enqueue_script('web3modal', 'https://unpkg.com/web3modal@1.9.0/dist/index.js', array(), $this->get_version(), true);
            wp_enqueue_script('evm-chains', 'https://unpkg.com/evm-chains@0.2.0/dist/umd/index.min.js', array(), $this->get_version(), true);
            wp_enqueue_script('@walletconnect', 'https://unpkg.com/@walletconnect/web3-provider@1.2.1/dist/umd/index.min.js', array(), $this->get_version(), true);
            wp_enqueue_script('fortmatic', 'https://unpkg.com/fortmatic@2.0.6/dist/fortmatic.js', array(), $this->get_version(), true);
            wp_enqueue_script('wp-web3', plugin_dir_url(__FILE__) . 'public/js/loginform.js', array(), $this->get_version(), true);

            // Localize the script with new data
            $login_form_settings = array();

            $options = get_option('wp_web3');

            if ($options['wallet_provider_popup_theme_mode'] === 'theme-light') {
                $login_form_settings['wallet_provider_popup_theme_mode'] = 'light';
            } elseif ($options['wallet_provider_popup_theme_mode'] === 'theme-dark') {
                $login_form_settings['wallet_provider_popup_theme_mode'] = 'dark';
            }

            if (!empty($options['login_button_theme_customisation'])) {
                $login_form_settings['login_button_theme_customisation'] = $options['login_button_theme_customisation'];
            }
            if (!empty($options['button_color'])) {
                $login_form_settings['button_color'] = $options['button_color'];
            }
            $login_form_settings['metamask']             = $options['metamask'];
            $login_form_settings['walletconnect']        = $options['walletconnect'];
            $login_form_settings['fortmatic']            = $options['fortmatic'];
            $login_form_settings['torus']                = $options['torus'];
            $login_form_settings['portis']               = $options['portis'];
            $login_form_settings['authereum']            = $options['authereum'];
            $login_form_settings['frame']                = $options['frame'];
            $login_form_settings['bitski']               = $options['bitski'];
            $login_form_settings['venly']                = $options['venly'];
            $login_form_settings['dcent']                = $options['dcent'];
            $login_form_settings['burnerconnect']        = $options['burnerconnect'];
            $login_form_settings['mewconnect']           = $options['mewconnect'];
            $login_form_settings['binance_chain_wallet'] = $options['binance_chain_wallet'];
            $login_form_settings['walletlink']           = $options['walletlink'];

            wp_localize_script('wp-web3', 'wp_web3_login', array(
                'nonce'               => wp_create_nonce('wp_web3_login_nonce'),
                'ajaxurl'             => admin_url('admin-ajax.php'),
                'pluginurl'           => plugin_dir_url(__FILE__),
                'login_form_settings' => $login_form_settings,
            ));
        }

        /**
         * Login Form Button.
         *
         * @since 1.0.0 WP Web 3.0
         * @return void
         */
        public function login_form_button()
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

        /**
         * Login functionality.
         *
         * @since 1.0.0 WP Web 3.0
         * @return void
         */
        public function login()
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

        /**
         * Add plugin settings links.
         * 
         * This function adds links to the plugin item on the plugins page.
         *
         * @param  mixed $links The links array.
         * @return mixed $links The links array.
         */
        public function add_settings_link($links)
        {
            $settings_link = '<a href="' . admin_url('options-general.php?page=wp_web3') . '">' . __('Settings', 'wp-web3') . '</a>';
            array_unshift($links, $settings_link);
            return $links;
        }

        // list down options on the plugin settings page
        public function plugin_settings()
        {
            $options = get_option('wp_web3');
            
            $aaaa = $options;
            echo '<pre style="display:;" data-smnr="pre">'.var_export($aaaa,1).'</pre>';
            die();
        }
    }
}

// add plugin options page.

require_once('class-option-pages.php');
$pages = array(
    'wp_web3'    => array(
        'page_title' => __('WP Web3', 'wp-web3'),
        'sections'   => array(
            'settings-general'    => array(
                'title'  => __('General Settings', 'wp-web3'),
                'fields' => array(

                    'enable_web3_wallet_login'            => array(
                        'title'   => __('Enable Web3 Wallet Login', 'wp-web3'),
                        'type'    => 'radio',
                        'value'   => 'disable',
                        'choices' => array(
                            'disable' => __('Disable', 'wp-web3'),
                            'enable'  => __('Enable', 'wp-web3'),
                        ),
                    ),
                    'keep_username_email_password_fallback'            => array(
                        'title'   => __('Keep Username / Email / Password as Fallback', 'wp-web3'),
                        'type'    => 'radio',
                        'value'   => 'disable',
                        'choices' => array(
                            'disable' => __('Disable', 'wp-web3'),
                            'enable'  => __('Enable', 'wp-web3'),
                        ),
                    ),
                ),
            ),
            'settings-visual-customisation-features'    => array(
                'title'  => __('Visual Customisation Features', 'wp-web3'),
                'fields' => array(

                    'wallet_provider_popup_theme_mode'            => array(
                        'title'   => __('Wallet Provider Popup Theme Mode', 'wp-web3'),
                        'type'    => 'radio',
                        'value'   => 'theme-light',
                        'choices' => array(
                            'theme-light' => __('Light Theme', 'wp-web3'),
                            'theme-dark'  => __('Dark Theme', 'wp-web3'),
                        ),
                    ),
                    'login_button_theme_customisation'            => array(
                        'title'   => __('Login Button Theme Customisation', 'wp-web3'),
                        'type'    => 'radio',
                        'value'   => 'web3-violet',
                        'choices' => array(
                            'web3-violet'        => __('Default Web3 icon, with Ethereum Violet button color', 'wp-web3'),
                            'web3-black'         => __('Default Web3 icon, with Web 3.0 signature black button color', 'wp-web3'),
                            'eth-rainbow-violet' => __('Ethereum Rainbow icon, with Ethereum Violet button color', 'wp-web3'),
                            'eth-white-violet'   => __('Ethereum White icon, with Ethereum Violet button color', 'wp-web3'),
                            'eth-rainbow-black'  => __('Ethereum Rainbow icon, with Web 3.0 signature black button color', 'wp-web3'),
                            'eth-white-black'    => __('Ethereum White icon, with Web 3.0 signature black button color', 'wp-web3'),
                            'other_button_color' => __('Specify a different custom button color, specified below.', 'wp-web3'),
                        ),
                    ),

                    'login_button_theme_customisation_button_color'            => array(
                        'title' => __('Button Color', 'wp-web3'),
                        'type'  => 'color',
                        'value' => '#1c1ce1',
                        'text'  => __('Specify the custom button color for the Web 3.0 login button.'),
                    ),

                ),
            ),
            'settings-wallet-providers'    => array(
                'title'  => __('Wallet Providers Settings', 'wp-web3'),
                'fields' => array(
                    'enable_wallet_provider_metamask'        => array(
                        'title' => __('MetaMask', 'wp-web3'),
                        'type'  => 'checkbox',
                        'text'  => __('Enable / Disable Wallet Provider: MetaMask'),
                    ),
                    'enable_wallet_provider_walletconnect'        => array(
                        'title' => __('WalletConnect', 'wp-web3'),
                        'type'  => 'checkbox',
                        'text'  => __('Enable / Disable Wallet Provider: WalletConnect'),
                    ),
                    'enable_wallet_provider_fortmatic'        => array(
                        'title' => __('Fortmatic', 'wp-web3'),
                        'type'  => 'checkbox',
                        'text'  => __('Enable / Disable Wallet Provider: Fortmatic'),
                    ),
                    'enable_wallet_provider_torus'        => array(
                        'title' => __('Torus', 'wp-web3'),
                        'type'  => 'checkbox',
                        'text'  => __('Enable / Disable Wallet Provider: Torus'),
                    ),
                    'enable_wallet_provider_portis'        => array(
                        'title' => __('Portis', 'wp-web3'),
                        'type'  => 'checkbox',
                        'text'  => __('Enable / Disable Wallet Provider: Portis'),
                    ),
                    'enable_wallet_provider_authereum'        => array(
                        'title' => __('Authereum', 'wp-web3'),
                        'type'  => 'checkbox',
                        'text'  => __('Enable / Disable Wallet Provider: Authereum'),
                    ),
                    'enable_wallet_provider_frame'        => array(
                        'title' => __('Frame', 'wp-web3'),
                        'type'  => 'checkbox',
                        'text'  => __('Enable / Disable Wallet Provider: Frame'),
                    ),
                    'enable_wallet_provider_bitski'        => array(
                        'title' => __('Bitski', 'wp-web3'),
                        'type'  => 'checkbox',
                        'text'  => __('Enable / Disable Wallet Provider: Bitski'),
                    ),
                    'enable_wallet_provider_venly'        => array(
                        'title' => __('Venly', 'wp-web3'),
                        'type'  => 'checkbox',
                        'text'  => __('Enable / Disable Wallet Provider: Venly'),
                    ),
                    'enable_wallet_provider_dcent'        => array(
                        'title' => __('DCent', 'wp-web3'),
                        'type'  => 'checkbox',
                        'text'  => __('Enable / Disable Wallet Provider: DCent'),
                    ),
                    'enable_wallet_provider_burnerconnect'        => array(
                        'title' => __('BurnerConnect', 'wp-web3'),
                        'type'  => 'checkbox',
                        'text'  => __('Enable / Disable Wallet Provider: BurnerConnect'),
                    ),
                    'enable_wallet_provider_mewconnect'        => array(
                        'title' => __('MEWConnect', 'wp-web3'),
                        'type'  => 'checkbox',
                        'text'  => __('Enable / Disable Wallet Provider: MEWConnect'),
                    ),
                    'enable_wallet_provider_binancechainwallet'        => array(
                        'title' => __('Binance Chain Wallet', 'wp-web3'),
                        'type'  => 'checkbox',
                        'text'  => __('Enable / Disable Wallet Provider: Binance Chain Wallet'),
                    ),
                    'enable_wallet_provider_walletlink'        => array(
                        'title' => __('WalletLink', 'wp-web3'),
                        'type'  => 'checkbox',
                        'text'  => __('Enable / Disable Wallet Provider: WalletLink'),
                    ),

                ),
            ),
        ),
    ),
);
$wp_web3_0 = new WPWeb3_0();
$option_page = new RationalOptionPages($pages);
