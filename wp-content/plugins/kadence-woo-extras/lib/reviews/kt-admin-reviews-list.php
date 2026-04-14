<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

global $kt_reviews;

if (!class_exists('KT_Admin_Reviews_List')) {

    class KT_Admin_Reviews_List extends WP_List_Table {
        private $kt_reviews;
        /**
         * Construct
         */
        public function __construct() {
            global $kt_reviews;
            $this->kt_reviews = $kt_reviews;
            //Set parent defaults
            parent::__construct(array(
                    'singular' => 'review', //singular name of the listed records
                    'plural' => 'reviews', //plural name of the listed records
                    'ajax' => false //does this table support ajax?
                )
            );
        }

        /**
         * Returns columns available in table
         */
        public function get_columns() {
            $columns = array(
                'cb' => '<input type="checkbox" />',
                'review-text' => __('Review', 'kadence-woo-extras'),
                'review-author' => __('Author', 'kadence-woo-extras'),
                'review-date' => __('Date', 'kadence-woo-extras'),
                'review-votes' => __('Votes', 'kadence-woo-extras'),
                'review-rating' => __('Rating', 'kadence-woo-extras'),
                'product' => __('Product', 'kadence-woo-extras')
            );

            return apply_filters('kt_reviews_columns', $columns);
        }

        protected function row_actions($actions, $always_visible = false) {
            $action_count = count($actions);
            $i = 0;

            if (!$action_count) {
                return '';
            }

            $out = '<div class="' . ($always_visible ? 'row-actions visible' : 'row-actions') . '">';
            foreach ($actions as $action => $link) {
                ++$i;
                ($i == $action_count) ? $sep = '' : $sep = ' | ';
                $out .= "<span class='$action'>$link$sep</span>";
            }
            $out .= '</div>';

            return $out;
        }

        private function get_params_for_current_view($args) {
            //  Start the filters array, selecting the review post type
            $params = array(
                'post_type' => 'kt_reviews',
                'suppress_filters' => false
            );

            //  Show a single page or all items
            $params['numberposts'] = -1;
            if (isset($args['page']) && ($args['page'] > 0) && isset($args['items_for_page']) && ($args['items_for_page'] > 0)) {
                //  set number of posts and offset
                $current_page = $args['page'];
                $items_for_page = $args['items_for_page'];
                $offset = ($current_page * $items_for_page) - $items_for_page;
                $params['offset'] = $offset;
                $params['numberposts'] = $items_for_page;

            } else {
                $params['offset'] = 0;
            }
            //  Choose post status
            if (isset($args['post_status']) && ("all" != $args['post_status'])) {
                $params['post_status'] = $args['post_status'];
            }

            if (isset($args['post_parent']) && ($args['post_parent'] >= 0)) {
                $params['post_parent'] = $args['post_parent'];
            }

            $order = isset($args['order']) ? $args['order'] : 'DESC';
            $params['order'] = $order;

            if (isset($args['orderby'])) {
                switch ($order_by = $args['orderby']) {
                    case 'review-rating' :
                        $params['meta_key'] = $this->kt_reviews->review_meta_rating;
                        $params['orderby'] = 'meta_value_num';
                        break;

                    case 'review-date' :
                        $params['orderby'] = 'post_date';
                        break;

                    default :
                        $params = apply_filters("kt_reviews_column_sort", $params, $order_by);
                }
            }

            if (isset($args['review_status'])) {

                switch ($args['review_status']) {
                    case 'all' :
                        break;

                    case 'trash' :
                        $params['post_status'] = 'trash';

                        break;

                    case 'not_approved' :
                        $params['meta_query'][] = array(
                            'key' => $this->kt_reviews->review_meta_approved,
                            'value' => 1,
                            'compare' => '!=',
                            'type' => 'numeric'
                        );
                        break;

                    default :
                        $params = apply_filters('kt_reviews_filter_view', $params, $args['review_status']);
                }
            }

            return $params;
        }

        public function filter_reviews_by_search_term($where) {
            $filter_content = isset($_GET["s"]) ? $_GET["s"] : '';
            $terms = explode("+", $filter_content);
            global $wpdb;
            $where_clause = '';
            foreach ($terms as $term) {
                if (!empty($where_clause)) {
                    $where_clause .= " OR ";
                }
                $where_clause .= "( {$wpdb->prefix}posts.post_content LIKE '%$term%' ) or ({$wpdb->prefix}posts.post_title like '%$term%') ";
            }

            $where = "$where AND ($where_clause)";

            return $where;
        }

        /**
         * Perform custom bulk actions, if there are some
         */
        public function process_bulk_action() {
            switch ($this->current_action()) {

                case 'untrash' :
                    foreach ($_GET['reviews'] as $review_id) {
                        $my_post = array(
                            'ID' => $review_id,
                            'post_status' => 'publish'
                        );

                        // Update the post into the database
                        wp_update_post($my_post);
                        $approve = get_post_meta( $review_id, $this->kt_reviews->review_meta_approved, true );
                        $comment_id = get_post_meta( $review_id, $this->kt_reviews->review_meta_comment_id, true );
                        $comment = get_comment( $comment_id );
                        if($comment != null) {
                            if($approve == 1){
                                $status = 'approve';
                            } else {
                                $status = 'hold';
                            }
                            wp_set_comment_status($comment_id, $status);
                        }
                    }

                    break;

                case 'trash' :
                    foreach ($_GET['reviews'] as $review_id) {
                        $my_post = array(
                            'ID' => $review_id,
                            'post_status' => 'trash'
                        );

                        // Update the post into the database
                        wp_update_post($my_post);

                        $comment_id = get_post_meta( $review_id, $this->kt_reviews->review_meta_comment_id, true );
                        $comment = get_comment( $comment_id );
                        if($comment != null) {
                            $status = 'trash';
                            wp_set_comment_status($comment_id, $status);
                        }
                    }

                    break;

                case 'delete' :
                    foreach ($_GET['reviews'] as $review_id) {
                        $comment_id = get_post_meta( $review_id, $this->kt_reviews->review_meta_comment_id, true );
                        wp_delete_post($review_id);
                        $comment = get_comment( $comment_id );
                        if($comment != null) {
                            wp_delete_comment($comment_id, true);
                        }
                    }

                    break;

                case 'approve' :
                    foreach ($_GET['reviews'] as $review_id) {
                        update_post_meta($review_id, $this->kt_reviews->review_meta_approved, 1);

                        $comment_id = get_post_meta( $review_id, $this->kt_reviews->review_meta_comment_id, true );
                        $comment = get_comment( $comment_id );
                        if($comment != null) {
                            $status = 'approve';
                            wp_set_comment_status($comment_id, $status);
                        }
                    }

                    break;

                case 'unapprove' :
                    foreach ($_GET['reviews'] as $review_id) {
                        update_post_meta($review_id, $this->kt_reviews->review_meta_approved, 0);
                        $comment_id = get_post_meta( $review_id, $this->kt_reviews->review_meta_comment_id, true );
                        $comment = get_comment( $comment_id );
                        if($comment != null) {
                            $status = 'hold';
                            wp_set_comment_status($comment_id, $status);
                        }
                    }

                    break;

                default :
                    if (isset($_GET['reviews'])) {
                        do_action('kt_reviews_process_bulk_actions', $this->current_action(), $_GET['reviews']);
                    }
            }
        }

        public function kt_prepare_items() {
            $this->process_bulk_action();

            // sets pagination arguments
            $current_page = absint($this->get_pagenum());

            // sets columns headers
            $columns = $this->get_columns();
            $hidden = array();
            $sortable = $this->get_sortable_columns();
            $this->_column_headers = array($columns, $hidden, $sortable);

            $review_status = isset($_GET["status"]) ? $_GET["status"] : 'all';
            $orderby = isset($_GET["orderby"]) ? $_GET["orderby"] : '';
            $order = isset($_GET["order"]) ? $_GET["order"] : 'desc';

            //  Start the filters array, selecting the review post type
            $params = array(
                'post_type' => 'kt_reviews',
                'items_for_page' => 20,
                'review_status' => $review_status,
                'orderby' => $orderby,
                'order' => $order
            );

            //  retrieve the number of items for the current filters
            $args = $this->get_params_for_current_view($params);
            $args['fields'] = 'ids';
            $total_items = count(get_posts($args));

            //  retrieve only a page for the current filter
            $params['page'] = $current_page;
            $args = $this->get_params_for_current_view($params);

            $filter_content = isset($_GET["s"]) ? $_GET["s"] : '';
            if (!empty($filter_content)) {
                //  Add a filter to alter WHERE clause on following get_posts call
                add_filter('posts_where', array($this, 'filter_reviews_by_search_term'));
            }

            $this->items = get_posts($args);

            //remove the previous filter, not needed anymore
            remove_filter('posts_where', array($this, 'filter_reviews_by_search_term'));

            $total_pages = ceil($total_items / 20);

            // Set the pagination
            $this->set_pagination_args(array(
                'total_items' => $total_items,
                'per_page' => 20,
                'total_pages' => $total_pages
            ));
        }


        public function get_sortable_columns() {

            $columns = array(

                'review-rating' => array('review-rating', false),
                'review-date' => array('review-date', false),
            );

            return apply_filters('kt_reviews_sortable_custom_columns', $columns);
        }

        public function get_bulk_actions() {
            $actions = array();

            $actions['untrash'] = __('Restore', 'kadence-woo-extras');
            $actions['trash'] = __('Move to Trash', 'kadence-woo-extras');

            $actions['delete'] = __('Delete permanently', 'kadence-woo-extras');
            $actions['approve'] = __('Approve reviews', 'kadence-woo-extras');
            $actions['unapprove'] = __('Unapprove reviews', 'kadence-woo-extras');

            return apply_filters('kt_reviews_bulk_actions', $actions);
        }

        protected function get_views() {
            $views = array(
                'all' => __('All', 'kadence-woo-extras'),
                'trash' => __('Trashed', 'kadence-woo-extras'),
                'not_approved' => __('Not approved', 'kadence-woo-extras')
            );

            $views = apply_filters('kt_reviews_table_views', $views);

            $current_view = $this->get_current_view();
            $args = array('status' => 0);

            $args['user_id'] = get_current_user_id();

            // merge Unpaid with Processing
            //$views['unpaid'] .= '/' . $views['processing'];
            unset($views['processing']);

            foreach ($views as $id => $view) {
                //  number of items for the view

                $args = $this->get_params_for_current_view(array(
                    'review_status' => $id
                ));
                $args['fields'] = 'ids';

                //  retrieve the number of items for the current filters
                $total_items = count(get_posts($args));

                $href = esc_url(add_query_arg('status', $id));
                $class = $id == $current_view ? 'current' : '';
                $args['status'] = 'unpaid' == $id ? array($id, 'processing') : $id;
                $views[$id] = sprintf("<a href='%s' class='%s'>%s <span class='count'>(%d)</span></a>", $href, $class, $view, $total_items);
            }

            return $views;
        }

        public function column_default($review, $column_name){

            switch ($column_name) {

                case 'review-text':

                    $post = get_post($review->ID);
                    if (empty($post->post_title) && empty($post->post_content)) {
                        return;
                    }

                    $edit_link = get_edit_post_link($review->ID);
                    echo '<a class="row-title" href="' . $edit_link . '">';

                    if (!empty($post->post_title)) {

                        echo '<span class="review-title">' . wc_trim_string($post->post_title, 80) . '</span>';
                        echo "<br>";
                    }

                    if (!empty($post->post_content)) {

                        echo '<span class="review-content">' . wc_trim_string($post->post_content, 80) . '</span>';
                    }

                    echo '</a>';

                    $post_type_object = get_post_type_object('kt_reviews');

                    if ('trash' == $post->post_status) {
                        $actions['untrash'] = "<a title='" . esc_attr__('Restore this item from the trash', 'kadence-woo-extras') . "' href='" . $this->kt_reviews->untrash_review_url($post) . "'>" . __('Restore', 'kadence-woo-extras') . "</a>";
                    } elseif (EMPTY_TRASH_DAYS) {
                        $actions['trash'] = "<a class='submitdelete' title='" . esc_attr__('Move this item to the trash', 'kadence-woo-extras') . "' href='" . $this->kt_reviews->trash_review_url($post) . "'>" . __('Trash', 'kadence-woo-extras') . "</a>";
                    }
                    if ('trash' == $post->post_status || !EMPTY_TRASH_DAYS) {
                        $actions['delete'] = "<a class='submitdelete' title='" . esc_attr__('Delete this item permanently', 'kadence-woo-extras') . "' href='" . $this->kt_reviews->delete_review_url($post) . "'>" . __('Delete permanently', 'kadence-woo-extras') . "</a>";
                    }

                    $actions = apply_filters('kt_reviews_row_actions', $actions, $post);

                    echo $this->row_actions($actions);


                    break;

                case 'review-rating':
                    if (0 == $review->post_parent) {
                        $rating = get_post_meta($review->ID, $this->kt_reviews->review_meta_rating, true);
                        echo '<div class="woocommerce">';
                        echo '<div class="star-rating" title="' . sprintf(__("Rated %d out of 5", 'kadence-woo-extras'), $rating) . '">';
                        echo '<span style="width:' . (($rating / 5) * 100) . '%"><strong>' . $rating . '</strong> ' . __("out of 5", 'kadence-woo-extras') . ' </span>';
                        echo '</div>';
                        echo '</div>';
                    }

                    break;
                case 'review-votes':
                    if (0 == $review->post_parent) {
                        $upvotes = get_post_meta($review->ID, $this->kt_reviews->review_meta_upvotes_count, true);
                        $downvotes = get_post_meta($review->ID, $this->kt_reviews->review_meta_downvotes_count, true);
                        echo '<div class="woocommerce">';
                        echo '<div class="kt-votes" title="Up Votes">';
                        echo '<span class="kt-upvotes">('.$upvotes.')</span>';
                        echo '<span class="kt-downvotes">('.$downvotes.')</span>';
                        echo '</div>';
                        echo '</div>';
                    }

                    break;

                case 'product':

                    $product_id = get_post_meta($review->ID, $this->kt_reviews->review_meta_product_id, true);

                    echo get_the_title($product_id) . "<br>";

                    if (current_user_can('edit_post', $product_id)) {
                        echo "<a class='edit-product-review' href='" . get_edit_post_link($product_id) . "'>" . __("Edit", 'kadence-woo-extras') . '</a>';
                    }

                    echo "<a class='view-product-review' href='" . get_permalink($product_id) . "' target='_blank'>" . __("View", 'kadence-woo-extras') . '</a>';

                    break;

                case 'review-author':
                	$link = false;
                    if ($review->post_author) {
                        $user = get_userdata($review->post_author);
                        $link = get_edit_user_link($review->post_author);
                        $author_name = $user ? $user->display_name : __('Anonymous', 'kadence-woo-extras');
                    } else {
                        $user = get_post_meta($review->ID, $this->kt_reviews->review_meta_review_author);

                        if ($user) {
                            $author_name = $user ? $user : __('Anonymous', 'kadence-woo-extras');
                        }
                    }
                    if($link) {
                    	echo '<a href="'.esc_url($link).'">';
                    }
                    if(is_array($author_name)) {
                        echo $author_name[0];
                    } else {
                        echo $author_name;
                    }
                    if($link) {
                    	echo '</a>';
                    }

                    break;

                case 'review-date':
                    $t_time = get_the_time(__('Y/m/d g:i:s a'));
                    $m_time = $review->post_date;
                    $time = get_post_time('G', true, $review);

                    $time_diff = time() - $time;

                    if ($time_diff > 0 && $time_diff < DAY_IN_SECONDS) {
                        $h_time = sprintf(__('%s ago'), human_time_diff($time));
                    } else {
                        $h_time = mysql2date(__('Y/m/d'), $m_time);
                    }

                    echo '<abbr title="' . $t_time . '">' . $h_time . '</abbr>';
                    break;

                default :
                    do_action('kt_reviews_show_advanced_reviews_columns', $column_name, $review->ID);
            }

            return null;
        }

        public function column_cb($rec) {

            return sprintf(
                '<input type="checkbox" name="%1$s[]" value="%2$s" />',
                $this->_args['plural'], //Let's simply repurpose the table's plural label
                $rec->ID //The value of the checkbox should be the record's id
            );
        }

        public function no_items() {
            _e('No reviews found.', 'kadence-woo-extras');
        }

        public function get_current_view() {
            return empty($_GET['status']) ? 'all' : $_GET['status'];
        }

        protected function display_tablenav($which) {
            if ('top' == $which) {
                wp_nonce_field('bulk-' . $this->_args['plural']);
            }
            ?>
            <div class="tablenav <?php echo esc_attr($which); ?>">

                <div class="alignleft actions bulkactions">
                    <?php $this->bulk_actions($which); ?>
                </div>
                <?php
                $this->extra_tablenav($which);
                $this->pagination($which);
                ?>

                <br class="clear"/>
            </div>
            <?php
        }

        public function single_row($item) {
            $approved = 1 == get_post_meta($item->ID, $this->kt_reviews->review_meta_approved, true);
            if (!$approved) {
                echo '<tr class="review-unapproved">';
            } else {
                echo '<tr>';
            }

            $this->single_row_columns($item);
            echo '</tr>';
        }
    }
}

