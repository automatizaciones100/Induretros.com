<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Meta API class
 */
class WPBean_Accordion_Menu_MetaAPI {

	/**
	 * Settings sections array
	 *
	 * @var array
	 */
	protected $settings_sections = array();

	/**
	 * Settings fields array
	 *
	 * @var array
	 */
	protected $settings_fields = array();

	/**
	 * Set settings sections
	 *
	 * @param array $sections setting sections array.
	 */
	public function set_sections( $sections ) {
		$this->settings_sections = $sections;

		return $this;
	}

	/**
	 * Get meta field
	 *
	 * @param array $fields meta fields.
	 * @param int   $post_id post id.
	 */
	public function get_meta_fields( $fields, $post_id ) {
		if ( $fields && ! empty( $fields ) ) {
			foreach ( $fields as $field ) {
				$this->add_field( $field, $post_id );
			}
		}
	}

	/**
	 * Add meta field
	 *
	 * @param array $field meta field args.
	 * @param int   $post_id post id.
	 */
	public function add_field( $field, $post_id ) {
		$defaults = array(
			'post_id'        => $post_id,
			'value'          => get_post_meta( $post_id, sanitize_text_field( wp_unslash( $field['name'] ) ), true ),
			'name'           => '',
			'label'          => '',
			'checkbox_label' => '',
			'desc'           => '',
			'wrapper_class'  => '',
			'default'        => '',
			'type'           => 'text',
		);

		$arg  = wp_parse_args( $field, $defaults );
		$type = isset( $arg['type'] ) ? $arg['type'] : 'text';

		$condition_operator = '=';

		if ( isset( $arg['condition'][2] ) ) {
			$condition_operator = $arg['condition'][2];
		}

		printf(
			'<div class="wpbean-sb-form-group %1$s" %2$s %3$s %4$s>%5$s',
			esc_attr( $arg['wrapper_class'] ),
			array_key_exists( 'condition', $arg ) ? 'data-condition-field="' . esc_attr( $arg['condition'][0] ) . '"' : '',
			array_key_exists( 'condition', $arg ) ? 'data-condition-value="' . esc_attr( $arg['condition'][1] ) . '"' : '',
			array_key_exists( 'condition', $arg ) ? 'data-condition-operator="' . esc_attr( $condition_operator ) . '"' : '',
			array_key_exists( 'label', $arg ) && '' !== $arg['label'] ? '<div class="wpbean-sb-field-title"><label>' . esc_attr( $arg['label'] ) . '</label>' : '',
		);
		if ( array_key_exists( 'label', $arg ) && '' !== $arg['label'] ) {
			$this->get_field_description( $arg );
			echo '</div>';
		}
		echo '<div class="wpbean-sb-fieldset">';
		call_user_func( array( $this, 'callback_' . $type ), $arg );
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Displays a group field for a meta field
	 *
	 * @param array $args meta field args.
	 */
	public function callback_group( $args ) {

		$size        = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
		$type        = isset( $args['type'] ) ? $args['type'] : 'text';
		$placeholder = empty( $args['placeholder'] ) ? '' : ' placeholder="' . $args['placeholder'] . '"';
		$value       = empty( $args['value'] ) && ! empty( $args['default'] ) ? $args['default'] : $args['value'];

		if ( array_key_exists( 'options', $args ) && ! empty( $args['options'] ) ) {
			echo '<div class="wpbean-sb-group-fields-wrapper' . ( array_key_exists( 'group_type', $args ) ? ' wpbean-sb-group-fields-' . esc_attr( $args['group_type'] ) : '' ) . '">';
			foreach ( $args['options'] as $field ) {
				$this->add_field( $field, $args['post_id'] );
			}
			echo '</div>';
		}
	}

	/**
	 * Displays a text field for a meta field
	 *
	 * @param array $args meta field args.
	 */
	public function callback_text( $args ) {

		$size        = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
		$type        = isset( $args['type'] ) ? $args['type'] : 'text';
		$placeholder = empty( $args['placeholder'] ) ? '' : ' placeholder="' . $args['placeholder'] . '"';
		$value       = empty( $args['value'] ) && ! empty( $args['default'] ) ? $args['default'] : $args['value'];

		printf( '<input type="%1$s" class="wpbean-sb-form-control %2$s-text" id="%3$s" name="%3$s" value="%4$s"%5$s/>', esc_attr( $type ), esc_attr( $size ), esc_attr( $args['name'] ), esc_attr( $value ), wp_kses_post( $placeholder ) );
	}

	/**
	 * Displays a number field for a meta field
	 *
	 * @param array $args meta field args.
	 */
	public function callback_number( $args ) {

		$size        = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
		$type        = isset( $args['type'] ) ? $args['type'] : 'text';
		$placeholder = empty( $args['placeholder'] ) ? '' : ' placeholder="' . $args['placeholder'] . '"';
		$value       = empty( $args['value'] ) && ! empty( $args['default'] ) ? $args['default'] : $args['value'];

		printf( '<input type="%1$s" class="wpbean-sb-form-control %2$s-text" id="%3$s" name="%3$s" value="%4$s"%5$s/>', esc_attr( $type ), esc_attr( $size ), esc_attr( $args['name'] ), esc_attr( $value ), wp_kses_post( $placeholder ) );
	}

	/**
	 * Displays a selectbox for a settings field
	 *
	 * @param array $args settings field args.
	 */
	public function callback_select( $args ) {

		$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
		$value = empty( $args['value'] ) && ! empty( $args['default'] ) ? $args['default'] : $args['value'];
		?>
			<select 
			class="wpbean-sb-form-control <?php echo esc_attr( $size ); ?>"
			name="<?php echo esc_attr( $args['name'] ); ?>"
			id="<?php echo esc_attr( $args['name'] ); ?>"
			>
			<?php
			foreach ( $args['options'] as $key => $val ) {
				?>
				<option value="<?php echo esc_attr( $key ); ?>"
					<?php

					if ( is_array( $value ) ) {
						selected( in_array( (string) $key, $value, true ), true );
					} else {
						selected( $value, (string) $key );
					}

					?>
				><?php echo esc_html( $val ); ?></option>
				<?php
			}
			?>
			</select>
		<?php
	}

	/**
	 * Displays a checkbox for a settings field
	 *
	 * @param array $args settings field args.
	 */
	public function callback_checkbox( $args ) {

		$value = empty( $args['value'] ) && ! empty( $args['default'] ) ? $args['default'] : $args['value'];
		?>

		<fieldset class="wpb-checkbox">
			<label class="wpbean-sb-switch-wrapper" for="<?php echo esc_attr( $args['name'] ); ?>">
				<span class="wpbean-sb-switch">
					<input type="checkbox" class="checkbox" id="<?php echo esc_attr( $args['name'] ); ?>" name="<?php echo esc_attr( $args['name'] ); ?>" value="on" <?php checked( $value, 'on' ); ?>>
					<span class="wpbean-sb-slider"></span>
				</span>
				<span class="wpbean-sb-switch-label"><?php echo( array_key_exists( 'checkbox_label', $args ) ? esc_html( $args['checkbox_label'] ) : '' ); ?></span>
			</label>
		</fieldset>
		<?php
	}

	/**
	 * Displays a radio button for a settings field
	 *
	 * @param array $args settings field args.
	 */
	public function callback_radio( $args ) {

		$value = empty( $args['value'] ) && ! empty( $args['default'] ) ? $args['default'] : $args['value'];
		?>
		<fieldset class="wpb-radio-button">
			<?php
			foreach ( $args['options'] as $key => $label ) {
				?>
				<label for="wpb-<?php echo esc_attr( $args['name'] ); ?>[<?php echo esc_attr( $key ); ?>]">
					<input type="radio" class="radio" id="wpb-<?php echo esc_attr( $args['name'] ); ?>[<?php echo esc_attr( $key ); ?>]" name="<?php echo esc_attr( $args['name'] ); ?>" value="<?php echo esc_attr( $key ); ?>" <?php checked( $value, $key ); ?> />
					<?php echo esc_html( $label ); ?>
				</label>
				<?php
			}
			?>
		</fieldset>
		<?php
	}

	/**
	 * Displays a Image Select for a settings field
	 *
	 * @param array $args settings field args.
	 */
	public function callback_image_select( $args ) {

		$value = empty( $args['value'] ) && ! empty( $args['default'] ) ? $args['default'] : $args['value'];
		$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
		?>
		<fieldset class="wpb-image-select wpb-image-select-<?php echo esc_attr( $size ); ?>">
			<?php
			foreach ( $args['options'] as $key => $image ) {
				?>
				<div class="wpb-radio-image <?php echo esc_attr( checked( $value, $key, false ) ? 'wpb-radio-image-active' : '' ); ?>">
					<figure>
						<img src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $key ); ?>">
						<input type="radio" class="radio" id="wpb-<?php echo esc_attr( $args['name'] ); ?>[<?php echo esc_attr( $key ); ?>]" name="<?php echo esc_attr( $args['name'] ); ?>" value="<?php echo esc_attr( $key ); ?>" <?php checked( $value, $key ); ?> />
					</figure>
				</div>
				<?php
			}
			?>
		</fieldset>
		<?php
	}

