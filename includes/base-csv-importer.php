<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * CSV Importer Class
 *
 * @since 1.0.0
 */
if( ! class_exists( 'Base_CSV_Importer' ) ) {
	class Base_CSV_Importer {

		public $rows_imported = 0;
		public $rows_failed = 0;
		public $columns = null;

		/**
		 * Get everything running.
		 *
		 * @since 1.0.0
		 */
		function __construct( $file_key ) {

			// In order to make this class re-usable as possible, pass a file key so we can auto-magically handle things
			$this->file_key = $file_key;

			// To make things easier within the class, grab the file from $_FILES
			$this->file = isset( $_FILES[ $file_key ] ) ? $_FILES[ $file_key ] : false;

			// The allowed mime types for CSV upload because not all OS/Browsers use the "right" mime type for CSV
			// http://stackoverflow.com/questions/6654351/check-file-uploaded-is-in-csv-format
			$this->mime_types = array(
				'text/csv',
				'text/plain',
				'application/csv',
				'text/comma-separated-values',
				'application/excel',
				'application/vnd.ms-excel',
				'application/vnd.msexcel',
				'text/anytext',
				'application/octet-stream',
				'application/txt',
			);

			// Sometimes the delimiter needs to be changed for various reasons. By saving it as a variable it can easily be changed later.
			$this->delimiter = ',';

		} /* __construct() */

		/**
		 * Master function to trigger all the other functions in the CSV import process
		 *
		 * @since 1.0.0
		 */
		public function handle_csv() {
			if( $this->validate_upload() ) {
				$this->run_import();
			}
		}

		/**
		 * Take the CSV and loop through it, passing a single row to another function to import it.
		 *
		 * @since 1.0.0
		 */
		public function run_import() {
			$csv = fopen( $this->file['tmp_name'], 'r' );
			if( false !== $csv ) {
				while ( false !== ( $row = fgetcsv( $csv, 0, $this->delimiter ) ) ) {
					if( is_null( $this->columns ) ) {
						$this->columns = $row;
					} elseif( $this->import_row( $row ) ) {
						$this->rows_imported++;
					} else {
						$this->rows_failed++;
					}
				}
				add_action( 'admin_notices', array( $this, 'notice_csv_imported' ) );
				fclose( $csv );
			} else {
				add_action( 'admin_notices', array( $this, 'notice_cannot_open_csv' ) );
			}
		}

		/**
		 * Take a single row of data from a CSV and handle it as desired.
		 *
		 * @since 1.0.0
		 */
		public function import_row( $row ) {
			$row = apply_filters( 'csv_import_base_import_row', $row, $this->columns );
			do_action( 'csv_import_base_import_row', $row, $this->columns );

			// Example Processing of data
			$post = array();
			foreach( $this->columns as $key => $column ) {
				switch( $column ) {
					case 'post_id':
						$post['ID'] = $row[ $key ];
						break;
					case 'title':
						$post['post_title'] = $row[ $key ];
						break;
					case 'content':
						$post['post_content'] = $row[ $key ];
						break;
				}
			}
//			We do not want to actually do anything with the data, so we will not insert a post or return, but for examples sake...
//			$id = wp_insert_post( $post );
//			return $id;
		}

		/**
		 * Validate the upload, ensuring that it did not have any errors and is in fact the right file type
		 *
		 * @since 1.0.0
		 */
		public function validate_upload() {
			if( $_FILES['csv-import-base-file']['error'] > 0 ) {
				add_action( 'admin_notices', array( $this, 'notice_file_upload_failed' ) );
				return false;
			} elseif( !in_array( $this->file['type'], $this->mime_types ) ) {
				add_action( 'admin_notices', array( $this, 'notice_wrong_filetype' ) );
				return false;
			}
			return true;
		}

		/**
		 * Notice to show that the file upload has failed, and the user should try again.
		 * Depending on the error code, a different error may be shown.
		 * http://www.php.net/manual/en/features.file-upload.errors.php
		 *
		 * @since 1.0.0
		 */
		public function notice_file_upload_failed() {
			$error = absint( $_FILES['csv-import-base-file']['error'] );
			switch( $error  ) {
				case 1:
					$message = __( 'The file was larger than your server allows. Please split up the file into multiple chunks and import separately, or contact your web host to increase your file upload size.', 'csv-import-base' );
					break;
				case 3:
					$message = __( 'The file was only partially uploaded and cannot be processed, please try again.', 'csv-import-base' );
					break;
				case 4:
					$message = __( 'No file was uploaded, please try again.', 'csv-import-base' );
					break;
				default:
					$message = sprintf( __( 'The file upload has failed (Error: %d). Please check your double check your CSV and try again.', 'csv-import-base' ), $error );
			}
			echo '<div id="message" class="error">' . wpautop( $message ) . '</div>';
		} /* notice_file_upload_failed() */

		/**
		 * Only CSV file types should be allowed to be uploaded, if it does not have an expected mime type tell the user what happened.
		 *
		 * @since 1.0.0
		 */
		public function notice_wrong_filetype() {
			$message = sprintf( __( 'The file uploaded was presented as the wrong file type (%s), please double check your file is a CSV (not a XLS/XLSX) and try again.', 'csv-import-base' ), $this->file['type'] );
			echo '<div id="message" class="error">' . wpautop( $message ) . '</div>';
		} /* notice_wrong_filetype() */

		/**
		 * If the CSV file cannot be opened, throw an error so the user knows what happened.
		 *
		 * @since 1.0.0
		 */
		public function notice_cannot_open_csv() {
			$message = sprintf( __( 'The CSV file that was uploaded was not able to be opened from the TMP directory. Please try again, if the issue continues please contact your web host.', 'csv-import-base' ), $this->file['type'] );
			echo '<div id="message" class="error">' . wpautop( $message ) . '</div>';
		} /* notice_cannot_open_csv() */

		/**
		 * If the CSV file cannot be opened, throw an error so the user knows what happened.
		 *
		 * @since 1.0.0
		 */
		public function notice_csv_imported() {
			$message = sprintf( __( '%1$d rows were successfully imported, while %2$d rows failed to import.', 'csv-import-base' ), $this->rows_imported, $this->rows_failed );
			echo '<div id="message" class="updated">' . wpautop( $message ) . '</div>';
		} /* notice_csv_imported() */

	}
}
