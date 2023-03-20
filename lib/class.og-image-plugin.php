<?php
/**
 * The main plugin
 *
 * @package    Acato/Plugins/BrandedSocialImages
 */

namespace Acato\Plugins\OGImage;

use Exception;
use RankMath;
use WP_Http_Cookie;

defined( 'ABSPATH' ) || die( 'You cannot be here.' );

/**
 * The main plugin class
 */
class Plugin {
	/**
	 * Defines the URL-endpoint. After changing, re-save permalinks to take effect
	 *
	 * @var string
	 */
	const BSI_IMAGE_NAME = 'social-image';

	/**
	 * Experimental feature text-stroke. (off|on)
	 *
	 * @var string
	 */
	const FEATURE_STROKE = 'off';

	/**
	 * Reduced functionality for clarity. (off|simple|on)
	 *
	 * @var string
	 */
	const FEATURE_SHADOW = 'simple';

	/**
	 * Enable text-options in post-meta. Turned off to reduce confusion. (off|on)
	 *
	 * @var string
	 */
	const FEATURE_META_TEXT_OPTIONS = 'off';

	/**
	 * Enable logo-options in post-meta. Turned off to reduce confusion. (off|on)
	 *
	 * @var string
	 */
	const FEATURE_META_LOGO_OPTIONS = 'off';

	/**
	 * Fraction of 1, maximum width of the text-field on the image. values from .7 to .95 work fine. Future feature: setting in interface.
	 *
	 * @var float
	 */
	const TEXT_AREA_WIDTH = .95;

	/**
	 * Logo and text offset from image edge. This is a value in pixels
	 *
	 * @var int
	 */
	const PADDING = 40;

	/**
	 * Experimental feature: attempt a smoother end result by using higher scale materials. Image and logo are pixel based and therefore need
	 *
	 * @var int
	 * to be of higher than average resolution for this to work;
	 * For example, if set to 2; 2400x1260 for the image and min 1200 w/h for the logo. You can even use 3 or 4 ;)
	 * After changing, you will need to use a 3rd party plugin to "rebuild thumbnails" to re-generate the proper formats.
	 */
	const AA = 1;

	/**
	 * The name of the folder in /wp-uploads (/wp-content/uploads)
	 *
	 * @var string
	 */
	const STORAGE = 'bsi-uploads';

	/**
	 * The name of the WordPress "image-size", visible in the interface with plugins like "ajax thumbnail rebuild"
	 *
	 * @var string
	 */
	const IMAGE_SIZE_NAME = 'og-image';

	/**
	 * The script and style names
	 *
	 * @var string
	 */
	const SCRIPT_STYLE_HANDLE = 'bsi';

	/**
	 * Lower boundary for logo scaling, a percentage value (positive number, 100 = 1:1 scale)
	 *
	 * @var int
	 */
	const MIN_LOGO_SCALE = 10;

	/**
	 * Upper boundary for logo scaling, a percentage value (positive number, 100 = 1:1 scale)
	 *
	 * @var int
	 */
	const MAX_LOGO_SCALE = 200;

	/**
	 * Lower boundary for font-size, a points value. (Yes, points. GD2 works in points, 100 pixels = 75 points)
	 *
	 * @var int
	 */
	const MIN_FONT_SIZE = 16;

	/**
	 * Upper boundary for font-size, a points value.
	 *
	 * @var int
	 */
	const MAX_FONT_SIZE = 64;

	/**
	 * Default value for font-size, a points value.
	 *
	 * @var int
	 */
	const DEF_FONT_SIZE = 40;

	/**
	 * External URL: the WP Plugin repository URL
	 *
	 * @var string
	 */
	const PLUGIN_URL_WPORG = 'https://wordpress.org/plugins/branded-social-images/';

	/**
	 * External URL: Our website
	 *
	 * @var string
	 */
	const AUTHOR_URL_INFO = 'https://acato.nl/';

	/**
	 * External URL: the WP Plugin support URL
	 *
	 * @var string
	 */
	const BSI_URL_CONTACT = 'https://wordpress.org/support/plugin/branded-social-images/';

	/**
	 * External URL: the GitHub  repository URL
	 *
	 * @var string
	 */
	const BSI_URL_CONTRIBUTE = 'https://github.com/clearsite/branded-social-images/';

	/**
	 * External tool for post-inspection, the name
	 *
	 * @var string
	 */
	const EXTERNAL_INSPECTOR_NAME = 'opengraph.xyz';

	/**
	 * External tool for post-inspection, the url-pattern
	 *
	 * @var string
	 */
	const EXTERNAL_INSPECTOR = 'https://www.opengraph.xyz/url/%s/';

	/**
	 * Admin Slug
	 *
	 * @var string
	 */
	const ADMIN_SLUG = 'branded-social-images';

	/**
	 * Which image to use in admin
	 *
	 * @var string
	 */
	const ICON = 'icon.svg';

	/**
	 * Which image to use in admin menu
	 *
	 * @var string
	 */
	const ADMIN_ICON = 'admin-icon.svg';

	/**
	 * The WordPress query-var variable name. In the rare case there is a conflict, this can be changed, but re-save permalinks after.
	 *
	 * @var string
	 */
	const QUERY_VAR = 'bsi_img';

	/**
	 * Internal value for a special options rendering case. Do not change.
	 *
	 * @var string
	 */
	const DO_NOT_RENDER = 'do_not_render';

	/**
	 * Options prefix
	 *
	 * @var string
	 */
	const DEFAULTS_PREFIX = '_bsi_default_';

	/**
	 * Meta prefix
	 *
	 * @var string
	 */
	const OPTION_PREFIX = '_bsi_';

	/**
	 * Output width. Cannot remember why this is not a constant...
	 *
	 * @var int
	 */
	public $width = 1200;

	/**
	 * Output height. same deal...
	 *
	 * @var int
	 */
	public $height = 630;

	/**
	 * Holds the logo_options
	 *
	 * @var array
	 */
	public $logo_options;

	/**
	 * Holds the text_options
	 *
	 * @var array
	 */
	public $text_options;

	/**
	 * Keeps track of existence of an og:image
	 *
	 * @var bool
	 */
	public $page_already_has_og_image = false;

	/**
	 * Keeps track of availability of an og:image
	 *
	 * @var bool
	 */
	public $og_image_available;

	/**
	 * Get the instance.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		static $instance;
		if ( ! $instance ) {
			$instance = new static();
		}

		return $instance;
	}

	/**
	 * Class constructor, main plugin code.
	 */
	public function __construct() {
		add_filter(
			'query_vars',
			function ( $vars ) {
				$vars[] = Plugin::QUERY_VAR;

				return $vars;
			}
		);

		add_action( 'wp', [ QueriedObject::class, 'setData' ], PHP_INT_MIN, 0 );
		add_action( 'admin_init', [ QueriedObject::class, 'setData' ], PHP_INT_MIN, 0 );

		if ( defined( 'BSI_DEBUG' ) && BSI_DEBUG ) {
			add_action( 'admin_notices', [ static::class, 'show_queried_object' ] );
		}

		add_action( 'init', [ static::class, 'init' ], 1000 );

		// in the new set-up, init was already done.
		add_action(
			'init',
			function () {
				// does not work in any possible way for Post-Type Archives.
				add_rewrite_endpoint( self::output_filename(), EP_PERMALINK | EP_ROOT | EP_PAGES | EP_CATEGORIES | EP_TAGS, Plugin::QUERY_VAR );

				if ( get_option( 'bsi_needs_rewrite_rules' ) || Plugin::output_filename() !== get_option( '_bsi_rewrite_rules_based_on' ) ) {
					delete_option( 'bsi_needs_rewrite_rules' );
					global $wp_rewrite;
					update_option( 'rewrite_rules', false );
					$wp_rewrite->flush_rules( true );
					update_option( '_bsi_rewrite_rules_based_on', Plugin::output_filename() );
					Plugin::purge_cache();
				}
				add_image_size( Plugin::IMAGE_SIZE_NAME, $this->width, $this->height, true );
				if ( Plugin::AA > 1 ) {
					for ( $i = Plugin::AA; $i > 1; $i -- ) {
						add_image_size( Plugin::IMAGE_SIZE_NAME . "@{$i}x", $this->width * $i, $this->height * $i, true );
					}
				}
			},
			PHP_INT_MAX
		);

		add_action(
			'wp',
			function () {
				$this->setup_defaults();
				$image_layers             = self::image_fallback_chain( true );
				$this->og_image_available = (bool) array_filter( $image_layers );

				$this->text_options['position'] = get_option( self::DEFAULTS_PREFIX . 'text_position', 'top-left' );
				$this->logo_options['position'] = get_option( self::DEFAULTS_PREFIX . 'logo_position', 'bottom-right' );

				[ $id, $type, $base_type ] = QueriedObject::instance();

				if ( $id && 'post' === $base_type ) {
					$allowed_meta           = array_keys( self::field_list()['meta'] );
					$overrule_text_position = get_post_meta( $id, self::OPTION_PREFIX . 'text_position', true );
					if ( $overrule_text_position && in_array( 'text_position', $allowed_meta, true ) ) {
						$this->text_options['position'] = $overrule_text_position;
					}

					$overrule_logo_enabled = get_post_meta( $id, self::OPTION_PREFIX . 'logo_enabled', true );
					if ( 'yes' === $overrule_logo_enabled || ( in_array( 'logo_enabled', $allowed_meta, true ) && ! $overrule_logo_enabled ) ) {
						$this->logo_options['enabled'] = $overrule_logo_enabled;
					}

					$overrule_logo_position = get_post_meta( $id, self::OPTION_PREFIX . 'logo_position', true );
					if ( $overrule_logo_position && in_array( 'logo_position', $allowed_meta, true ) ) {
						$this->logo_options['position'] = $overrule_logo_position;
					}

					$overrule_color = get_post_meta( $id, self::OPTION_PREFIX . 'color', true );
					if ( $overrule_color && in_array( 'color', $allowed_meta, true ) ) {
						$this->text_options['color'] = $overrule_color;
					}

					$overrule_color = get_post_meta( $id, self::OPTION_PREFIX . 'background_color', true );
					if ( $overrule_color && in_array( 'background_color', $allowed_meta, true ) ) {
						$this->text_options['background-color'] = $overrule_color;
					}

					$overrule_color = get_post_meta( $id, self::OPTION_PREFIX . 'text_stroke_color', true );
					if ( $overrule_color && in_array( 'text_stroke_color', $allowed_meta, true ) ) {
						$this->text_options['text-stroke-color'] = $overrule_color;
					}

					$overrule = get_post_meta( $id, self::OPTION_PREFIX . 'text_stroke', true );
					if ( '' !== $overrule && in_array( 'text_stroke', $allowed_meta, true ) ) {
						$this->text_options['text-stroke'] = (int) $overrule;
					}

					$overrule_color = get_post_meta( $id, self::OPTION_PREFIX . 'text_shadow_color', true );
					if ( $overrule_color && in_array( 'text_shadow_color', $allowed_meta, true ) ) {
						$this->text_options['text-shadow-color'] = $overrule_color;
					}

					$overrule_left = get_post_meta( $id, self::OPTION_PREFIX . 'text_shadow_left', true );
					if ( '' !== $overrule_left && in_array( 'text_shadow_left', $allowed_meta, true ) ) {
						$this->text_options['text-shadow-left'] = $overrule_left;
					}

					$overrule_top = get_post_meta( $id, self::OPTION_PREFIX . 'text_shadow_top', true );
					if ( '' !== $overrule_top && in_array( 'text_shadow_top', $allowed_meta, true ) ) {
						$this->text_options['text-shadow-top'] = $overrule_top;
					}

					$overrule_tsenabled = get_post_meta( $id, self::OPTION_PREFIX . 'text_shadow_enabled', true );
					if ( 'on' === $overrule_tsenabled && in_array( 'text_shadow_enabled', $allowed_meta, true ) ) {
						$this->text_options['text-shadow-color'] = '#555555DD';
						$this->text_options['text-shadow-top']   = 2;
						$this->text_options['text-shadow-left']  = - 2;
					}
				}

				$this->expand_text_options();
				$this->expand_logo_options();
			}
		);

		/**
		 * Url endpoint, the WordPress way:.
		 *
		 * Pros:
		 * 1. it works on non-standard hosts,
		 * 2. works on nginx hosts
		 *
		 * Cons:
		 * 1. does not work for custom post-type archives
		 * 2. because it is in essence a page-modifier, WP considers this a page, therefore
		 * - adds a trailing slash
		 * - confusing caching plugins into thinking the content-type should be text/html
		 * 3. the WP construction assumes an /endpoint/value/ set-up, requiring cleanup, see filter rewrite_rules_array implementation below
		 *
		 * Why this way?
		 *
		 * Because an .htaccess RewriteRule, although improving performance 20-fold, would be web-server-software specific, blog-set-up specific and multi-site aware
		 * which makes it quite impossible to do universally. Unfortunately.
		 *
		 * If you feel adventurous, you can always add it yourself! It should look something like this:
		 *
		 * RewriteRule (.+)/social-image.(jpg|png)/?$ $1/?bsi_img=1 [QSA,L,NC]
		 *
		 * If only for a certain domain, you can add a condition;
		 *
		 * RewriteCond %{HTTP_HOST} yourdomain.com
		 * RewriteRule (.+)/social-image.(jpg|png)/?$ $1/?bsi_img=1 [QSA,L,NC]
		 *
		 * For more information on apache rewrite rules, see
		 *
		 * @see https://httpd.apache.org/docs/2.4/mod/mod_rewrite.html
		 */

		// this filter is used when a re-save permalink occurs.
		add_filter(
			'rewrite_rules_array',
			function ( $rules ) {
				$new_rules = [];
				/**
				 * Make post-type archives work.
				 */
				$pt_archives = [];
				foreach ( $rules as $target ) {
					if ( preg_match( '/^index.php\?post_type=([^&%]+)$/', $target, $m ) ) {
						$pt_archives[ $m[1] . '/' . Plugin::output_filename() . '(/(.*))?/?$' ] = $target . '&' . Plugin::QUERY_VAR . '=$matches[2]';
					}
				}
				$rules = array_merge( $pt_archives, $rules );

				/**
				 * Make custom taxonomies work.
				 */
				$taxonomies          = get_taxonomies(
					[
						'public'   => true,
						'_builtin' => false,
					],
					'objects'
				);
				$taxonomy_rules      = [];
				$permalink_structure = get_option( 'permalink_structure', '' );
				$permalink_structure = explode( '/%', $permalink_structure );
				$prefix              = ltrim( trailingslashit( $permalink_structure[0] ), '/' );
				foreach ( $taxonomies as $tax_id => $tax ) {
					$taxonomy_rules[ ( $tax->rewrite['with_front'] ? $prefix : '' ) . $tax->rewrite['slug'] . '/(.+?)/' . self::output_filename() . '(/(.*))?/?$' ] = 'index.php?' . $tax_id . '=$matches[1]&' . Plugin::QUERY_VAR . '=$matches[3]';
				}
				$rules = array_merge( $taxonomy_rules, $rules );

				/**
				 * Changes the rewrite rules so the endpoint is value-less and more a tag, like 'feed' is for WordPress.
				 */
				foreach ( $rules as $source => $target ) {
					if (
						preg_match(
							'/' . strtr(
								self::output_filename(),
								[
									'.' => '\\.',
									'-' => '\\-',
								]
							) . '/',
							$source
						)
					) {
						$source = explode( self::output_filename(), $source );
						$source = $source[0] . self::output_filename() . '/?$';

						$target = explode( Plugin::QUERY_VAR . '=', $target );
						$target = $target[0] . Plugin::QUERY_VAR . '=1';
					}
					$new_rules[ $source ] = $target;
				}

				/**
				 * Move all urls regarding social-image to the top.
				 */
				$top     = [];
				$bottom  = [];
				$si_name = self::output_filename();
				foreach ( $new_rules as $source => $target ) {
					if ( false !== strpos( $source, $si_name ) ) {
						$top[ $source ] = $target;
					} else {
						$bottom[ $source ] = $target;
					}
				}

				return array_merge( $top, $bottom );
			}
		);

		// WordPress will not know what to do with the endpoint urls, and look for a template.
		// at this time, we detect the endpoint and push an image to the browser.
		add_action(
			'template_redirect',
			function () {
				global $wp_query;

				/**
				 * Fix for permalink manager.
				 *
				 * @see https://wordpress.org/support/topic/does-plugin-work-with-custom-post-types/
				 */
				$bsi_filename = basename( Plugin::output_filename() );
				if ( array_key_exists( $bsi_filename, $wp_query->query ) && array_key_exists( 'do_not_redirect', $wp_query->query ) && $wp_query->query['do_not_redirect'] ) {
					$wp_query->query[ Plugin::QUERY_VAR ] = 1;
					set_query_var( Plugin::QUERY_VAR, 1 );
				}

				/**
				 * Handle the call.
				 */
				if ( get_query_var( Plugin::QUERY_VAR ) ) {
					require_once __DIR__ . '/class.og-image.php';
					$og_image = new Image( $this );
					$og_image->serve();
					exit;
				}
			}
		);

		// Yes, a second hook on 'wp', but note; this runs absolute last using priority PHP_INT_MAX.
		add_action( 'wp', [ $this, '_init' ], PHP_INT_MAX );

		add_action( 'admin_bar_menu', [ static::class, 'admin_bar' ], 100 );

		add_action(
			'admin_init',
			function () {
				$this->setup_defaults();
			}
		);

		add_filter( 'bsi_post_types', [ static::class, 'post_types' ], ~PHP_INT_MAX, 0 );
		add_filter( 'bsi_taxonomies', [ static::class, 'taxonomies' ], ~PHP_INT_MAX, 0 );

		/**
		 * Patch the response to WordPress oembed request.
		 */
		add_filter(
			'oembed_response_data',
			function ( $data, $post ) {
				$id = $post->ID;

				if ( self::go_for_id( $id, $post->post_type, 'post' ) ) {
					$url = static::get_og_image_url( $id, $post->post_type, 'post' );

					$data['thumbnail_url']    = $url;
					$data['thumbnail_width']  = static::instance()->width;
					$data['thumbnail_height'] = static::instance()->height;
				}

				return $data;
			},
			PHP_INT_MAX,
			2
		);

		/**
		 * Patch SEO by RankMath JD+JSON data.
		 */
		add_filter(
			'rank_math/json_ld',
			function ( $data, $rankmath_schema_jsonld ) {
				$id = $rankmath_schema_jsonld->post_id;

				if ( $id && self::go_for_id( $id, get_post_type( $id ), 'post' ) ) {
					$url = static::get_og_image_url( $id, get_post_type( $id ), 'post' );

					$data['primaryImage']['url']    = $url;
					$data['primaryImage']['width']  = static::instance()->width;
					$data['primaryImage']['height'] = static::instance()->height;
				}

				return $data;

			},
			PHP_INT_MAX,
			2
		);
	}

