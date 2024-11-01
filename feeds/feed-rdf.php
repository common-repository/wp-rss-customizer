<?php
/**
 * RSS 1 RDF Feed Template for displaying RSS 1 Posts feed.
 *
 * @package WordPress
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

$queried_object = get_queried_object();

$upload_dir = wp_upload_dir();
$dir_path   = $upload_dir['basedir'] . '/wrc_cache';
$file_path  = $dir_path . '/feed_' . $queried_object->ID . '_wrc.txt';

$rss1 = json_decode( file_get_contents( $file_path ), true );
header( 'Content-Type: ' . feed_content_type( 'rdf' ) . '; charset=' . get_option( 'blog_charset' ), true );
$more = 1;

echo '<?xml version="1.0" encoding="' . get_option( 'blog_charset' ) . '"?' . '>';

/** This action is documented in wp-includes/feed-rss2.php */
do_action( 'rss_tag_pre', 'rdf' );
?>
<rdf:RDF
	xmlns="http://purl.org/rss/1.0/"
	xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
	xmlns:dc="http://purl.org/dc/elements/1.1/"
	xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
	xmlns:admin="http://webns.net/mvcb/"
	xmlns:content="http://purl.org/rss/1.0/modules/content/"
	<?php
	/**
	 * Fires at the end of the feed root to add namespaces.
	 *
	 * @since 2.0.0
	 */
	do_action( 'rdf_ns' );
	?>
>
	<channel rdf:about="<?php bloginfo_rss( "url" ) ?>">
		<title><?php wp_title_rss(); ?></title>
		<link><?php bloginfo_rss( 'url' ) ?></link>
		<description><?php bloginfo_rss( 'description' ) ?></description>
		<dc:date><?php echo mysql2date( 'Y-m-d\TH:i:s\Z', get_lastpostmodified( 'GMT' ), false ); ?></dc:date>
		<sy:updatePeriod><?php
			/** This filter is documented in wp-includes/feed-rss2.php */
			echo apply_filters( 'rss_update_period', 'hourly' );
			?></sy:updatePeriod>
		<sy:updateFrequency><?php
			/** This filter is documented in wp-includes/feed-rss2.php */
			echo apply_filters( 'rss_update_frequency', '1' );
			?></sy:updateFrequency>
		<sy:updateBase>2000-01-01T12:00+00:00</sy:updateBase>
		<?php
		/**
		 * Fires at the end of the RDF feed header.
		 *
		 * @since 2.0.0
		 */
		do_action( 'rdf_header' );
		?>
		<items>
			<rdf:Seq>
				<?php foreach ( $rss1 as $rss ) : ?>
					<rdf:li rdf:resource="<?php echo $rss['link']; ?>" />
				<?php endforeach; ?>
			</rdf:Seq>
		</items>
	</channel>
	<?php rewind_posts();
	foreach ( $rss1 as $rss ) : ?>
		<item rdf:about="<?php echo $rss['link']; ?>">
			<title><?php echo $rss['title']; ?></title>
			<link><?php echo $rss['link']; ?></link>
			<dc:date><?php echo $rss['pubDate_rdf']; ?></dc:date>
			<dc:creator><![CDATA[<?php echo $rss['creator']; ?>]]></dc:creator>
			<?php echo $rss['category_rdf']; ?>
			<?php if ( get_option( 'rss_use_excerpt' ) ) : ?>
				<description><![CDATA[<?php echo $rss['description']; ?>]]></description>
			<?php else : ?>
				<description><![CDATA[<?php echo $rss['description']; ?>]]></description>
				<content:encoded><![CDATA[<?php echo $rss['content_rdf']; ?>]]></content:encoded>
			<?php endif; ?>
			<?php
			/**
			 * Fires at the end of each RDF feed item.
			 *
			 * @since 2.0.0
			 */
			do_action( 'rdf_item' );
			?>
		</item>
	<?php endforeach; ?>
</rdf:RDF>
