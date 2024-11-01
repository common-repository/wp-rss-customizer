<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

define( 'WPRSSCUSTOMIZER_DIR', WP_PLUGIN_DIR . '/wp-rss-customizer/' );
define( 'WPRSSCUSTOMIZER_IMAGES_URL', WP_PLUGIN_URL . '/wp-rss-customizer/images/' );
/**
 * Get image of post
 *
 * @param $post
 *
 * @return string
 */
function wrc_catch_that_image( $post ) {
	$first_img = '';
	ob_start();
	ob_end_clean();
	$output = preg_match_all( '/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $post->post_content, $matches );
	if ( $output ) {
		$first_img = $matches[1][0];
	}
	if ( get_the_post_thumbnail_url( $post ) ) {
		return get_the_post_thumbnail_url( $post );
	} else {
		return $first_img;
	}
}

/**
 * Out put fields to html
 *
 * @param $post
 * @param $field
 */
function wrc_print_fields( $post, $field ) {
	$value = get_post_meta( $post->ID, $field['field_id'], true );
	if ( ! isset( $field['default_value'] ) ) {
		$field['default_value'] = '';
	}
	if ( $value == '' ) {
		$value = $field['default_value'];
	}
	if ( ! isset( $field['description'] ) ) {
		$field['description'] = '';
	}
	if ( ! isset( $field['class'] ) ) {
		$field['class'] = '';
	}
	switch ( $field['field_type'] ) {
		case 'checkbox':
			echo '<div class="' . $field['class'] . '">';
			echo '<label for="' . $field['field_id'] . '" class="wrc-label">' . $field['field_title'] . '</label>';
			echo '<input type="checkbox" id="' . $field['field_id'] . '" name="' . $field['field_id'] . '" ' . checked( $value, 'on', false ) . '/>';
			echo '<p class="description">' . $field['description'] . '</p>';
			echo '</div>';
			break;
		case  'text':
			echo '<div class="' . $field['class'] . '">';
			echo '<label class="wrc-label" for="' . $field['field_id'] . '">' . $field['field_title'] . '</label>';
			echo '<input type="text" id="' . $field['field_id'] . '" name="' . $field['field_id'] . '" value="' . $value . '"/>';
			echo '<p class="description">' . $field['description'] . '</p>';
			echo '</div>';
			break;
		case  'radio':
			echo '<div class="' . $field['class'] . '">';
			echo '<label class="wrc-label" for="' . $field['field_id'] . '">' . $field['field_title'] . '</label>';
			$i = 0;
			foreach ( $field['options'] as $opvalue => $title ) {
				$checked = '';
				if ( $i == 0 && $value == '' ) {
					$checked = 'checked';
				}
				$i ++;
				if ( $opvalue == $value ) {
					$checked = 'checked';
				}
				echo '<label class="wrc-label-child">' . $title . '<input type="radio" name="' . $field['field_id'] . '" value="' . $opvalue . '" ' . $checked . ' />' . '</label>';
			}
			echo '<p class="description">' . $field['description'] . '</p>';
			echo '</div>';
			break;
		case  'select':
			echo '<div class="' . $field['class'] . '">';
			echo '<label class="wrc-label" for="' . $field['field_id'] . '">' . $field['field_title'] . '</label>';
			echo '<select id="' . $field['field_id'] . '" name="' . $field['field_id'] . '" style="width: 200px;"/>';
			foreach ( $field['options'] as $opvalue => $title ) {
				echo '<option value="' . $opvalue . '" ' . selected( $value, $opvalue ) . ' >' . $title . '</option>';
			}
			echo "</select>";
			echo '<p class="description">' . $field['description'] . '</p>';
			echo '</div>';
			break;
		case  'multiple':
			echo '<div class="' . $field['class'] . '">';
			echo '<label class="wrc-label" for="' . $field['field_id'] . '">' . $field['field_title'] . '</label>';
			echo '<select multiple id="' . $field['field_id'] . '" name="' . $field['field_id'] . '[]"  style="width: 200px;"/>';
			foreach ( $field['options'] as $opvalue => $title ) {
				$selected = '';
				foreach ( $value as $val ) {
					if ( $val == $opvalue ) {
						$selected = 'selected';
					}
				}

				echo '<option value="' . $opvalue . '" ' . $selected . ' >' . $title . '</option>';
			}
			echo "</select>";
			echo '<p class="description">' . $field['description'] . '</p>';
			echo '</div>';
			break;
	}
	do_action( 'wrc_print_fields' );
}

/**
 * @param $post
 * @param $fields
 */
function wrc_print_metabox( $post, $fields ) {
	$count = 0;
	wp_nonce_field( 'wrc_metabox_submit', 'wrc_metabox' );
	?>
	<div class="wp-rss-customizer">
		<?php
		foreach ( $fields['args'] as $field ) {
			wrc_print_fields( $post, $field );
			$count ++;
		} ?>
	</div>
	<?php
}

/**
 * @param $post_id
 * @param $metabox_arrays
 */
function wrc_save_meta_data_fields( $post_id, $metabox_arrays ) {
	if ( isset( $_POST['wrc_metabox'] ) && wp_verify_nonce( $_POST['wrc_metabox'], 'wrc_metabox_submit' ) && current_user_can( 'manage_options' ) ) {
		foreach ( $metabox_arrays as $metabox ) {
			foreach ( $metabox['fields'] as $field ) {
				if ( isset( $_POST[$field['field_id']] ) ) {
					if ( $field['field_type'] == 'multiple' ) {
						$value = $_POST[$field['field_id']];
						if ( is_array( $value ) ) {
							foreach ( $value as &$v ) {
								$v = esc_attr( $v );
							}
							unset( $v );
						} else {
							$value = esc_attr( $value );
						}
					} else {
						$value = sanitize_text_field( $_POST[$field['field_id']] );
					}
					update_post_meta( $post_id, $field['field_id'], $value );
				} else {
					update_post_meta( $post_id, $field['field_id'], 'off' );//for checkbox field unchecked
				}
			}
		}
	}
}

/**
 * Get template part (for templates like the shop-loop).
 *
 * @access public
 *
 * @param mixed  $slug
 * @param string $name (default: '')
 *
 * @return void
 */
function wrc_get_template_part( $slug, $name = '' ) {
	$template = '';

	// Get default slug-name.php
	if ( $name && $slug ) {
		$template = locate_template( array( "wp-rss-customizer/{$slug}-{$name}.php" ), false, false );
	}

	if ( ! $template && $slug ) {
		$template = locate_template( array( "wp-rss-customizer/{$slug}.php" ), false, false );
	}

	if ( ! $template && $name && $slug && file_exists( WPRSSCUSTOMIZER_DIR . "templates/{$slug}-{$name}.php" ) ) {
		$template = WPRSSCUSTOMIZER_DIR . "templates" . DIRECTORY_SEPARATOR . "{$slug}-{$name}.php";

	}

	if ( ! $template && $slug && file_exists( WPRSSCUSTOMIZER_DIR . "templates" . DIRECTORY_SEPARATOR . "{$slug}.php" ) ) {

		$template = WPRSSCUSTOMIZER_DIR . "templates" . DIRECTORY_SEPARATOR . "{$slug}.php";

	}
	if ( $template ) {
		load_template( $template, false );
	}

}

function wrc_get_all_feed_links( $post_id = null ) {
	if ( empty( $post_id ) ) {
		global $post;
		$post_id = $post->ID;
	}
	$link = get_permalink( $post_id );

	$link_atom = add_query_arg( array( 'format' => 'atom' ), $link );
	$link_rss2 = add_query_arg( array( 'format' => 'rss2' ), $link );
	$link_rss  = add_query_arg( array( 'format' => 'rss' ), $link );
	$link_json = add_query_arg( array( 'format' => 'json' ), $link );
	$link_rdf  = add_query_arg( array( 'format' => 'rdf' ), $link );

	$link_burner = get_post_meta( $post_id, 'feed_bunner_url', true );

	$html = '';
	$html .= '<a href="' . esc_url( $link_atom ) . '"><img title="' . esc_html__( 'ATOM', 'wp-rss-customizer' ) . '" src="' . WPRSSCUSTOMIZER_IMAGES_URL . 'atom.png"/></a>&nbsp;';
	$html .= '<a href="' . esc_url( $link_rss2 ) . '"><img title="' . esc_html__( 'RSS 2.0', 'wp-rss-customizer' ) . '" src="' . WPRSSCUSTOMIZER_IMAGES_URL . 'rss2.png"/></a>&nbsp;';
	$html .= '<a href="' . esc_url( $link_rss ) . '"><img title="' . esc_html__( 'RSS 1.0', 'wp-rss-customizer' ) . '" src="' . WPRSSCUSTOMIZER_IMAGES_URL . 'rss.png"/></a>&nbsp;';
	$html .= '<a href="' . esc_url( $link_rdf ) . '"><img title="' . esc_html__( 'RDF', 'wp-rss-customizer' ) . '" src="' . WPRSSCUSTOMIZER_IMAGES_URL . 'rdf.png"/></a>&nbsp;';
	$html .= '<a href="' . esc_url( $link_json ) . '"><img title="' . esc_html__( 'JSON', 'wp-rss-customizer' ) . '" src="' . WPRSSCUSTOMIZER_IMAGES_URL . 'json.png"/></a>&nbsp;';
	if ( filter_var( $link_burner, FILTER_VALIDATE_URL ) === false ) {

	} else {
		$html .= '<a title="' . esc_html__( 'Feed Burner', 'wp-rss-customizer' ) . '" href="' . esc_url( $link_burner ) . '"><img src="' . WPRSSCUSTOMIZER_IMAGES_URL . 'feedburner.png"/></a>';
	}

	return $html;

}