	/**
	 * Test if we want a BSI for this post.
	 *
	 * @param int|string $object_id   The WordPress object ID, either a post_id, or a term_id.
	 * @param string     $object_type The object type to tell which; this is a very specific type, for example a custom post type.
	 * @param string     $base_type   The object type to tell which; this is the base type of the object either post or category.
	 *
	 * @return bool
	 */
	public static function go_for_id( $object_id, $object_type, $base_type ): bool {
		// @phpcs:ignore Generic.Commenting.Todo
		// @todo : future version will have to detect archives and categories as well
		// Default to NO GO.
		$go         = false;
		$image      = false;
		$killswitch = 'on';

		if ( 'post' === $base_type ) {
			$killswitch = get_post_meta( $object_id, self::OPTION_PREFIX . 'disabled', true ) ?: get_option( self::DEFAULTS_PREFIX . 'disabled', 'off' );
			$image      = get_post_meta( $object_id, self::OPTION_PREFIX . 'image', true );
		}
		if ( 'category' === $base_type ) {
			$killswitch = get_term_meta( $object_id, self::OPTION_PREFIX . 'disabled', true ) ?: get_option( self::DEFAULTS_PREFIX . 'disabled', 'off' );
			$image      = get_term_meta( $object_id, self::OPTION_PREFIX . 'image', true );
		}
		$go = ( self::image_fallback_chain() ) || $image;
		if ( 'on' === $killswitch ) {
			$go = false;
		}

		return $go;
	}

	/**
	 * Logging function used for debugging.
	 * When called with ?debug=BSI, an image url produces debug output.
	 * Because some hosts mess with output buffers and/or reverse proxies, this data is not always visible.
	 * so it is stored for viewing in the config panel as well.
	 *
	 * @return array
	 */
	public static function log(): array {
		static $log, $static;

		if ( ! $static ) {
			$static   = [];
			$static[] = '-- active plugins --';
			$plugins  = get_option( 'active_plugins', [] );
			foreach ( $plugins as $key => $value ) {
				$static[] = ( $key + 1 ) . ": $value";
			}
			$static[] = '-- php/wp functions --';
			foreach (
				[
					'mime_content_type',
					'finfo_open',
					'wp_check_filetype',
					'exec',
					'shell_exec',
					'passthru',
					'system',
				] as $function
			) {
				$static[] = "$function: " . ( constant( strtoupper( $function ) . '_EXISTED_BEFORE_PATCH' ) ? 'exists' : 'does not exist' );
			}
			$static[] = '-- php settings --';
			foreach ( [ 'memory_limit', 'max_execution_time' ] as $setting ) {
				$static[] = "$setting: " . ini_get( $setting );
			}
			$static[] = '-- end of log --';
		}
		if ( ! $log ) {
			$log   = [];
			$log[] = 'Log start: ' . gmdate( 'r' );
			$log[] = 'BSI version: ' . self::get_version();
			$log[] = 'BSI revision date: ' . gmdate( 'r', filemtime( BSI_PLUGIN_FILE ) );
			$log[] = 'Start memory usage: ' . ceil( memory_get_peak_usage() / ( 1024 * 1024 ) ) . 'M';
			$log[] = '-- image generation --';
			$log[] = 'BSI Debug log for  http' . ( empty( $_SERVER['HTTPS'] ) ? '' : 's' ) . '://' . $_SERVER['HTTP_HOST'] . remove_query_arg( 'debug' );
			$log   = array_merge( $log, self::array_key_prefix( QueriedObject::instance()->getTable() ) );
		}
		if ( func_num_args() > 0 ) {
			$item = func_get_arg( 0 );
			// Sanitize.
			$root_path = self::get_site_root_path();
			$item      = str_replace( trailingslashit( $root_path ), $root_path . '/', $item );
			$log[]     = $item;
		}

		return array_merge( $log, [ 'Peak memory usage: ' . ceil( memory_get_peak_usage() / ( 1024 * 1024 ) ) . 'M' ], $static );
	}

	/**
	 * Get the actual root path to the WordPress website, not to the location where WordPress files are stored.
	 *
	 * @return string
	 */
	public static function get_site_root_path() {
		// ABSPATH might be pointing to a subdirectory.
		static $root_path;
		if ( $root_path ) {
			return $root_path;
		}

		// example: http://somesite.org/ .
		$home_url = home_url();

		// example: http://somesite.org/wp/ .
		$site_url = site_url();

		// Don't bother about the schema;.
		$home_url = preg_replace( '/https?:/i', '', $home_url );
		$site_url = preg_replace( '/https?:/i', '', $site_url );

		// add trailing slash to compare apples with apples.
		$home_url = trailingslashit( $home_url );
		$site_url = trailingslashit( $site_url );

		// In our example, this is now the word   wp  .
		$subdir = trim( str_replace( $home_url, '', $site_url ), '/' );

		$root_path = ABSPATH; // This points to the path with  wp  at the end (again; in our example).
		if ( '' !== $subdir ) {
			$root_path = preg_replace( '/\/' . $subdir . '$/', '', $subdir );
		}

		return $root_path;
	}

	/**
	 * Display the log. Also store the log for later viewing.
	 */
	public static function display_log() {
		if ( current_user_can( self::get_management_permission() ) ) {
			header( 'Content-Type: text/plain' );
			$log = implode( "\n", self::log() );
			set_transient( self::OPTION_PREFIX . '_debug_log', $log, 7 * 86400 ); // keep log for 1 week.
			// @phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- we're outputting plain text. stop nagging.
			print $log;
			exit;
		}
	}

	/**
	 * Yes, we are mimicking get_plugin_data here because get_plugin_data is not always available while get_file_data is.
	 * we could have statically set the version number, but we could forget to update it.
	 *
	 * @return string the version number of the plugin.
	 */
	public static function get_version() {
		static $version;
		if ( ! $version ) {
			$data    = get_file_data( BSI_PLUGIN_FILE, [ 'Version' => 'Version' ], 'plugin' );
			$version = $data['Version'];
		}

		return $version;
	}

	/**
	 * Retrieves Media-library-item data with a certain size.
	 * A mashup of wp_get_attachment_image_src and wp_get_attachment_metadata.
	 *
	 * @param int|string $image_id The attachment ID.
	 * @param string     $size     The WordPress size indicator.
	 *
	 * @return array|false
	 */
	public static function wp_get_attachment_image_data( $image_id, string $size ) {
		$data = wp_get_attachment_image_src( $image_id, $size );
		$meta = wp_get_attachment_metadata( $image_id );
		if ( is_array( $data ) && is_array( $meta ) ) {
			$upl = wp_upload_dir();
			$upl = $upl['basedir'];
			// path to the base image.
			$base = trailingslashit( $upl ) . $meta['file'];
			$file = $base;
			self::log( sprintf( ' Base image for #%d: %s', $image_id, $base ) );
			if ( ! empty( $meta['sizes'][ $size ]['path'] ) ) {
				// path to the resized image, if it exists.
				$file = trailingslashit( $upl ) . $meta['sizes'][ $size ]['path'];
				self::log( sprintf( ' Using \'path\' entry: %s with size-name %s', $file, $size ) );
			} elseif ( ! empty( $meta['sizes'][ $size ]['file'] ) ) {
				// only a new filename is listed, use directory of base.
				$file = dirname( $base ) . '/' . $meta['sizes'][ $size ]['file'];
				self::log( sprintf( ' Using \'file\' entry: %s with size-name %s', $file, $size ) );
			}
			// check existence of file.
			if ( is_file( $file ) ) {
				$data[4] = $file;
			} else {
				self::log( ' Selected size-specific file does not exist. Please run a thumbnail rebuild.' );
				if ( is_file( $base ) ) {
					$data[4] = $base;
				} else {
					self::log( ' Base image also does not exist. THIS IS A PROBLEM!' );
				}
			}
		}

		return $data;
	}

