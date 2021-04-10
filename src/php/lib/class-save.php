<?php

namespace JITS\StringLocator;

use JITS\StringLocator\Tests\Loopback;
use JITS\StringLocator\Tests\Smart_Scan;

class Save {

	/**
	 * An object of available test runners.
	 *
	 * @var object
	 */
	private $test = array(
		'loopback',
		'smart_scan',
	);

	/**
	 * An array of notices to send back to the user.
	 *
	 * @var array
	 */
	public $notice = array();

	/**
	 * Save constructor.
	 */
	public function __construct() {
		$this->test['loopback']   = new Loopback();
		$this->test['smart_scan'] = new Smart_Scan();
	}

	/**
	 * Handler for storing the content of the code editor.
	 *
	 * Also runs over the Smart-Scan if enabled.
	 *
	 * @return void|array
	 */
	public function save( $save_params ) {
		$_POST = $save_params;

		$check_loopback = isset( $_POST['string-locator-loopback-check'] );
		$do_smart_scan  = isset( $_POST['string-locator-smart-edit'] );

		if ( String_Locator::is_valid_location( $_POST['string-locator-path'] ) ) {
			$path    = urldecode( $_POST['string-locator-path'] );
			$content = stripslashes( $_POST['string-locator-editor-content'] );

			/**
			 * Send an error notice if the file isn't writable
			 */
			if ( ! is_writeable( $path ) ) {
				$this->notice[] = array(
					'type'    => 'error',
					'message' => __( 'The file could not be written to, please check file permissions or edit it manually.', 'string-locator' ),
				);

				return array(
					'notices' => $this->notice,
				);
			}

			/**
			 * If enabled, run the Smart-Scan on the content before saving it
			 */
			if ( $do_smart_scan && ! $this->test['smart_scan']->run( $content ) ) {
				return array(
					'notices' => $this->test['smart_scan']->get_errors(),
				);
			}

			$original = file_get_contents( $path );

			$this->write_file( $path, $content );

			/**
			 * Check the status of the site after making our edits.
			 * If the site fails, revert the changes to return the sites to its original state
			 */
			if ( $check_loopback && ! $this->test['loopback']->run() ) {
				$this->write_file( $path, $original );

				return array(
					'notices' => $this->test['loopback']->get_errors(),
				);
			}

			return array(
				'notices' => array(
					array(
						'type'    => 'success',
						'message' => __( 'The file has been saved', 'string-locator' ),
					),
				),
			);
		} else {
			return array(
				'notices' => array(
					array(
						'type'    => 'error',
						'message' => sprintf(
						// translators: %s: The file location that was sent.
							__( 'The file location provided, <strong>%s</strong>, is not valid.', 'string-locator' ),
							$_POST['string-locator-path']
						),
					),
				),
			);
		}
	}

	/**
	 * When editing a file, this is where we write all the new content.
	 * We will break early if the user isn't allowed to edit files.
	 *
	 * @param string $path The path to the file.
	 * @param string $content The content to write to the file.
	 *
	 * @return void
	 */
	private function write_file( $path, $content ) {
		if ( ! current_user_can( 'edit_themes' ) ) {
			return;
		}

		// Verify the location is valid before we try using it.
		if ( ! String_Locator::is_valid_location( $path ) ) {
			return;
		}

		$back_compat_filter = apply_filters( 'string-locator-filter-closing-php-tags', true ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

		if ( apply_filters( 'string_locator_filter_closing_php_tags', $back_compat_filter ) ) {
			$content = preg_replace( '/\?>$/si', '', trim( $content ), - 1, $replaced_strings );

			if ( $replaced_strings >= 1 ) {
				$this->notice[] = array(
					'type'    => 'error',
					'message' => __( 'We detected a PHP code tag ending, this has been automatically stripped out to help prevent errors in your code.', 'string-locator' ),
				);
			}
		}

		$file        = fopen( $path, 'w' );
		$lines       = explode( "\n", str_replace( array( "\r\n", "\r" ), "\n", $content ) );
		$total_lines = count( $lines );

		for ( $i = 0; $i < $total_lines; $i ++ ) {
			$write_line = $lines[ $i ];

			if ( ( $i + 1 ) < $total_lines ) {
				$write_line .= PHP_EOL;
			}

			fwrite( $file, $write_line );
		}

		fclose( $file );
	}
}
