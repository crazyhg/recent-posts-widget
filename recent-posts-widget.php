<?php
/*
 * Plugin Name: Recent Posts Widget (async)
 * Description: Widget to show recent posts asynchronously.
 * Author: hg
 * Version: 0.2.0
 */

class RecentPostsWidget extends WP_Widget
{
    public function __construct()
    {
        $widget_ops =
        parent::__construct(
            'recent-posts-widget',
            'Recent Posts Widget (async)',
            array('classname' => 'recent-posts-widget', 'description' => 'List of recent posts')
        );

        add_action('wp_ajax_nopriv_load_recent_posts', array($this, 'load_recent_posts_callback'));
        add_action('wp_ajax_load_recent_posts', array($this, 'load_recent_posts_callback'));
    }

    protected function set_default_values(&$instance)
    {
        $defaults = array(
            'title' => 'Recent posts',
            'categories' => '',
            'post_limit' => 10,
            'carousel_timeout' => 10,
            'carousel_enabled' => true,
            'ajax_enabled' => true,
            'css_enabled' => true
        );

        foreach ($defaults as $field => $value) {
            if (!isset($instance[$field])) {
                $instance[$field] = $value;
            }
        }
    }

    protected function output_widget_content($instance)
    {
        $args = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC',
            'posts_per_page' => 10,
            'category_name' => $instance['categories']
        );
        $query = new WP_Query( $args );

        while ( $query->have_posts() ) : $query->the_post();
        ?>
            <div class="rpw-item">
                <div class="rpw-post-title">
                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                </div>
                <div class="rpw-comment-count">
                    <p class="disqus-comment-count" data-disqus-url="<?php the_guid() ?>">
                        <?php $number = get_comments_number(); printf(_n('%s Comment', '%s Comments', $number), number_format_i18n($number));?>
                    </p>
                </div>
            </div>
        <?php
        endwhile;

