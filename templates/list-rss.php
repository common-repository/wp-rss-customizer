<?php
/*
  Template Name: RSS LIST
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly
get_header();
$args_post  = array(
	'post_type'      => 'wrc',
	'posts_per_page' => - 1,
	'post_status'    => 'publish'
);
$post_query = new WP_Query( $args_post );
$posts      = $post_query->posts;
?>

	<div id="primary" class="content-area">
		<div id="content" class="site-content" role="main">
			<h1> <?php esc_html_e( 'LIST RSS', 'wp-rss-customizer' ) ?></h1>
			<?php /* The loop */ ?>
			<?php foreach ( $posts as $post ):
				setup_postdata( $post );
				?>
				<ul>
					<li>
						<a href="<?php echo esc_url( get_the_permalink() ) ?>"><?php the_title() ?></a>
						<?php echo wrc_get_all_feed_links() ?>
					</li>
				</ul>
			<?php endforeach; ?>

		</div><!-- #content -->
	</div><!-- #primary -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>