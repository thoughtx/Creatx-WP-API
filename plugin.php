<?php
/**
 * Plugin Name:Creatx  WP REST API
 * Description: Extension of JSON-based REST API for WordPrress, that adds a few apis for creatx.io.
 * Author: WP REST API Team, thought(x)
 * Author URI: http://creatx.io
 * Version: 2.0-beta13.1
 * Plugin URI: https://github.com/thoughtx/Creatx-WP-API
 * License: GPL2+
 */

/**
 * WP_REST_Controller class.
 */
if ( ! class_exists( 'WP_REST_Controller' ) ) {
	require_once dirname( __FILE__ ) . '/lib/endpoints/class-wp-rest-controller.php';
}

/**
 * WP_REST_Posts_Controller class.
 */
if ( ! class_exists( 'WP_REST_Posts_Controller' ) ) {
	require_once dirname( __FILE__ ) . '/lib/endpoints/class-wp-rest-posts-controller.php';
}

/**
 * WP_REST_Attachments_Controller class.
 */
if ( ! class_exists( 'WP_REST_Attachments_Controller' ) ) {
	require_once dirname( __FILE__ ) . '/lib/endpoints/class-wp-rest-attachments-controller.php';
}

/**
 * WP_REST_Post_Types_Controller class.
 */
if ( ! class_exists( 'WP_REST_Post_Types_Controller' ) ) {
	require_once dirname( __FILE__ ) . '/lib/endpoints/class-wp-rest-post-types-controller.php';
}

/**
 * WP_REST_Post_Statuses_Controller class.
 */
if ( ! class_exists( 'WP_REST_Post_Statuses_Controller' ) ) {
	require_once dirname( __FILE__ ) . '/lib/endpoints/class-wp-rest-post-statuses-controller.php';
}

/**
 * WP_REST_Revisions_Controller class.
 */
if ( ! class_exists( 'WP_REST_Revisions_Controller' ) ) {
	require_once dirname( __FILE__ ) . '/lib/endpoints/class-wp-rest-revisions-controller.php';
}

/**
 * WP_REST_Taxonomies_Controller class.
 */
if ( ! class_exists( 'WP_REST_Taxonomies_Controller' ) ) {
	require_once dirname( __FILE__ ) . '/lib/endpoints/class-wp-rest-taxonomies-controller.php';
}

/**
 * WP_REST_Terms_Controller class.
 */
if ( ! class_exists( 'WP_REST_Terms_Controller' ) ) {
	require_once dirname( __FILE__ ) . '/lib/endpoints/class-wp-rest-terms-controller.php';
}

/**
 * WP_REST_Users_Controller class.
 */
if ( ! class_exists( 'WP_REST_Users_Controller' ) ) {
	require_once dirname( __FILE__ ) . '/lib/endpoints/class-wp-rest-users-controller.php';
}

/**
 * WP_REST_Comments_Controller class.
 */
if ( ! class_exists( 'WP_REST_Comments_Controller' ) ) {
	require_once dirname( __FILE__ ) . '/lib/endpoints/class-wp-rest-comments-controller.php';
}

/**
 * REST extras.
 */
include_once( dirname( __FILE__ ) . '/extras.php' );
require_once( dirname( __FILE__ ) . '/core-integration.php' );

add_filter( 'init', '_add_extra_api_post_type_arguments', 11 );
add_action( 'init', '_add_extra_api_taxonomy_arguments', 11 );
add_action( 'rest_api_init', 'create_initial_rest_routes', 0 );

/**
 * Adds extra post type registration arguments.
 *
 * These attributes will eventually be committed to core.
 *
 * @since 4.4.0
 *
 * @global array $wp_post_types Registered post types.
 */
function _add_extra_api_post_type_arguments() {
	global $wp_post_types;

	if ( isset( $wp_post_types['post'] ) ) {
		$wp_post_types['post']->show_in_rest = true;
		$wp_post_types['post']->rest_base = 'posts';
		$wp_post_types['post']->rest_controller_class = 'WP_REST_Posts_Controller';
	}

	if ( isset( $wp_post_types['page'] ) ) {
		$wp_post_types['page']->show_in_rest = true;
		$wp_post_types['page']->rest_base = 'pages';
		$wp_post_types['page']->rest_controller_class = 'WP_REST_Posts_Controller';
	}

	if ( isset( $wp_post_types['attachment'] ) ) {
		$wp_post_types['attachment']->show_in_rest = true;
		$wp_post_types['attachment']->rest_base = 'media';
		$wp_post_types['attachment']->rest_controller_class = 'WP_REST_Attachments_Controller';
	}
}