	/**
	 * Get a list of purgable items.
	 *
	 * @param string $type One of 'all', 'directories', 'files', 'images', 'locks', 'tmp'.
	 *
	 * @return array|false
	 */
	public static function get_purgable_cache( string $type = 'all' ) {
		$filter = 'is_file';
		switch ( $type ) {
			case 'directories':
				$ext    = '';
				$filter = 'is_dir';
				break;
			case 'files':
				$ext = '/*';
				break;
			case 'images':
				$ext = '/*.{png,jpg,webp}';
				break;
			case 'locks':
				$ext = '/*.lock';
				break;
			case 'tmp':
				$ext = '/*.tmp';
				break;
			case 'all':
			default:
				$list = array_merge( self::get_purgable_cache( 'files' ), self::get_purgable_cache( 'directories' ), array_map( 'dirname', self::get_purgable_cache( 'directories' ) ) );
				// sort files first, then directory depth.
				uasort(
					$list,
					function ( $item1, $item2 ) {
						if ( is_file( $item1 ) ) {
							return - 1;
						}
						if ( is_dir( $item1 ) ) {
							return strnatcmp( count( explode( '/', $item1 ) ), count( explode( '/', $item2 ) ) );
						}

						return 0;
					}
				);
				// now the items are sorted, but the order in the array is wrong ?!?!.
				$sorted = [];
				$sorted = $list;

				return array_values( array_unique( $sorted ) );
		}
		$cache = glob( self::instance()->storage() . '/*/*' . $ext, GLOB_BRACE );

		return array_filter( $cache, $filter );
	}

	/**
	 * Scrape a title from a webpage.
	 *
	 * @param string $url The URL to fetch.
	 *
	 * @return mixed
	 */
	public static function scrape_title( $url ) {
		return self::scrape_title_data( $url )[1];
	}

	/**
	 * Scrape the status code from a webpage.
	 *
	 * @param string $url The URL to fetch.
	 *
	 * @return mixed
	 */
	public static function scrape_code( string $url ) {
		return self::scrape_title_data( $url )[0];
	}

	/**
	 * Scrape the title from a rendered page.
	 * This really is an eye-sore and we will replace it with a title-builder in the future.
	 *
	 * @param string $url The URL to fetch.
	 *
	 * @return array[2] Status code, HTML
	 */
	public static function scrape_title_data( string $url ): array {
		static $previous = [];
		if ( ! empty( $previous[ $url ] ) ) {
			return $previous[ $url ];
		}

		$title   = '';
		$page    = '';
		$code    = 0;
		$cookies = [];
		foreach ( $_COOKIE as $cookie => $value ) {
			$cookies[] = new WP_Http_Cookie(
				[
					'name'  => $cookie,
					'value' => $value,
				]
			);
		}
		try {
			$result = wp_remote_get(
				$url,
				[
					'httpversion' => '1.1',
					'user-agent'  => $_SERVER['HTTP_USER_AGENT'],
					'referer'     => remove_query_arg( 'asd' ),
					'cookies'     => $cookies,
				]
			);
			$code   = wp_remote_retrieve_response_code( $result );
			if ( 200 === (int) $code ) {
				$page = wp_remote_retrieve_body( $result );
				// limit size of string to work with.
				[ $page ] = explode( '</head>', $page );
				// remove line endings for better scraping.
				$page = str_replace( [ "\n", "\r" ], '', $page );
			}
		} catch ( Exception $e ) {
			$page = '';
		}

		if ( $page && ( false !== strpos( $page, 'og:title' ) ) && preg_match( '/og:title.+content=([\'"])(.+)\1([ \/>])/mU', $page, $m ) ) {
			$title = trim( $m[2], ' />' . $m[1] );
		}
		if ( $page && ! $title && ( false !== strpos( $page, '<title' ) ) && preg_match( '/<title[^>]*>(.+)<\/title>/mU', $page, $m ) ) {
			$title = trim( $m[1] );
		}

		$previous[ $url ] = [ $code, html_entity_decode( $title ) ];

		return $previous[ $url ];
	}

	/**
	 * Get the title format.
	 *
	 * @param int|null $post_id  The post_id, returns default format without it.
	 * @param bool     $no_title Do not use a title if true.
	 *
	 * @return string
	 */
	public static function title_format( $post_id = null, $no_title = false ) {
		// to return, in case of no post.
		$format = get_option( self::OPTION_PREFIX . 'title_format', '{title} - {blogname}' );
		if ( $post_id ) { // post asked, build the full title.
			$tokens = apply_filters(
				'bsi_title_tokens',
				[
					'{title}'    => $no_title ? '{title}' : get_the_title( $post_id ),
					'{blogname}' => get_bloginfo( 'name' ),
				]
			);

			return strtr( $format, $tokens );
		}

		return $format;
	}

	/**
	 * Get the output filename.
	 *
	 * @return string
	 */
	public static function output_filename() {
		$fallback_format = 'jpg';
		$output_format   = self::setting( 'output_format', $fallback_format );
		if ( is_array( $output_format ) ) {
			$fallback_format = $output_format[1];
			$output_format   = $output_format[0];
		}
		if ( ! in_array( $fallback_format, [ 'png', 'jpg' /* 'webp' */ ], true ) ) {
			$fallback_format = 'jpg';
		}
		if ( 'webp' === $output_format && ! function_exists( 'imagewebp' ) ) {
			$output_format = $fallback_format;
		}
		if ( ! in_array( $output_format, [ 'png', 'jpg' /* 'webp' */ ], true ) ) {
			$output_format = $fallback_format;
		}

		return self::BSI_IMAGE_NAME . '.' . $output_format;
	}

	/**
	 * Wrapper for unlink function for proper error handling or at least error-prevention.
	 *
	 * @param string $path Filepath to delete.
	 *
	 * @return bool
	 *
	 * @see unlink
	 */
	private static function unlink( $path ): bool {
		try {
			$result = unlink( $path );
		} catch ( Exception $e ) {
			$result = false;
		}

		return $result;
	}

	/**
	 * Wrapper for rmdir function for proper error handling or at least error-prevention.
	 * also clears .DS_Store items (macOS sucks sometimes) before attempting rmdir.
	 *
	 * @param string $path Folderpath to delete.
	 *
	 * @return bool
	 *
	 * @see rmdir
	 */
	private static function rmdir( $path ): bool {
		if ( is_file( "$path/.DS_Store" ) ) {
			self::unlink( "$path/.DS_Store" );
		}
		try {
			$result = rmdir( $path );
		} catch ( Exception $e ) {
			$result = false;
		}

		return $result;
	}

	/**
	 * Clear the BSI cache.
	 *
	 * @return bool
	 */
	public static function purge_cache(): bool {
		$purgable = self::get_purgable_cache();
		// protection!
		$base   = trailingslashit( self::instance()->storage() );
		$result = true;
		foreach ( $purgable as $item ) {
			if ( false === strpos( $item, $base ) ) {
				continue;
			}

			try {
				if ( is_file( $item ) && ! self::unlink( $item ) ) {
					$result = false;
				}
				if ( is_dir( $item ) && ! self::rmdir( $item ) ) {
					$result = false;
				}
			} catch ( Exception $e ) {
				$result = false;
			}
		}

		return $result;
	}

	/**
	 * Flush or Destroy all output buffers.
	 *
	 * @param false $destroy_buffer if false; do a nice flush. if true; previous output is destroyed.
	 */
	public static function no_output_buffers( $destroy_buffer = false ) {
		if ( ob_get_level() !== 0 ) {
			$list = ob_list_handlers();
			foreach ( $list as $item ) {
				// @phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize -- NO. JSON does not show difference between arrays and objects.
				self::log( 'Output buffer detected: ' . ( is_string( $item ) ? $item : serialize( $item ) ) );
			}
			while ( ob_get_level() ) {
				if ( $destroy_buffer ) {
					ob_end_clean();
				}
			}
			self::log( 'All buffers are ' . ( $destroy_buffer ? 'cleaned' : 'flushed' ) );
		}
	}

	/**
	 * Used for printing a log to show what array key and values are.
	 *
	 * @param array $array_combine The array to prepare for printing.
	 *
	 * @return array
	 */
	public static function array_key_prefix( $array_combine ) {
		foreach ( $array_combine as $key => &$value ) {
			if ( ! is_scalar( $value ) || is_bool( $value ) ) {
				$value = wp_json_encode( $value );
			}
			$value = "$key: $value";
		}

		return array_values( $array_combine );
	}

	/**
	 * Show the currently queried object.
	 *
	 * @return void
	 */
	public static function show_queried_object() {
		$queried_object = QueriedObject::instance()->getTable();
		// nice table view.
		?>
		<div class="updated">
			<table>
				<thead>
				<th align="left">BSI Variable</th>
				<th align="left">Value</th>
				</thead>
				<?php
				foreach ( $queried_object as $key => $value ) {
					if ( 'go?' === $key ) {
						$value = $value ? 'Social image enabled and available' : 'Social image not available/not in use';
					}
					echo '<tr><td>' . esc_html( $key ) . '</td><td>' . esc_html( $value ?: 'n/a' ) . '</td></tr>';
				}
				?>
			</table>
			<p><em>This information is shown because BSI_DEBUG is enabled</em></p></div>
		<?php
	}

	/**
	 * Checks if a URL can be rewritten. Returns the matching rewrite rule if found.
	 *
	 * @param string $url The URL to check.
	 *
	 * @return array|false
	 */
	public static function url_can_be_rewritten( $url ) {
		static $rewrite_rules, $base;
		if ( ! $rewrite_rules ) {
			$rewrite_rules = get_option( 'rewrite_rules', [] );
		}
		if ( ! $rewrite_rules ) {
			return false;
		}
		$url_path      = wp_parse_url( $url, PHP_URL_PATH );
		$is_front_page = false;
		if ( get_option( 'permalink_structure' ) && ( ! trim( $url_path, '/' ) || self::output_filename() === $url_path ) ) {
			$url_path      = '/' . $url_path;
			$is_front_page = true;
		}
		$i = 0;
		if ( get_option( 'permalink_structure' ) ) {
			foreach ( $rewrite_rules as $rewrite_rule => $rewrite_target ) {
				$have_match = preg_match( '@^/' . str_replace( '@', '\\@', $rewrite_rule ) . '@i', $url_path, $m );

				if ( $have_match ) {
					$target = $rewrite_target;
					foreach ( $m as $j => $t ) {
						$target = str_replace( '$matches[' . $j . ']', $t, $target );
					}
					foreach ( range( $j + 1, 20 ) as $j ) {
						$target = preg_replace( '/[^?&]+=\$matches\[' . $j . '\]/', '', $target );
					}

					$target = trim( trim( $target ), '&?' );

					return [
						'rule#'         => $i,
						'rule'          => $rewrite_rule,
						'target'        => $is_front_page ? 'index.php?' : $target,
						'has_query_var' => false !== strpos( $target, self::QUERY_VAR ),
					];
				}
				$i ++;
			}

			return false;
		}

		return [
			'rule#'         => 'n/a',
			'rule'          => 'n/a',
			'target'        => $url,
			'has_query_var' => false !== strpos( $url, self::QUERY_VAR ),
		];
	}

