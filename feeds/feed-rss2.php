<?php
/**
 * RSS 2.0 Feed Template for displaying RSS 2.0 Posts feed.
 *
 * @package WordPress
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

$queried_object = get_queried_object();
$post           = get_post_meta( $queried_object->ID );
$upload_dir     = wp_upload_dir();
$dir_path       = $upload_dir['basedir'] . '/wrc_cache';
$file_path      = $dir_path . '/feed_' . $queried_object->ID . '_wrc.txt';
$rss            = json_decode( file_get_contents( $file_path ), true );
header( 'Content-Type: ' . feed_content_type( 'rss2' ) . '; charset=' . get_option( 'blog_charset' ), true );
echo '<?xml version="1.0" encoding="' . get_option( 'blog_charset' ) . '"?' . '>';
?>
<rss version="2.0"
	 xmlns:content="http://purl.org/rss/1.0/modules/content/"
	 xmlns:wfw="http://wellformedweb.org/CommentAPI/"
	 xmlns:dc="http://purl.org/dc/elements/1.1/"
	 xmlns:atom="http://www.w3.org/2005/Atom"
	 xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
	 xmlns:slash="http://purl.org/rss/1.0/modules/slash/"
>

	<channel>
		<title><?php wp_title_rss(); ?></title>
		<atom:link href="<?php self_link(); ?>" rel="self" type="application/rss+xml" />
		<link><?php bloginfo_rss( 'url' ) ?></link>
		<description><?php bloginfo_rss( "description" ) ?></description>
		<lastBuildDate><?php echo mysql2date( 'D, d M Y H:i:s +0000', get_lastpostmodified( 'GMT' ), false ); ?></lastBuildDate>
		<language><?php bloginfo_rss( 'language' ); ?></language>
		<sy:updatePeriod><?php
			$duration = 'hourly';

			/**
			 * Filter how often to update the RSS feed.
			 *
			 * @since 2.1.0
			 *
			 * @param string $duration The update period. Accepts 'hourly', 'daily', 'weekly', 'monthly',
			 *                         'yearly'. Default 'hourly'.
			 */
			echo apply_filters( 'rss_update_period', $duration );
			?></sy:updatePeriod>
		<sy:updateFrequency><?php
			$frequency = '1';

			/**
			 * Filter the RSS update frequency.
			 *
			 * @since 2.1.0
			 *
			 * @param string $frequency An integer passed as a string representing the frequency
			 *                          of RSS updates within the update period. Default '1'.
			 */
			echo apply_filters( 'rss_update_frequency', $frequency );
			?></sy:updateFrequency>
		<?php
		/**
		 * Fires at the end of the RSS2 Feed Header.
		 *
		 * @since 2.0.0
		 */
		do_action( 'rss2_head' );

		foreach ( $rss as $rss ) :
			?>
			<item>
				<title><?php echo $rss['title']; ?></title>
				<link><?php echo $rss['link']; ?></link>
				<?php if ( $rss['comments'] != '' ) : ?>
					<comments><?php echo $rss['comments']; ?></comments>
				<?php endif; ?>
				<pubDate><?php echo $rss['pubDate']; ?></pubDate>
				<dc:creator><![CDATA[<?php echo $rss['creator']; ?>]]></dc:creator>
				<?php echo $rss['category']; ?>

				<guid isPermaLink="false"><?php echo $rss['guid']; ?></guid>
				<description><![CDATA[<?php echo $rss['description']; ?>]]></description>
				<content:encoded><![CDATA[<?php echo $rss['content']; ?>]]></content:encoded>
				<?php if ( $rss['commentRss'] != '' ) : ?>
					<wfw:commentRss><?php echo $rss['commentRss']; ?></wfw:commentRss>
					<slash:comments><?php echo $rss['commentnumber']; ?></slash:comments>
				<?php endif; ?>
				<?php echo $rss['rss_enclosure']; ?>
				<?php
				/**
				 * Fires at the end of each RSS2 feed item.
				 *
				 * @since 2.0.0
				 */
				do_action( 'rss2_item' );
				?>
			</item>
		<?php endforeach; ?>
	</channel>
</rss>