	/**
	 * Displays a color picker field for a settings field
	 *
	 * @param array $args settings field args.
	 */
	public function callback_color( $args ) {

		$value   = empty( $args['value'] ) && ! empty( $args['default'] ) ? $args['default'] : $args['value'];
		$default = ! empty( $args['default'] ) ? $args['default'] : '';
		?>
		<input type="text" class="wp-color-picker-field color-picker" id="<?php echo esc_attr( $args['name'] ); ?>" name="<?php echo esc_attr( $args['name'] ); ?>" value="<?php echo esc_attr( $value ); ?>" data-default-color="<?php echo esc_attr( $default ); ?>" data-alpha-enabled="true" />
		<?php
	}

	/**
	 * Displays a textarea for a settings field
	 *
	 * @param array $args settings field args.
	 */
	public function callback_textarea( $args ) {
		$value       = empty( $args['value'] ) && ! empty( $args['default'] ) ? $args['default'] : esc_textarea( $args['value'] );
		$size        = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
		$placeholder = empty( $args['placeholder'] ) ? '' : ' placeholder="' . $args['placeholder'] . '"';

		$textarea_settings = array(
			'rows' => 5,
			'cols' => 55,
		);

		if ( isset( $args['options'] ) && is_array( $args['options'] ) ) {
			$textarea_settings = array_merge( $textarea_settings, $args['options'] );
		}
		?>
		<textarea rows="<?php echo esc_attr( $textarea_settings['rows'] ); ?>" cols="<?php echo esc_attr( $textarea_settings['cols'] ); ?>" class="wpbean-sb-form-control <?php echo esc_attr( $size ); ?>-text" id="<?php echo esc_attr( $args['name'] ); ?>" name="<?php echo esc_attr( $args['name'] ); ?>"<?php echo wp_kses( $placeholder, array( 'placeholder' => true ) ); ?>><?php echo wp_kses_post( $value ); ?></textarea>
		<?php
	}

