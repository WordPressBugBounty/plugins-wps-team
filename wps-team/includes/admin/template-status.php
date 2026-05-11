<?php

namespace WPSpeedo_Team;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use FilesystemIterator;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tools → Template status (override detection, versions).
 */
class Template_Status {

	use AJAX_Handler;

	public $ajax_key = 'wpspeedo_team';

	public $ajax_scope = '_template_status_handler';

	public function __construct() {
		$this->set_ajax_scope_hooks();
	}

	public function ajax_get_template_status() {

		if ( ! current_user_can( 'manage_options' ) ) {
			$message = _x( 'You do not have permission to perform this action', 'Settings: Tools', 'wps-team' );
			if ( wp_doing_ajax() ) {
				wp_send_json_error( $message, 403 );
			}
			return;
		}

		wp_send_json_success( self::build_report() );
	}

	/**
	 * @return array<string,mixed>
	 */
	public static function build_report() {

		$rows   = array();
		$folder = (string) apply_filters( 'wpspeedo_team/template/folder', 'wpspeedo-team' );
		$folder = '/' . trailingslashit( ltrim( $folder, '/\\' ) );

		foreach ( self::collect_plugin_template_files() as $file ) {
			$rows[] = self::inspect_file( $file['full'], $file['rel'], $folder );
		}

		$report = array(
			'template_folder' => trim( $folder, '/' ),
			'doc_url'         => 'https://wpspeedo.com/docs/team-template-override/',
			'rows'            => $rows,
			'counts'          => self::summarize_counts( $rows ),
		);

		return apply_filters( 'wpspeedo_team/template_status/report', $report );
	}

	/**
	 * @return array<int,array{full:string,rel:string}>
	 */
	private static function collect_plugin_template_files() {

		$base = WPS_TEAM_PATH . 'templates/';
		$out   = array();

		if ( ! is_dir( $base ) ) {
			return $out;
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $base, FilesystemIterator::SKIP_DOTS )
		);

		foreach ( $iterator as $file ) {
			if ( ! $file->isFile() ) {
				continue;
			}
			if ( strtolower( $file->getExtension() ) !== 'php' ) {
				continue;
			}
			$full = $file->getPathname();
			$rel  = ltrim( str_replace( '\\', '/', substr( $full, strlen( $base ) ) ), '/' );
			$out[] = array(
				'full' => $full,
				'rel'  => $rel,
			);
		}

		usort(
			$out,
			function ( $a, $b ) {
				return strcmp( $a['rel'], $b['rel'] );
			}
		);

