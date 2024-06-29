<?php
/**
 * Google Auth Shortcode.
 *
 * @link          https://wpmudev.com/
 * @since         1.0.0
 *
 * @author        WPMUDEV (https://wpmudev.com)
 * @package       WPMUDEV\PluginTest
 *
 * @copyright (c) 2023, Incsub (http://incsub.com)
 */

namespace WPMUDEV\PluginTest\Endpoints\V1;

// Abort if called directly.
defined( 'WPINC' ) || die;

use WPMUDEV\PluginTest\Endpoint;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;


class Auth extends Endpoint {
	/**
	 * API endpoint for the current endpoint.
	 *
	 * @since 1.0.0
	 *
	 * @var string $endpoint
	 */
	protected $endpoint = 'auth/auth-url';
	
	/** 
	 * The endpoint for the OAuth return URL.
	 * 
	 * @since 1.0.0
	 * 
	 * @var string $confirm_endpoint
	*/
	protected $confirm_endpoint = 'auth/confirm';


	

	/**
	 * Register the routes for handling auth functionality.
	 *
	 * @return void
	 * @since 1.0.0
	 *
	 */
	public function register_routes() {
		// TODO
		// Add a new Route to logout.
		

		// Route to get auth url.
		register_rest_route(
			$this->get_namespace(),
			$this->get_endpoint(),
			array(
				array(
					'methods' => 'POST',
					'callback' => array( $this, 'save_credentials' ),
					'permission_callback' => function() {
						return current_user_can( 'manage_options' );
					},
					'args'    => array(
						'client_id'     => array(
							'required'    => true,
							'description' => __( 'The client ID from Google API project.', 'wpmudev-plugin-test' ),
							'type'        => 'string',
						),
						'client_secret' => array(
							'required'    => true,
							'description' => __( 'The client secret from Google API project.', 'wpmudev-plugin-test' ),
							'type'        => 'string',
						),
					),
				),
			)
		);

		// Route for the OAuth return URL.
		register_rest_route(
			$this->get_namespace(),
			$this->get_confirm_endpoint(),
			array(
				array(
					'methods' => 'GET',
					'callback' => array($this, 'wpmudev_handle_google_oauth_response'),
					'permission_callback' => '__return_true',
				),
			)
		);

		// Route to posts scan
		register_rest_route(
			$this->get_namespace(),
			'posts/scan',
			array(
				array(
					'methods' => 'POST',
					'callback' => array( $this, 'scan_posts' ),
					'permission_callback' => function() {
						return current_user_can( 'manage_options' );
					},
				),
			)
		);	
	}

	/**
     * Scan all public posts and pages and update the wpmudev_test_last_scan post_meta with the current timestamp.
     *
     * @param WP_REST_Request $request The request object.
     *
     * @return WP_REST_Response
     * @since 1.0.0
     */
	public function scan_posts( WP_REST_Request $request ) {
		
		$args = array(
            'post_type' => array('post', 'page'),
            'post_status' => 'publish',
            'posts_per_page' => -1,
        );

        $query = new \WP_Query($args);

        while ($query->have_posts()) {
            $query->the_post();
            update_post_meta(get_the_ID(), 'wpmudev_test_last_scan', current_time('mysql'));
        }

        wp_reset_postdata();
		
        return new WP_REST_Response(array('message' => 'Posts scanned successfully'), 200);
	}

	/**
     * Save the client id and secret.
     *
     * @param WP_REST_Request $request The request object.
     *
     * @return WP_REST_Response
     * @since 1.0.0
     */
    public function save_credentials( WP_REST_Request $request ) {

		 // Check the nonce.
		$nonce = $request->get_header('X-WP-Nonce');
		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_REST_Response( array( 'message' => 'Invalid nonce' ), 403 );
		}
		 
        $client_id     = sanitize_text_field( $request->get_param('client_id') );
        $client_secret = sanitize_text_field( $request->get_param('client_secret') );

		// Check if client_id or client_secret are empty.
		if ( empty( $client_id ) || empty( $client_secret ) ) {
			return new WP_REST_Response(array('message' => 'Client ID and Client Secret cannot be empty'), 400);
		}
	
        // Store the credentials in the 'wpmudev_plugin_test_settings' option.
        $settings = get_option('wpmudev_plugin_test_settings', array());
        $settings['client_id'] = $client_id;
        $settings['client_secret'] = $client_secret;
        update_option('wpmudev_plugin_test_settings', $settings);

        // Verify correct storage.
        $stored_settings = get_option('wpmudev_plugin_test_settings');
        if ( $stored_settings['client_id'] === $client_id && $stored_settings['client_secret'] === $client_secret ) {
            return new WP_REST_Response( array( 'message' => 'Credentials stored successfully' ), 200 );
        } else {
            return new WP_REST_Response( array( 'message' => 'Error storing credentials' ), 500 );
        }
    }

	public function wpmudev_handle_google_oauth_response(WP_REST_Request $request) {
		// Get the authorization code from the request
		$code = $request->get_param('code');
	
		if (!$code) {
			return new WP_REST_Response(array('message' => 'Authorization code not found'), 400);
		}
	
		// Exchange the authorization code for an access token
		$response = wp_remote_post('https://oauth2.googleapis.com/token', array(
			'body' => array(
				'code' => $code,
				'client_id' => get_option('wpmudev_plugin_test_settings')['client_id'],
				'client_secret' => get_option('wpmudev_plugin_test_settings')['client_secret'],
				'redirect_uri' => home_url('/wp-json/wpmudev/v1/auth/confirm'),
				'grant_type' => 'authorization_code',
			),
		));
	
		if (is_wp_error($response)) {
			return new WP_REST_Response(array('message' => 'Error fetching access token'), 500);
		}
	
		$body = json_decode(wp_remote_retrieve_body($response), true);
		if (isset($body['error'])) {
			return new WP_REST_Response(array('message' => 'Error: ' . $body['error_description']), 500);
		}
	
		$access_token = $body['access_token'];
	
		// Fetch user info from Google
		$response = wp_remote_get('https://www.googleapis.com/oauth2/v2/userinfo', array(
			'headers' => array('Authorization' => 'Bearer ' . $access_token),
		));
	
		if (is_wp_error($response)) {
			return new WP_REST_Response(array('message' => 'Error fetching user info'), 500);
		}
	
		$user_info = json_decode(wp_remote_retrieve_body($response), true);
		$email = $user_info['email'];
		$name = $user_info['name'];
	
		if (email_exists($email)) {
			// Log in the user if email exists
			$user = get_user_by('email', $email);
			wp_set_current_user($user->ID);
			wp_set_auth_cookie($user->ID);
		} else {
			// Create a new user if email does not exist
			$random_password = wp_generate_password();
			$user_id = wp_create_user($email, $random_password, $email);
			wp_update_user(array(
				'ID' => $user_id,
				'display_name' => $name,
			));
	
			// Log in the newly created user
			wp_set_current_user($user_id);
			wp_set_auth_cookie($user_id);
		}
	
		// Redirect to admin or home page
		wp_redirect(admin_url());
		exit;
	}
	
}