	/**
	 * BSI Initialisation.
	 *
	 * @return void
	 */
	public function _init() { // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore -- I'll be the judge of that.
		[ $id, $type, $base_type, $link, $ogimage, $go ] = QueriedObject::instance();
		if ( $go ) {
			if ( ! self::instance()->og_image_available ) {
				$go = false;
			}
			if ( $go ) {
				$image_main       = function () use ( $ogimage ) {
					Plugin::instance()->page_already_has_og_image = true;

					return $ogimage;
				};
				$image_additional = function () use ( $ogimage ) {
					return $ogimage;
				};

				// overrule RankMath.
				add_filter( 'rank_math/opengraph/facebook/image', $image_main, PHP_INT_MAX );
				add_filter( 'rank_math/opengraph/facebook/image_secure_url', $image_main, PHP_INT_MAX );
				add_filter( 'rank_math/opengraph/facebook/og_image', $image_main, PHP_INT_MAX );
				add_filter( 'rank_math/opengraph/facebook/og_image_secure_url', $image_main, PHP_INT_MAX );
				add_filter( 'rank_math/opengraph/twitter/twitter_image', $image_additional, PHP_INT_MAX );
				add_filter(
					'rank_math/opengraph/facebook/og_image_type',
					[
						static::class,
						'overrule_og_type',
					],
					PHP_INT_MAX
				);
				add_filter(
					'rank_math/opengraph/facebook/og_image_width',
					function () {
						return static::instance()->width;
					},
					PHP_INT_MAX
				);
				add_filter(
					'rank_math/opengraph/facebook/og_image_height',
					function () {
						return static::instance()->height;
					},
					PHP_INT_MAX
				);

				// overrule Yoast SEO.
				add_filter( 'wpseo_opengraph_image', $image_main, PHP_INT_MAX );
				add_filter( 'wpseo_twitter_image', $image_additional, PHP_INT_MAX );

				/**
				 * This is a very intrusive way, but Yoast does not allow overruling the image dimensions.
				 * investigate the option of always doing it this way. I wanted to prevent it, but since we cannot
				 * perhaps we should always just kill all og:image data from the head and add it ourselves.
				 */
				add_action( 'wpseo_head', [ static::class, 'patch_wpseo_head' ], ~PHP_INT_MAX );
				add_action( 'wpseo_head', [ static::class, 'patch_wpseo_head' ], PHP_INT_MAX );

				add_filter(
					'wpseo_schema_main_image',
					function ( $graph_piece ) use ( $ogimage ) {
						$graph_piece['url']        = $ogimage;
						$graph_piece['contentUrl'] = $ogimage;
						$graph_piece['width']      = static::instance()->width;
						$graph_piece['height']     = static::instance()->height;

						return $graph_piece;
					},
					11
				);

				// overrule WordPress JetPack recently acquired SocialImageGenerator, because, hey, we were here first!
				add_filter( 'sig_image_url', $image_additional, PHP_INT_MAX, 2 );

				// if an overrule did not take, we need to define our own.
				add_action( 'wp_head', [ static::class, 'late_head' ], PHP_INT_MAX );
			}
		}
	}

	/**
	 * Set all default values in memory.
	 *
	 * @return void
	 */
	public function setup_defaults() {
		$defaults                       = Admin::base_settings();
		$this->logo_options             = $defaults['logo_options'];
		$this->logo_options['position'] = get_option( self::DEFAULTS_PREFIX . 'logo_position', 'top-left' );

		// text options.
		$this->text_options = $defaults['text_options'];
		$font_file          = get_option( self::DEFAULTS_PREFIX . 'text__font', 'Roboto-Bold' );

		$this->text_options['font-file'] = $font_file;

		$this->text_options['position']           = get_option( self::DEFAULTS_PREFIX . 'text_position', 'bottom-left' );
		$this->text_options['color']              = get_option( self::DEFAULTS_PREFIX . 'color', '#FFFFFFFF' );
		$this->text_options['background-color']   = get_option( self::DEFAULTS_PREFIX . 'background_color', '#66666666' );
		$this->text_options['background-enabled'] = get_option( self::DEFAULTS_PREFIX . 'background_enabled', 'on' );
		$this->text_options['text-stroke']        = get_option( self::DEFAULTS_PREFIX . 'text_stroke' );
		$this->text_options['text-stroke-color']  = get_option( self::DEFAULTS_PREFIX . 'text_stroke_color' );

		$this->text_options['font-size']   = get_option( self::OPTION_PREFIX . 'text__font_size', self::DEF_FONT_SIZE );
		$this->text_options['line-height'] = get_option( self::OPTION_PREFIX . 'text__font_size', self::DEF_FONT_SIZE ) * 1.25;

		if ( 'on' === self::FEATURE_SHADOW ) {
			$this->text_options['text-shadow-color'] = get_option( self::DEFAULTS_PREFIX . 'text_shadow_color' );
			$this->text_options['text-shadow-left']  = get_option( self::DEFAULTS_PREFIX . 'text_shadow_left' );
			$this->text_options['text-shadow-top']   = get_option( self::DEFAULTS_PREFIX . 'text_shadow_top' );
		}
		if ( 'simple' === self::FEATURE_SHADOW ) {
			$enabled                                 = get_option( self::DEFAULTS_PREFIX . 'text_shadow_enabled', 'off' );
			$enabled                                 = 'off' === $enabled ? false : $enabled;
			$this->text_options['text-shadow-color'] = $enabled ? '#555555DD' : '#00000000';
			$this->text_options['text-shadow-left']  = - 2;
			$this->text_options['text-shadow-top']   = 2;
		}
		$this->validate_text_options();
		$this->validate_logo_options();
	}

	/**
	 * List all post-types that should be handled by BSI.
	 *
	 * @return array
	 */
	public static function post_types(): array {
		$list = get_post_types( [ 'public' => true ] );

		return array_values( $list );
	}

	/**
	 * List all taxonomies that should be handled by BSI.
	 *
	 * @return array
	 */
	public static function taxonomies(): array {
		$list = get_taxonomies( [ 'public' => true ] );

		return array_values( $list );
	}

	/**
	 * Validate all text-options.
	 *
	 * @return void
	 */
	public function validate_text_options() {
		$all_possible_options             = Admin::base_settings();
		$all_possible_options             = $all_possible_options['text_options'];
		$all_possible_options['position'] = 'left';

		$this->text_options = shortcode_atts( $all_possible_options, $this->text_options );

		// colors.
		$colors = [ 'background-color', 'color', 'text-shadow-color', 'text-stroke-color' ];
		foreach ( $colors as $_color ) {
			$color = strtolower( $this->text_options[ $_color ] );

			// single "digit" colors.
			if ( preg_match( '/#[0-9a-f]{3,4}$/', trim( $color ), $m ) ) {
				// make sure an alpha value is present.
				$color .= 'f';
				// Expand the color to components.
				$color =
					'#' . substr( $color, 1, 1 ) . substr( $color, 1, 1 ) . substr( $color, 2, 1 ) . substr( $color, 2, 1 )
					. substr( $color, 3, 1 ) . substr( $color, 3, 1 ) . substr( $color, 4, 1 ) . substr( $color, 4, 1 );
			}

			// not a valid hex code.
			if ( ! preg_match( '/#[0-9a-f]{6,8}$/', trim( $color ), $m ) || preg_match( '/#[0-9a-f]{7}$/', trim( $color ), $m ) ) {
				$color = '';
			}
			$this->text_options[ $_color ] = $color;
		}
		$this->text_options['text'] = get_option( self::DEFAULTS_PREFIX . 'text' );
	}

	/**
	 * Validate all logo options.
	 *
	 * @return void
	 */
	public function validate_logo_options() {
		$all_possible_options = Admin::base_settings();
		$all_possible_options = $all_possible_options['logo_options'];
		$this->logo_options   = shortcode_atts( $all_possible_options, $this->logo_options );
	}

	/**
	 * Expand text-options to use in generating the image.
	 *
	 * @param bool $fast Only do the essentials if true.
	 *
	 * @return void
	 */
	public function expand_text_options( $fast = false ) {
		if ( empty( $this->text_options['position'] ) ) {
			$this->text_options['position'] = 'left';
		}
		switch ( $this->text_options['position'] ) {
			case 'top-left':
			case 'top':
			case 'top-right':
				$this->text_options['top'] = self::PADDING;
				break;
			case 'bottom-left':
			case 'bottom':
			case 'bottom-right':
				$this->text_options['bottom'] = self::PADDING;
				break;
			case 'left':
			case 'center':
			case 'right':
				$this->text_options['top']    = self::PADDING;
				$this->text_options['bottom'] = self::PADDING;
				break;
		}
		switch ( $this->text_options['position'] ) {
			case 'top-left':
			case 'bottom-left':
			case 'left':
				$this->text_options['left'] = self::PADDING;
				break;
			case 'top-right':
			case 'bottom-right':
			case 'right':
				$this->text_options['right'] = self::PADDING;
				break;
			case 'top':
			case 'center':
			case 'bottom':
				$this->text_options['left']  = self::PADDING;
				$this->text_options['right'] = self::PADDING;
				break;
		}

		if ( ! $fast ) {
			$this->text_options['font-weight'] = $this->evaluate_font_weight( $this->text_options['font-weight'] );
			$this->text_options['font-style']  = $this->evaluate_font_style( $this->text_options['font-style'] );

			if ( ! $this->text_options['font-file'] ) {
				$this->text_options['font-file'] = $this->font_filename( $this->text_options['font-family'], $this->text_options['font-weight'], $this->text_options['font-style'] );
			}
			if ( '.' === dirname( $this->text_options['font-file'] ) ) { // just a name.
				$this->text_options['font-file'] = self::storage() . '/' . $this->text_options['font-file'];
				if ( ! is_file( $this->text_options['font-file'] ) && is_file( $this->text_options['font-file'] . '.ttf' ) ) {
					$this->text_options['font-file'] .= '.ttf';
				}
				if ( ! is_file( $this->text_options['font-file'] ) && is_file( $this->text_options['font-file'] . '.otf' ) ) {
					$this->text_options['font-file'] .= '.otf';
				}
				// revert to just a filename for backward compatibility.
				$this->text_options['font-file'] = basename( $this->text_options['font-file'] );
			}

			// we need a TTF.
			if (
				! is_file( $this->storage() . '/' . $this->text_options['font-file'] ) || (
					substr( $this->text_options['font-file'], - 4 ) !== '.ttf' && substr( $this->text_options['font-file'], - 4 ) !== '.otf' )
			) {
				$this->text_options['font-file'] = $this->download_font( $this->text_options['font-family'], $this->text_options['font-weight'], $this->text_options['font-style'] );
			}
			if ( is_file( $this->storage() . '/' . $this->text_options['font-file'] ) ) {
				$this->text_options['font-file'] = $this->storage() . '/' . $this->text_options['font-file'];
			}
		}

		// text positioning.
		list( $valign, $halign ) = $this->evaluate_positions( $this->text_options['top'], $this->text_options['right'], $this->text_options['bottom'], $this->text_options['left'] );
		// Store values.
		$this->text_options['valign'] = $valign;
		$this->text_options['halign'] = $halign;

		$shadow_type = 'open';
		foreach ( [ 'left', 'top' ] as $dir ) {
			if ( preg_match( '/\d+S/', $this->text_options[ 'text-shadow-' . $dir ] ) ) {
				$shadow_type = 'solid';
			}
			if ( preg_match( '/\d+G/', $this->text_options[ 'text-shadow-' . $dir ] ) ) {
				$shadow_type = 'gradient';
			}
		}
		$this->text_options['text-shadow-type'] = $shadow_type;
	}

	/**
	 * Evaluate the font weight, normalize to valid values 100 - 800 in steps of 100.
	 *
	 * @param int|string $weight  A font-weight.
	 * @param int        $default The default weight to use if invalid.
	 *
	 * @return float|int|mixed
	 */
	public function evaluate_font_weight( $weight, $default = 400 ) {
		$translate = Admin::font_weights();

		if ( 0 === (int) $weight ) {
			$weight = $translate[ strtolower( $weight ) ] ?? $default;
		}
		$weight = floor( $weight / 100 ) * 100;
		if ( 0.0 === $weight ) {
			$weight = $default;
		}
		if ( $weight > 800 ) {
			$weight = 800;
		}

		return $weight;
	}

	/**
	 * Evaluate font style and return a normalized value.
	 *
	 * @param string $style   The font-style.
	 * @param string $default The default to use if invalid.
	 *
	 * @return mixed|string
	 */
	public function evaluate_font_style( $style, $default = 'normal' ) {
		$allowed = [ 'normal', 'italic' ];
		if ( ! in_array( $style, $allowed, true ) ) {
			return $default;
		}

		return $style;
	}

	/**
	 * Get a font filename for a Google font.
	 *
	 * @param string     $font_family The font family.
	 * @param int|string $font_weight The font weight.
	 * @param string     $font_style  The font style.
	 *
	 * @return string
	 */
	public function font_filename( $font_family, $font_weight, $font_style ): string {
		if ( preg_match( '/google:(.+)/', $font_family, $m ) ) {
			$italic = 'italic' === $font_style ? 'italic' : '';

			return $m[1] /* fontname */ . '-w' . $font_weight . ( '' !== $italic ? '-' . $italic : '' ) . '.ttf';
		}

		// don't know what to do with any other.
		return '';
	}

	/**
	 * Get the storage folder.
	 *
	 * @return string
	 */
	public function storage(): string {
		$dir = wp_upload_dir();
		$dir = $dir['basedir'] . '/' . self::STORAGE;
		if ( ! is_dir( $dir ) ) {
			mkdir( $dir );
		}
		self::set_error( 'storage', null );
		if ( ! is_dir( $dir ) ) {
			self::set_error( 'storage', __( 'Could not create the storage directory in the uploads folder.', 'bsi' ) . ' ' . __( 'In a WordPress site the uploads folder should always be writable.', 'bsi' ) . ' ' . __( 'Please fix this.', 'bsi' ) . ' ' . __( 'This error will disappear once the problem has been corrected.', 'bsi' ) );
		}

		return $dir;
	}

	/**
	 * Set error for displaying.
	 *
	 * @param string $tag  A tag unique for this message.
	 * @param string $text The message.
	 *
	 * @return void
	 */
	public static function set_error( $tag, $text ) {
		$errors         = get_option( self::OPTION_PREFIX . 'image__errors', [] );
		$errors[ $tag ] = $text;
		$errors         = array_filter( $errors );
		update_option( self::OPTION_PREFIX . 'image__og_logo_errors', $errors );
	}