/**
 * Adds extra taxonomy registration arguments.
 *
 * These attributes will eventually be committed to core.
 *
 * @since 4.4.0
 *
 * @global array $wp_taxonomies Registered taxonomies.
 */
function _add_extra_api_taxonomy_arguments() {
	global $wp_taxonomies;

	if ( isset( $wp_taxonomies['category'] ) ) {
		$wp_taxonomies['category']->show_in_rest = true;
		$wp_taxonomies['category']->rest_base = 'categories';
		$wp_taxonomies['category']->rest_controller_class = 'WP_REST_Terms_Controller';
	}

	if ( isset( $wp_taxonomies['post_tag'] ) ) {
		$wp_taxonomies['post_tag']->show_in_rest = true;
		$wp_taxonomies['post_tag']->rest_base = 'tags';
		$wp_taxonomies['post_tag']->rest_controller_class = 'WP_REST_Terms_Controller';
	}
}

if ( ! function_exists( 'create_initial_rest_routes' ) ) {
	/**
	 * Registers default REST API routes.
	 *
	 * @since 4.4.0
	 */
	function create_initial_rest_routes() {

		foreach ( get_post_types( array( 'show_in_rest' => true ), 'objects' ) as $post_type ) {
			$class = ! empty( $post_type->rest_controller_class ) ? $post_type->rest_controller_class : 'WP_REST_Posts_Controller';

			if ( ! class_exists( $class ) ) {
				continue;
			}
			$controller = new $class( $post_type->name );
			if ( ! is_subclass_of( $controller, 'WP_REST_Controller' ) ) {
				continue;
			}

			$controller->register_routes();

			if ( post_type_supports( $post_type->name, 'revisions' ) ) {
				$revisions_controller = new WP_REST_Revisions_Controller( $post_type->name );
				$revisions_controller->register_routes();
			}
		}

		// Post types.
		$controller = new WP_REST_Post_Types_Controller;
		$controller->register_routes();

		// Post statuses.
		$controller = new WP_REST_Post_Statuses_Controller;
		$controller->register_routes();

		// Taxonomies.
		$controller = new WP_REST_Taxonomies_Controller;
		$controller->register_routes();

		// Terms.
		foreach ( get_taxonomies( array( 'show_in_rest' => true ), 'object' ) as $taxonomy ) {
			$class = ! empty( $taxonomy->rest_controller_class ) ? $taxonomy->rest_controller_class : 'WP_REST_Terms_Controller';

			if ( ! class_exists( $class ) ) {
				continue;
			}
			$controller = new $class( $taxonomy->name );
			if ( ! is_subclass_of( $controller, 'WP_REST_Controller' ) ) {
				continue;
			}

			$controller->register_routes();
		}

		// Users.
		$controller = new WP_REST_Users_Controller;
		$controller->register_routes();

		// Comments.
		$controller = new WP_REST_Comments_Controller;
		$controller->register_routes();
	}
}

if ( ! function_exists( 'rest_authorization_required_code' ) ) {
	/**
	 * Returns a contextual HTTP error code for authorization failure.
	 *
	 * @return integer
	 */
	function rest_authorization_required_code() {
		return is_user_logged_in() ? 403 : 401;
	}
}

