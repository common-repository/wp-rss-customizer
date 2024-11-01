<?php
/**
 * RSS 0.92 Feed Template for displaying RSS 0.92 Posts feed.
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

$rss = json_decode( file_get_contents( $file_path ), true );

header( 'Content-Type: ' . feed_content_type( 'rss' ) . '; charset=' . get_option( 'blog_charset' ), true );
$more = 1;

echo '<?xml version="1.0" encoding="' . get_option( 'blog_charset' ) . '"?' . '>'; ?>
<rss version="0.92">
	<channel>
		<title><?php wp_title_rss(); ?></title>
		<link><?php bloginfo_rss( 'url' ) ?></link>
		<description><?php bloginfo_rss( 'description' ) ?></description>
		<lastBuildDate><?php echo mysql2date( 'D, d M Y H:i:s +0000', get_lastpostmodified( 'GMT' ), false ); ?></lastBuildDate>
		<docs>http://backend.userland.com/rss092</docs>
		<language><?php bloginfo_rss( 'language' ); ?></language>

		<?php
		/**
		 * Fires at the end of the RSS Feed Header.
		 *
		 * @since 2.0.0
		 */
		do_action( 'rss_head' );
		?>

		<?php foreach ( $rss as $rss ) : ?>
			<item>
				<title><?php echo $rss['title']; ?></title>
				<description><![CDATA[<?php echo $rss['description']; ?>]]></description>
				<link><?php echo $rss['link']; ?></link>
				<?php
				/**
				 * Fires at the end of each RSS feed item.
				 *
				 * @since 2.0.0
				 */
				do_action( 'rss_item' );
				?>
			</item>
		<?php endforeach; ?>
	</channel>
</rss>
