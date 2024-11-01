<?php
/**
 * Admin View: Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$disable_core_rss = get_option( 'wrc_disable_core_rss' );
if ( $disable_core_rss != 'on' ) {
	$disable_core_rss = 'off';
}
$wrc_no_tile_record = get_option( 'wrc_no_tile_record', 'on' );
if ( $wrc_no_tile_record != 'on' ) {
	$wrc_no_tile_record = 'off';
}
$wrc_slug = get_option( 'wrc_slug' );
if ( ! isset( $wrc_slug ) || $wrc_slug == '' ) {
	$wrc_slug = 'wrc';
}
?>

<div class="wrap wrc-wrap">
	<h2><?php esc_html_e( 'RSS Settings', 'wp-rss-customizer' ) ?></h2>
	<form method="post">
		<table class="" border="0" cellspacing="2" cellpadding="3">
			<tr>
				<td>
					<label for="disable_core_rss" class="wrc-label"><?php esc_html_e( 'Disable Wordpress RSS', 'wp-rss-customizer' ) ?></label>
				</td>
				<td>
					<input type="checkbox" id="disable_core_rss" name="disable_core_rss" <?php echo checked( $disable_core_rss, 'on', false ); ?> />
				</td>
			</tr>
			<tr>
				<td>
					<label for="wrc_no_tile_record" class="wrc-label"><?php esc_html_e( 'Get No title records', 'wp-rss-customizer' ) ?></label>
				</td>
				<td>
					<input type="checkbox" id="wrc_no_tile_record" name="wrc_no_tile_record" <?php echo checked( $wrc_no_tile_record, 'on', false ); ?> />
				</td>
			</tr>
			<tr>
				<td>
					<label for="wrc_slug" class="wrc-label"><?php esc_html_e( 'Slug', 'wp-rss-customizer' ) ?></label>
				</td>
				<td>
					<input type="text" id="wrc_slug" name="wrc_slug" value="<?php esc_attr_e( $wrc_slug ); ?>" />
					<p class="description"><?php esc_html_e( 'After change SLUG, you have to change Permalinks of Wordpress by go to: Settings->Permalinks, then press "Save changes" button', 'wp-rss-customizer' ); ?></p>
				</td>
			</tr>
		</table>
		<?php wp_nonce_field( 'wrc_setting_submit', 'wrc_setting' ); ?>
		<?php submit_button(); ?>
	</form>
</div>