if ( ! function_exists( 'register_rest_field' ) ) {
	/**
	 * Registers a new field on an existing WordPress object type.
	 *
	 * @global array $wp_rest_additional_fields Holds registered fields, organized
	 *                                          by object type.
	 *
	 * @param string|array $object_type Object(s) the field is being registered
	 *                                  to, "post"|"term"|"comment" etc.
	 * @param string $attribute         The attribute name.
	 * @param array  $args {
	 *     Optional. An array of arguments used to handle the registered field.
	 *
	 *     @type string|array|null $get_callback    Optional. The callback function used to retrieve the field
	 *                                              value. Default is 'null', the field will not be returned in
	 *                                              the response.
	 *     @type string|array|null $update_callback Optional. The callback function used to set and update the
	 *                                              field value. Default is 'null', the value cannot be set or
	 *                                              updated.
	 *     @type string|array|null $schema          Optional. The callback function used to create the schema for
	 *                                              this field. Default is 'null', no schema entry will be returned.
	 * }
	 */
	function register_rest_field( $object_type, $attribute, $args = array() ) {
		$defaults = array(
			'get_callback'    => null,
			'update_callback' => null,
			'schema'          => null,
		);

		$args = wp_parse_args( $args, $defaults );

		global $wp_rest_additional_fields;

		$object_types = (array) $object_type;

		foreach ( $object_types as $object_type ) {
			$wp_rest_additional_fields[ $object_type ][ $attribute ] = $args;
		}
	}
}

if ( ! function_exists( 'register_api_field' ) ) {
	/**
	 * Backwards compat shim
	 */
	function register_api_field( $object_type, $attributes, $args = array() ) {
		_deprecated_function( 'register_api_field', 'WPAPI-2.0', 'register_rest_field' );
		register_rest_field( $object_type, $attributes, $args );
	}
}

if ( ! function_exists( 'rest_validate_request_arg' ) ) {
	/**
	 * Validate a request argument based on details registered to the route.
	 *
	 * @param  mixed            $value
	 * @param  WP_REST_Request  $request
	 * @param  string           $param
	 * @return WP_Error|boolean
	 */
	function rest_validate_request_arg( $value, $request, $param ) {

		$attributes = $request->get_attributes();
		if ( ! isset( $attributes['args'][ $param ] ) || ! is_array( $attributes['args'][ $param ] ) ) {
			return true;
		}
		$args = $attributes['args'][ $param ];

		if ( ! empty( $args['enum'] ) ) {
			if ( ! in_array( $value, $args['enum'] ) ) {
				return new WP_Error( 'rest_invalid_param', sprintf( __( '%s is not one of %s' ), $param, implode( ', ', $args['enum'] ) ) );
			}
		}

		if ( 'integer' === $args['type'] && ! is_numeric( $value ) ) {
			return new WP_Error( 'rest_invalid_param', sprintf( __( '%s is not of type %s' ), $param, 'integer' ) );
		}

		if ( 'string' === $args['type'] && ! is_string( $value ) ) {
			return new WP_Error( 'rest_invalid_param', sprintf( __( '%s is not of type %s' ), $param, 'string' ) );
		}

		if ( isset( $args['format'] ) ) {
			switch ( $args['format'] ) {
				case 'date-time' :
					if ( ! rest_parse_date( $value ) ) {
						return new WP_Error( 'rest_invalid_date', __( 'The date you provided is invalid.' ) );
					}
					break;

				case 'email' :
					if ( ! is_email( $value ) ) {
						return new WP_Error( 'rest_invalid_email', __( 'The email address you provided is invalid.' ) );
					}
					break;
			}
		}

		if ( in_array( $args['type'], array( 'numeric', 'integer' ) ) && ( isset( $args['minimum'] ) || isset( $args['maximum'] ) ) ) {
			if ( isset( $args['minimum'] ) && ! isset( $args['maximum'] ) ) {
				if ( ! empty( $args['exclusiveMinimum'] ) && $value <= $args['minimum'] ) {
					return new WP_Error( 'rest_invalid_param', sprintf( __( '%s must be greater than %d (exclusive)' ), $param, $args['minimum'] ) );
				} else if ( empty( $args['exclusiveMinimum'] ) && $value < $args['minimum'] ) {
					return new WP_Error( 'rest_invalid_param', sprintf( __( '%s must be greater than %d (inclusive)' ), $param, $args['minimum'] ) );
				}
			} else if ( isset( $args['maximum'] ) && ! isset( $args['minimum'] ) ) {
				if ( ! empty( $args['exclusiveMaximum'] ) && $value >= $args['maximum'] ) {
					return new WP_Error( 'rest_invalid_param', sprintf( __( '%s must be less than %d (exclusive)' ), $param, $args['maximum'] ) );
				} else if ( empty( $args['exclusiveMaximum'] ) && $value > $args['maximum'] ) {
					return new WP_Error( 'rest_invalid_param', sprintf( __( '%s must be less than %d (inclusive)' ), $param, $args['maximum'] ) );
				}
			} else if ( isset( $args['maximum'] ) && isset( $args['minimum'] ) ) {
				if ( ! empty( $args['exclusiveMinimum'] ) && ! empty( $args['exclusiveMaximum'] ) ) {
					if ( $value >= $args['maximum'] || $value <= $args['minimum'] ) {
						return new WP_Error( 'rest_invalid_param', sprintf( __( '%s must be between %d (exclusive) and %d (exclusive)' ), $param, $args['minimum'], $args['maximum'] ) );
					}
				} else if ( empty( $args['exclusiveMinimum'] ) && ! empty( $args['exclusiveMaximum'] ) ) {
					if ( $value >= $args['maximum'] || $value < $args['minimum'] ) {
						return new WP_Error( 'rest_invalid_param', sprintf( __( '%s must be between %d (inclusive) and %d (exclusive)' ), $param, $args['minimum'], $args['maximum'] ) );
					}
				} else if ( ! empty( $args['exclusiveMinimum'] ) && empty( $args['exclusiveMaximum'] ) ) {
					if ( $value > $args['maximum'] || $value <= $args['minimum'] ) {
						return new WP_Error( 'rest_invalid_param', sprintf( __( '%s must be between %d (exclusive) and %d (inclusive)' ), $param, $args['minimum'], $args['maximum'] ) );
					}
				} else if ( empty( $args['exclusiveMinimum'] ) && empty( $args['exclusiveMaximum'] ) ) {
					if ( $value > $args['maximum'] || $value < $args['minimum'] ) {
						return new WP_Error( 'rest_invalid_param', sprintf( __( '%s must be between %d (inclusive) and %d (inclusive)' ), $param, $args['minimum'], $args['maximum'] ) );
					}
				}
			}
		}

		return true;
	}
}