	/**
	 * Displays a rich text textarea for a settings field
	 *
	 * @param array $args settings field args.
	 */
	public function callback_editor( $args ) {

		$value = empty( $args['value'] ) && ! empty( $args['default'] ) ? $args['default'] : $args['value'];

		echo '<div class="wpb_wp_editor_field">';

		$editor_settings = array(
			'teeny'         => false,
			'textarea_name' => $args['name'],
			'textarea_rows' => 10,
			'tinymce'       => true,
		);

		if ( isset( $args['options'] ) && is_array( $args['options'] ) ) {
			$editor_settings = array_merge( $editor_settings, $args['options'] );
		}

		wp_editor( $value, $args['name'] . '_' . $args['post_id'], $editor_settings );

		echo '</div>';
	}

	/**
	 * Displays a image upload field for a settings field
	 *
	 * @param array $args settings field args.
	 */
	public function callback_image( $args ) {

		$value                 = empty( $args['value'] ) && ! empty( $args['default'] ) ? $args['default'] : $args['value'];
		$label                 = isset( $args['options']['button_label'] ) ? $args['options']['button_label'] : esc_html__( 'Upload', 'wpb-accordion-menu-or-category' );
		$wp_media_button_label = isset( $args['options']['wp_media_button_label'] ) ? $args['options']['wp_media_button_label'] : esc_html__( 'Use this image', 'wpb-accordion-menu-or-category' );
		?>
		<input type="hidden" class="wpbean_fopo_image_id" id="<?php echo esc_attr( $args['name'] ); ?>" name="<?php echo esc_attr( $args['name'] ); ?>" value="<?php echo esc_attr( $value ); ?>"/>
		<div class="wpb-image-preview-wrapper<?php echo esc_attr( $value && '' !== $value ? ' active' : '' ); ?>">
			<div class="wpb-image-preview">
				<i class="wpb-image-remove dashicons dashicons-no-alt"></i>
				<span>
					<img src="<?php echo esc_url( wp_get_attachment_image_url( $value, 'thumbnail' ) ); ?>" class="wpb-image-src">
				</span>
			</div>
		</div>
		<input type="button" class="button button-primary wpbean-sb-button wpbean_fopo_image_browse" value="<?php echo esc_html( $label ); ?>" data-uploader_title="<?php echo esc_attr( $args['label'] ); ?>" data-uploader_button_text="<?php echo esc_html( $wp_media_button_label ); ?>" />
		<?php
	}

