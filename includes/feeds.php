<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

$queried_object = get_queried_object();
$post_meta      = get_post_meta( $queried_object->ID );

foreach ( $post_meta as $variable => $values ) {
	$$variable = $values[0];
}
/* 
Variable list:
feed_bunner_url
wrc_selected_posttype
wrc_feed_cache_time
wrc_output_feed
wrc_page_feed_orderby
wrc_page_feed_show_out
wrc_page_show_image
wrc_post_feed_orderby
wrc_post_feed_show_out
wrc_post_show_image
post_feed_exclude_author
post_feed_exclude_category
product_feed_exclude_author
product_feed_exclude_category
*/
$wrc_output_feed = get_query_var( 'format', $wrc_output_feed );

if ( isset( ${"wrc_" . $wrc_selected_posttype . "_feed_orderby"} ) && ${"wrc_" . $wrc_selected_posttype . "_feed_orderby"} == 'date' ) {
	$order = 'DES';
} else {
	$order = 'ASC';
}
$wrc_feed_orderby         = isset( ${"wrc_" . $wrc_selected_posttype . "_feed_orderby"} ) ? ${"wrc_" . $wrc_selected_posttype . "_feed_orderby"} : 'title';
$show_feed_image          = isset( ${"wrc_" . $wrc_selected_posttype . "_show_image"} ) ? ${"wrc_" . $wrc_selected_posttype . "_show_image"} : 'off';
$wrc_feed_show_out        = isset( ${"wrc_" . $wrc_selected_posttype . "_feed_show_out"} ) ? ${"wrc_" . $wrc_selected_posttype . "_feed_show_out"} : 'content';
$post_feed_exclude_author = get_post_meta( $queried_object->ID, 'post_feed_exclude_author', true );
$excluded_author          = array();
if ( $wrc_selected_posttype == 'post' && is_array( $post_feed_exclude_author ) ) {
	foreach ( $post_feed_exclude_author as $seq => $id ) {
		$excluded_author[] = $id;
	}
}

$post_feed_exclude_category = get_post_meta( $queried_object->ID, 'post_feed_exclude_category', true );
$excluded_category          = array();
if ( $wrc_selected_posttype == 'post' && is_array( $post_feed_exclude_category ) ) {
	foreach ( $post_feed_exclude_category as $seq => $id ) {
		$excluded_category[] = $id;
	}
}

