<?php
/**
 * Post Maintenance block.
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

// // 4. Admin Menu for Posts Maintenance
//Introduce a new admin menu page titled **Posts Maintenance** featuring a **Scan Posts** button. When clicked, this button should scan all public posts and pages (with customizable post type filters) and update the `wpmudev_test_last_scan` post_meta with the current timestamp. Ensure that operation will keep running if the user leaves that page. This operation should be repeated daily to ensure ongoing maintenance.

class PostMaintenance extends Base {

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
    private $page_slug = 'wpmudev_plugintest_postmaintenance';

    /**
     * Google auth credentials.
     *
     * @since 1.0.0
     *
     * @var array
     */
    private $creds = array();

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {

        $this->page_title = __( 'Posts Maintenance', 'wpmudev-plugin-test' );

        // Add the admin menu.
        add_action( 'admin_menu', array( $this, 'add_menu' ) );

        // Add the admin page.
        add_action( 'admin_menu', array( $this, 'add_page' ) );

        // Add the admin page content.
        add_action( 'admin_init', array( $this, 'add_content' ) );
    }

    /**
     * Add the admin menu.
     *
     * @since 1.0.0
     */
    public function add_menu() {
        add_menu_page(
            $this->page_title,
            $this->page_title,
            'manage_options',
            $this->page_slug,
            array( $this, 'render_page' ),
            'dashicons-admin-tools',
            6
        );
    }

    /**
     * Add the admin page.
     *
     * @since 1.0.0
     */
    public function add_page() {
        add_submenu_page(
            $this->page_slug,
            $this->page_title,
            $this->page_title,
            'manage_options',
            $this->page_slug,
            array( $this, 'render_page' )
        );
    }

    /**
     * Add the admin page content.
     *
     * @since 1.0.0
     */
    public function add_content() {
        if ( ! isset( $_GET['page'] ) || $this->page_slug !== $_GET['page'] ) {
            return;
        }

        // Scan posts.
        if ( isset( $_POST['scan_posts'] ) ) {
            $this->scan_posts();

            // Redirect to the same page.
            wp_redirect( admin_url( 'admin.php?page=' . $this->page_slug ) );
            exit;
        }
    }

    /**
     * Render the admin page.
     *
     * @since 1.0.0
     */
    public function render_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( $this->page_title ); ?></h1>

            <form method="post">
                <p>
                    <input type="submit" name="scan_posts" class="button button-primary" value="Scan Posts">
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Scan posts.
     *
     * @since 1.0.0
     */
    // post type should be any
    public function scan_posts() {
        $args = array(
            'post_type'      => 'any',
            'posts_per_page' => -1,
        );

        $query = new \WP_Query( $args );

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();

                // Update the last scan timestamp.
                update_post_meta( get_the_ID(), 'wpmudev_test_last_scan', time() );
            }

            wp_reset_postdata();
        }
    }
}

//new PostMaintenance();

// 5. Cron Job for Daily Maintenance
//Create a daily cron job that will run the `scan_posts` method from the `PostMaintenance` class. This cron job should be scheduled to run at 3:00 AM every day. The cron job should be registered when the plugin is activated and deregistered when the plugin is deactivated. The cron job should be registered using the `wp_schedule_event` function.

// Register the cron job.
// register_activation_hook( __FILE__, 'wpmudev_plugintest_register_cron_job' );
// function wpmudev_plugintest_register_cron_job() {
//     if ( ! wp_next_scheduled( 'wpmudev_plugintest_daily_maintenance' ) ) {
//         wp_schedule_event( strtotime( '3:00:00' ), 'daily', 'wpmudev_plugintest_daily_maintenance' );
//     }
// }

// Deregister the cron job.
// register_deactivation_hook( __FILE__, 'wpmudev_plugintest_deregister_cron_job' );
// function wpmudev_plugintest_deregister_cron_job() {
//     wp_clear_scheduled_hook( 'wpmudev_plugintest_daily_maintenance' );
// }

// Hook the cron job.
// add_action( 'wpmudev_plugintest_daily_maintenance', 'wpmudev_plugintest_run_daily_maintenance' );
// function wpmudev_plugintest_run_daily_maintenance() {
//     $post_maintenance = new PostMaintenance();
//     $post_maintenance->scan_posts();
// }








