<?php

/*
Plugin Name: WP RSS CUSTOMIZER
Plugin URI: http://villatheme.com
Description: Create and custom your rss feeds, json easily for your wordpress site
Version: 1.0.0
Author: Cuong Nguyen and Andy Ha
Author URI: http://villatheme.com
Copyright 2016 VillaTheme.com. All rights reserved.
*/
include( 'includes/functions.php' );
if ( ! class_exists( 'WP_Rss_Customizer' ) ):
	class WP_Rss_Customizer {

		public function __construct() {

			add_action( 'admin_enqueue_scripts', array( $this, 'wrc_load_js_css' ) );
			add_action( 'init', array( $this, 'wrc_save_option' ) );
			add_action( 'init', array( $this, 'wrc_create_post_type' ) );
			add_filter( 'enter_title_here', array( $this, 'wrc_enter_title_here' ), 1, 2 );
			add_filter( 'wrc-metabox_array', array( $this, 'init_metabox' ) );
			add_action( 'add_meta_boxes', array( $this, 'wrc_add_meta_box' ) );

			add_action( 'admin_menu', array( $this, 'wrc_add_submenu_page' ) );
			add_action( 'save_post', array( $this, 'wrc_save_meta_data' ) );
			//add_action( 'template_redirect', array($this,'rss_func' ));
			if ( get_option( 'wrc_disable_core_rss' ) == 'on' ) {
				add_action( 'wp_loaded', array( $this, 'disable_core_rss' ) );
				add_action( 'template_redirect', array( $this, 'filter_feeds' ), 1 );
			}
			add_action( "admin_init", array( $this, 'admin_init' ), 1 );
			add_filter( 'rewrite_rules_array', array( $this, 'wrc_insert_rewrite_rules' ) );
			add_filter( 'query_vars', array( $this, 'wrc_insert_query_vars' ) );
			add_action( 'wp_loaded', array( $this, 'wrc_flush_rules' ) );
			add_filter( 'template_include', array( $this, 'rss_output' ) );
			add_action( 'init', array( $this, 'wrc_load_text_domain' ) );

			/*Set default page*/
			add_action( 'template_redirect', array( $this, 'template_redirect' ) );
			add_filter( 'query_vars', array( $this, 'custom_query_vars_filter' ) );

		}

		/**
		 * Add variable
		 *
		 * @param $vars
		 *
		 * @return array
		 */
		public function custom_query_vars_filter( $vars ) {
			$vars[] = 'format';

			return $vars;
		}

		/**
		 *
		 */
		public function template_redirect() {
			global $wp_query;
			if ( ! trim( $wp_query->query_vars['s'] ) ) {
				if ( 'wrc' == get_post_type() && is_archive() ) {
					wrc_get_template_part( 'list-rss' );
					exit();
				}
			}
		}

		/**
		 * Load text domain
		 */
		public function wrc_load_text_domain() {
			$plugin_dir = basename( dirname( __FILE__ ) );
			load_plugin_textdomain( 'wp-rss-customizer', false, $plugin_dir . '\languages' );
		}

		/**
		 * Get list of posttype [addon]
		 * @return mixed
		 */
		public function wrc_get_posttype_list() {
			$posttype_list = array( 'page' => 'Page', 'post' => 'Post' );

			return apply_filters( 'wrc_posttype_list', $posttype_list );
		}

		/**
		 * Adding the id var so that WP recognizes it
		 *
		 * @param $vars
		 *
		 * @return mixed
		 */
		public function wrc_insert_query_vars( $vars ) {
			array_push( $vars, 'format' );

			return $vars;
		}

		/**
		 * flush_rules() if our rules are not yet included
		 */
		public function wrc_flush_rules() {
			$slug  = get_option( 'wrc_slug', 'rss' );
			$rules = get_option( 'rewrite_rules' );
			if ( ! isset( $rules['(' . $slug . ')/(.*)/(feed|rdf|rss|rss2|atom|json)$'] ) ) {
				global $wp_rewrite;
				$wp_rewrite->flush_rules();
			}
		}

		/**
		 * Adding a new rule
		 *
		 * @param $rules
		 *
		 * @return array
		 */
		public function wrc_insert_rewrite_rules( $rules ) {
			$slug                                                            = get_option( 'wrc_slug', 'rss' );
			$newrules                                                        = array();
			$newrules['(' . $slug . ')/(.*)/(feed|rdf|rss|rss2|atom|json)$'] = 'index.php?wrc=$matches[2]&format=$matches[3]';

			return $newrules + $rules;
		}

		public function admin_init() {

		}

		/**
		 * Disable rss function of wordpress
		 */
		public function disable_core_rss() {
			remove_action( 'wp_head', 'feed_links', 2 );
			remove_action( 'wp_head', 'feed_links_extra', 3 );
		}

		/**
		 * Fillter request
		 */
		public function filter_feeds() {
			if ( ! is_feed() || is_404() ) {
				return;
			}
			$this->redirect_feed();
		}

		/**
		 * Redirect feed link of standar wordpress when disable
		 */
		public function redirect_feed() {
			global $wp_rewrite, $wp_query;
			if ( isset( $_GET['feed'] ) ) {
				wp_redirect( esc_url_raw( remove_query_arg( 'feed' ) ), 301 );
				exit;
			}

			if ( get_query_var( 'feed' ) !== 'old' )    // WP redirects these anyway, and removing the query var will confuse it thoroughly
			{
				set_query_var( 'feed', '' );
			}

			redirect_canonical();    // Let WP figure out the appropriate redirect URL.

			// Still here? redirect_canonical failed to redirect, probably because of a filter. Try the hard way.
			$struct        = ( ! is_singular() && is_comment_feed() ) ? $wp_rewrite->get_comment_feed_permastruct() : $wp_rewrite->get_feed_permastruct();
			$struct        = preg_quote( $struct, '#' );
			$struct        = str_replace( '%feed%', '(\w+)?', $struct );
			$struct        = preg_replace( '#/+#', '/', $struct );
			$requested_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			$new_url       = preg_replace( '#' . $struct . '/?$#', '', $requested_url );

			if ( $new_url != $requested_url ) {
				wp_redirect( $new_url, 301 );
				exit;
			}
		}

		/**
		 * Rediect template to generate rss xml
		 *
		 * @param $template
		 *
		 * @return string
		 */
		public function rss_output( $template ) {
			if ( is_single() && get_post_type() == 'wrc' ) {
				$template = plugin_dir_path( __FILE__ ) . 'includes/feeds.php';
			}

			return $template;
		}

		/**
		 * Load js and css
		 */
		public function wrc_load_js_css() {
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'jquery-ui-core' );
			wp_enqueue_script( 'jquery-ui-sortable' );
			wp_enqueue_script( 'select2', plugins_url( '/js/select2.js', __FILE__ ) );
			if ( get_current_screen()->post_type == 'wrc' && get_current_screen()->base != 'edit' ) {
				wp_enqueue_script( 'wrcjs', plugins_url( '/js/wrc.js', __FILE__ ) );
			}
			wp_enqueue_style( 'select2css', plugins_url( '/css/select2.css', __FILE__ ) );
			wp_enqueue_style( 'wp-rss-customizer-admin', plugins_url( '/css/wp-rss-customizer-admin.css', __FILE__ ) );
			wp_enqueue_style( 'wrc-bootstrap-grid', plugins_url( '/css/wrc-bootstrap-grid.css', __FILE__ ) );
		}

		/**
		 * Get all author of system
		 * @return array
		 */
		public function get_author() {
			$authors = get_users( array( 'fields' => array( 'id', 'display_name' ) ) );
			$author  = array();
			foreach ( $authors as $k ) {
				$author[$k->id] = $k->display_name;
			}

			return $author;
		}

		/**
		 * Init metabox array
		 * @return array
		 */
		public function init_metabox() {
			$metabox_arrays = array(
				array(
					'id'        => 'wrc_options',
					'title'     => esc_html__( 'Add Ons', 'wp-rss-customizer' ),
					'call_back' => 'wrc_print_metabox',
					'post_type' => 'wrc',
					'context'   => 'side',
					'priority'  => '',
					'fields'    => array(
						array(
							'field_id'      => 'wrc_selected_posttype',
							'field_title'   => '',
							'field_type'    => 'select',
							'default_value' => 'post',
							'class'         => 'wrc-metabox',
							'description'   => esc_html__( 'Choose addon to generate rss', 'wp-rss-customizer' ),
							'options'       => $this->wrc_get_posttype_list(),
						)
					),
				),
				array(
					'id'        => 'wrc_output',
					'title'     => esc_html__( 'OutPut', 'wp-rss-customizer' ),
					'call_back' => 'wrc_print_metabox',
					'post_type' => 'wrc',
					'context'   => 'side',
					'priority'  => '',
					'fields'    => array(
						array(
							'field_id'      => 'wrc_feed_cache_time',
							'field_title'   => esc_html__( 'Cache Time', 'wp-rss-customizer' ),
							'field_type'    => 'text',
							'default_value' => 15,
							'class'         => 'wrc-metabox',
							'description'   => esc_html__( 'Set time for cache of rss follow minute', 'wp-rss-customizer' ),
						),
						array(
							'field_id'      => 'wrc_output_feed',
							'field_title'   => esc_html__( 'Output format', 'wp-rss-customizer' ),
							'field_type'    => 'radio',
							'default_value' => 'rss2',
							'class'         => 'wrc-metabox',
							'description'   => esc_html__( 'Default out put feed type, you can change output by add http://yoursite.com/rss/example-feed/[rss2|rss|rdf|atom|json]', 'wp-rss-customizer' ),
							'options'       => array(
								'rss2' => esc_html__( 'RSS 2.0', 'wp-rss-customizer' ),
								'rss'  => esc_html__( 'RSS 1.0 ', 'wp-rss-customizer' ),
								'rdf'  => esc_html__( 'RDF', 'wp-rss-customizer' ),
								'atom' => esc_html__( 'ATOM', 'wp-rss-customizer' ),
								'json' => esc_html__( 'JSON', 'wp-rss-customizer' ),
							),
						),
					),
				),
				array(
					'id'        => 'wrc_page',
					'title'     => esc_html__( 'Options', 'wp-rss-customizer' ),
					'call_back' => 'wrc_print_metabox',
					'post_type' => 'wrc',
					'context'   => 'normal',
					'priority'  => 'high',
					'fields'    => array(
						array(
							'field_id'      => 'wrc_page_feed_orderby',
							'field_title'   => esc_html__( 'Order By', 'wp-rss-customizer' ),
							'field_type'    => 'select',
							'default_value' => 'date',
							'class'         => 'wrc-metabox col-sm-5',
							'description'   => esc_html__( 'Order your feed by', 'wp-rss-customizer' ),
							'options'       => array(
								'date'  => esc_html__( 'Date', 'wp-rss-customizer' ),
								'title' => esc_html__( 'Title', 'wp-rss-customizer' ),
							),
						),
						array(
							'field_id'      => 'wrc_page_feed_show_out',
							'field_title'   => esc_html__( 'Show out', 'wp-rss-customizer' ),
							'field_type'    => 'select',
							'default_value' => 'post_content',
							'class'         => 'wrc-metabox col-sm-5',
							'description'   => esc_html__( 'Description of feed', 'wp-rss-customizer' ),
							'options'       => array(
								'post_content' => esc_html__( 'Content', 'wp-rss-customizer' ),
								'post_excerpt' => esc_html__( 'Excerpt', 'wp-rss-customizer' ),
							),
						),
						array(
							'field_id'      => 'wrc_page_show_image',
							'field_title'   => esc_html__( 'Show image', 'wp-rss-customizer' ),
							'field_type'    => 'checkbox',
							'default_value' => 'on',
							'class'         => 'wrc-metabox col-sm-5',
							'description'   => esc_html__( 'Show image in feed item', 'wp-rss-customizer' ),
						),
					),
				),
				array(
					'id'        => 'wrc_post',
					'title'     => esc_html__( 'Options', 'wp-rss-customizer' ),
					'call_back' => 'wrc_print_metabox',
					'post_type' => 'wrc',
					'context'   => 'normal',
					'priority'  => 'high',
					'fields'    => array(
						array(
							'field_id'      => 'wrc_post_feed_orderby',
							'field_title'   => esc_html__( 'Order By:', 'wp-rss-customizer' ),
							'field_type'    => 'select',
							'default_value' => 'date',
							'class'         => 'wrc-metabox col-sm-5',
							'description'   => esc_html__( '', 'wp-rss-customizer' ),
							'options'       => array(
								'date'  => esc_html__( 'Date', 'wp-rss-customizer' ),
								'title' => esc_html__( 'Title', 'wp-rss-customizer' ),
							),
						),
						array(
							'field_id'      => 'wrc_post_feed_show_out',
							'field_title'   => esc_html__( 'Show out', 'wp-rss-customizer' ),
							'field_type'    => 'select',
							'default_value' => 'post_content',
							'class'         => 'wrc-metabox col-sm-5',
							'description'   => esc_html__( 'Description of feed', 'wp-rss-customizer' ),
							'options'       => array(
								'post_content' => esc_html__( 'Content', 'wp-rss-customizer' ),
								'post_excerpt' => esc_html__( 'Excerpt', 'wp-rss-customizer' ),
							),
						),
						array(
							'field_id'      => 'wrc_post_length_limit',
							'field_title'   => esc_html__( 'Description length limit', 'wp-rss-customizer' ),
							'field_type'    => 'text',
							'default_value' => '100',
							'class'         => 'wrc-metabox col-sm-5',
							'description'   => esc_html__( 'The length limit of descriptions of feed', 'wp-rss-customizer' ),
						),
						array(
							'field_id'      => 'wrc_post_show_image',
							'field_title'   => esc_html__( 'Show image', 'wp-rss-customizer' ),
							'field_type'    => 'checkbox',
							'default_value' => 'on',
							'class'         => 'wrc-metabox col-sm-5',
							'description'   => esc_html__( 'Show image in feed item', 'wp-rss-customizer' ),
						),
						array(
							'field_id'      => 'post_feed_exclude_author',
							'field_title'   => esc_html__( 'Exclude Author', 'wp-rss-customizer' ),
							'field_type'    => 'multiple',
							'default_value' => '',
							'class'         => 'wrc-metabox col-sm-5',
							'description'   => esc_html__( 'Exclude item of choosed Author', 'wp-rss-customizer' ),
							'options'       => $this->get_author(),
						),
						array(
							'field_id'      => 'post_feed_exclude_category',
							'field_title'   => esc_html__( 'Exclude Category', 'wp-rss-customizer' ),
							'field_type'    => 'multiple',
							'default_value' => '',
							'class'         => 'wrc-metabox col-sm-5',
							'description'   => esc_html__( 'Exclude item of choosed category', 'wp-rss-customizer' ),
							'options'       => (array) get_terms(
								'category', array(
									'get'    => 'all',
									'fields' => 'id=>name'
								)
							),
						),
					),
				),
				array(
					'id'        => 'wrc_feed_burner_url',
					'title'     => __( 'Feed Burner Url', 'wp-rss-customizer' ),
					'call_back' => 'wrc_print_metabox',
					'post_type' => 'wrc',
					'context'   => 'normal',
					'priority'  => 'high',
					'fields'    => array(
						array(
							'field_id'      => 'feed_bunner_url',
							'field_title'   => esc_html__( 'URL ', 'wp-rss-customizer' ),
							'field_type'    => 'text',
							'default_value' => '',
							'class'         => 'wrc-metabox',
							'description'   => esc_html__( 'Feed burnner link. ', 'wp-rss-customizer' ),
						),
					),
				),
			);

			return apply_filters( 'wrc-metabox_arrays', $metabox_arrays );
		}

		/**
		 * Add metabox
		 */
		public function wrc_add_meta_box() {
			$metabox_arrays = array();
			$metabox_arrays = apply_filters( 'wrc-metabox_array', $metabox_arrays );
			foreach ( $metabox_arrays as $metabox ) {
				add_meta_box( $metabox['id'], $metabox['title'], $metabox['call_back'], $metabox['post_type'], $metabox['context'], $metabox['priority'], $metabox['fields'] );
			}
		}

		/**Save meta datas
		 *
		 * @param $post_id
		 */
		public function wrc_save_meta_data( $post_id ) {
			wrc_save_meta_data_fields( $post_id, $this->init_metabox() );
		}

		/**
		 *Save setting options
		 */
		public function wrc_save_option() {
			if ( isset( $_POST['wrc_setting'] ) && wp_verify_nonce( $_POST['wrc_setting'], 'wrc_setting_submit' ) && current_user_can( 'manage_options' ) ) {
				//update_option( 'wrc_cache_time', $_POST['cache_time'] );
				if ( isset( $_POST['disable_core_rss'] ) ) {
					$disable_core_rss = sanitize_text_field( $_POST['disable_core_rss'] );
					update_option( 'wrc_disable_core_rss', $disable_core_rss );
				} else {
					update_option( 'wrc_disable_core_rss', 'off' );
				}
				if ( isset( $_POST['wrc_no_tile_record'] ) ) {
					$wrc_no_tile_record = sanitize_text_field( $_POST['wrc_no_tile_record'] );
					update_option( 'wrc_no_tile_record', $wrc_no_tile_record );
				} else {
					update_option( 'wrc_no_tile_record', 'off' );
				}
				if ( isset( $_POST['wrc_slug'] ) ) {
					$wrc_slug = sanitize_text_field( $_POST['wrc_slug'] );
					update_option( 'wrc_slug', $wrc_slug );
				} else {
					update_option( 'wrc_slug', 'rss' );
				}
			}
		}

		/**
		 * Create wp-rss-customizer post type
		 */
		public function wrc_create_post_type() {
			$slug    = get_option( 'wrc_slug', 'rss' );
			$labels  = array(
				'name'                  => _x( 'RSS Feeds', 'Post Type General Name', 'wp-rss-customizer' ),
				'singular_name'         => _x( 'RSS Feed', 'Post Type Singular Name', 'wp-rss-customizer' ),
				'menu_name'             => __( 'RSS Customizer', 'wp-rss-customizer' ),
				'name_admin_bar'        => __( 'RSS Feed', 'wp-rss-customizer' ),
				'archives'              => __( 'RSS Feed Archives', 'wp-rss-customizer' ),
				'parent_item_colon'     => __( 'Parent RSS Feed:', 'wp-rss-customizer' ),
				'all_items'             => __( 'All RSS Feeds', 'wp-rss-customizer' ),
				'add_new_item'          => __( 'Add New RSS Feed', 'wp-rss-customizer' ),
				'add_new'               => __( 'Add New RSS Feed', 'wp-rss-customizer' ),
				'new_item'              => __( 'New RSS Feed', 'wp-rss-customizer' ),
				'edit_item'             => __( 'Edit RSS Feed', 'wp-rss-customizer' ),
				'update_item'           => __( 'Update RSS Feed', 'wp-rss-customizer' ),
				'view_item'             => __( 'View RSS Feed', 'wp-rss-customizer' ),
				'search_items'          => __( 'Search RSS Feed', 'wp-rss-customizer' ),
				'not_found'             => __( 'Not found', 'wp-rss-customizer' ),
				'not_found_in_trash'    => __( 'Not found in Trash', 'wp-rss-customizer' ),
				'featured_image'        => __( 'Featured Image', 'wp-rss-customizer' ),
				'set_featured_image'    => __( 'Set featured image', 'wp-rss-customizer' ),
				'remove_featured_image' => __( 'Remove featured image', 'wp-rss-customizer' ),
				'use_featured_image'    => __( 'Use as featured image', 'wp-rss-customizer' ),
				'insert_into_item'      => __( 'Insert into RSS Feed', 'wp-rss-customizer' ),
				'uploaded_to_this_item' => __( 'Uploaded to this RSS Feed', 'wp-rss-customizer' ),
				'items_list'            => __( 'RSS Feeds list', 'wp-rss-customizer' ),
				'items_list_navigation' => __( 'RSS Feeds list navigation', 'wp-rss-customizer' ),
				'filter_items_list'     => __( 'Filter RSS Feeds list', 'wp-rss-customizer' ),
			);
			$rewrite = array(
				'slug'       => $slug,
				'with_front' => true,
				'pages'      => true,
				'feeds'      => false,
			);
			$args    = array(
				'label'               => __( 'RSS Customizer', 'wp-rss-customizer' ),
				'description'         => __( 'Create Custom RSS Feeds for your Wordpress site', 'wp-rss-customizer' ),
				'labels'              => $labels,
				'supports'            => array( 'title', 'excerpt', /*'custom-fields',*/ ),
				'taxonomies'          => array( 'wrc_rss_feeds' ),
				'hierarchical'        => false,
				'public'              => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'menu_position'       => 25,
				'menu_icon'           => 'dashicons-rss',
				'show_in_admin_bar'   => true,
				'show_in_nav_menus'   => false,
				'can_export'          => true,
				'has_archive'         => true,
				'exclude_from_search' => false,
				'publicly_queryable'  => true,
				'rewrite'             => $rewrite,
				'capability_type'     => 'post',
			);
			register_post_type( 'wrc', $args );
		}

		/**
		 * Add Setting page to posttype
		 */
		public function wrc_add_submenu_page() {

			add_submenu_page(
				'edit.php?post_type=wrc', esc_html__( 'Settings', 'wp-rss-customizer' ), esc_html__( 'Settings', 'wp-rss-customizer' ), 'manage_options', 'wrc_setting', array(
					$this,
					'wrc_print_setting_page'
				)
			);
		}

		/**
		 * Out put html of setting page
		 */
		public function wrc_print_setting_page() {
			include( '/views/html-admin-setting.php' );
		}

		/**
		 * Change place holder in rss field name when create
		 *
		 * @param $text
		 * @param $post
		 *
		 * @return mixed
		 */
		public function wrc_enter_title_here( $text, $post ) {
			if ( $post->post_type == 'wrc' ) {
				$text = esc_html__( 'RSS Feed name', 'wp-rss-customizer' );
			}

			return $text;
		}
	}
endif;
$WRC = new WP_Rss_Customizer();
?>