	/**
	 * Download a Google font.
	 *
	 * @param string     $font_family The font family name.
	 * @param string|int $font_weight The font weight, bold, normal, or 400, 700 etc.
	 * @param string     $font_style  The font style, either italic or empty.
	 *
	 * @return false|mixed|string
	 */
	public function download_font( $font_family, $font_weight, $font_style ) {
		self::set_error( 'font-family', null );
		$font_filename = $this->font_filename( $font_family, $font_weight, $font_style );
		if ( '' === $font_filename ) {
			self::set_error( 'font-family', __( 'Don\'t know where to get this font.', 'bsi' ) . ' ' . __( 'Sorry.', 'bsi' ) );

			return false;
		}
		if ( is_file( $this->storage() . '/' . $font_filename ) ) {
			return $font_filename;
		}
		if ( preg_match( '/google:(.+)/', $font_family, $m ) ) {
			$italic   = 'italic' === $font_style ? 'italic' : '';
			$font_css = wp_remote_retrieve_body( wp_remote_get( 'https://fonts.googleapis.com/css?family=' . rawurlencode( $m[1] ) . ':' . $font_weight . $italic, [ 'useragent' => ' ' ] ) );

			if ( ! $font_css ) {
				self::set_error( 'font-family', __( 'Could not download font from Google Fonts.', 'bsi' ) . ' ' . __( 'Please download yourself and upload here.', 'bsi' ) );

				return false;
			}
			// grab any url.
			self::set_error( 'font-family', null );
			if ( preg_match( '@https?://[^)]+[ot]tf@', $font_css, $n ) ) {
				$font_ttf = wp_remote_retrieve_body( wp_remote_get( $n[0] ) );
				$this->file_put_contents( $this->storage() . '/' . $font_filename, $font_ttf );

				return $font_filename;
			} else {
				self::set_error( 'font-family', __( 'This Google Fonts does not offer a TTF or OTF file.', 'bsi' ) . ' ' . __( 'Sorry, cannot continue at this time.', 'bsi' ) );

				return false;
			}
		}

		// don't know what to do with any other.
		return $font_family;
	}

	/**
	 * Wrapper for file_put_contents that creates the path to the file when needed, but only works if the target path is in the configured storage folder.
	 *
	 * @param string $filename Filepath.
	 * @param string $content  Content.
	 *
	 * @return false|int
	 *
	 * @see file_put_contents()
	 */
	public function file_put_contents( $filename, $content ) {
		// for security reasons, $filename must be in $this->storage() .
		if ( substr( trim( $filename ), 0, strlen( $this->storage() ) ) !== $this->storage() ) {
			return false;
		}
		$dirs = [];
		$dir  = $filename; // we will be dirname-ing this .

		// phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition -- Intentional for recurse purposes.
		while ( ( $dir = dirname( $dir ) ) && $dir && '.' !== $dir && $this->storage() !== $dir && ! is_dir( $dir ) ) {
			array_unshift( $dirs, $dir );
		}

		array_map( 'mkdir', $dirs );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents -- No. If your server needs the FTP methods for WordPress; time to move on to a better hosting platform.
		return file_put_contents( $filename, $content );
	}

	/**
	 * Evaluate percentage distance vertically.
	 *
	 * @param int|null|string $top    If set and ending in %, calculated against image height.
	 * @param int|null|string $bottom If set and ending in %, calculated against image height.
	 *
	 * @return void
	 */
	private function evaluate_vertical( &$top, &$bottom ) {
		if ( $top && '%' === substr( $top, - 1 ) ) {
			$top = (int) floor( (int) $top / 100 * $this->height );
		}

		if ( $bottom && '%' === substr( $bottom, - 1 ) ) {
			$bottom = (int) ceil( (int) $bottom / 100 * $this->height );
		}
	}

	/**
	 * Evaluate percentage distance horizontally.
	 *
	 * @param int|null|string $left  If set and ending in %, calculated against image width.
	 * @param int|null|string $right If set and ending in %, calculated against image width.
	 *
	 * @return void
	 */
	private function evaluate_horizontal( &$left, &$right ) {
		if ( $left && '%' === substr( $left, - 1 ) ) {
			$left = (int) floor( (int) $left / 100 * $this->width );
		}
		if ( $right && '%' === substr( $right, - 1 ) ) {
			$right = (int) ceil( (int) $right / 100 * $this->width );
		}
	}

	/**
	 * Evaluate logo options.
	 *
	 * @return void
	 */
	public function expand_logo_options() {
		if ( empty( $this->logo_options['enabled'] ) && false !== $this->logo_options['enabled'] ) {
			$this->logo_options['enabled'] = true;
		}

		switch ( $this->logo_options['position'] ) {
			case 'top-left':
			case 'top':
			case 'top-right':
				$this->logo_options['top'] = self::PADDING;
				break;
			case 'bottom-left':
			case 'bottom':
			case 'bottom-right':
				$this->logo_options['bottom'] = self::PADDING;
				break;
			case 'left':
			case 'center':
			case 'right':
				$this->logo_options['top']    = self::PADDING;
				$this->logo_options['bottom'] = self::PADDING;
				break;
		}
		switch ( $this->logo_options['position'] ) {
			case 'top-left':
			case 'bottom-left':
			case 'left':
				$this->logo_options['left'] = self::PADDING;
				break;
			case 'top-right':
			case 'bottom-right':
			case 'right':
				$this->logo_options['right'] = self::PADDING;
				break;
			case 'top':
			case 'center':
			case 'bottom':
				$this->logo_options['left']  = self::PADDING;
				$this->logo_options['right'] = self::PADDING;
				break;
		}

		$this->logo_options['file'] = get_option( self::OPTION_PREFIX . 'image_logo' );
		if ( is_numeric( $this->logo_options['file'] ) ) {
			$this->logo_options['file'] = get_attached_file( $this->logo_options['file'] );
		}
		list( $sw, $sh ) = is_file( $this->logo_options['file'] ) ? getimagesize( $this->logo_options['file'] ) : [
			0,
			0,
		];
		if ( $sw && $sh ) {
			$sa                                       = $sw / $sh;
			$this->logo_options['source_width']       = $sw;
			$this->logo_options['source_height']      = $sh;
			$this->logo_options['source_aspectratio'] = $sa;
		} else {
			// not an image.
			$this->logo_options['file']    = false;
			$this->logo_options['error']   = 'Not an image';
			$this->logo_options['enabled'] = false;

			return;
		}

		// logo positioning.
		list( $valign, $halign ) = $this->evaluate_positions( $this->logo_options['top'], $this->logo_options['right'], $this->logo_options['bottom'], $this->logo_options['left'] );

		$this->logo_options['valign'] = $valign;
		$this->logo_options['halign'] = $halign;

		// size w and h are bounding box!.
		$this->logo_options['size'] = (int) $this->logo_options['size'];
		$this->logo_options['size'] = min( self::MAX_LOGO_SCALE, $this->logo_options['size'] );
		$this->logo_options['size'] = max( self::MIN_LOGO_SCALE, $this->logo_options['size'] );
		$this->logo_options['w']    = $this->logo_options['size'] / 100 * $sw;
		$this->logo_options['h']    = $this->logo_options['size'] / 100 * $sh;
		// set size to a percentage.
		$this->logo_options['size'] .= '%';

		// resolve aspect issues.
		// -> this makes bounding box actual image size.
		$scale                   = min( $this->logo_options['w'] / $sw, $this->logo_options['h'] / $sh );
		$this->logo_options['w'] = $sw * $scale;
		$this->logo_options['h'] = $sh * $scale;
	}

	/**
	 * Evaluate locational position.
	 *
	 * @param null|int $top    The position offset-top.
	 * @param null|int $right  The position offset-right.
	 * @param null|int $bottom The position offset-bottom.
	 * @param null|int $left   The position offset-left.
	 *
	 * @return string[]
	 */
	public function evaluate_positions( &$top, &$right, &$bottom, &$left ) {
		$top    = empty( $top ) || 'null' === $top ? null : $top;
		$right  = empty( $right ) || 'null' === $right ? null : $right;
		$bottom = empty( $bottom ) || 'null' === $bottom ? null : $bottom;
		$left   = empty( $left ) || 'null' === $left ? null : $left;

		$this->evaluate_vertical( $top, $bottom );
		$this->evaluate_horizontal( $left, $right );

		if ( null !== $top && null !== $bottom ) {
			$valign = 'center';
		} elseif ( null !== $top ) {
			$valign = 'top';
		} else {
			$valign = 'bottom';
		}
		if ( null !== $left && null !== $right ) {
			$halign = 'center';
		} elseif ( null !== $left ) {
			$halign = 'left';
		} else {
			$halign = 'right';
		}

		return [ $valign, $halign ];
	}

	/**
	 * Runs on wp_head, as late as possible.
	 *
	 * @return void
	 */
	public static function late_head() {
		if ( ! self::instance()->og_image_available ) {
			return;
		}
		if ( ! self::instance()->page_already_has_og_image ) {
			?>
			<meta property="og:image" content="<?php print esc_attr( QueriedObject::instance()->og_image ); ?>">
			<?php
		}
	}

	/**
	 * Deprecated, do not use, here to prevent breaking sites.
	 *
	 * @return string
	 */
	public static function overrule_og_image(): string {
		_deprecated_function( __FUNCTION__, '2.0.0', '' );
		$og_url = QueriedObject::instance()->og_image;

		return apply_filters( 'bsi_image_url', $og_url );
	}

	/**
	 * Allows overruling the Rank Math image type, using filter rank_math/opengraph/facebook/og_image_type .
	 *
	 * @return string
	 */
	public static function overrule_og_type(): string {
		$ext = explode( '.', self::output_filename() );
		$ext = end( $ext );
		if ( 'jpg' === $ext ) {
			$ext = 'jpeg';
		}

		return 'image/' . $ext;
	}

	/**
	 * DO NOT USE THIS TO GET THE URL FOR THE CURRENT PAGE/POST/...
	 * Use QueriedObject::instance()->og_image for that.
	 *
	 * This function will not be updated to support more types, and it may even be incorrect.
	 *
	 * @param int    $object_id   Object id, like, post-id.
	 * @param string $object_type The object type, like 'post'.
	 * @param string $base_type   The base object type. Unlike object_type, this can only be post or category.
	 *
	 * @return false|string
	 */
	public static function get_og_image_url( $object_id, $object_type, $base_type ) {
		if ( 'post' === $base_type ) {
			if ( 'archive' === $object_id ) {
				return get_post_type_archive_link( $object_type ) ? get_post_type_archive_link( $object_type ) . self::output_filename() . '/' : false;
			} else {
				return get_permalink( $object_id ) ? get_permalink( $object_id ) . self::output_filename() . '/' : false;
			}
		}
		if ( 'category' === $base_type ) {
			if ( 'archive' === $object_id ) {
				return get_category_link( $object_type ) ? get_category_link( $object_type ) . self::output_filename() . '/' : false;
			} else {
				return get_term_link( $object_id, $object_type ) ? get_term_link( $object_id, $object_type ) . self::output_filename() . '/' : false;
			}
		}

		return false;
	}

	/**
	 * Handler to fix the output of YoastSEO.
	 */
	public static function patch_wpseo_head() {
		static $step;
		if ( ! $step ) {
			$step = 0;
		}
		$step ++;

		switch ( $step ) {
			case 1:
				ob_start();
				break;
			case 2:
				$wpseo_head = ob_get_clean();
				if ( preg_match( '@/' . self::output_filename() . '/@', $wpseo_head ) ) {
					$wpseo_head = preg_replace( '/og:image:width" content="(\d+)"/', 'og:image:width" content="' . self::instance()->width . '"', $wpseo_head );
					$wpseo_head = preg_replace( '/og:image:height" content="(\d+)"/', 'og:image:height" content="' . self::instance()->height . '"', $wpseo_head );
				}
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- No. Garbage In, Garbage Out.
				print $wpseo_head;
		}
	}

	/**
	 * Runs on Init.
	 *
	 * @return void
	 */
	public static function init() {
		$instance = self::instance();
		if ( is_admin() ) {
			$admin          = Admin::instance();
			$admin->storage = $instance->storage();
		}

		load_plugin_textdomain( 'bsi', false, basename( dirname( __DIR__ ) ) . '/languages' );
	}

	/**
	 * EXPERIMENTAL.
	 *
	 * @param string $source Source image path.
	 *
	 * @return mixed|string
	 *
	 * @uses exec to execute system command. this might not be supported.
	 *
	 * @see  file php.ini. disable_functions = "show_source,system,shell_exec,exec" <- remove exec
	 */
	public static function convert_webp_to_png( $source ) {
		$support = self::maybe_fake_support_webp(); // just in case.
		$target  = false;
		if ( $support ) {
			$bin     = dirname( __DIR__ ) . '/bin';
			$target  = "$source.temp.png";
			$command = "$bin/dwebp \"$source\" -o \"$target\"";
			ob_start();
			try {
				// @phpcs:ignore Squiz.PHP.CommentedOutCode.Found
				// print $command; .
				// @phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
				exec( $command );
			} catch ( Exception $e ) {
				$target = false;
			}
			ob_get_clean();
		}

		if ( ! $target || ! file_exists( $target ) ) {
			$target = $source;
		}

		return $target;
	}