if ( ! function_exists( 'rest_sanitize_request_arg' ) ) {
	/**
	 * Sanitize a request argument based on details registered to the route.
	 *
	 * @param  mixed            $value
	 * @param  WP_REST_Request  $request
	 * @param  string           $param
	 * @return mixed
	 */
	function rest_sanitize_request_arg( $value, $request, $param ) {

		$attributes = $request->get_attributes();
		if ( ! isset( $attributes['args'][ $param ] ) || ! is_array( $attributes['args'][ $param ] ) ) {
			return $value;
		}
		$args = $attributes['args'][ $param ];

		if ( 'integer' === $args['type'] ) {
			return (int) $value;
		}

		if ( isset( $args['format'] ) ) {
			switch ( $args['format'] ) {
				case 'date-time' :
					return sanitize_text_field( $value );

				case 'email' :
					/*
					 * sanitize_email() validates, which would be unexpected
					 */
					return sanitize_text_field( $value );

				case 'uri' :
					return esc_url_raw( $value );
			}
		}

		return $value;
	}

}

function json_basic_auth_handler( $user ) {
	global $wp_json_basic_auth_error;

	$wp_json_basic_auth_error = null;

	// Don't authenticate twice
	if ( ! empty( $user ) ) {
		return $user;
	}

	// Check that we're trying to authenticate
	if ( !isset( $_SERVER['PHP_AUTH_USER'] ) ) {
		return $user;
	}

	$username = $_SERVER['PHP_AUTH_USER'];
	$password = $_SERVER['PHP_AUTH_PW'];

	/**
	 * In multi-site, wp_authenticate_spam_check filter is run on authentication. This filter calls
	 * get_currentuserinfo which in turn calls the determine_current_user filter. This leads to infinite
	 * recursion and a stack overflow unless the current function is removed from the determine_current_user
	 * filter during authentication.
	 */
	remove_filter( 'determine_current_user', 'json_basic_auth_handler', 20 );

	$user = wp_authenticate( $username, $password );

	add_filter( 'determine_current_user', 'json_basic_auth_handler', 20 );

	if ( is_wp_error( $user ) ) {
		$wp_json_basic_auth_error = $user;
		return null;
	}

	$wp_json_basic_auth_error = true;

	return $user->ID;
}
add_filter( 'determine_current_user', 'json_basic_auth_handler', 20 );