        wp_reset_postdata();
    }

    public function load_recent_posts_callback()
    {
        $number = intval($_POST['widgetNumber']);

        $widgets = get_option('widget_'.$this->id_base);

        if ($widgets !== false) {
            $instance = $widgets[$number];
            $this->output_widget_content($instance);
        }

        wp_die();
    }

    public function widget($args, $instance)
    {
        $this->set_default_values($instance);

        if ($instance['css_enabled']) {
            wp_enqueue_style('recent-posts-widget', plugins_url('recent-posts-widget.css', __FILE__));
        }
        if ($instance['carousel_enabled']) {
            wp_enqueue_script('slick', '//cdn.jsdelivr.net/jquery.slick/1.5.9/slick.min.js', array('jquery'), '1.5.9', true);
        }
        wp_enqueue_script('dotdotdot', '//cdnjs.cloudflare.com/ajax/libs/jQuery.dotdotdot/1.7.4/jquery.dotdotdot.min.js', array('jquery'), '1.7.4', true);

        echo $args['before_widget'];

        echo $args['before_title'];
        echo esc_html($instance['title']);
        echo $args['after_title'];

        echo '<div class="rpw-list" data-widget-number="'.$this->number.'">';

        if ($instance['ajax_enabled']) {
            $carousel_prev_arrow = '<button type="button" class="slick-prev"><i class="dashicons dashicons-arrow-left-alt2"></i>Previous</button>';
            $carousel_next_arrow = '<button type="button" class="slick-next"><i class="dashicons dashicons-arrow-right-alt2"></i>Next</button>';

            wp_enqueue_script('recent-posts-widget', plugins_url('recent-posts-widget.js', __FILE__), array('jquery'), '0.2.0', true);
            wp_localize_script('recent-posts-widget', 'rpwData'.$this->number, array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'carouselTimeout' => $instance['carousel_timeout'],
                'carouselEnabled' => $instance['carousel_enabled'],
                'carouselPrevArrow' => $carousel_prev_arrow,
                'carouselNextArrow' => $carousel_next_arrow,
            ));
        } else {
            $this->output_widget_content($instance);
        }

        echo '</div>';
        if ($instance['carousel_enabled']) {
            echo '<div class="rpw-controls"><button type="button" class="rpw-index"><i class="rpw-index-content"></i></button></div>';
        }

        echo $args['after_widget'];
    }

    public function update($new_instance, $old_instance)
    {
        $instance = $old_instance;

        $instance['title'] = strip_tags($new_instance['title']);
        $instance['post_limit'] = (int) $new_instance['post_limit'];
        $instance['categories'] = strip_tags($new_instance['categories']);
        $instance['carousel_timeout'] = (int) $new_instance['carousel_timeout'];
        $instance['carousel_enabled'] = (bool) $new_instance['carousel_enabled'];
        $instance['ajax_enabled'] = (bool) $new_instance['ajax_enabled'];
        $instance['css_enabled'] = (bool) $new_instance['css_enabled'];

        return $instance;
    }

    public function form($instance)
    {
        $this->set_default_values($instance);
        extract($instance);

        ?>

        <p>
        <label for="<?= $this->get_field_id('title') ?>"><?='Title' ?></label>
        <input id="<?= $this->get_field_id('title') ?>"
               name="<?= $this->get_field_name('title') ?>"
               value="<?= esc_attr($title) ?>"
               type="text" class="widefat" />
        </p>

        <p>
        <label for="<?= $this->get_field_id('post_limit') ?>"><?= 'Posts shown in the widget' ?></label>
        <input id="<?= $this->get_field_id('post_limit') ?>"
               name="<?= $this->get_field_name('post_limit') ?>"
               value="<?= esc_attr($post_limit) ?>"
               type="number" min="1" max="100" class="widefat"/>
        </p>

        <p>
        <label for="<?= $this->get_field_id('categories') ?>"><?='Categories <small>slugs separated by commas</small>' ?></label>
        <input id="<?= $this->get_field_id('categories') ?>"
               name="<?= $this->get_field_name('categories') ?>"
               value="<?= esc_attr($categories) ?>"
               type="text" class="widefat" />
        </p>

        <p>
        <label for="<?= $this->get_field_id('carousel_timeout') ?>"><?= 'Carousel interval <small>in seconds, 0 to disable</small>' ?></label>
        <input id="<?= $this->get_field_id('carousel_timeout') ?>"
               name="<?= $this->get_field_name('carousel_timeout') ?>"
               value="<?= esc_attr($carousel_timeout) ?>"
               type="number" min="0" class="widefat" />
        </p>

        <p>
        <input id="<?= $this->get_field_id('carousel_enabled') ?>"
               name="<?= $this->get_field_name('carousel_enabled') ?>"
                <?php checked($instance[ 'carousel_enabled' ]) ?>
               value="true" type="checkbox" />
        <label for="<?= $this->get_field_id('carousel_enabled') ?>"><?= 'Carousel' ?></label>
        </p>

        <p>
        <input id="<?= $this->get_field_id('ajax_enabled') ?>"
               name="<?= $this->get_field_name('ajax_enabled') ?>"
                <?php checked($instance[ 'ajax_enabled' ]) ?>
               value="true" type="checkbox" />
        <label for="<?= $this->get_field_id('ajax_enabled') ?>"><?= 'Asynchronous loading' ?></label>
        </p>

        <p>
        <input id="<?= $this->get_field_id('css_enabled') ?>"
               name="<?= $this->get_field_name('css_enabled') ?>"
                <?php checked($instance[ 'css_enabled' ]) ?>
               value="true" type="checkbox" />
        <label for="<?= $this->get_field_id('css_enabled') ?>"><?= 'Use widget CSS' ?></label>
        </p>


    <?php

    }

}

add_action('widgets_init', function () {
    register_widget('RecentPostsWidget');
});

?>
