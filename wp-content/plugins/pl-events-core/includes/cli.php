<?php
/**
 * WP-CLI: wp pl import [--file=<path>] [--overwrite] · wp pl export · wp pl stats · wp pl status-check
 *
 * @package pl-events-core
 */

defined( 'ABSPATH' ) || exit;

WP_CLI::add_command(
	'pl',
	new class() {
		/**
		 * Import from a pl-data.json file (default: the bundled seed).
		 *
		 * [--file=<path>]
		 * [--overwrite]
		 */
		public function import( $args, $assoc ) {
			$r = ( new PL_Importer() )->run( $assoc['file'] ?? PL_Importer::seed_file(), ! empty( $assoc['overwrite'] ) );
			if ( is_wp_error( $r ) ) {
				WP_CLI::error( $r->get_error_message() );
			}
			WP_CLI::log( wp_json_encode( $r, JSON_PRETTY_PRINT ) );
			WP_CLI::success( sprintf( '%d created, %d updated, %d skipped, %d media.', $r['created'], $r['updated'], $r['skipped'], $r['media'] ) );
		}

		/** Print PL_Data::all() as JSON. */
		public function export() {
			WP_CLI::log( wp_json_encode( PL_Data::all(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
		}

		/** Site-wide numbers. */
		public function stats() {
			$s = PL_Data::stats();
			WP_CLI::log( wp_json_encode( array_diff_key( $s, array_flip( array( 'specialty_list' ) ) ), JSON_UNESCAPED_UNICODE ) );
		}

		/** Run the hourly status check now. */
		public function status_check() {
			WP_CLI::log( PL_Cache::hourly_check() ? 'Status changed — caches cleared.' : 'No change.' );
		}
	}
);