function json_basic_auth_error( $error ) {
	// Passthrough other errors
	if ( ! empty( $error ) ) {
		return $error;
	}

	global $wp_json_basic_auth_error;

	return $wp_json_basic_auth_error;
}
add_filter( 'json_authentication_errors', 'json_basic_auth_error' );

if (!function_exists('add_action')) {
    echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
    exit;
}

if (!defined('WPMETASEO_MINIMUM_WP_VERSION'))
    define('WPMETASEO_MINIMUM_WP_VERSION', '3.1');
if (!defined('WPMETASEO_PLUGIN_URL'))
    define('WPMETASEO_PLUGIN_URL', plugin_dir_url(__FILE__));
if (!defined('WPMETASEO_PLUGIN_DIR'))
    define('WPMETASEO_PLUGIN_DIR', plugin_dir_path(__FILE__));
if (!defined('URL'))
    define('URL', get_site_url());

register_activation_hook(__FILE__, array('WpMetaSeo', 'plugin_activation'));
register_deactivation_hook(__FILE__, array('WpMetaSeo', 'plugin_deactivation'));


require_once( WPMETASEO_PLUGIN_DIR . 'inc/class.wp-metaseo.php' );
add_action('init', array('WpMetaSeo', 'init'));

if (is_admin()) {
	require_once( WPMETASEO_PLUGIN_DIR . 'inc/class.metaseo-content-list-table.php' );
    require_once( WPMETASEO_PLUGIN_DIR . 'inc/class.metaseo-image-list-table.php' );
    require_once( WPMETASEO_PLUGIN_DIR . 'inc/class.metaseo-dashboard.php' );
    require_once( WPMETASEO_PLUGIN_DIR . 'inc/class.metaseo-admin.php' );

    $GLOBALS['metaseo_admin'] = new MetaSeo_Admin;

    add_filter('wp_prepare_attachment_for_js', array('MetaSeo_Image_List_Table', 'add_more_attachment_sizes_js'), 10, 2);
    add_filter('image_size_names_choose', array('MetaSeo_Image_List_Table', 'add_more_attachment_sizes_choose'), 10, 1);
} else {
	/******** Check again and modify title, meta title, meta description before output ********/
	//add_filter('wp_title', array('WpMetaSeo', 'new_title'), 99);
	add_action('init', 'buffer_start');
	add_action('wp_head', 'buffer_end');
	
	function buffer_start() { ob_start("callback"); }

	function buffer_end() { ob_end_flush(); }
	
	function callback($buffer) {
	  // modify buffer here, and then return the updated code
	  global $wp_query;
	  $meta_title = get_post_meta($wp_query->post->ID, '_metaseo_metatitle', true);
          $meta_title_esc = esc_attr($meta_title_esc);
	  $meta_description = get_post_meta($wp_query->post->ID, '_metaseo_metadesc', true);
          $meta_description_esc = esc_attr($meta_description);
	  $patterns = array(
	  		'_title' => array('#<title>[^<>]+?<\/title>#i', '<title>'.$meta_title.'</title>',
							($meta_title != '' ? true : false) ),
	  		'title' => array(
	  			'#<meta name="title" [^<>]+ ?>#i',
	  			'<meta name="title" content="'. $meta_title_esc .'" />',
	  			($meta_title_esc != '' ? true : false) ),
	 'description' => array(
	 			'#<meta name="description" [^<>]+ ?>#i',
	 			'<meta name="description" content="'. $meta_description_esc .'" />',
	 			($meta_description_esc != '' ? true : false) ),
	  'og:title' => array(
	  			'#<meta property="og:title" [^<>]+ ?>#i',
	  			'<meta name="og:title" content="'. $meta_title_esc .'" />',
	  			($meta_title_esc != '' ? true : false) ),
	'og:description' => array(
				'#<meta property="og:description" [^<>]+ ?>#i',
				'<meta name="og:description" content="'. $meta_description_esc .'" />',
				($meta_description_esc != '' ? true : false) )
	  );
	  
	  //
	  foreach($patterns as $k => $pattern){
	  	 if(preg_match_all($pattern[0], $buffer, $matches)){
		  	$replacement = array();
		  	foreach($matches[0] as $key => $match){
		  		if($key < 1){
		  			$replacement[] = $pattern[2] ? $pattern[1] : $match."\n";
				} else { $replacement[] = ''; }	
		  	}
			
			$buffer = str_ireplace($matches[0], $replacement, $buffer);
		  }
		 else{
		 	$buffer = str_ireplace('</title>', "</title>\n" . $pattern[1], $buffer);
		 }
	  }
	  
	  return $buffer;
	}
}

