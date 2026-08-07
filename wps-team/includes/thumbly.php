<?php
/**
 * On-demand custom image resizing for WPS Team.
 *
 * Used only for shortcode/custom thumbnail width, height, and crop.
 * Relies on core WP_Image_Editor (no global editor overrides).
 *
 * @package WPS_Team
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WPS_THUMB_UPLOAD_DIR' ) ) {
	define( 'WPS_THUMB_UPLOAD_DIR', 'wps-team/thumbs' );
}

if ( ! function_exists( 'wps_thumb' ) ) {

	/**
	 * Resize a local image URL and return the generated URL (or size array).
	 *
	 * @param string $url    Local image URL.
	 * @param array  $params {
	 *     @type int|string|null $width   Target width in pixels (or percentage string).
	 *     @type int|string|null $height  Target height in pixels (or percentage string).
	 *     @type bool            $crop    Whether to hard-crop.
	 *     @type int             $quality JPEG quality 1–100.
	 * }
	 * @param bool   $single If true return URL string; if false return [ url, width, height ].
	 * @return string|array|false
	 */
	function wps_thumb( $url, $params = array(), $single = true ) {
		return WPS_Thumb::thumb( $url, $params, $single );
	}
}

if ( ! class_exists( 'WPS_Thumb' ) ) {

	/**
	 * Custom thumbnail generator.
	 */
	class WPS_Thumb {

		/**
		 * Whether the upscale-capable resize-dimensions filter is active.
		 *
		 * @var bool
		 */
		private static $upscale_filter_active = false;

		/**
		 * @param string $url    Local image URL.
		 * @param array  $params Resize options.
		 * @param bool   $single Return URL only when true.
		 * @return string|array|false
		 */
		public static function thumb( $url, $params = array(), $single = true ) {
			if ( empty( $url ) || ! is_string( $url ) ) {
				return false;
			}

			$width   = isset( $params['width'] ) ? $params['width'] : null;
			$height  = isset( $params['height'] ) ? $params['height'] : null;
			$crop    = ! empty( $params['crop'] );
			$quality = isset( $params['quality'] ) ? (int) $params['quality'] : 0;

			$upload_info = wp_upload_dir();
			$upload_dir  = $upload_info['basedir'];
			$upload_url  = $upload_info['baseurl'];
			$theme_url   = get_template_directory_uri();
			$theme_dir   = get_template_directory();

			$img_path = '';

			if ( false !== strpos( $url, $upload_url ) ) {
				$rel_path = str_replace( $upload_url, '', $url );
				$img_path = $upload_dir . $rel_path;
			} elseif ( false !== strpos( $url, $theme_url ) ) {
				$rel_path = str_replace( $theme_url, '', $url );
				$img_path = $theme_dir . $rel_path;
			}

			if ( '' === $img_path || ! file_exists( $img_path ) ) {
				return $url;
			}

			$size = @getimagesize( $img_path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			if ( ! $size ) {
				return $url;
			}

			list( $orig_w, $orig_h ) = $size;

			$info = pathinfo( $img_path );
			$ext  = isset( $info['extension'] ) ? $info['extension'] : '';

			if ( null !== $width && false !== stripos( (string) $width, '%' ) ) {
				$width = (int) ( ( (float) str_replace( '%', '', (string) $width ) ) / 100 * $orig_w );
			}
			if ( null !== $height && false !== stripos( (string) $height, '%' ) ) {
				$height = (int) ( ( (float) str_replace( '%', '', (string) $height ) ) / 100 * $orig_h );
			}

			if ( '' === $width || false === $width ) {
				$width = null;
			}
			if ( '' === $height || false === $height ) {
				$height = null;
			}

			if ( null !== $width ) {
				$width = (int) $width;
			}
			if ( null !== $height ) {
				$height = (int) $height;
			}

			if ( ( null === $width || $width <= 0 ) && ( null === $height || $height <= 0 ) ) {
				return $url;
			}

			self::enable_upscale_dimensions();
			$dims = image_resize_dimensions( $orig_w, $orig_h, $width, $height, $crop );
			self::disable_upscale_dimensions();

			if ( ! is_array( $dims ) ) {
				return $url;
			}

			$dst_w = isset( $dims[4] ) ? (int) $dims[4] : $orig_w;
			$dst_h = isset( $dims[5] ) ? (int) $dims[5] : $orig_h;

			$suffix = (string) filemtime( $img_path )
				. str_pad( (string) ( $width ? $width : 0 ), 5, '0', STR_PAD_LEFT )
				. str_pad( (string) ( $height ? $height : 0 ), 5, '0', STR_PAD_LEFT )
				. ( $crop ? '1' : '0' )
				. ( ( $quality > 0 && $quality <= 100 ) ? (string) $quality : '0' );
			$suffix = self::base_convert_arbitrary( $suffix, 10, 36 );

			$dst_rel_path = str_replace( '.' . $ext, '', basename( $img_path ) );
			$ext         = strtolower( $ext );

			$dest_dir = trailingslashit( $upload_dir ) . WPS_THUMB_UPLOAD_DIR;
			$dest_url = trailingslashit( $upload_url ) . WPS_THUMB_UPLOAD_DIR;

			if ( ! is_dir( $dest_dir ) ) {
				wp_mkdir_p( $dest_dir );
			}

			$destfilename = "{$dest_dir}/{$dst_rel_path}-{$suffix}.{$ext}";
			$img_url      = "{$dest_url}/{$dst_rel_path}-{$suffix}.{$ext}";

			if ( ! file_exists( $destfilename ) || ! @getimagesize( $destfilename ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				$editor = wp_get_image_editor( $img_path );

				if ( is_wp_error( $editor ) ) {
					return false;
				}

				self::enable_upscale_dimensions();
				$resized = $editor->resize( $width, $height, $crop );
				self::disable_upscale_dimensions();

				if ( is_wp_error( $resized ) ) {
					return false;
				}

				if ( $quality > 0 && $quality <= 100 && 'png' !== $ext ) {
					$editor->set_quality( $quality );
				}

				$saved = $editor->save( $destfilename );
				if ( is_wp_error( $saved ) ) {
					return false;
				}
			}

			if ( $single ) {
				return $img_url;
			}

			return array( $img_url, $dst_w, $dst_h );
		}

		/**
		 * Allow resizing to dimensions larger than the original (scoped to wps_thumb).
		 *
		 * @param mixed $payload Default payload.
		 * @param int   $orig_w  Original width.
		 * @param int   $orig_h  Original height.
		 * @param int   $dest_w  Destination width.
		 * @param int   $dest_h  Destination height.
		 * @param bool  $crop    Whether to crop.
		 * @return array|null
		 */
		public static function image_resize_dimensions( $payload, $orig_w, $orig_h, $dest_w, $dest_h, $crop = false ) {
			if ( ! self::$upscale_filter_active ) {
				return $payload;
			}

			$orig_w = (int) $orig_w;
			$orig_h = (int) $orig_h;

			if ( $orig_w <= 0 || $orig_h <= 0 ) {
				return null;
			}

			$aspect_ratio = $orig_w / $orig_h;
			$new_w        = (int) $dest_w;
			$new_h        = (int) $dest_h;

			if ( $new_w <= 0 ) {
				$new_w = (int) round( $new_h * $aspect_ratio );
			}

			if ( $new_h <= 0 ) {
				$new_h = (int) round( $new_w / $aspect_ratio );
			}

			if ( $new_w > 5000 || $new_h > 5000 || $new_w <= 0 || $new_h <= 0 ) {
				return null;
			}

			$size_ratio = max( $new_w / $orig_w, $new_h / $orig_h );
			$crop_w     = (int) round( $new_w / $size_ratio );
			$crop_h     = (int) round( $new_h / $size_ratio );
			$s_x        = (int) floor( ( $orig_w - $crop_w ) / 2 );
			$s_y        = (int) floor( ( $orig_h - $crop_h ) / 2 );

			return array( 0, 0, $s_x, $s_y, $new_w, $new_h, $crop_w, $crop_h );
		}

		/**
		 * Enable scoped upscale filter.
		 */
		private static function enable_upscale_dimensions() {
			if ( self::$upscale_filter_active ) {
				return;
			}
			self::$upscale_filter_active = true;
			add_filter( 'image_resize_dimensions', array( __CLASS__, 'image_resize_dimensions' ), 10, 6 );
		}

		/**
		 * Disable scoped upscale filter.
		 */
		private static function disable_upscale_dimensions() {
			if ( ! self::$upscale_filter_active ) {
				return;
			}
			remove_filter( 'image_resize_dimensions', array( __CLASS__, 'image_resize_dimensions' ), 10 );
			self::$upscale_filter_active = false;
		}

		/**
		 * Shorten a numeric string into base-36.
		 *
		 * @param string $number    Digit string.
		 * @param int    $from_base Source base.
		 * @param int    $to_base   Target base.
		 * @return string
		 */
		protected static function base_convert_arbitrary( $number, $from_base, $to_base ) {
			$digits = '0123456789abcdefghijklmnopqrstuvwxyz';
			$length = strlen( $number );
			$result = '';

			$nibbles = array();
			for ( $i = 0; $i < $length; ++$i ) {
				$nibbles[ $i ] = strpos( $digits, $number[ $i ] );
			}

			do {
				$value  = 0;
				$newlen = 0;

				for ( $i = 0; $i < $length; ++$i ) {
					$value = $value * $from_base + $nibbles[ $i ];

					if ( $value >= $to_base ) {
						$nibbles[ $newlen++ ] = (int) ( $value / $to_base );
						$value               %= $to_base;
					} elseif ( $newlen > 0 ) {
						$nibbles[ $newlen++ ] = 0;
					}
				}

				$length = $newlen;
				$result = $digits[ $value ] . $result;
			} while ( 0 !== $newlen );

			return $result;
		}
	}
}

/**
 * Hook custom sizes into core image helpers when `'wps_thumb' => true` is set.
 *
 * Example: wp_get_attachment_image( $id, array( 400, 300, 'wps_thumb' => true, 'crop' => true ) );
 */
add_filter( 'image_downsize', 'wps_image_downsize', 1, 3 );

if ( ! function_exists( 'wps_image_downsize' ) ) {

	/**
	 * @param bool|array   $out  Default.
	 * @param int          $id   Attachment ID.
	 * @param string|array $size Size name or array.
	 * @return bool|array
	 */
	function wps_image_downsize( $out, $id, $size ) {
		if ( ! is_array( $size ) || empty( $size['wps_thumb'] ) ) {
			return false;
		}

		$img_url = wp_get_attachment_url( $id );
		if ( ! $img_url ) {
			return false;
		}

		$params           = $size;
		$params['width']  = isset( $size[0] ) ? $size[0] : null;
		$params['height'] = isset( $size[1] ) ? $size[1] : null;
		$params['crop']   = ! empty( $size['crop'] );

		return wps_thumb( $img_url, $params, false );
	}
}
