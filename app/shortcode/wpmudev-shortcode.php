<?php
/**
 * Class to boot up plugin.
 *
 * @link    https://wpmudev.com/
 * @since   1.0.0
 *
 * @author  WPMUDEV (https://wpmudev.com)
 * @package WPMUDEV_PluginTest
 *
 * @copyright (c) 2023, Incsub (http://incsub.com)
 */
namespace WPMUDEV\PluginTest\Shortcode;

// Abort if called directly.
defined( 'WPINC' ) || die;


// please make a shortcode class use prefix wpmudev
class WPMUDEV_Shortcode_Test {
    /**
     * Singleton constructor.
     *
     * Protect the class from being initiated multiple times.
     *
     * @param array $props Optional properties array.
     *
     * @since 1.0.0
     */
    protected function __construct( $props = array() ) {
        // Protect class from being initiated multiple times.
    }

    /**
     * Instance obtaining method.
     *
     * @return static Called class instance.
     * @since 1.0.0
     */
    public static function instance() {
        static $instances = array();

        // @codingStandardsIgnoreLine Plugin-backported
        $called_class_name = get_called_class();

        if ( ! isset( $instances[ $called_class_name ] ) ) {
            $instances[ $called_class_name ] = new $called_class_name();
        }

        return $instances[ $called_class_name ];
    }

    /**
     * Register the shortcode.
     *
     * @return void
     * @since 1.0.0
     */
    public function register() {
        add_shortcode( 'wpmudev_login', array( $this, 'shortcode' ) );
    }

    /**
     * Shortcode callback.
     *
     * @param array $atts Shortcode attributes.
     *
     * @return string
     * @since 1.0.0
     */
    public function shortcode( $atts ) {
        if ( is_user_logged_in() ) {
            $user = wp_get_current_user();
            return sprintf( '<p>Welcome back, %s!</p>', esc_html( $user->display_name ) );
        }

        // Google OAuth URL
        $client_id = get_option('wpmudev_plugin_test_settings')['client_id'];
        $redirect_uri = urlencode(rest_url('wpmudev/v1/auth/confirm'));
        $auth_url = "https://accounts.google.com/o/oauth2/auth?response_type=code&client_id={$client_id}&redirect_uri={$redirect_uri}&scope=email%20profile";

        return sprintf( '<a href="%s">Login with Google</a>', esc_url( $auth_url ) );
    }
}

// Initialize the shortcode.
// /Shortcode::instance()->register();




