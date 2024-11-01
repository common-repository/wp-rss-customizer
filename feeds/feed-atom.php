<?php
/**
 * Atom Feed Template for displaying Atom Posts feed.
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

header( 'Content-Type: ' . feed_content_type( 'atom' ) . '; charset=' . get_option( 'blog_charset' ), true );
$more = 1;

echo '<?xml version="1.0" encoding="' . get_option( 'blog_charset' ) . '"?' . '>';

/** This action is documented in wp-includes/feed-rss2.php */
do_action( 'rss_tag_pre', 'atom' );
?>
<feed
	xmlns="http://www.w3.org/2005/Atom"
	xmlns:thr="http://purl.org/syndication/thread/1.0"
	xml:lang="<?php bloginfo_rss( 'language' ); ?>"
	xml:base="<?php bloginfo_rss( 'url' ) ?>/wp-atom.php"
	<?php
	/**
	 * Fires at end of the Atom feed root to add namespaces.
	 *
	 * @since 2.0.0
	 */
	do_action( 'atom_ns' );
	?>
>
	<title type="text"><?php wp_title_rss(); ?></title>
	<subtitle type="text"><?php bloginfo_rss( "description" ) ?></subtitle>

	<updated><?php echo mysql2date( 'Y-m-d\TH:i:s\Z', get_lastpostmodified( 'GMT' ), false ); ?></updated>

	<link rel="alternate" type="<?php bloginfo_rss( 'html_type' ); ?>" href="<?php bloginfo_rss( 'url' ) ?>" />
	<id><?php bloginfo( 'atom_url' ); ?></id>
	<link rel="self" type="application/atom+xml" href="<?php self_link(); ?>" />

	<?php
	/**
	 * Fires just before the first Atom feed entry.
	 *
	 * @since 2.0.0
	 */
	do_action( 'atom_head' );

	foreach ( $rss as $rss ) :?>
		<entry>
			<author>
				<name><?php echo $rss['creator']; ?></name>
				<?php $author_url = $rss['author_url'];
				if ( ! empty( $author_url ) ) : ?>
					<uri><?php echo $rss['author_url']; ?></uri>
				<?php endif;

				/**
				 * Fires at the end of each Atom feed author entry.
				 *
				 * @since 3.2.0
				 */
				do_action( 'atom_author' );
				?>
			</author>
			<title type="<?php echo $rss['type_title']; ?>"><![CDATA[<?php echo $rss['title']; ?>]]></title>
			<link rel="alternate" type="<?php echo $rss['type_link']; ?>" href="<?php echo $rss['link']; ?>" />
			<id><?php echo $rss['guid']; ?></id>
			<updated><?php echo $rss['updated']; ?></updated>
			<published><?php echo $rss['published']; ?></published>
			<?php echo $rss['category_atom']; ?>
			<summary type="<?php echo $rss['type_title']; ?>"><![CDATA[<?php echo $rss['summary']; ?>]]></summary>
			<?php if ( ! get_option( 'rss_use_excerpt' ) ) : ?>
				<content type="<?php echo $rss['type_title']; ?>" xml:base="<?php echo $rss['link']; ?>">
					<![CDATA[<?php echo $rss['content_atom']; ?>]]>
				</content>
			<?php endif; ?>
			<?php echo $rss['atom_enclosure'];
			/**
			 * Fires at the end of each Atom feed item.
			 *
			 * @since 2.0.0
			 */
			do_action( 'atom_entry' );

			?>
		</entry>
	<?php endforeach; ?>
</feed>