	/**
	 * Displays ajax select field for a settings field
	 *
	 * @param array $args settings field args.
	 */
	public function callback_select2( $args ) {

		$value        = get_post_meta( $args['post_id'], sanitize_text_field( wp_unslash( $args['name'] ) ) . '[]', true );
		$placeholder  = isset( $args['options']['placeholder'] ) ? $args['options']['placeholder'] : esc_html__( 'Select an Option', 'wpb-accordion-menu-or-category' );
		$data_type    = isset( $args['options']['type'] ) ? $args['options']['type'] : '';
		$post_type    = isset( $args['options']['post_type'] ) ? $args['options']['post_type'] : '';
		$taxonomy     = isset( $args['options']['taxonomy'] ) ? $args['options']['taxonomy'] : '';
		$multiple     = isset( $args['options']['multiple'] ) ? $args['options']['multiple'] : true;
		$allowed_tags = array(
			'select' => array(
				'class'            => array(),
				'name'             => array(),
				'id'               => array(),
				'data-placeholder' => array(),
				'data-type'        => array(),
				'data-post_type'   => array(),
				'data-taxonomy'    => array(),
				'multiple'         => array(),
			),
			'option' => array(
				'value'    => array(),
				'selected' => array(),
			),
		);

		$html = sprintf(
			'<select class="wpbean-sb-ajax-select2" name="%1$s[]" id="%1$s" data-placeholder="%2$s" data-type="%3$s" data-post_type="%4$s" data-taxonomy="%5$s" %6$s>',
			esc_attr( $args['name'] ),
			esc_attr( $placeholder ),
			esc_attr( $data_type ),
			esc_attr( $post_type ),
			esc_attr( $taxonomy ),
			true === $multiple ? 'multiple="multiple"' : '',
		);

		if ( is_array( $value ) && ! empty( $value ) ) {
			foreach ( $value as $id ) {
				if ( 'post' === $data_type ) {
					$title = get_the_title( $id );
					// if the post title is too long, truncate it and add "..." at the end.
					$title = ( mb_strlen( $title ) > 50 ) ? mb_substr( $title, 0, 49 ) . '...' : $title;

					if ( 'any_public' === $post_type ) {
						$html .= sprintf( '<option value="%s" selected="selected">%s</option>', esc_attr( $id ), '#' . esc_attr( $id ) . ' ' . esc_html( $title ) . ' (' . get_post_type( $id ) . ')' );
					} else {
						$html .= sprintf( '<option value="%s" selected="selected">%s</option>', esc_attr( $id ), esc_html( $title ) );
					}
				} elseif ( 'taxonomy' === $data_type ) {
					if ( get_term( $id ) && ! is_wp_error( get_term( $id ) ) ) {
						if ( 'any_public' === $taxonomy ) {
							$html .= sprintf( '<option value="%s" selected="selected">%s</option>', esc_attr( $id ), '#' . esc_attr( $id ) . ' ' . esc_html( get_term( $id )->name ) . ' (' . get_term( $id )->taxonomy . ')' );
						} else {
							$html .= sprintf( '<option value="%s" selected="selected">%s</option>', esc_attr( $id ), esc_html( get_term( $id )->name ) );
						}
					}
				}
			}
		}

		$html .= sprintf( '</select>' );

		echo wp_kses( $html, $allowed_tags );
	}

