<?php
/**
 * Plugin Name:       WPMU DEV Plugin Test
 * Description:       A plugin focused on testing coding skills.
 * Requires at least: 6.1
 * Requires PHP:      7.4
 * Version:           0.1.0
 * Author:            Fahad Ahmed
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wpmudev-plugin-test
 *
 * @package           create-block
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Support for site-level autoloading.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

// Debugging statement to check class loading
// if (class_exists('WPMUDEV_Shortcode')) {
//     var_dump('WPMUDEV_Shortcode class is loaded.');
// } else {
//     var_dump('WPMUDEV_Shortcode class is not loaded.');
// }


// Plugin version.
if ( ! defined( 'WPMUDEV_PLUGINTEST_VERSION' ) ) {
	define( 'WPMUDEV_PLUGINTEST_VERSION', '1.0.0' );
}

// Define WPMUDEV_PLUGINTEST_PLUGIN_FILE.
if ( ! defined( 'WPMUDEV_PLUGINTEST_PLUGIN_FILE' ) ) {
	define( 'WPMUDEV_PLUGINTEST_PLUGIN_FILE', __FILE__ );
}

// Plugin directory.
if ( ! defined( 'WPMUDEV_PLUGINTEST_DIR' ) ) {
	define( 'WPMUDEV_PLUGINTEST_DIR', plugin_dir_path( __FILE__ ) );
}

// Plugin url.
if ( ! defined( 'WPMUDEV_PLUGINTEST_URL' ) ) {
	define( 'WPMUDEV_PLUGINTEST_URL', plugin_dir_url( __FILE__ ) );
}

// Assets url.
if ( ! defined( 'WPMUDEV_PLUGINTEST_ASSETS_URL' ) ) {
	define( 'WPMUDEV_PLUGINTEST_ASSETS_URL', WPMUDEV_PLUGINTEST_URL . '/assets' );
}

// Shared UI Version.
if ( ! defined( 'WPMUDEV_PLUGINTEST_SUI_VERSION' ) ) {
	define( 'WPMUDEV_PLUGINTEST_SUI_VERSION', '2.12.23' );
}


/**
 * WPMUDEV_PluginTest class.
 */
class WPMUDEV_PluginTest {

	/**
	 * Holds the class instance.
	 *
	 * @var WPMUDEV_PluginTest $instance
	 */
	private static $instance = null;

	/**
	 * Return an instance of the class
	 *
	 * Return an instance of the WPMUDEV_PluginTest Class.
	 *
	 * @return WPMUDEV_PluginTest class instance.
	 * @since 1.0.0
	 *
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Class initializer.
	 */
	public function load() {
		load_plugin_textdomain(
			'wpmudev-plugin-test',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);

		WPMUDEV\PluginTest\Loader::instance();
		
		// Schedule daily task on plugin activation.
        register_activation_hook(WPMUDEV_PLUGINTEST_PLUGIN_FILE, array($this, 'wpmudev_schedule_daily_task'));

        // Remove scheduled task on plugin deactivation.
        register_deactivation_hook(WPMUDEV_PLUGINTEST_PLUGIN_FILE, array($this, 'wpmudev_remove_scheduled_task'));
	}

	/**
     * Schedule daily task.
     */
    public function wpmudev_schedule_daily_task()
    {
		if (!wp_next_scheduled('wpmudev_daily_task')) {
			wp_schedule_event(time(), 'daily', 'wpmudev_daily_task');
		}
    }

	/**
     * Remove scheduled task.
     */
    public function wpmudev_remove_scheduled_task()
    {
        wp_clear_scheduled_hook('wpmudev_daily_task');
    }
}

// Init the plugin and load the plugin instance for the first time.
add_action(
	'plugins_loaded',
	function () {
		WPMUDEV_PluginTest::get_instance()->load();
	}
);

// Hook the function to run daily.
add_action('wpmudev_daily_task', 'wpmudev_daily_task');

function wpmudev_daily_task()
{
    $args = array(
        'post_type'      => array('post', 'page'),
        'post_status'    => 'publish',
        'posts_per_page' => -1,
    );

    $query = new WP_Query($args);

    while ($query->have_posts()) {
        $query->the_post();
        update_post_meta(get_the_ID(), 'wpmudev_test_last_scan', current_time('mysql'));
    }

    wp_reset_postdata();
}