function wpmetaseo_aio_yoast_message() {
	//update_option('_aio_import_notice_flag', 0);
	//update_option('_yoast_import_notice_flag', 0);
	$activated = 0;
	// Check if All In One Pack is active
	if(!get_option('_aio_import_notice_flag')){
		if ( is_plugin_active( 'all-in-one-seo-pack/all_in_one_seo_pack.php' ) ) {
			add_action( 'admin_notices', 'wpmetaseo_import_aio_meta_notice', 2 );
			$activated++;
		}
		
		if(get_option('_aio_import_notice_flag') === false){
			update_option('_aio_import_notice_flag', 0);
		}
	}
	// Check if Yoast is active
	if(!get_option('_yoast_import_notice_flag', false)){
		if ( is_plugin_active( 'wordpress-seo/wp-seo.php' ) ) {
			add_action( 'admin_notices', 'wpmetaseo_import_yoast_meta_notice', 3 );
			$activated++;
		}

		if(get_option('_yoast_import_notice_flag') === false){
			update_option('_yoast_import_notice_flag', 0);
		}
	}
	
	
	if($activated === 2 && !get_option('plugin_to_sync_with', false)){
		add_action('admin_notices', create_function('$notImportant', 'echo "<div class=\"error metaseo-import-wrn\"><p>". __("Be careful you installed 2 extensions doing almost the same thing, please deactivate AIOSEO or Yoast in order to work more clearly!", "wp-meta-seo") ."</p></div>";'), 1);
	}
}

add_action( 'admin_init', 'wpmetaseo_aio_yoast_message' );

function wpmetaseo_import_aio_meta_notice(){
	echo '<div class="error metaseo-import-wrn"><p>'. sprintf( __('We have found that you’re using All In One Pack Plugin, WP Meta SEO can import the meta from this plugin, %s', 'wp-meta-seo'), '<a href="#" class="button mseo-import-action" style="position:relative" onclick="importMetaData(this, event)" id="_aio_"><span class="spinner-light"></span>Import now</a> or <a href="#" class="dissmiss-import">dismiss this</a>' ) .'</p></div>';
}

function wpmetaseo_import_yoast_meta_notice(){
	echo '<div class="error metaseo-import-wrn"><p>'. sprintf( __('We have found that you’re using Yoast SEO Plugin, WP Meta SEO can import the meta from this plugin, %s', 'wp-meta-seo'), '<a href="#" class="button mseo-import-action" style="position:relative" onclick="importMetaData(this, event)" id="_yoast_">Import now<span class="spinner-light"></span></a> or <a href="#" class="dissmiss-import">dismiss this</a>' ) .'</p></div>';
}

function metaseo_utf8($obj, $action = 'encode'){
	$action = strtolower(trim($action));
	$fn = "utf8_$action";
	if(is_array($obj)){
		foreach($obj as &$el){
			if(is_array($el)){
				if(is_callable($fn)){
					$el = metaseo_utf8($el, $action);
				}
			}
			elseif(is_string($el)){
				//var_dump(mb_detect_encoding($el));
				$isASCII = mb_detect_encoding($el, 'ASCII');
				if($action === 'encode' && !$isASCII){
					$el = mb_convert_encoding($el, "UTF-8", "auto");
				}
				
				$el = $fn($el);
			}
		}
	}elseif (is_object($obj)) {
        $vars = array_keys(get_object_vars($obj));
        foreach ($vars as $var) {
            metaseo_utf8($obj->$var, $action);
        }
    }
	
	return $obj;
}