	/**
	 * Displays a Heading field for a meta field
	 *
	 * @param array $args meta field args.
	 */
	public function callback_heading( $args ) {
		?>
		<div class="wpbean-sb-heading-field">
			<h3><?php echo esc_html( $args['title'] ); ?></h3>
			<?php echo esc_html( $args['content'] ); ?>
		</div>
		<?php
	}

	/**
	 * Get field description for display
	 *
	 * @param array $args meta field args.
	 */
	public function get_field_description( $args ) {
		if ( ! empty( $args['desc'] ) ) {
			$desc = sprintf( '<small class="wpbean-sb-form-text">%s</small>', $args['desc'] );
			?>
			<small class="wpbean-sb-form-text"><?php echo esc_html( $args['desc'] ); ?></small>
			<?php
		}
	}

	/**
	 * Show navigations as tab
	 *
	 * Shows all the settings section labels as tab.
	 */
	public function show_navigation() {
		$count = count( $this->settings_sections );

		// don't show the navigation if only one section exists.
		if ( 1 === $count ) {
			return;
		}

		?>
		<div class="wpb-sb-tabs-nav">
			<ul>
				<?php
				foreach ( $this->settings_sections as $tab ) {
					?>
						<li>
							<a href="#<?php echo esc_attr( $tab['id'] ); ?>" class="wpbean-sb-nav-tab-nav-tab" id="<?php echo esc_attr( $tab['id'] ); ?>-tab">
								<?php
								if ( array_key_exists( 'icon', $tab ) ) {
									?>
										<span class="wpbean-sb-tab-nav-svg-image"><span class="wpbean-sb-tab-nav-svg-bg" style="background-image: url(<?php echo esc_attr( $tab['icon'] ); ?>) !important;" aria-hidden="true"></span></span>
									<?php
								}
								?>
								<span class="wpbean-sb-tab-nav-title-and-desc">
									<span class="wpbean-sb-tab-nav-title"><?php echo esc_attr( $tab['title'] ); ?></span>
									<?php
									if ( array_key_exists( 'desc', $tab ) ) {
										?>
											<span class="wpbean-sb-tab-nav-desc"><?php echo wp_kses_post( $tab['desc'] ); ?></span>
										<?php
									}
									?>
								</span>
							</a>
						</li>
						<?php
				}
				?>
			</ul>
		</div>
		<?php
	}

	/**
	 * Show the section settings forms
	 *
	 * @param array $fields The meta fields.
	 * @param ini   $post_id The post ID.
	 * @return void
	 */
	public function show_fields( $fields, $post_id ) {
		?>
		<div class="wpb-sb-tabs-content-wrapper">
			<?php foreach ( $this->settings_sections as $section ) : ?>
				<div id="<?php echo esc_attr( $section['id'] ); ?>" class="wpb-sb-tab-content wpbean-sb-group" style="display: none;">
					<?php
						do_action( 'wsa_form_top_' . $section['id'], $section );

					if ( array_key_exists( $section['id'], $fields ) ) {
						$section_fields = $fields[ $section['id'] ];
						$this->get_meta_fields( $section_fields, $post_id );
					}

						do_action( 'wsa_form_bottom_' . $section['id'], $section );
					?>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}
}