		return $out;
	}

	/**
	 * Path relative to `templates/` → argument for {@see Utils::load_template()}.
	 */
	public static function canonical_load_key( $rel ) {

		if ( strpos( $rel, 'pro/' ) === 0 ) {
			return substr( $rel, 4 );
		}

		return $rel;
	}

	/**
	 * @param string $full Absolute path under plugin templates/.
	 * @param string $rel  Path relative to templates/ (e.g. pro/foo.php, partials/bar.php).
	 * @param string $folder Normalized override folder with leading/trailing slash segments.
	 * @return array<string,mixed>
	 */
	private static function inspect_file( $full, $rel, $folder ) {

		$key          = self::canonical_load_key( $rel );
		$core_path    = wp_normalize_path( $full );
		$core_version = self::parse_template_version( $full );

		$resolved = Utils::load_template( $key );

		if ( $resolved instanceof WP_Error ) {
			return array(
				'template'       => $rel,
				'load_key'       => $key,
				'status'         => 'inactive',
				'status_label'   => _x( 'Not loaded', 'Template status', 'wps-team' ),
				'core_version'   => $core_version ? $core_version : '',
				'override_path'  => '',
				'override_label' => '',
				'override_version' => '',
				'core_path'      => $core_path,
				'message'        => $resolved->get_error_message(),
			);
		}

		$effective = wp_normalize_path( $resolved );
		$core_rp     = file_exists( $full ) ? realpath( $full ) : false;
		$effect_rp   = file_exists( $resolved ) ? realpath( $resolved ) : false;
		$core_cmp    = $core_rp ? wp_normalize_path( $core_rp ) : $core_path;
		$effect_cmp  = $effect_rp ? wp_normalize_path( $effect_rp ) : $effective;

		$overridden = ( $effect_cmp !== $core_cmp );

		$override_version = '';
		$override_label   = '';
		$override_path    = '';

		if ( $overridden ) {
			$override_version = self::parse_template_version( $resolved );
			$override_path    = $effective;
			$override_label   = self::resolve_location_label( $resolved, $folder );
		}

		$status       = 'current';
		$status_label = _x( 'Up to date', 'Template status', 'wps-team' );

		if ( ! $overridden ) {
			$status       = 'current';
			$status_label = _x( 'Core (not overridden)', 'Template status', 'wps-team' );
		} else {
			if ( $core_version && $override_version ) {
				if ( version_compare( $core_version, $override_version, '>' ) ) {
					$status       = 'outdated';
					$status_label = _x( 'Outdated', 'Template status', 'wps-team' );
				} else {
					$status       = 'override_ok';
					$status_label = _x( 'Overridden', 'Template status', 'wps-team' );
				}
			} else {
				$status       = 'unknown';
				$status_label = _x( 'Version unknown', 'Template status', 'wps-team' );
			}
		}

		return array(
			'template'         => $rel,
			'load_key'         => $key,
			'status'           => $status,
			'status_label'     => $status_label,
			'core_version'     => $core_version ? $core_version : '',
			'override_path'    => $override_path,
			'override_label'   => $override_label,
			'override_version' => $override_version ? $override_version : '',
			'core_path'        => $core_path,
			'message'          => '',
		);
	}

	/**
	 * @param array<int,array<string,mixed>> $rows
	 * @return array<string,int>
	 */
	private static function summarize_counts( $rows ) {

		$counts = array(
			'total'     => count( $rows ),
			'overrides' => 0,
			'outdated'  => 0,
			'unknown'   => 0,
			'inactive'  => 0,
		);

		foreach ( $rows as $row ) {
			if ( ! empty( $row['override_path'] ) ) {
				$counts['overrides']++;
			}
			if ( isset( $row['status'] ) && $row['status'] === 'outdated' ) {
				$counts['outdated']++;
			}
			if ( isset( $row['status'] ) && $row['status'] === 'unknown' ) {
				$counts['unknown']++;
			}
			if ( isset( $row['status'] ) && $row['status'] === 'inactive' ) {
				$counts['inactive']++;
			}
		}

		return $counts;
	}

	/**
	 * @param string $file
	 * @return string
	 */
	public static function parse_template_version( $file ) {

		if ( ! is_readable( $file ) ) {
			return '';
		}

		$fh = fopen( $file, 'rb' );
		if ( ! $fh ) {
			return '';
		}
		$head = fread( $fh, 8192 );
		fclose( $fh );

		if ( preg_match( '/@version\s+(\S+)/i', (string) $head, $m ) ) {
			return trim( $m[1], "* /\t\r\n" );
		}

		return '';
	}

	/**
	 * @param string $path   Resolved template path.
	 * @param string $folder Normalized override folder (leading /, trailing /).
	 * @return string
	 */
	private static function resolve_location_label( $path, $folder ) {

		$path = wp_normalize_path( $path );

		if ( strpos( $path, wp_normalize_path( WPMU_PLUGIN_DIR ) ) === 0 ) {
			return _x( 'Must-use plugin', 'Template status', 'wps-team' );
		}

		$stylesheet = wp_normalize_path( trailingslashit( get_stylesheet_directory() ) );
		$template   = wp_normalize_path( trailingslashit( get_template_directory() ) );

		if ( is_child_theme() && strpos( $path, $template ) === 0 ) {
			return _x( 'Parent theme', 'Template status', 'wps-team' );
		}

		if ( strpos( $path, $stylesheet ) === 0 ) {
			return is_child_theme()
				? _x( 'Child theme', 'Template status', 'wps-team' )
				: _x( 'Theme', 'Template status', 'wps-team' );
		}

		return _x( 'Custom location', 'Template status', 'wps-team' );
	}
}
