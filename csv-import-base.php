<?php
/**
 * Plugin Name: CSV Import Base
 * Plugin URI: https://pmgarman.me/
 * Description: A quick way to jump start your CSV import plugin
 * Author: Patrick Garman
 * Version: 1.0.0
 * Author URI: https://pmgarman.me/
 * License: GNU AGPLv3
 * License URI: http://www.gnu.org/licenses/agpl-3.0.html
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * CSV Import Base Class
 *
 * @since 1.0.0
 */
if( ! class_exists( 'CSV_Import_Base_Plugin' ) ) {
	class CSV_Import_Base_Plugin {

		/**
		 * Get everything running.
		 * @since 1.0.0
		 */
		function __construct() {

			// Define plugin constants
			$this->basename = plugin_basename( __FILE__ );
			$this->directory_path = plugin_dir_path( __FILE__ );
			$this->directory_url = plugins_url( dirname( $this->basename ) );

			// Load translations
			load_plugin_textdomain( 'csv-import-base', false, dirname( $this->basename ) . '/languages' );

			// This is a base plugin, make sure everyone knows it
			add_action( 'admin_notices', array( $this, 'notice_base_plugin' ) );

			// Setup the plugin
			add_action( 'init', array( $this, 'includes' ) );
			add_action( 'admin_menu', array( $this, 'register_importer_page' ) );
			add_action( 'admin_init', array( $this, 'handle_csv_uploads' ) );

		} /* __construct() */


		/**
		 * Include our plugin dependencies
		 *
		 * @since 1.0.0
		 */
		public function includes() {

			// Include Files
			require_once 'includes/base-csv-importer.php';

		} /* includes() */

		/**
		 * This plugin has no functionality, so let's make sure everyone knows that.
		 * @since 1.0.0
		 */
		public function notice_base_plugin() {
			echo '<div id="message" class="error">';
			echo '<p>' . sprintf( __( 'The CSV Import Base is meant to be a way to allow your quick building of your own CSV import plugin, it does not have any functionality on it\'s own.', 'csv-import-base' ), admin_url( 'plugins.php' ) ) . '</p>';
			echo '</div>';
		} /* notice_base_plugin() */

		/**
		 * Add the options page to the tools menu
		 * @since 1.0.0
		 */
		public function register_importer_page() {
			add_management_page( __( 'CSV Import', 'csv-import-base' ), __( 'CSV Import', 'csv-import-base' ), 'manage_options', 'csv-import-base', array( $this, 'render_importer_page' ) );
		} /* add_importer_page() */

		/**
		 * Render the HTML for the options page
		 * @since 1.0.0
		 */
		public function render_importer_page() {
			?>
			<div class="wrap">
				<h2><?php _e( 'CSV Importer', 'csv-import-base' ); ?></h2>
				<form method="post" action="" enctype="multipart/form-data">
					<?php echo wpautop( __( 'This CSV import requires column headers, the first row will be used to capture these headers. If you do not include column headers, things will not work as expected.', 'csv-import-base' ) ); ?>
					<label
						for="csv-import-base-file"><?php _e( 'Select your CSV file to be uploaded:', 'csv-import-base' ); ?></label></br>
					<input type="file" name="csv-import-base-file" id="csv-import-base-file">
					<?php wp_nonce_field( 'csv-import-base-upload', 'csv-import-base-upload' ); ?>
					<?php submit_button( __( 'Import CSV', 'csv-import-base' ) ); ?>
					<?php echo wpautop( __( 'Note: There is no confirmation after submitting this form, make sure everything is correct! There is no "undo" for this import, you may want to make a database backup.', 'csv-import-base' ) ); ?>
				</form>
			</div>
		<?php
		} /* render_importer_page() */

		/**
		 * Watch for CSV file uploads, and when present validate them, then trigger the CSV import
		 * @since 1.0.0
		 */
		public function handle_csv_uploads() {
			if( isset( $_POST['csv-import-base-upload'] ) ) {
				if ( wp_verify_nonce( $_POST['csv-import-base-upload'], 'csv-import-base-upload' ) ) {
					$importer = new Base_CSV_Importer( 'csv-import-base-file' );
					$importer->handle_csv();
				} else {
					wp_die( __( 'CSV Import Base nonce could not be verified.', 'csv-import-base' ) );
				}
			}
		}

	} /* CSV_Import_Base_Plugin */

	// Instantiate our class to a global variable that we can access elsewhere
	$GLOBALS['csv_import_base_plugin'] = new CSV_Import_Base_Plugin();
}