$args_post = array(
	'post_type'        => $wrc_selected_posttype,
	'order'            => $order,
	'orderby'          => $wrc_feed_orderby,
	'nopaging'         => true,
	'author__not_in'   => $excluded_author,
	'category__not_in' => $excluded_category,
);
if ( ! isset( $wrc_feed_cache_time ) || $wrc_feed_cache_time == '' ) {
	$wrc_feed_cache_time = 0;
}
$cache_time = $wrc_feed_cache_time * 60;
$upload_dir = wp_upload_dir();
$dir_path   = $upload_dir['basedir'] . '/wrc_cache';
if ( ! is_dir( $dir_path ) ) {
	wp_mkdir_p( $dir_path );
}
if ( ! isset( $wrc_output_feed ) ) {
	$wrc_output_feed == 'rss2';
}
if ( $wrc_output_feed == 'rss2' || $wrc_output_feed == 'rss' || $wrc_output_feed == 'rdf' || $wrc_output_feed == 'atom' || $wrc_output_feed == 'json' ) {

	$file_path    = $dir_path . '/feed_' . $queried_object->ID . '_wrc.txt';
	$current_time = time();
	$last_build   = get_transient( $queried_object->ID . '_feed_wrc_lastbuild' );
	if ( ! file_exists( $file_path ) || ( $last_build + $cache_time < $current_time ) ) {
		$post_query = new WP_Query( $args_post );
		$posts      = $post_query->posts;
		//To write add-on for this plugin, use this filter to change value of output records
		$posts = apply_filters( 'wrc_posts_array', $posts, $post_meta );

		//Save infor to rss
		$rss = array();
		foreach ( $posts as $post ) {
			$post->post_content = strip_shortcodes( $post->post_content );
			setup_postdata( $post );
			$image = "";
			if ( $show_feed_image == 'on' ) {
				if ( wrc_catch_that_image( $post ) ) {
					$image = '<img width="130" height="100" title="' . get_the_title_rss() . '" alt="' . get_the_title_rss() . '" src="' . wrc_catch_that_image( $post ) . '"/>' . "<br>";
				}
			}
			$rss[$post->ID]['ID']    = $post->ID;
			$rss[$post->ID]['title'] = get_the_title_rss();
			$rss[$post->ID]['link']  = get_permalink();
			if ( get_comments_number() || comments_open() ) {
				$rss[$post->ID]['comments'] = get_comments_link();
			} else {
				$rss[$post->ID]['comments'] = '';
			}
			$rss[$post->ID]['pubDate']       = mysql2date( 'D, d M Y H:i:s +0000', get_post_time( 'Y-m-d H:i:s', true ), false );
			$rss[$post->ID]['pubDate_rdf']   = mysql2date( 'Y-m-d\TH:i:s\Z', $post->post_date_gmt, false );
			$rss[$post->ID]['creator']       = get_the_author();
			$rss[$post->ID]['author_url']    = get_the_author_meta( 'url' );
			$rss[$post->ID]['category']      = get_the_category_rss( 'rss2' );
			$rss[$post->ID]['category_rdf']  = get_the_category_rss( 'rdf' );
			$rss[$post->ID]['category_atom'] = get_the_category_rss( 'atom' );
			$rss[$post->ID]['guid']          = get_the_guid();
			$rss[$post->ID]['content_rdf']   = get_the_content_feed( 'rdf' );
			$content                         = get_the_content_feed( 'rss2' );
			if ( $wrc_feed_show_out != 'content' ) {
				$rss[$post->ID]['description'] = get_the_excerpt();
			} else {
				$rss[$post->ID]['description'] = $content;
			}
			if ( strlen( $content ) > 0 ) {
				$rss[$post->ID]['content'] = $content;
			} else {
				$rss[$post->ID]['content'] = get_the_excerpt();
			}
			$rss[$post->ID]['content']       = '';
			$rss[$post->ID]['commentRss']    = '';
			$rss[$post->ID]['commentnumber'] = '';
			if ( get_comments_number() || comments_open() ) {
				$rss[$post->ID]['commentRss']    = esc_url( get_post_comments_feed_link( null, 'rss2' ) );
				$rss[$post->ID]['commentnumber'] = get_comments_number();
			}
			$rss[$post->ID]['content_atom'] = $rss[$post->ID]['content'];
			ob_start();
			rss_enclosure();
			$rss[$post->ID]['rss_enclosure'] = ob_get_clean();
			ob_start();
			html_type_rss();
			$rss[$post->ID]['type_title'] = ob_get_clean();
			ob_start();
			bloginfo_rss( 'html_type' );
			$rss[$post->ID]['type_link'] = ob_get_clean();
			ob_start();
			atom_enclosure();
			$rss[$post->ID]['atom_enclosure'] = ob_get_clean();
			$rss[$post->ID]['updated']        = get_post_modified_time( 'Y-m-d\TH:i:s\Z', true );
			$rss[$post->ID]['published']      = get_post_time( 'Y-m-d\TH:i:s\Z', true );
			$rss[$post->ID]['summary']        = get_the_excerpt();

			if ( $wrc_selected_posttype == 'post' ) {
				if ( $wrc_post_length_limit > 0 ) {
					$rss[$post->ID]['description'] = substr( $rss[$post->ID]['description'], 0, $wrc_post_length_limit );
				}
			}
			$rss[$post->ID]['description'] = $image . $rss[$post->ID]['description'];
		}

		$rss    = apply_filters( 'wrc_posts_array_after', $rss );
		$fields = array(
			'title', 'link', 'comments', 'pubDate', 'creator', 'author_url',
			'category', 'category_rdf', 'category_atom', 'guid', 'content_rdf', 'description',
			'content', 'commentRss', 'commentnumber', 'content_atom', 'rss_enclosure', 'type_title',
			'html_type', 'type_link', 'atom_enclosure', 'updated', 'published', 'summary', 'pubDate_rdf',
		);
		foreach ( $rss as $key => &$rss1 ) {
			if ( $rss1['title'] == '' ) {
				if ( get_option( 'wrc_no_tile_record', 'on' ) == 'on' ) {
					$rss1['title'] = 'No Title';
				} else {
					unset( $rss[$key] );
				}
			}
			foreach ( $fields as $field ) {
				if ( ! isset( $rss1[$field] ) ) {
					$rss1[$field] = '';
				}
			}
		}
		file_put_contents( $file_path, json_encode( $rss ) );
		set_transient( $queried_object->ID . '_feed_wrc_lastbuild', $current_time, $cache_time );
	}
	load_template( ABSPATH . 'wp-content\plugins\wp-rss-customizer\feeds\feed-' . $wrc_output_feed . '.php' );
}
?>