<?php
/*
Plugin Name: Ultimate Posts Widget
Plugin URI: http://wordpress.org/plugins/ultimate-posts-widget/
Description: The ultimate widget for displaying posts, custom post types or sticky posts with an array of options.
Version: 1.9.0
Author: Boston Dell-Vandenberg
Author URI: http://pomelodesign.com
License: GPL2

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if ( !class_exists( 'WP_Widget_Ultimate_Posts' ) ) {

  class WP_Widget_Ultimate_Posts extends WP_Widget {

    function WP_Widget_Ultimate_Posts() {

      $widget_options = array( 
        'classname' => 'widget_ultimate_posts', 
        'description' => __( 'Displays list of posts with an array of options', 'upw' ) 
      );

      $control_options = array();

      $this->WP_Widget( 
        'sticky-posts', 
        __( 'Ultimate Posts', 'upw' ), 
        $widget_options,
        $control_options
      );

      $this->alt_option_name = 'widget_ultimate_posts';

      add_action( 'save_post', array( &$this, 'flush_widget_cache' ) );
      add_action( 'deleted_post', array( &$this, 'flush_widget_cache' ) );
      add_action( 'switch_theme', array( &$this, 'flush_widget_cache' ) );

      if (apply_filters('upw_use_default_css', true) && !is_admin()) {
        add_action('wp_enqueue_scripts', 'upw_enqueue_styles');
      }

      load_plugin_textdomain('upw', false, basename( dirname( __FILE__ ) ) . '/languages' );

    }

    function widget( $args, $instance ) {

      global $post;
      $current_post_id =  $post->ID;

      $cache = wp_cache_get( 'widget_ultimate_posts', 'widget' );

      if ( !is_array( $cache ) )
        $cache = array();

      if ( isset( $cache[$args['widget_id']] ) ) {
        echo $cache[$args['widget_id']];
        return;
      }

      ob_start();
      extract( $args );

      $title = apply_filters( 'widget_title', $instance['title'] );
      $title_link = $instance['title_link'];
      $number = $instance['number'];
      $types = ($instance['types'] ? explode(',', $instance['types']) : '');
      $cats = ($instance['cats'] ? explode(',', $instance['cats']) : '');
      $atcat = $instance['atcat'] ? true : false;
      $thumb_size = $instance['thumb_size'];
      $excerpt_length = $instance['excerpt_length'];
      $excerpt_readmore = $instance['excerpt_readmore'];
      $sticky = $instance['sticky'];
      $order = $instance['order'];
      $orderby = $instance['orderby'];
      $meta_key = $instance['meta_key'];
      $custom_fields = $instance['custom_fields'];
      
      // Sticky posts
      if ($sticky == 'only') {
        $sticky_query = array( 'post__in' => get_option( 'sticky_posts' ) );
      } elseif ($sticky == 'hide') {
        $sticky_query = array( 'post__not_in' => get_option( 'sticky_posts' ) );
      } else {
        $sticky_query = null;
      }

      // If $atcat true and in category
      if ($atcat && is_category()) {
        $cats = get_query_var('cat');
      }

      // If $atcat true and is single post
      if ($atcat && is_single()) {
        $cats = '';
        foreach (get_the_category() as $catt) {
          $cats .= $catt->cat_ID.' ';
        }
        $cats = str_replace(" ", ",", trim($cats));
      }

      //Excerpt more filter
      $new_excerpt_more = create_function('$more', 'return "...";');
      add_filter('excerpt_more', $new_excerpt_more);

      // Excerpt length filter
      $new_excerpt_length = create_function('$length', "return " . $excerpt_length . ";");
      if ( $instance["excerpt_length"] > 0 ) add_filter('excerpt_length', $new_excerpt_length);

      echo $before_widget;
      if ( $title ) {
        echo $before_title;
        if ( $title_link ) echo "<a href='$title_link'>";
        echo $title;
        if ( $title_link ) echo "</a>";
        echo $after_title;
      }

      $args = array(
        'showposts' => $number,
        'order' => $order,
        'orderby' => $orderby,
        'category__in' => $cats,
        'post_type' => $types
      );

      if ($orderby === 'meta_value') {
        $args['meta_key'] = $meta_key;
      }

      if (!empty($sticky_query)) {
        $args[key($sticky_query)] = reset($sticky_query);
      }

      $args = apply_filters('upw_wp_query_args', $args);

      $upw_query = new WP_Query($args);

      if ($instance['template'] === 'custom') {
        $custom_template_path = apply_filters('upw_custom_template_path',  '/upw/' . $instance['template_custom'] . '.php');
        if (locate_template($custom_template_path)) {
          require_once(get_stylesheet_directory() . $custom_template_path);
        } else {
          require_once('templates/standard.php');
        }
      } else {
        require_once('templates/' . $instance['template'] . '.php');
      }

      // Reset the global $the_post as this query will have stomped on it
      wp_reset_postdata();

      echo $after_widget;

      if ($cache) {
        $cache[$args['widget_id']] = ob_get_flush();
      }
      wp_cache_set( 'widget_ultimate_posts', $cache, 'widget' );
    }

    function update( $new_instance, $old_instance ) {
      $instance = $old_instance;

      $instance['title'] = strip_tags( $new_instance['title'] );
      $instance['title_link'] = strip_tags( $new_instance['title_link'] );
      $instance['number'] = strip_tags( $new_instance['number'] );
      $instance['types'] = (isset( $new_instance['types'] )) ? implode(',', (array) $new_instance['types']) : '';
      $instance['cats'] = (isset( $new_instance['cats'] )) ? implode(',', (array) $new_instance['cats']) : '';
      $instance['atcat'] = isset( $new_instance['atcat'] );
      $instance['show_excerpt'] = isset( $new_instance['show_excerpt'] );
      $instance['show_content'] = isset( $new_instance['show_content'] );
      $instance['show_thumbnail'] = isset( $new_instance['show_thumbnail'] );
      $instance['show_date'] = isset( $new_instance['show_date'] );
      $instance['date_format'] = strip_tags( $new_instance['date_format'] );
      $instance['show_title'] = isset( $new_instance['show_title'] );
      $instance['show_author'] = isset( $new_instance['show_author'] );
      $instance['thumb_size'] = strip_tags( $new_instance['thumb_size'] );
      $instance['show_readmore'] = isset( $new_instance['show_readmore']);
      $instance['excerpt_length'] = strip_tags( $new_instance['excerpt_length'] );
      $instance['excerpt_readmore'] = strip_tags( $new_instance['excerpt_readmore'] );
      $instance['sticky'] = $new_instance['sticky'];
      $instance['order'] = $new_instance['order'];
      $instance['orderby'] = $new_instance['orderby'];
      $instance['meta_key'] = $new_instance['meta_key'];
      $instance['show_morebutton'] = isset( $new_instance['show_morebutton'] );
      $instance['morebutton_url'] = strip_tags( $new_instance['morebutton_url'] );
      $instance['morebutton_text'] = strip_tags( $new_instance['morebutton_text'] );
      $instance['show_cats'] = isset( $new_instance['show_cats'] );
      $instance['show_tags'] = isset( $new_instance['show_tags'] );
      $instance['custom_fields'] = strip_tags( $new_instance['custom_fields'] );
      $instance['template'] = strip_tags( $new_instance['template'] );
      $instance['template_custom'] = strip_tags( $new_instance['template_custom'] );

      $this->flush_widget_cache();

      $alloptions = wp_cache_get( 'alloptions', 'options' );
      if ( isset( $alloptions['widget_ultimate_posts'] ) )
        delete_option( 'widget_ultimate_posts' );

      return $instance;

    }

    function flush_widget_cache() {

      wp_cache_delete( 'widget_ultimate_posts', 'widget' );

    }

    function form( $instance ) {

      // Set default arguments
      $instance = wp_parse_args( (array) $instance, array(
        'title' => '',
        'title_link' => '' ,
        'number' => '5',
        'types' => 'post',
        'cats' => '',
        'atcat' => false,
        'thumb_size' => 'thumbnail',
        'excerpt_length' => 10,
        'excerpt_readmore' => __('Read more &rarr;', 'upw'),
        'order' => 'DESC',
        'orderby' => 'date',
        'meta_key' => '',
        'morebutton_text' => __('View More Posts', 'upw'),
        'morebutton_url' => site_url(),
        'sticky' => 'show',
        'show_cats' => false,
        'show_tags' => false,
        'show_title' => false,
        'show_date' => false,
        'date_format' => get_option('date_format') . ' ' . get_option('time_format'),
        'show_author' => false,
        'show_excerpt' => false,
        'show_content' => false,
        'show_readmore' => false,
        'show_thumbnail' => false,
        'custom_fields' => '',
        'show_morebutton' => false,
        'template' => 'legacy',
        'template_custom' => ''
      ) );

      // Or use the instance
      $title  = strip_tags($instance['title']);
      $title_link  = strip_tags($instance['title_link']);
      $number = strip_tags($instance['number']);
      $types  = $instance['types'];
      $cats = $instance['cats'];
      $atcat = $instance['atcat'];
      $thumb_size = $instance['thumb_size'];
      $excerpt_length = strip_tags($instance['excerpt_length']);
      $excerpt_readmore = strip_tags($instance['excerpt_readmore']);
      $order = $instance['order'];
      $orderby = $instance['orderby'];
      $meta_key = $instance['meta_key'];
      $morebutton_text = strip_tags($instance['morebutton_text']);
      $morebutton_url = strip_tags($instance['morebutton_url']);
      $sticky = $instance['sticky'];
      $show_cats = $instance['show_cats'];
      $show_tags = $instance['show_tags'];
      $show_title = $instance['show_title'];
      $show_date = $instance['show_date'];
      $date_format = $instance['date_format'];
      $show_author = $instance['show_author'];
      $show_excerpt = $instance['show_excerpt'];
      $show_content = $instance['show_content'];
      $show_readmore = $instance['show_readmore'];
      $show_thumbnail = $instance['show_thumbnail'];
      $show_morebutton = $instance['show_morebutton'];
      $custom_fields = strip_tags($instance['custom_fields']);
      $template = $instance['template'];
      $template_custom = strip_tags($instance['template_custom']);

      //Let's turn $types and $cats into an array
      $types = explode(',', $types);
      $cats = explode(',', $cats);

      //Count number of post types for select box sizing
      $cpt_types = get_post_types( array( 'public' => true ), 'names' );
      foreach ($cpt_types as $cpt ) {
         $cpt_ar[] = $cpt;
      }
      $n = count($cpt_ar);
      if($n > 10) { $n = 10; }

      //Count number of categories for select box sizing
      $cat_list = get_categories( 'hide_empty=0' );
      foreach ($cat_list as $cat ) {
         $cat_ar[] = $cat;
      }
      $c = count($cat_ar);
      if($c > 10) { $c = 10; }

      ?>

      <style>
        .upw-divider {
          border: 0;
          border-top: 1px solid #DFDFDF;
        }
      </style>

      <p>
        <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title', 'upw' ); ?>:</label>
        <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" />
      </p>

      <p>
        <label for="<?php echo $this->get_field_id( 'title_link' ); ?>"><?php _e( 'Title URL', 'upw' ); ?>:</label>
        <input class="widefat" id="<?php echo $this->get_field_id( 'title_link' ); ?>" name="<?php echo $this->get_field_name( 'title_link' ); ?>" type="text" value="<?php echo $title_link; ?>" />
      </p>

      <p>
        <label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of posts', 'upw' ); ?>:</label>
        <input id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="text" value="<?php echo $number; ?>" size="2" />
      </p>

      <p>
        <input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('show_morebutton'); ?>" name="<?php echo $this->get_field_name('show_morebutton'); ?>" <?php checked( (bool) $show_morebutton, true ); ?> />
        <label for="<?php echo $this->get_field_id('show_morebutton'); ?>"> <?php _e('Show more button', 'upw'); ?></label>
      </p>

      <p>
        <label for="<?php echo $this->get_field_id('morebutton_text'); ?>"><?php _e( 'More button text', 'upw' ); ?>:</label>
        <input class="widefat" type="text" id="<?php echo $this->get_field_id('morebutton_text'); ?>" name="<?php echo $this->get_field_name('morebutton_text'); ?>" value="<?php echo $morebutton_text; ?>" />
      </p>

      <p>
        <label for="<?php echo $this->get_field_id('morebutton_url'); ?>"><?php _e( 'More button URL', 'upw' ); ?>:</label>
        <input class="widefat" type="text" id="<?php echo $this->get_field_id('morebutton_url'); ?>" name="<?php echo $this->get_field_name('morebutton_url'); ?>" value="<?php echo $morebutton_url; ?>" />
      </p>

      <hr class="upw-divider">

      <h4><?php _e('Post Display', 'upw'); ?></h4>

      <p>
        <input class="checkbox" id="<?php echo $this->get_field_id( 'show_title' ); ?>" name="<?php echo $this->get_field_name( 'show_title' ); ?>" type="checkbox" <?php checked( (bool) $show_title, true ); ?> />
        <label for="<?php echo $this->get_field_id( 'show_title' ); ?>"><?php _e( 'Show title', 'upw' ); ?></label>
      </p>

      <p>
        <input class="checkbox" id="<?php echo $this->get_field_id( 'show_date' ); ?>" name="<?php echo $this->get_field_name( 'show_date' ); ?>" type="checkbox" <?php checked( (bool) $show_date, true ); ?> />
        <label for="<?php echo $this->get_field_id( 'show_date' ); ?>"><?php _e( 'Show published date', 'upw' ); ?></label>
      </p>

      <p>
        <label for="<?php echo $this->get_field_id('date_format'); ?>"><?php _e( 'Date Format', 'upw' ); ?>:</label>
        <input class="widefat" type="text" id="<?php echo $this->get_field_id('date_format'); ?>" name="<?php echo $this->get_field_name('date_format'); ?>" value="<?php echo $date_format; ?>" />
      </p>

      <p>
        <input class="checkbox" id="<?php echo $this->get_field_id( 'show_author' ); ?>" name="<?php echo $this->get_field_name( 'show_author' ); ?>" type="checkbox" <?php checked( (bool) $show_author, true ); ?> />
        <label for="<?php echo $this->get_field_id( 'show_author' ); ?>"><?php _e( 'Show post author', 'upw' ); ?></label>
      </p>

      <p>
        <input class="checkbox" id="<?php echo $this->get_field_id( 'show_excerpt' ); ?>" name="<?php echo $this->get_field_name( 'show_excerpt' ); ?>" type="checkbox" <?php checked( (bool) $show_excerpt, true ); ?> />
        <label for="<?php echo $this->get_field_id( 'show_excerpt' ); ?>"><?php _e( 'Show excerpt', 'upw' ); ?></label>
      </p>

      <p>
        <label for="<?php echo $this->get_field_id('excerpt_length'); ?>"><?php _e( 'Excerpt length (in words)', 'upw' ); ?>:</label>
        <input style="text-align: center;" type="text" id="<?php echo $this->get_field_id('excerpt_length'); ?>" name="<?php echo $this->get_field_name('excerpt_length'); ?>" value="<?php echo $excerpt_length; ?>" size="3" />
      </p>

      <p>
        <input class="checkbox" id="<?php echo $this->get_field_id( 'show_content' ); ?>" name="<?php echo $this->get_field_name( 'show_content' ); ?>" type="checkbox" <?php checked( (bool) $show_content, true ); ?> />
        <label for="<?php echo $this->get_field_id( 'show_content' ); ?>"><?php _e( 'Show content', 'upw' ); ?></label>
      </p>

      <p>
        <label for="<?php echo $this->get_field_id('show_readmore'); ?>">
        <input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('show_readmore'); ?>" name="<?php echo $this->get_field_name('show_readmore'); ?>"<?php checked( (bool) $show_readmore, true ); ?> />
        <?php _e( 'Show read more link', 'upw' ); ?>
        </label>
      </p>

      <p>
        <input class="widefat" type="text" id="<?php echo $this->get_field_id('excerpt_readmore'); ?>" name="<?php echo $this->get_field_name('excerpt_readmore'); ?>" value="<?php echo $excerpt_readmore; ?>" />
      </p>

      <?php if ( function_exists('the_post_thumbnail') && current_theme_supports( 'post-thumbnails' ) ) : ?>

        <?php $sizes = get_intermediate_image_sizes(); ?>

        <p>
          <input class="checkbox" id="<?php echo $this->get_field_id( 'show_thumbnail' ); ?>" name="<?php echo $this->get_field_name( 'show_thumbnail' ); ?>" type="checkbox" <?php checked( (bool) $show_thumbnail, true ); ?> />

          <label for="<?php echo $this->get_field_id( 'show_thumbnail' ); ?>"><?php _e( 'Show thumbnail', 'upw' ); ?></label>
        </p>

        <p>
          <select id="<?php echo $this->get_field_id('thumb_size'); ?>" name="<?php echo $this->get_field_name('thumb_size'); ?>" class="widefat">
            <?php foreach ($sizes as $size) : ?>
              <option value="<?php echo $size; ?>"<?php if ($thumb_size == $size) echo ' selected'; ?>><?php echo $size; ?></option>
            <?php endforeach; ?>
          </select>
        </p>

      <?php endif; ?>

      <p>
        <input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('show_cats'); ?>" name="<?php echo $this->get_field_name('show_cats'); ?>" <?php checked( (bool) $show_cats, true ); ?> />
        <label for="<?php echo $this->get_field_id('show_cats'); ?>"> <?php _e('Show post categories', 'upw'); ?></label>
      </p>

      <p>
        <input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('show_tags'); ?>" name="<?php echo $this->get_field_name('show_tags'); ?>" <?php checked( (bool) $show_tags, true ); ?> />
        <label for="<?php echo $this->get_field_id('show_tags'); ?>"> <?php _e('Show post tags', 'upw'); ?></label>
      </p>

      <p>
        <label for="<?php echo $this->get_field_id( 'custom_fields' ); ?>"><?php _e( 'Show Custom Fields (comma separated)', 'upw' ); ?>:</label>
        <input class="widefat" id="<?php echo $this->get_field_id( 'custom_fields' ); ?>" name="<?php echo $this->get_field_name( 'custom_fields' ); ?>" type="text" value="<?php echo $custom_fields; ?>" />
      </p>

      <hr class="upw-divider">

      <h4><?php _e('Filters', 'upw'); ?></h4>

      <p>
        <input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('atcat'); ?>" name="<?php echo $this->get_field_name('atcat'); ?>" <?php checked( (bool) $atcat, true ); ?> />
        <label for="<?php echo $this->get_field_id('atcat'); ?>"> <?php _e('Show posts only from current category', 'upw');?></label>
      </p>

      <p>
        <label for="<?php echo $this->get_field_id('cats'); ?>"><?php _e( 'Categories', 'upw' ); ?>:</label>
        <select name="<?php echo $this->get_field_name('cats'); ?>[]" id="<?php echo $this->get_field_id('cats'); ?>" class="widefat" style="height: auto;" size="<?php echo $c ?>" multiple>
          <?php
          $categories = get_categories( 'hide_empty=0' );
          foreach ($categories as $category ) { ?>
            <option value="<?php echo $category->term_id; ?>" <?php if( in_array($category->term_id, $cats)) { echo 'selected="selected"'; } ?>><?php echo $category->cat_name;?></option>
          <?php } ?>
        </select>
      </p>

      <p>
        <label for="<?php echo $this->get_field_id('types'); ?>"><?php _e( 'Post types', 'upw' ); ?>:</label>
        <select name="<?php echo $this->get_field_name('types'); ?>[]" id="<?php echo $this->get_field_id('types'); ?>" class="widefat" style="height: auto;" size="<?php echo $n ?>" multiple>
          <?php
          $args = array( 'public' => true );
          $post_types = get_post_types( $args, 'names' );
          foreach ($post_types as $post_type ) { ?>
            <option value="<?php echo $post_type; ?>" <?php if( in_array($post_type, $types)) { echo 'selected="selected"'; } ?>><?php echo $post_type;?></option>
          <?php } ?>
        </select>
      </p>

      <p>
        <label for="<?php echo $this->get_field_id('sticky'); ?>"><?php _e( 'Sticky posts', 'upw' ); ?>:</label>
        <select name="<?php echo $this->get_field_name('sticky'); ?>" id="<?php echo $this->get_field_id('sticky'); ?>" class="widefat">
          <option value="show"<?php if( $sticky === 'show') echo ' selected'; ?>><?php _e('Show All Posts', 'upw'); ?></option>
          <option value="hide"<?php if( $sticky == 'hide') echo ' selected'; ?>><?php _e('Hide Sticky Posts', 'upw'); ?></option>
          <option value="only"<?php if( $sticky == 'only') echo ' selected'; ?>><?php _e('Show Only Sticky Posts', 'upw'); ?></option>
        </select>
      </p>

      <hr class="upw-divider">

      <h4><?php _e('Order', 'upw'); ?></h4>

      <p>
        <select name="<?php echo $this->get_field_name('orderby'); ?>" id="<?php echo $this->get_field_id('orderby'); ?>" class="widefat">
          <option value="date"<?php if( $orderby == 'date') echo ' selected'; ?>><?php _e('Published Date', 'upw'); ?></option>
          <option value="title"<?php if( $orderby == 'title') echo ' selected'; ?>><?php _e('Title', 'upw'); ?></option>
          <option value="comment_count"<?php if( $orderby == 'comment_count') echo ' selected'; ?>><?php _e('Comment Count', 'upw'); ?></option>
          <option value="rand"<?php if( $orderby == 'rand') echo ' selected'; ?>><?php _e('Random'); ?></option>
          <option value="meta_value"<?php if( $orderby == 'meta_value') echo ' selected'; ?>><?php _e('Custom Field', 'upw'); ?></option>
        </select>
      </p>

      <p<?php if( $orderby !== 'meta_value') { echo ' style="display:none;"'; } ?>>
        <label for="<?php echo $this->get_field_id( 'meta_key' ); ?>"><?php _e('Custom Field', 'upw'); ?>:</label>
        <input class="widefat" id="<?php echo $this->get_field_id('meta_key'); ?>" name="<?php echo $this->get_field_name('meta_key'); ?>" type="text" value="<?php echo $meta_key; ?>" />
      </p>
      
      <p>
        <select name="<?php echo $this->get_field_name('order'); ?>" id="<?php echo $this->get_field_id('order'); ?>" class="widefat">
          <option value="DESC"<?php if( $order == 'DESC') echo ' selected'; ?>><?php _e('Descending', 'upw'); ?></option>
          <option value="ASC"<?php if( $order == 'ASC') echo ' selected'; ?>><?php _e('Ascending', 'upw'); ?></option>
        </select>
      </p>

      <hr class="upw-divider">

      <h4><?php _e('Template', 'upw'); ?></h4>

      <p>
        <select name="<?php echo $this->get_field_name('template'); ?>" id="<?php echo $this->get_field_id('template'); ?>" class="widefat">
          <option value="legacy"<?php if( $template == 'legacy') echo ' selected'; ?>><?php _e('Legacy', 'upw'); ?></option>
          <option value="standard"<?php if( $template == 'standard') echo ' selected'; ?>><?php _e('Standard', 'upw'); ?></option>
          <option value="custom"<?php if( $template == 'custom') echo ' selected'; ?>><?php _e('Custom', 'upw'); ?></option>
        </select>
      </p>

      <p>
        <label for="<?php echo $this->get_field_id('template_custom'); ?>"><?php _e('Custom Template Name', 'upw'); ?>:</label>
        <input class="widefat" id="<?php echo $this->get_field_id('template_custom'); ?>" name="<?php echo $this->get_field_name('template_custom'); ?>" type="text" value="<?php echo $template_custom; ?>" />
      </p>

      <hr class="upw-divider">

      <p class="credits"><small><?php _e('Developed by', 'upw'); ?> <a href="http://pomelodesign.com"><?php _e('Pomelo Design', 'upw'); ?></a></small></p>

      <?php if ( $instance ) { ?>

        <script>

          jQuery(document).ready(function($){

            var show_excerpt = $("#<?php echo $this->get_field_id( 'show_excerpt' ); ?>");
            var show_readmore = $("#<?php echo $this->get_field_id( 'show_readmore' ); ?>");
            var show_thumbnail = $("#<?php echo $this->get_field_id( 'show_thumbnail' ); ?>");
            var show_date = $("#<?php echo $this->get_field_id( 'show_date' ); ?>");
            var date_format = $("#<?php echo $this->get_field_id( 'date_format' ); ?>").parents('p');
            var excerpt_length = $("#<?php echo $this->get_field_id( 'excerpt_length' ); ?>").parents('p');
            var excerpt_readmore = $("#<?php echo $this->get_field_id( 'excerpt_readmore' ); ?>").parents('p');
            var thumb_size = $("#<?php echo $this->get_field_id( 'thumb_size' ); ?>").parents('p');
            var show_morebutton = $("#<?php echo $this->get_field_id( 'show_morebutton' ); ?>");
            var morebutton_text = $("#<?php echo $this->get_field_id( 'morebutton_text' ); ?>").parents('p');
            var morebutton_url = $("#<?php echo $this->get_field_id( 'morebutton_url' ); ?>").parents('p');
            var order = $("#<?php echo $this->get_field_id('orderby'); ?>");
            var meta_key = $("#<?php echo $this->get_field_id( 'meta_key' ); ?>").parents('p');
            var template = $("#<?php echo $this->get_field_id('template'); ?>");
            var template_custom = $("#<?php echo $this->get_field_id('template_custom'); ?>").parents('p');
            <?php
            // Use PHP to determine if not checked and hide if so
            // jQuery method was acting up
            if ( !$show_excerpt ) {
              echo 'excerpt_length.hide();';
            }
            if ( !$show_readmore ) {
              echo 'excerpt_readmore.hide();';
            }
            if ( !$show_date ) {
              echo 'date_format.hide();';
            }
            if ( !$show_thumbnail ) {
              echo 'thumb_size.hide();';
            }
            if ( !$show_morebutton ) {
              echo 'morebutton_text.hide();';
              echo 'morebutton_url.hide();';
            }
            if ( $orderby !== 'meta_value' ) {
                echo 'meta_key.hide();';
            }
            if ( $template !== 'custom' ) {
                echo 'template_custom.hide();';
            }
            ?>

            // Toggle excerpt length on click
            show_excerpt.click(function(){

              if ( $(this).is(":checked") ) {
                excerpt_length.show("fast");
              } else {
                excerpt_length.hide("fast");
              }

             });

            // Toggle excerpt length on click
            show_readmore.click(function(){

              if ( $(this).is(":checked") ) {
                excerpt_readmore.show("fast");
              } else {
                excerpt_readmore.hide("fast");
              }

             });

            // Toggle date format on click
            show_date.click(function(){

              if ( $(this).is(":checked") ) {
                date_format.show("fast");
              } else {
                date_format.hide("fast");
              }

             });

            // Toggle excerpt length on click
            show_thumbnail.click(function(){

              if ( $(this).is(":checked") ) {
                thumb_size.show("fast");
              } else {
                thumb_size.hide("fast");
              }

            });

            // Toggle more button on click
            show_morebutton.click(function(){

              if ( $(this).is(":checked") ) {
                morebutton_text.show("fast");
                morebutton_url.show("fast");
              } else {
                morebutton_text.hide("fast");
                morebutton_url.hide("fast");
              }

            });

            // Show or hide custom field meta_key value on order change
            order.change(function(){

              if ( $(this).val() === "meta_value") {
                meta_key.show("fast");
              } else {
                meta_key.hide("fast");
              }

             });

            // Show or hide custom template field
            template.change(function(){

              if ( $(this).val() === "custom") {
                template_custom.show("fast");
              } else {
                template_custom.hide("fast");
              }

             });

          });

        </script>

      <?php

      }

    }

  }

  function init_WP_Widget_Ultimate_Posts() {
    register_widget( 'WP_Widget_Ultimate_Posts' );
  }

  function upw_enqueue_styles() {
    wp_register_style('upw_styles_standard', plugins_url('css/standard.min.css', __FILE__));
    wp_enqueue_style('upw_styles_standard');
  }

  add_action( 'widgets_init', 'init_WP_Widget_Ultimate_Posts' );
}
