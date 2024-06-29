<?php
/**
 * Posts Maintenance block.
 *
 * @link          https://wpmudev.com/
 * @since         1.0.0
 *
 * @author        WPMUDEV (https://wpmudev.com)
 * @package       WPMUDEV\PluginTest
 *
 * @copyright (c) 2023, Incsub (http://incsub.com)
 */

namespace WPMUDEV\PluginTest\App\Admin_Pages;

// Abort if called directly.
defined( 'WPINC' ) || die;

use WPMUDEV\PluginTest\Base;

class Posts_Maintenance extends Base {
    /**
     * The page title.
     *
     * @var string
     */
    private $page_title;

    /**
     * The page slug.
     *
     * @var string
     */
    private $page_slug = 'wpmudev_plugintest_posts_maintenance';

    /**
	 * Page Assets.
	 *
	 * @var array
	 */
	private $page_scripts = array();

    /**
     * Assets version.
     *
     * @var string
     */
    private $assets_version = '';

    /**
     * A unique string id to be used in markup and jsx.
     *
     * @var string
     */
    private $unique_id = '';

    /**
     * Constructor.
     *
     * @return void
     * @since 1.0.0
     *
     */
    public function __construct() {
        $this->init();
    }

    /**
     * Initializes the page.
     *
     * @return void
     * @since 1.0.0
     *
     */
    public function init() {
        $this->page_title     = __( 'Posts Maintenance', 'wpmudev-plugin-test' );
        $this->assets_version = WPMUDEV_PLUGINTEST_VERSION;
        $this->unique_id      = "wpmudev_plugintest_posts_maintenance_main_wrap-{$this->assets_version}";

        add_action( 'admin_menu', array( $this, 'register_admin_page' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    public function register_admin_page() {
        $page = add_menu_page(
            'Posts Maintenance',
            $this->page_title,
            'manage_options',
            $this->page_slug,
            array( $this, 'callback' ),
            'dashicons-admin-tools',
            7
        );

        add_action( 'load-' . $page, array( $this, 'prepare_assets' ) );
    }

    /**
     * The admin page callback method.
     *
     * @return void
     */
    public function callback() {
        $this->view();
    }

    /**
     * Prepares assets.
     *
     * @return void
     */
    public function prepare_assets() {
        $handle       = 'wpmudev_plugintest_postsmaintenance';
        $src          = WPMUDEV_PLUGINTEST_ASSETS_URL . '/js/postsmaintenancepage.min.js';
        $style_src    = WPMUDEV_PLUGINTEST_ASSETS_URL . '/css/postsmaintenancepage.min.css';
        $dependencies = array(
            'react',
            'wp-element',
            'wp-components',
            'wp-i18n',
            'wp-is-shallow-equal',
            'wp-polyfill',
        );

        $this->page_scripts[ $handle ] = array(
            'src'       => $src,
            'style_src' => $style_src,
            'deps'      => $dependencies,
            'ver'       => $this->assets_version,
            'strategy'  => true,
            'localize'  => array(
                'dom_element_id' => $this->unique_id,
                'nonce'          => wp_create_nonce( 'wp_rest' ),
            ),
        );
    }

    /**
     * Enqueues assets.
     *
     * @return void
     */
    public function enqueue_assets() {
        foreach ( $this->page_scripts as $handle => $page_script ) {
            wp_register_script(
                $handle,
                $page_script['src'],
                $page_script['deps'],
                $page_script['ver'],
                $page_script['strategy']
            );

            if ( ! empty( $page_script['localize'] ) ) {
                wp_localize_script( $handle, 'wpmudevPostMaintenance', $page_script['localize'] );
            }

            wp_enqueue_script( $handle );

            if ( ! empty( $page_script['style_src'] ) ) {
                wp_enqueue_style( $handle, $page_script['style_src'], array(), $this->assets_version );
            }
        }
    }

    /**
     * Prints the wrapper element which React will use as root.
     *
     * @return void
     */
    protected function view() {
        echo '<div id="' . esc_attr( $this->unique_id ) . '" class="sui-wrap"></div>';
    }
}
