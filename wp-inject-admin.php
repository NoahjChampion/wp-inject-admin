<?php
/**
 * Add new administrator to WordPress.
 *
 * @package    Utilities
 * @subpackage WordPress
 * @author     Robert Neu
 * @copyright  Copyright (c) 2015, Robert Neu
 * @license    MIT
 */

define( 'WP_USE_THEMES', false );

require_once 'wp-load.php';

/**
 * Methods to inject an administrator.
 *
 * @since 0.1.0
 */
class SiteCare_Utilities_Inject_Admin {
	/**
	 * A list of allowed characters to use when generating users and emails.
	 *
	 * @since 0.1.0
	 * @var   string
	 */
	protected $allowed = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

	/**
	 * A list of top level domains to use when generating emails.
	 *
	 * @since 0.1.0
	 * @var   array
	 */
	protected $tlds = array( 'com', 'net', 'gov', 'org', 'edu', 'biz', 'info' );

	/**
	 * Placeholder for a randomly-generated username.
	 *
	 * @since 0.1.0
	 * @var   string
	 */
	protected static $username;

	/**
	 * Placeholder for a single class instance.
	 *
	 * @since 0.1.0
	 * @var   SiteCare_Utilities_Inject_Admin
	 */
	private static $instance;

	/**
	 * Set up required class properties.
	 *
	 * @since  0.1.0
	 * @access public
	 * @return void
	 */
	public function __construct() {
		self::$username = $this->generate_random_username();
	}

	/**
	 * Attempt to delete this file after a single execution.
	 *
	 * @since  0.1.0
	 * @access public
	 * @return void
	 */
	public function __destruct() {
		if ( is_writable( __FILE__ )  ) {
			unlink( __FILE__ );
		} else {
			wp_die( __FILE__ , ' could not be deleted. Please delete it manually.' );
		}
	}

	/**
	 * Get a single instance of the class.
	 *
	 * @since  0.1.0
	 * @access public
	 * @return SiteCare_Utilities_Inject_Admin a single class instance.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Generate a random username.
	 *
	 * @since  0.1.0
	 * @access protected
	 * @param  int $length The length of the username to generate.
	 * @return string $username a randomly-generated username.
	 */
	protected function generate_random_username( $length = 8 ) {
		$username = '';
		for ( $i = 0; $i < $length; $i++ ) {
			$username .= $this->allowed[ mt_rand( 0, strlen( $this->allowed ) ) ];
		}
		return $username;
	}

	/**
	 * Generate a random email address.
	 *
	 * @since  0.1.0
	 * @access protected
	 * @return string $address a randomly-generated email address.
	 */
	protected function generate_random_email() {
		$address       = '';
		$user_length   = mt_rand( 5, 10 );
		$domain_length = mt_rand( 7, 17 );

		for ( $i = 1; $i <= $user_length; $i++ ) {
			$address .= substr( $this->allowed, mt_rand( 0, strlen( $this->allowed ) ), 1 );
		}

		$address .= '@';

		for ( $i = 1; $i <= $domain_length; $i++ ) {
			$address .= substr( $this->allowed, mt_rand( 0, strlen( $this->allowed ) ), 1 );
		}

		$address .= '.';

		$address .= $this->tlds[ mt_rand( 0, ( count( $this->tlds ) -1 ) ) ];

		return $address;
	}

	/**
	 * Create a new WordPress admin account.
	 *
	 * @since  0.1.0
	 * @access protected
	 * @param  string $username The user name for the new admin account.
	 * @param  string $email The email for the new admin account.
	 * @param  string $password The password for the new admin account.
	 * @return bool True if a user has been created.
	 */
	protected function create( $username, $email, $password ) {
		if ( username_exists( $username ) || email_exists( $email ) ) {
			return false;
		}

		$user_id = wp_create_user( $username, $password, $email );

		if ( is_int( $user_id ) ) {
			$object = new WP_User( $user_id );
			$object->set_role( 'administrator' );
			return true;
		}

		return false;
	}

	/**
	 * Automatically log in with our new random user and redirect to WP Admin.
	 *
	 * @since  0.1.0
	 * @access protected
	 * @param  string $username the username for the user to be logged in.
	 * @return void
	 */
	protected function auto_login( $username ) {
		if ( ! is_user_logged_in() ) {
			$user = get_user_by( 'login', $username );
			wp_set_current_user( $user->ID, $user->user_login );
			wp_set_auth_cookie( $user->ID );
			do_action( 'wp_login', $user->user_login );
		}
		wp_safe_redirect( admin_url() );
		exit;
	}

	/**
	 * Create an admin account with random user data.
	 *
	 * @since  0.1.0
	 * @access protected
	 * @return bool True if a user has been created.
	 */
	public function create_random() {
		if ( $user = $this->create( self::$username, $this->generate_random_email(), wp_generate_password() ) ) {
			$this->auto_login( self::$username );
		}

		return $user;
	}

	/**
	 * Halt script execution and print an error when user creation fails.
	 *
	 * @since  0.1.0
	 * @access public
	 * @return void
	 */
	protected function no_user_created() {
		wp_die( 'A new user could not be created.' );
	}
}

SiteCare_Utilities_Inject_Admin::get_instance()->create_random();