	/**
	 * Try to fake WebP support in GD by using tools to convert the file.
	 *
	 * @return bool
	 *
	 * @see  file php.ini. disable_functions = "show_source,system, shell_exec,exec" <- remove exec
	 *
	 * @uses exec to execute system command. this might not be supported.
	 */
	public static function maybe_fake_support_webp(): bool {
		$support = false;

		$bin = dirname( __DIR__ ) . '/bin';
		// not downloaded yet.
		if ( function_exists( 'exec' ) && ! file_exists( "$bin/dwebp" ) ) {
			// can we fake support?.
			ob_start();
			try {
				// @phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec -- We know! We try anyway.
				exec( $bin . '/download.sh' );
			} catch ( Exception $e ) {
				$support = false;
			}
			ob_end_clean();
		}
		// downloaded and ran conversion tool successfully.
		if ( file_exists( "$bin/dwebp" ) && file_exists( $bin . '/can-execute-binaries-from-php.success' ) ) {
			$support = true;
		}

		return $support;
	}

	/**
	 * Fetches pixel corrections dor fonts.
	 *
	 * @param string $font    The font.
	 * @param string $section A section of the tweak to fetch.
	 *
	 * @return false|mixed
	 */
	public static function font_rendering_tweaks_for( $font, $section ) {
		$tweaks = self::font_rendering_tweaks();
		$b      = basename( $font );
		$base   = basename( $font, '.ttf' );
		if ( $b === $base ) {
			$base = basename( $font, '.otf' );
		}
		$font = $base;
		if ( ! empty( $tweaks[ $font ] ) && ! empty( $tweaks[ $font ][ $section ] ) ) {
			return $tweaks[ $font ][ $section ];
		}

		return false;
	}

	/**
	 * Get the list of supported Google fonts (as in: packaged with the plugin).
	 *
	 * @return array
	 */
	public static function default_google_fonts(): array {
		return array_map(
			function ( $font ) {
				// PATCH THE DATA.
				$font['font_family'] = $font['font_name'];
				unset( $font['admin'], $font['gd'] );

				return $font;
			},
			self::font_rendering_tweaks( false, false )
		);
	}

	/**
	 * Fonts packaged with the plugin with their pixel-perfect tweaking values.
	 *
	 * @param bool $write_json Write the data back to disk.
	 * @param bool $read_disk  Read from disk.
	 *
	 * @return array
	 */
	public static function font_rendering_tweaks( $write_json = false, $read_disk = true ): array {
		$tweaks = [
			// letter-spacing: px, line-height: factor .
			'Anton'             => [
				'font_name'   => 'Anton',
				'font_weight' => 400,
				'admin'       => [ 'letter-spacing' => '-0.32px' ],
				'gd'          => [ 'line-height' => 1 ],
			],
			'Courgette'         => [
				'font_name'   => 'Courgette',
				'font_weight' => 400,
				'admin'       => [ 'letter-spacing' => '-0.32px' ],
				'gd'          => [ 'line-height' => .86 ],
			],
			'JosefinSans-Bold'  => [
				'font_name'   => 'Josefin Sans',
				'font_weight' => 700,
				'admin'       => [ 'letter-spacing' => '-0.4px' ],
				'gd'          => [ 'line-height' => .96 ],
			],
			'Merriweather-Bold' => [
				'font_name'   => 'Merriweather',
				'font_weight' => 700,
				'admin'       => [ 'letter-spacing' => '0px' ],
				'gd'          => [ 'line-height' => .86 ],
			],
			'OpenSans-Bold'     => [
				'font_name'   => 'Open Sans',
				'font_weight' => 700,
				'admin'       => [ 'letter-spacing' => '0px' ],
				'gd'          => [
					'line-height'     => .95,
					'text-area-width' => .96,
				],
			],
			'Oswald-Bold'       => [
				'font_name'   => 'Oswald',
				'font_weight' => 700,
				'admin'       => [ 'letter-spacing' => '0px' ],
				'gd'          => [
					'line-height'     => .92,
					'text-area-width' => .96,
				],
			],
			'PTSans-Bold'       => [
				'font_name'   => 'PT Sans',
				'font_weight' => 700,
				'admin'       => [ 'letter-spacing' => '0px' ],
				'gd'          => [ 'line-height' => 1.03 ],
			],
			'Roboto-Bold'       => [
				'font_name'   => 'Roboto',
				'font_weight' => 700,
				'admin'       => [ 'letter-spacing' => '0px' ],
				'gd'          => [ 'line-height' => .97 ],
			],
			'WorkSans-Bold'     => [
				'font_name'   => 'Work Sans',
				'font_weight' => 700,
				'admin'       => [ 'letter-spacing' => '0px' ],
				'gd'          => [ 'line-height' => 1 ],
			],
			'AkayaKanadaka'     => [
				'font_name'   => 'Akaya Kanadaka',
				'font_weight' => 400,
				'admin'       => [ 'letter-spacing' => '0px' ],
				'gd'          => [ 'line-height' => .98 ],
			],
		];

		if ( $read_disk ) {

			$json_files = glob( self::instance()->storage() . '/*.json' );
			foreach ( $json_files as $file ) {
				$font = basename( $file, '.json' );
				if ( empty( $tweaks[ $font ] ) ) {
					$tweaks[ $font ] = [];
				}
				// @phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- WHAT!? If file_get_contents doesn't work, then PHP cannot read any file. Why is this a rule?.
				$tweaks[ $font ] = array_merge( $tweaks[ $font ], json_decode( file_get_contents( $file ), true ) );
			}
		}

		if ( $write_json ) {
			foreach ( $tweaks as $font => $data ) {
				self::instance()->file_put_contents( self::instance()->storage() . '/' . $font . '.json', wp_json_encode( $data, JSON_PRETTY_PRINT ) );
			}
			$documentation_literal = <<< EODOC
Files in here:
- *.otf, *.ttf:             These are the fonts :) You can place any TTF or OTF font here.
                            Just make sure you have the proper license for the font(s).
                            We only put Google Fonts here for you, which are licensed for web.
- *.json:                   for tweaking the rendering of the font in CSS and in image generation, see
                            below for more details.
- what-are-these-files.txt: well, that's this file

Info on the .json files;
Some fonts render different (some even VERY different) in GD2 than they do in HTML/CSS.
Sample content:

{
    "font_name": "Open Sans",
    "admin": {
        "letter-spacing": "0px"
    },
    "gd": {
        "line-height": 0.95,
        "text-area-width": .96
    }
}

Here are defined; the readable font-name. Useful when the font-name has a space.

The admin sub-tree can contain:
- letter-spacing:  a CSS compatible value to tweak the rendering of the font in the
                   admin interface.
Need more? Let us know. See the WordPress BSI Settings panel for details on contacting us.

The gd sub-tree can contain:
- line-height:     a FACTOR (absent or  a value of 1 means "no change") to tweak the
                   line-height as calculated by GD.
- text-area-width: a factor (again) to tweak the width of the text-area.
                   This is useful when fonts render slightly to narrow or to wide.
Need more? Let us know. See the WordPress BSI Settings panel for details on contacting us.
EODOC;
			// force Windows line endings, because I KNOW that most users that need this documentation are not Linux, Unix or macOS users.
			$documentation_literal = str_replace( "\n", "\r\n", str_replace( "\r", '', $documentation_literal ) );
			self::instance()->file_put_contents( self::instance()->storage() . '/what-are-these-files.txt', $documentation_literal );
		}

