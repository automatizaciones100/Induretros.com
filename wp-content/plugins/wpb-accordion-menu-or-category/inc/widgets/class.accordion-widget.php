<?php

if (! defined('ABSPATH')) {
	exit;
}

/**
 * New Accordion Widget.
 */
class WPB_Accordion_Menu_Widget extends WP_Widget
{

	/**
	 * Constructs the new widget.
	 *
	 * @see WP_Widget::__construct()
	 */
	public function __construct()
	{
		parent::__construct(
			'wpb_wmca_accordion_widget',
			esc_html__('WPB Accordion', 'wpb-accordion-menu-or-category'),
			array('description' => esc_html__('New widget for WPB Accordion.', 'wpb-accordion-menu-or-category'))
		);
	}

	/**
	 * The widget's HTML output.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Display arguments including before_title, after_title,
	 *                        before_widget, and after_widget.
	 * @param array $instance The settings for the particular instance of the widget.
	 */
	public function widget($args, $instance)
	{

		echo wp_kses_post($args['before_widget']);

		if (isset($instance['title'])) {
			echo wp_kses_post($args['before_title']) . wp_kses_post(apply_filters('widget_title', $instance['title'])) . wp_kses_post($args['after_title']);
		}

		echo do_shortcode('[wpb_wmca_accordion_pro id="' . esc_attr($instance['id']) . '"]');

		echo wp_kses_post($args['after_widget']);
	}

	/**
	 * Output the admin widget options form HTML.
	 *
	 * @param array $instance The current widget settings.
	 * @return string The HTML markup for the form.
	 */
	public function form($instance)
	{
		$shortcode_items = get_posts(
			array(
				'post_type'      => 'wpb_wmca_shortcodes',
				'post_status'    => 'publish',
				'posts_per_page' => '-1',
			)
		);

		$title = isset($instance['title']) ? $instance['title'] : '';
		$id    = isset($instance['id']) ? $instance['id'] : '';
?>
		<p>
			<label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php esc_html_e('Title:', 'wpb-accordion-menu-or-category'); ?></label>
			<input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_html($title); ?>">
		</p>

		<p>
			<?php if (! empty($shortcode_items) && isset($shortcode_items)) : ?>
				<label for="<?php echo esc_attr($this->get_field_id('id')); ?>"><?php esc_html_e('Select a ShortCode', 'wpb-accordion-menu-or-category'); ?></label>
				<select class="widefat" id="<?php echo esc_attr($this->get_field_id('id')); ?>" name="<?php echo esc_attr($this->get_field_name('id')); ?>">
					<option><?php esc_html_e('Select a ShortCode', 'wpb-accordion-menu-or-category'); ?></option>
					<?php
					foreach ($shortcode_items as $shortcode_item) {
						printf('<option value="%s" %s>%s</option>', esc_attr($shortcode_item->ID), selected($id, $shortcode_item->ID, false), esc_html($shortcode_item->post_title));
					}
					?>
				</select>
			<?php else : ?>
				<?php printf('<span>%s</span><span><a href="%s">%s</a></span>', esc_html__('First, add some ShortCodes. ', 'wpb-accordion-menu-or-category'), esc_url(admin_url('/post-new.php?post_type=wpb_wmca_shortcodes', false)), esc_html__('Go to the accordion ShortCode builder', 'wpb-accordion-menu-or-category')); ?>
			<?php endif; ?>
		</p>
<?php
	}

	/**
	 * The widget update handler.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance The new instance of the widget.
	 * @param array $old_instance The old instance of the widget.
	 * @return array The updated instance of the widget.
	 */
	public function update($new_instance, $old_instance)
	{
		$instance          = array();
		$instance['title'] = (! empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
		$instance['id']    = (! empty($new_instance['id'])) ? absint($new_instance['id']) : '';
		return $instance;
	}
}
