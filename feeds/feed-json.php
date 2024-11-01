<?php
/**
 * Json Feed Template for displaying Json feed.
 *
 * @package WordPress
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

$queried_object = get_queried_object();
$post           = get_post_meta( $queried_object->ID );

$upload_dir = wp_upload_dir();
$dir_path   = $upload_dir['basedir'] . '/wrc_cache';
$file_path  = $dir_path . '/feed_' . $queried_object->ID . '_wrc.txt';

$rss = file_get_contents( $file_path );
echo $rss;
die;
?>