		return $tweaks;
	}

	/**
	 * Items in the position grid.
	 *
	 * @return array
	 */
	public static function position_grid(): array {
		return [
			'top-left'     => __( 'Top Left', 'bsi' ),
			'top'          => __( 'Top Center', 'bsi' ),
			'top-right'    => __( 'Top Right', 'bsi' ),
			'left'         => __( 'Left Middle', 'bsi' ),
			'center'       => __( 'Centered', 'bsi' ),
			'right'        => __( 'Right Middle', 'bsi' ),
			'bottom-left'  => __( 'Bottom Left', 'bsi' ),
			'bottom'       => __( 'Bottom Center', 'bsi' ),
			'bottom-right' => __( 'Bottom Right', 'bsi' ),
		];
	}

	/**
	 * Used to combat injection/request forgery.
	 *
	 * @param string|false $section Limit processing to this section.
	 *
	 * @return array
	 */
	public static function get_valid_post_keys( $section = false ): array {
		$list  = self::field_list();
		$valid = [];
		foreach ( $list as $_section => $sublist ) {
			if ( $section && $_section !== $section ) {
				continue;
			}
			foreach ( $sublist as $key => $item ) {
				if ( empty( $valid[ $item['namespace'] ] ) ) {
					$valid[ $item['namespace'] ] = [];
				}
				$valid[ $item['namespace'] ][] = $key;
			}
		}

		return $valid;
	}

	/**
	 * Field lists for the interface.
	 *
	 * @return array
	 */
	public static function field_list(): array {
		$qo = QueriedObject::instance();

		$image_comment = __( 'The following process is used to determine the OG:Image (in order of importance)', 'bsi' ) . ':
<ol><li>' . __( 'Branded Social Image on page/post', 'bsi' ) . '</li>';
		if ( defined( 'WPSEO_VERSION' ) ) {
			$image_comment .= '<li>' . __( 'Yoast Social image on page/post', 'bsi' ) . '</li>';
		}
		$image_comment .= '<li>' . __( 'Featured image on page/post (when checked in general settings)', 'bsi' ) . '</li>';
		$image_comment .= '<li>' . __( 'Fallback Branded Social image in general settings', 'bsi' ) . '</li></ol>';

		$options = [
			'admin' => [
				'disabled'            => [
					'namespace' => self::DEFAULTS_PREFIX,
					'type'      => 'checkbox',
					'label'     => __( 'Select to disable the plugin Branded Social Images by default.', 'bsi' ),
					'default'   => 'off',
				],
				'menu_location'       => [
					'namespace' => self::DEFAULTS_PREFIX,
					'type'      => 'select',
					'label'     => __( 'Where does Branded Social Images live in the menu?', 'bsi' ),
					'default'   => 'main',
					'options'   => [
						'main'    => __( 'At the main level', 'bsi' ),
						'options' => __( 'In the Settings sub-menu', 'bsi' ),
						'media'   => __( 'In the Media sub-menu', 'bsi' ),
					],
				],
				'meta_location'       => [
					'namespace' => self::DEFAULTS_PREFIX,
					'type'      => 'select',
					'label'     => __( 'Branded Social Images meta-box location', 'bsi' ),
					'default'   => 'advanced',
					'options'   => [
						'advanced' => __( 'Below the content editor', 'bsi' ),
						'side'     => __( 'In the sidebar', 'bsi' ),
					],
				],
				'image'               => [
					'namespace' => self::DEFAULTS_PREFIX,
					'type'      => 'image',
					'types'     => 'image/png,image/jpeg,image/webp',
					'class'     => '-no-remove',
					'label'     => __( 'Fallback OG:Image.', 'bsi' ),
					'comment'   => __( 'Used for any page/post that has no OG image selected.', 'bsi' ) . ' ' . __( 'You can use JPEG and PNG.', 'bsi' ) . ' ' . __( 'Recommended size: 1200x630 pixels.', 'bsi' ),
				],
				'image_use_thumbnail' => [
					'namespace' => self::OPTION_PREFIX,
					'type'      => 'checkbox',
					'label'     => __( 'Use the WordPress Featured image.', 'bsi' ),
					'default'   => 'on',
					'info-icon' => 'dashicons-info',
					'info'      => $image_comment,
				],
				'image_logo'          => [
					'namespace' => self::OPTION_PREFIX,
					'type'      => 'image',
					'types'     => 'image/gif,image/png',
					'label'     => __( 'Your logo', 'bsi' ),
					'comment'   => __( 'Image should be approximately 600 pixels wide/high.', 'bsi' ) . ' ' . __( 'Use a transparent PNG for best results.', 'bsi' ),
				],
				'logo_position'       => [
					'namespace' => self::DEFAULTS_PREFIX,
					'type'      => 'radios',
					'class'     => 'position-grid',
					'options'   => self::position_grid(),
					'label'     => __( 'Default logo position', 'bsi' ),
					'default'   => 'top-left',
				],
				'image_logo_size'     => [
					'namespace' => self::OPTION_PREFIX,
					'type'      => 'slider',
					'class'     => 'single-slider',
					'label'     => __( 'Logo-scale (%)', 'bsi' ),
					'comment'   => '',
					'default'   => '100',
					'min'       => self::MIN_LOGO_SCALE,
					'max'       => self::MAX_LOGO_SCALE,
					'step'      => 1,
				],
				'text'                => [
					'namespace' => self::DEFAULTS_PREFIX,
					'class'     => 'hidden editable-target',
					'type'      => 'textarea',
					'label'     => __( 'The text to overlay if no other text or title can be found.', 'bsi' ),
					'comment'   => __( 'This should be a generic text that is applicable to the entire website.', 'bsi' ),
					'default'   => self::instance()->dummy_data( 'text' ),
				],
				'text__font'          => [
					'namespace' => self::DEFAULTS_PREFIX,
					'type'      => 'select',
					'label'     => __( 'Select a font', 'bsi' ),
					'options'   => self::get_font_list(),
					'default'   => 'Roboto-Bold',
				],
				'text__ttf_upload'    => [
					'namespace' => self::DEFAULTS_PREFIX,
					'type'      => 'file',
					'types'     => 'font/ttf,font/otf',
					'label'     => __( 'Font upload', 'bsi' ),
					'upload'    => __( 'Upload .ttf/.otf file', 'bsi' ),
					'info-icon' => 'dashicons-info',
					'info'      => __( 'Custom font must be a .ttf or .otf file.', 'bsi' ) . ' ' . __( 'You\'re responsible for the proper permissions and usage rights of the font.', 'bsi' ),
				],
				'text_position'       => [
					'namespace' => self::DEFAULTS_PREFIX,
					'type'      => 'radios',
					'class'     => 'position-grid',
					'label'     => __( 'Text position', 'bsi' ),
					'options'   => self::position_grid(),
					'default'   => 'bottom-left',
				],
				'color'               => [
					'namespace'  => self::DEFAULTS_PREFIX,
					'type'       => 'color',
					'attributes' => 'rgba',
					'label'      => __( 'Default Text color', 'bsi' ),
					'default'    => '#FFFFFFFF',
				],
				'text__font_size'     => [
					'namespace' => self::OPTION_PREFIX,
					'type'      => 'slider',
					'class'     => 'single-slider',
					'label'     => __( 'Font-size (px)', 'bsi' ),
					'comment'   => '',
					'default'   => self::DEF_FONT_SIZE,
					'min'       => self::MIN_FONT_SIZE,
					'max'       => self::MAX_FONT_SIZE,
					'step'      => 1,
				],
				'background_color'    => [
					'namespace'  => self::DEFAULTS_PREFIX,
					'type'       => 'color',
					'attributes' => 'rgba',
					'label'      => __( 'Text background color', 'bsi' ),
					'default'    => '#66666666',
				],
				'background_enabled'  => [
					'namespace' => self::DEFAULTS_PREFIX,
					'type'      => 'checkbox',
					'label'     => __( 'Use text background', 'bsi' ),
					'value'     => 'on',
					'default'   => 'on',
				],
				'text_stroke_color'   => [
					'namespace'  => self::DEFAULTS_PREFIX,
					'type'       => 'text',
					'color',
					'attributes' => 'rgba',
					'label'      => __( 'Stroke color', 'bsi' ),
					'default'    => '#00000000',
				],
				'text_stroke'         => [
					'namespace' => self::DEFAULTS_PREFIX,
					'type'      => 'text',
					'label'     => __( 'Default stroke width', 'bsi' ),
					'default'   => 0,
				],
				'text_shadow_color'   => [
					'namespace' => self::DEFAULTS_PREFIX,
					'type'      => 'color',
					'label'     => __( 'Default Text shadow color', 'bsi' ),
					'#00000000',
				],
				'text_shadow_top'     => [
					'namespace' => self::DEFAULTS_PREFIX,
					'type'      => 'text',
					'label'     => __( 'Shadow offset - vertical.', 'bsi' ) . ' ' . __( 'Negative numbers to top, Positive numbers to bottom.', 'bsi' ),
					'default'   => '-2',
				],
				'text_shadow_left'    => [
					'namespace' => self::DEFAULTS_PREFIX,
					'type'      => 'text',
					'label'     => __( 'Shadow offset - horizontal.', 'bsi' ) . ' ' . __( 'Negative numbers to left, Positive numbers to right.', 'bsi' ),
					'default'   => '2',
				],
				'text_shadow_enabled' => [
					'namespace' => self::DEFAULTS_PREFIX,
					'type'      => 'checkbox',
					'label'     => __( 'Use a text shadow', 'bsi' ),
					'value'     => 'on',
					'default'   => 'off',
				],
			],
			'meta'  => [
				'disabled'            => [
					'namespace' => self::OPTION_PREFIX,
					'type'      => 'checkbox',
					'label'     => __( 'Select to disable the plugin Branded Social Images for this post.', 'bsi' ),
					'default'   => get_option( self::DEFAULTS_PREFIX . 'disabled', 'off' ),
					'comment'   => '<div class="disabled-notice">' . __( 'The plugin Branded Social Images is disabled for this post.', 'bsi' ) . '</div>',
				],
				'text_enabled'        => [
					'namespace' => self::OPTION_PREFIX,
					'type'      => 'checkbox',
					'label'     => __( 'Display text on this image.', 'bsi' ),
					'default'   => 'yes',
					'value'     => 'yes',
				],
				'image'               => [
					'namespace' => self::OPTION_PREFIX,
					'type'      => 'image',
					'types'     => 'image/png,image/jpeg,image/webp',
					'label'     => __( 'You can upload/select a specific Social Image here', 'bsi' ),
					'comment'   => __( 'You can use JPEG and PNG.', 'bsi' ) . ' ' . __( 'Recommended size: 1200x630 pixels.', 'bsi' ),
					'info-icon' => 'dashicons-info',
					'info'      => $image_comment,
				],
				'text'                => [
					'namespace' => self::OPTION_PREFIX,
					'type'      => 'textarea',
					'class'     => 'hidden editable-target',
					'label'     => __( 'Text on image', 'bsi' ),
				],
				'color'               => [
					'namespace'  => self::OPTION_PREFIX,
					'type'       => 'color',
					'attributes' => 'rgba',
					'label'      => __( 'Text color', 'bsi' ),
					'default'    => get_option( self::DEFAULTS_PREFIX . 'color', '#FFFFFFFF' ),
				],
				'text_position'       => [
					'namespace' => self::OPTION_PREFIX,
					'type'      => 'radios',
					'class'     => 'position-grid',
					'label'     => __( 'Text position', 'bsi' ),
					'options'   => self::position_grid(),
					'default'   => get_option( self::DEFAULTS_PREFIX . 'text_position', 'bottom-left' ),
				],
				'background_color'    => [
					'namespace'  => self::OPTION_PREFIX,
					'type'       => 'color',
					'attributes' => 'rgba',
					'label'      => __( 'Text background color', 'bsi' ),
					'default'    => get_option( self::DEFAULTS_PREFIX . 'background_color', '#66666666' ),
				],
				'text_stroke_color'   => [
					'namespace'  => self::OPTION_PREFIX,
					'type'       => 'color',
					'attributes' => 'rgba',
					'label'      => __( 'Stroke color', 'bsi' ),
					'default'    => get_option( self::DEFAULTS_PREFIX . 'text_stroke_color', '#00000000' ),
				],
				'text_stroke'         => [
					'namespace' => self::OPTION_PREFIX,
					'type'      => 'text',
					'label'     => __( 'Default stroke width', 'bsi' ),
					'default'   => get_option( self::DEFAULTS_PREFIX . 'text_stroke', '0' ),
				],
				'text_shadow_color'   => [
					'namespace' => self::OPTION_PREFIX,
					'type'      => 'color',
					'label'     => __( 'Text shadow color', 'bsi' ),
					get_option( self::DEFAULTS_PREFIX . 'text_shadow', '#00000000' ),
				],
				'text_shadow_top'     => [
					'namespace' => self::OPTION_PREFIX,
					'type'      => 'text',
					'label'     => __( 'Shadow offset - vertical.', 'bsi' ) . ' ' . __( 'Negative numbers to top, Positive numbers to bottom.', 'bsi' ),
					'default'   => get_option( self::DEFAULTS_PREFIX . 'shadow_top', '-2' ),
				],
				'text_shadow_left'    => [
					'namespace' => self::OPTION_PREFIX,
					'type'      => 'text',
					'label'     => __( 'Shadow offset - horizontal.', 'bsi' ) . ' ' . __( 'Negative numbers to left, Positive numbers to right.', 'bsi' ),
					'default'   => get_option( self::DEFAULTS_PREFIX . 'shadow_left', '2' ),
				],
				'text_shadow_enabled' => [
					'namespace' => self::OPTION_PREFIX,
					'type'      => 'checkbox',
					'label'     => __( 'Use a text shadow', 'bsi' ),
					'comment'   => __( 'Will improve readability of light text on light background.', 'bsi' ),
					'value'     => 'on',
					'default'   => get_option( self::DEFAULTS_PREFIX . 'shadow_enabled', 'off' ),
				],
				'logo_enabled'        => [
					'namespace' => self::OPTION_PREFIX,
					'type'      => 'checkbox',
					'label'     => __( 'Use a logo on this image?', 'bsi' ),
					'default'   => 'yes',
					'comment'   => __( 'Uncheck if you do not wish a logo on this image, or choose a position below.', 'bsi' ),
				],
				'logo_position'       => [
					'namespace' => self::OPTION_PREFIX,
					'type'      => 'radios',
					'label'     => __( 'Logo position', 'bsi' ),
					'class'     => 'position-grid',
					'options'   => self::position_grid(),
					'default'   => get_option( self::DEFAULTS_PREFIX . 'logo_position', 'top-left' ),
				],
				'image_logo'          => [
					'namespace' => self::DO_NOT_RENDER,
					'type'      => 'image',
					'types'     => 'image/gif,image/png',
					'label'     => __( 'Your logo', 'bsi' ),
					'comment'   => __( 'Image should be approximately 600 pixels wide/high.', 'bsi' ) . ' ' . __( 'Use a transparent PNG for best results.', 'bsi' ),
					'default'   => get_option( self::OPTION_PREFIX . 'image_logo' ),
				],
			],
		];

		if ( 'on' !== self::FEATURE_STROKE ) {
			unset( $options['admin']['text_stroke_color'] );
			unset( $options['admin']['text_stroke'] );
			unset( $options['meta']['text_stroke_color'] );
			unset( $options['meta']['text_stroke'] );
		}

		if ( 'on' !== self::FEATURE_META_LOGO_OPTIONS ) {
			unset( $options['meta']['logo_position'] );
			unset( $options['meta']['logo_enabled'] );
		}

		if ( 'on' !== self::FEATURE_META_TEXT_OPTIONS ) {
			unset( $options['meta']['color'] );
			unset( $options['meta']['text_position'] );
			unset( $options['meta']['background_color'] );
			unset( $options['meta']['text_shadow_enabled'] );
		}

		if ( 'on' !== self::FEATURE_SHADOW ) {
			unset( $options['admin']['text_shadow_color'] );
			unset( $options['admin']['text_shadow_top'] );
			unset( $options['admin']['text_shadow_left'] );
			unset( $options['meta']['text_shadow_color'] );
			unset( $options['meta']['text_shadow_top'] );
			unset( $options['meta']['text_shadow_left'] );
		}
		if ( 'simple' !== self::FEATURE_SHADOW ) {
			unset( $options['admin']['text_shadow_enabled'] );
		}

		foreach ( $options['admin'] as $field => $_ ) {
			$options['admin'][ $field ]['current_value'] = get_option( $_['namespace'] . $field, empty( $_['default'] ) ? null : $_['default'] );
		}

		if ( $qo->object_id ) {
			foreach ( $options['meta'] as $field => $_ ) {
				if ( $qo->isPost() ) {
					$options['meta'][ $field ]['current_value'] = get_post_meta( $qo->object_id, $_['namespace'] . $field, true ) ?: ( empty( $_['default'] ) ? null : $_['default'] );
				}
				if ( $qo->isCategory() ) {
					$options['meta'][ $field ]['current_value'] = get_term_meta( $qo->object_id, $_['namespace'] . $field, true ) ?: ( empty( $_['default'] ) ? null : $_['default'] );
				}
			}
		}

		/**
		 * Allow extra settings by add-ons
		 *
		 * @param array $settings Two-level array, yourGroup => [ yourSettings ]
		 *
		 * @since 1.0.18
		 *
		 * @see   Plugin::field_list for examples.
		 */
		foreach ( apply_filters( 'bsi_editor_fields', [] ) as $group => $fields ) {
			// no, you are not allowed to override existing groups.
			if ( 'admin' === $group || 'meta' === $group ) {
				continue;
			}
			$options[ $group ] = $fields;
		}

		return $options;
	}

	/**
	 * Grab the text for the image in a chain of options.
	 *
	 * @return array
	 */
	public static function text_fallback_chain(): array {
		static $chain; // prevent double work.
		if ( $chain ) {
			return $chain;
		}

		list( $object_id, $object_type, $base_type, $permalink ) = QueriedObject::instance();

		$layers = [];
		switch ( $base_type ) {
			case 'post':
				$function = 'get_the_title';
				break;
			case 'category':
				$function = 'get_cat_name';
				break;
			default:
				$function = '__return_false';
				break;
		}

		$title = '';
		if ( $object_id ) {
			$new_post = 'new' === $object_id;

			if ( self::setting( 'use_bare_post_title' ) && ! $new_post ) {
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
				$title = apply_filters( 'the_title', $function( $object_id ), $object_id );
				if ( $title ) {
					$layers['wordpress'] = $title;
				}
			}

			if ( ! $title && $permalink ) {
				$title = self::scrape_title( $permalink );
				if ( $title ) {
					$layers['scraped'] = $title;
				}
			}
			if ( ! $title ) { // no text from scraping, build it.
				$title = self::title_format( $object_id, $new_post );
				if ( '' !== $title ) {
					$layers['by-format'] = $title;
				}
			}

			$layers['default'] = get_option( self::DEFAULTS_PREFIX . 'text', esc_attr( self::instance()->dummy_data( 'text' ) ) );
		}

		// Set chain in the static.
		$chain = array_filter( $layers );

		// Return it. Next time, the static will be used.
		return $chain;
	}

	/**
	 * Grab the image for the social-image in a chain of options.
	 *
	 * @param bool $with_post Include the image selected with the post?.
	 *
	 * @return array
	 */
	public static function image_fallback_chain( $with_post = false ): array {
		list( $object_id, $object_type, $base_type ) = QueriedObject::instance()->getData();

		if ( ! $object_id || 'unsupported' === $base_type ) {
			return [];
		}

		// layers are stacked in order, bottom first.
		$layers = [];
		// layer 1: the configured default.
		$settings           = self::field_list()['admin'];
		$layers['settings'] = $settings['image']['current_value'];
		// layer 2, if enabled, the thumbnail/featured.
		if ( 'post' === $base_type && 'on' === $settings['image_use_thumbnail']['current_value'] ) {
			$layers['thumbnail'] = get_post_thumbnail_id( get_the_ID() );
		}
		// layer 3, if available, social plugins.

		// maybe Yoast SEO?.
		if ( 'post' === $base_type && defined( 'WPSEO_VERSION' ) ) {
			$layers['yoast'] = get_post_meta( get_the_ID(), '_yoast_wpseo_opengraph-image-id', true );
		}

		// maybe RankMath? Latest version of rank math uses thumbnail ?????.
		if ( 'post' === $base_type && class_exists( RankMath::class ) ) {
			$layers['rankmath'] = get_post_meta( get_the_ID(), 'rank_math_facebook_image_id', true );
		}

		if ( 'post' === $base_type && $with_post ) {
			$layers['meta'] = get_post_meta( get_the_ID(), self::OPTION_PREFIX . 'image', true );
		}

		foreach ( $layers as &$layer ) {
			if ( $layer ) {
				$image = wp_get_attachment_image_src( $layer, self::IMAGE_SIZE_NAME );
				$layer = is_array( $image ) && ! empty( $image[0] ) ? $image[0] : false;
			}
		}

		return $layers;
	}

	/**
	 * Get a list of fonts.
	 *
	 * @return array
	 */
	public static function get_font_list(): array {
		$fonts   = Admin::valid_fonts();
		$options = [];

		foreach ( $fonts as $font_base => $_ ) {
			if ( ! $_['valid'] ) {
				continue;
			}
			$font_name             = $_['display_name'];
			$options[ $font_base ] = $font_name;
		}

		return $options;
	}

	/**
	 * Handles the Admin Bar items.
	 *
	 * @param \WP_Admin_Bar $admin_bar The admin bar.
	 *
	 * @return void
	 */
	public static function admin_bar( $admin_bar ) {
		global $pagenow;
		$qo = QueriedObject::instance();
		if ( $qo->showInterface() ) {
			$og_image  = $qo->og_image;
			$permalink = $qo->permalink;

			$args = [
				'id'    => self::ADMIN_SLUG . '-inspector',
				'title' => self::icon() . __( 'Inspect Social Image', 'bsi' ),
				'href'  => self::EXTERNAL_INSPECTOR,
				'meta'  => [
					'target' => '_blank',
					'title'  => __( 'Shows how this post is shared using an external, unaffiliated service.', 'bsi' ),
				],
			];

			$args['href'] = sprintf( $args['href'], rawurlencode( $permalink ) );
			$admin_bar->add_node( $args );

			if (
				( ! defined( 'BSI_DEBUG' ) || ! BSI_DEBUG ) &&
				array_filter( self::image_fallback_chain( true ) )
			) {
				$args = [
					'id'     => self::ADMIN_SLUG . '-view',
					'parent' => self::ADMIN_SLUG . '-inspector',
					'title'  => __( 'View Social Image', 'bsi' ),
					'href'   => $og_image,
					'meta'   => [
						'target' => '_blank',
						'class'  => self::ADMIN_SLUG . '-view',
					],
				];
				$admin_bar->add_node( $args );
			}

			if ( defined( 'BSI_DEBUG' ) && BSI_DEBUG ) {
				$admin_bar->add_group(
					[
						'id'     => self::ADMIN_SLUG . '-debug',
						'parent' => self::ADMIN_SLUG . '-inspector',
						'meta'   => [ 'class' => 'ab-sub-secondary' ],
					]
				);
				$admin_bar->add_group(
					[
						'id'     => self::ADMIN_SLUG . '-debug2',
						'parent' => self::ADMIN_SLUG . '-inspector',
					]
				);

				if ( 'unsupported' !== $qo->base_type ) {
					$admin_bar->add_node(
						[
							'id'     => self::ADMIN_SLUG . '-object_id',
							'parent' => self::ADMIN_SLUG . '-debug',
							'title'  => "Object ID: $qo->object_id",
						]
					);
				}
				$admin_bar->add_node(
					[
						'id'     => self::ADMIN_SLUG . '-object_type',
						'parent' => self::ADMIN_SLUG . '-debug',
						'title'  => "Object Type: $qo->object_type",
					]
				);
				if ( 'unsupported' !== $qo->base_type ) {
					$admin_bar->add_node(
						[
							'id'     => self::ADMIN_SLUG . '-base_type',
							'parent' => self::ADMIN_SLUG . '-debug',
							'title'  => "Base Object Type: $qo->base_type",
						]
					);
					$admin_bar->add_node(
						[
							'id'     => self::ADMIN_SLUG . '-permalink',
							'parent' => self::ADMIN_SLUG . '-debug2',
							'title'  => 'Link to Object (new tab)',
							'href'   => $qo->permalink,
							'meta'   => [
								'target' => '_blank',
							],
						]
					);
					$admin_bar->add_node(
						[
							'id'     => self::ADMIN_SLUG . '-og_image',
							'parent' => self::ADMIN_SLUG . '-debug2',
							'title'  => 'Link to Image (new tab)',
							'href'   => $qo->og_image,
							'meta'   => [
								'target' => '_blank',
							],
						]
					);
					$admin_bar->add_node(
						[
							'id'     => self::ADMIN_SLUG . '-go',
							'parent' => self::ADMIN_SLUG . '-debug',
							'title'  => 'Ready to go?: ' . ( $qo->go ? 'yes' : 'no' ),
						]
					);
				}
			}

			add_action( 'wp_footer', [ static::class, 'admin_bar_icon_style' ], PHP_INT_MAX );
			add_action( 'admin_footer', [ static::class, 'admin_bar_icon_style' ], PHP_INT_MAX );
		}
	}

	/**
	 * Style for the admin bar icon.
	 *
	 * @return void
	 */
	public static function admin_bar_icon_style() {

		?>
		<style>#wp-admin-bar-<?php print self::ADMIN_SLUG; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Yeah... No... ?>-inspector svg {
				position: relative;
				top: 3px;
			}</style>
		<?php
	}

	/**
	 * The icon.
	 *
	 * @return string
	 */
	private static function icon() {
		if ( preg_match( '/\.svg$/', self::ICON ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Not a URL, shut up.
			$icon = file_get_contents( dirname( __DIR__ ) . '/img/' . basename( '/' . self::ICON ) );

			return str_replace( '<path', '<path fill="currentColor"', $icon );
		} else {
			return '<img src="' . esc_attr( plugins_url( '/img/' . basename( '/' . self::ICON ), __DIR__ ) ) . '" />';
		}
	}

	/**
	 * Path to the plugin main file.
	 *
	 * @return string
	 */
	public static function get_plugin_file() {
		return str_replace( trailingslashit( WP_PLUGIN_DIR ), '', BSI_PLUGIN_FILE );
	}

	/**
	 * Management permission of BSI.
	 *
	 * @return string
	 */
	public static function get_management_permission() {
		// phpcs:ignore Generic.Commenting.Todo.TaskFound
		// @todo: improve this. This is crap.
		$permission = apply_filters( 'bsi_management_permission', 'edit_posts' );
		if ( ! $permission || 'read' === trim( $permission ) ) {
			$permission = 'edit_posts';
		}

		return $permission;
	}

	/**
	 * @function Will eventually handle all settings, but for now, this allows you to overrule...
	 *
	 * @param $setting                             string can be one of ...
	 *                                             $setting = use_bare_post_title, filter = bsi_settings_use_bare_post_title, expects true or false
	 *                                             with true, the WordPress title is used as default
	 *                                             with false, the default title is scraped from the HTML and will therefore be
	 *                                             influenced by plugins like Yoast SEO. This is standard behavior.
	 *                                             $setting = png_compression_level, filter = bsi_settings_png_compression_level, expects number 0 - 9,
	 *                                             0 = no compression, 9 = highest compression, default = 2
	 *                                             WARNING                 If you change the format, you must flush all page-caches, flush the BSI Image
	 *                                             cache and re-save permalinks!  THIS IS YOUR OWN RESPONSIBILITY.
	 *
	 * @return mixed|void
	 */
	public static function setting( $setting, $default = null ) {
		return apply_filters( 'bsi_settings_' . $setting, $default );
	}

	public static function protect_dir( $dir ) {
		if ( ! file_exists( $dir . '/.htaccess' ) ) {
			// invalid HTACCESS code to prevent downloads the hard way
			file_put_contents( $dir . '/.htaccess', 'You cannot be here' );
		}
	}

	public static function text_is_identical( $value1, $value2 ): bool {
		$value1 = trim( str_replace( [ "\n", "\r" ], '', $value1 ) );
		$value2 = trim( str_replace( [ "\n", "\r" ], '', $value2 ) );

		return strip_tags( $value1 ) === strip_tags( $value2 );
	}

	public function hex_to_rgba( $hex_color, $alpha_is_gd = false ): array {
		if ( substr( $hex_color, 0, 1 ) !== '#' ) {
			$hex_color = '#ffffffff';
		}
		$hex_values = str_split( substr( $hex_color, 1 ), 2 );
		$int_values = array_map( 'hexdec', $hex_values );
		// the last value is 255 for opaque and 0 for transparent, but GD uses 0 - 127 for the same
		if ( $alpha_is_gd ) {
			$int_values[3] = 255 - $int_values[3];
			$int_values[3] = $int_values[3] / 255 * 127;
			$int_values[3] = (int) floor( $int_values[3] );
		}

		return $int_values;
	}

	public function rgba_to_hex( $rgba_color, $alpha_is_gd = false ): string {
		if ( $alpha_is_gd ) {
			$rgba_color[3] = (int) $rgba_color[3];
			$rgba_color[3] = $rgba_color[3] / 127 * 255;
			$rgba_color[3] = 255 - floor( $rgba_color[3] );
			$rgba_color[3] = max( 0, $rgba_color[3] ); // minimum value = 0
			$rgba_color[3] = min( 255, $rgba_color[3] ); // maximum value = 255
		}
		$hex_values = array_map(
			function ( $in ) {
				return sprintf( '%02s', dechex( $in ) );
			},
			$rgba_color
		);

		return '#' . strtoupper( substr( implode( '', $hex_values ), 0, 8 ) );
	}

	/**
	 * @param $what
	 *
	 * @return string|void
	 */
	public function dummy_data( $what ) {
		if ( $what === 'text' ) {
			return __( 'Type here to change the text on the image', 'bsi' ) . "\n" .
			       __( 'Change logo and image below', 'bsi' );
		}
	}


	/**
	 * On plugin activation, register a flag to rewrite permalinks.
	 * The plugin will do so after adding the post-endpoint.
	 */
	public static function on_activation( $network_wide ) {
		global $wpdb;
		update_option( 'bsi_needs_rewrite_rules', true );
		if ( $network_wide && function_exists( 'is_multisite' ) && is_multisite() ) {
			$blog_ids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->base_prefix}blogs" );
			foreach ( $blog_ids as $blog_id ) {
				switch_to_blog( $blog_id );
				update_option( 'bsi_needs_rewrite_rules', true );
				restore_current_blog();
			}
		}
	}

	public static function on_deactivation( $network_wide ) {
		global $wpdb;
		self::purge_cache();
		if ( $network_wide && function_exists( 'is_multisite' ) && is_multisite() ) {
			$blog_ids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->base_prefix}blogs" );
			foreach ( $blog_ids as $blog_id ) {
				switch_to_blog( $blog_id );
				self::purge_cache();
				restore_current_blog();
			}
		}
	}

	public static function on_uninstall( $network_wide ) {
		// cannot uninstall without deactivation, so we may expect WordPress to call the dectivation hook.
		// no need to do it ourselves.
	}
}
