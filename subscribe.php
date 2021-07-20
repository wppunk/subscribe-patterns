<?php
/**
 * Subscribe form and list of subscribers.
 *
 * Plugin Name:         Subscribe
 * Description:         The plugin creates a subscribe form and store subscribers into database.
 * Version:             1.0.0
 * Requires at least:   4.9
 * Requires PHP:        5.5
 * Author:              wppunk
 * License:             MIT
 * Text Domain:         subscribe
 *
 * @package     Subscribe
 */

namespace Subscribe;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SUBSCRIBE_VERSION', '1.0.0' );
define( 'SUBSCRIBE_PATH', plugin_dir_path( __FILE__ ) );
define( 'SUBSCRIBE_URL', plugin_dir_url( __FILE__ ) );

class Subscribe {

	const SUBSCRIBE_NONCE_ACTION = 'subscribe-action';

	public function hooks() {

		add_shortcode( 'subscribe_form', [ $this, 'form' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'register_styles' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'register_scripts' ] );
		add_action( 'wp_ajax_subscribe', [ $this, 'subscribe' ] );
		add_action( 'wp_ajax_nopriv_subscribe', [ $this, 'subscribe' ] );
	}

	public function register_styles() {

		wp_register_style(
			'subscribe',
			SUBSCRIBE_URL . '/assets/css/main.css',
			[],
			SUBSCRIBE_VERSION
		);
	}

	public function register_scripts() {

		wp_register_script(
			'subscribe',
			SUBSCRIBE_URL . '/assets/js/main.js',
			[],
			SUBSCRIBE_VERSION,
			true
		);
		wp_localize_script(
			'subscribe',
			'subscribe',
			[
				'adminUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( self::SUBSCRIBE_NONCE_ACTION ),
			]
		);
	}

	public function form() {

		wp_enqueue_style( 'subscribe' );
		wp_enqueue_script( 'subscribe' );
		ob_start();
		?>
		<form action="" method="POST" class="subscribe-form">
			<div class="subscribe-form-row">
				<input type="email" name="email" class="subscribe-form-field" required>
				<button type="submit" class="subscribe-form-button"><?php echo esc_html( 'Subscribe', 'subscribe' ); ?></button>
			</div>
			<div class="subscribe-form-message" style="display: none"></div>
		</form>
		<?php
		return ob_get_clean();
	}

	public function subscribe() {

		check_ajax_referer( self::SUBSCRIBE_NONCE_ACTION );

		$email = filter_input( INPUT_POST, 'email', FILTER_SANITIZE_EMAIL );

		if ( ! is_email( $email ) ) {
			wp_send_json_error(
				sprintf(
					esc_html__( 'The %s is invalid email', 'subscribe' ),
					esc_html( $email )
				)
			);
		}

		if ( 2 === $this->save_subscriber( $email ) ) {
			wp_send_json_error(
				sprintf(
					esc_html__( 'The %s email is already exists', 'subscribe' ),
					esc_html( $email )
				)
			);
		}

		wp_send_json_success( esc_html__( 'You were successfully subscribed', 'subscribe' ) );
	}

	private function save_subscriber( $email ) {

		global $wpdb;

		return $wpdb->replace(
			$this->get_table_name(),
			[
				'email' => sanitize_email( $email ),
			],
			[
				'email' => '%s',
			]
		);
	}

	private function get_table_name() {

		global $wpdb;

		return $wpdb->prefix . 'subscribers';
	}

	public function create_table() {

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		global $wpdb;

		$table_name = $this->get_table_name();

		$sql = "CREATE TABLE {$table_name} (
			email VARCHAR(255) UNIQUE NOT NULL,
			PRIMARY KEY (email)
		) {$wpdb->get_charset_collate()};";

		dbDelta( $sql );
	}
}

$subscribe = new Subscribe();
$subscribe->hooks();

register_activation_hook( __FILE__, [ $subscribe, 'create_table' ] );
