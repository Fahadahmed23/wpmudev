<?php
/**
 * Google Auth block.
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
// use trait
use WPMUDEV\PluginTest\Core\Google_Auth\GoogleAuthTrait;
use WPMUDEV\PluginTest\Endpoints\V1\Auth_Confirm;


// // Check if the GoogleAuthTrait is included
// if (trait_exists('WPMUDEV\PluginTest\Core\Google_Auth\GoogleAuthTrait')) {
//     echo "GoogleAuthTrait is included and accessible.";
// } else {
//     echo "GoogleAuthTrait is not included or accessible.";
// }

//die;


class Auth extends Base {

	use GoogleAuthTrait;

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
	private $page_slug = 'wpmudev_plugintest_auth';

	/**
	 * Google auth credentials.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	private $creds = array();

	/**
	 * Option name.
	 *
	 * @var string
	 */
	private $option_name = 'wpmudev_plugin_test_settings';

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

		// Get the credentials.
		$this->creds['client_id'] = get_option('wpmudev_google_auth_client_id');	
		$this->creds['client_secret'] = get_option('wpmudev_google_auth_client_secret');


		// echo "<pre>";
		// var_dump($this->set_up());
		// echo "</pre>";
		// wp_die();

		// if ( $this->set_up() ) {
        //     return 'Google authentication set up successfully.';
		// 	wp_die();
        // } else {
        //     return 'Failed to set up Google authentication.';
        // }



		// use trait function here
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
		$this->page_title     = __( 'Google Auth', 'wpmudev-plugin-test' );
		$this->creds          = get_option( $this->option_name, array() );
		$this->assets_version = ! empty( $this->script_data( 'version' ) ) ? $this->script_data( 'version' ) : WPMUDEV_PLUGINTEST_VERSION;
		$this->unique_id      = "wpmudev_plugintest_auth_main_wrap-{$this->assets_version}";

		add_action( 'admin_menu', array( $this, 'register_admin_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		// Add body class to admin pages.
		add_filter( 'admin_body_class', array( $this, 'admin_body_classes' ) );
	}

	public function register_admin_page() {
		$page = add_menu_page(
			'Google Auth setup',
			$this->page_title,
			'manage_options',
			$this->page_slug,
			array( $this, 'callback' ),
			'dashicons-google',
			6
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
		if ( ! is_array( $this->page_scripts ) ) {
			$this->page_scripts = array();
		}

		$handle       = 'wpmudev_plugintest_authpage';
		$src          = WPMUDEV_PLUGINTEST_ASSETS_URL . '/js/authsettingspage.min.js';
		$style_src    = WPMUDEV_PLUGINTEST_ASSETS_URL . '/css/authsettingspage.min.css';
		$dependencies = ! empty( $this->script_data( 'dependencies' ) )
			? $this->script_data( 'dependencies' )
			: array(
				'react',
				'wp-element',
				'wp-i18n',
				'wp-is-shallow-equal',
				'wp-polyfill',
			);

		// get client id and client secret also check isset.
		$client_id = isset( $this->creds['client_id'] ) ? $this->creds['client_id'] : '';
		$client_secret = isset( $this->creds['client_secret'] ) ? $this->creds['client_secret'] : '';
			
	
		$this->page_scripts[ $handle ] = array(
			'src'       => $src,
			'style_src' => $style_src,
			'deps'      => $dependencies,
			'ver'       => $this->assets_version,
			'strategy'  => true,
			'localize'  => array(
				'dom_element_id'   => $this->unique_id,
				'clientID'         => $client_id,
				'clientSecret'     => $client_secret,
				'redirectUrl'      => 'redirectUrl',
				'restEndpointSave' => 'wpmudev/v1/auth/auth-url',
				'returnUrl'        => '/wp-json/wpmudev/v1/auth/confirm',
				'nonce'            => wp_create_nonce( 'wp_rest' ),
			),
		);
	}

	/**
	 * Gets assets data for given key.
	 *
	 * @param string $key
	 *
	 * @return string|array
	 */
	protected function script_data( string $key = '' ) {
		$raw_script_data = $this->raw_script_data();

		return ! empty( $key ) && ! empty( $raw_script_data[ $key ] ) ? $raw_script_data[ $key ] : '';
	}

	/**
	 * Gets the script data from assets php file.
	 *
	 * @return array
	 */
	protected function raw_script_data(): array {
		static $script_data = null;

		if ( is_null( $script_data ) && file_exists( WPMUDEV_PLUGINTEST_DIR . 'assets/js/authsettingspage.min.asset.php' ) ) {
			$script_data = include WPMUDEV_PLUGINTEST_DIR . 'assets/js/authsettingspage.min.asset.php';
		}

		return (array) $script_data;
	}

	/**
	 * Prepares assets.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		if ( ! empty( $this->page_scripts ) ) {
			foreach ( $this->page_scripts as $handle => $page_script ) {
				wp_register_script(
					$handle,
					$page_script['src'],
					$page_script['deps'],
					$page_script['ver'],
					$page_script['strategy']
				);

				if ( ! empty( $page_script['localize'] ) ) {
					wp_localize_script( $handle, 'wpmudevPluginTest', $page_script['localize'] );
				}

				wp_enqueue_script( $handle );

				if ( ! empty( $page_script['style_src'] ) ) {
					wp_enqueue_style( $handle, $page_script['style_src'], array(), $this->assets_version );
				}
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
	protected function viewOld() {
		?>
		<div id="<?php echo esc_attr( $this->unique_id ); ?>" class="sui-wrap">
			<div id="wpmudev-auth-settings-notice" style="display:none;">
				<div id="notice-message"></div>
				<button id="close-notice" style="position:absolute; right:10px; top:10px;">X</button>
			</div>

			<form id="wpmudev-auth-settings-form">
				<h2><?php esc_html_e( 'Google Auth Settings', 'wpmudev-plugin-test' ); ?></h2>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="client_id"><?php esc_html_e( 'Client ID *', 'wpmudev-plugin-test' ); ?></label>
						</th>
						<td>
							<input name="client_id" type="text" id="client_id" value="<?php echo esc_attr( $this->creds['client_id'] ?? '' ); ?>" class="regular-text" required>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="client_secret"><?php esc_html_e( 'Client Secret *', 'wpmudev-plugin-test' ); ?></label>
						</th>
						<td>
							<input name="client_secret" type="password" id="client_secret" value="<?php echo esc_attr( $this->creds['client_secret'] ?? '' ); ?>" class="regular-text" required>
						</td>
					</tr>
				</table>
				<p class="submit">
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Changes', 'wpmudev-plugin-test' ); ?></button>
				</p>
			</form>
		</div>
		<script type="text/javascript">
			(function($){
				$('#wpmudev-auth-settings-form').on('submit', function(e) {
					e.preventDefault();
					var clientId = $('#client_id').val();
					var clientSecret = $('#client_secret').val();

					console.log("WPMUDEV Credentials");
					console.log(clientId, clientSecret);

					$.ajax({
						url: '<?php echo esc_url( rest_url( 'wpmudev/v1/auth/auth-url' ) ); ?>',
						method: 'POST',
						beforeSend: function(xhr) {
							xhr.setRequestHeader('X-WP-Nonce', '<?php echo esc_attr( wp_create_nonce( 'wp_rest' ) ); ?>');
						},
						data: {
							client_id: clientId,
							client_secret: clientSecret
						},
						success: function(response) {
							$('#wpmudev-auth-settings-notice').show();
							$('#notice-message').html('<div class="notice notice-success"><p>' + response.message + '</p></div>');
						},
						error: function(response) {
							$('#wpmudev-auth-settings-notice').show();
							$('#notice-message').html('<div class="notice notice-error"><p>' + response.responseJSON.message + '</p></div>');
						}
					});
				});

				$('#close-notice').click(function() {
					$('#wpmudev-auth-settings-notice').hide();
				});
			})(jQuery);
		</script>
		<?php
	}
	

	/**
	 * Adds the SUI class on markup body.
	 *
	 * @param string $classes
	 *
	 * @return string
	 */
	public function admin_body_classes( $classes = '' ) {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return $classes;
		}

		$current_screen = get_current_screen();

		if ( empty( $current_screen->id ) || ! strpos( $current_screen->id, $this->page_slug ) ) {
			return $classes;
		}

		$classes .= ' sui-' . str_replace( '.', '-', WPMUDEV_PLUGINTEST_SUI_VERSION ) . ' ';

		return $classes;
	}
}
