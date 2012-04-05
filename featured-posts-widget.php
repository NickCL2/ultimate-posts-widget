<?php

/*
Plugin Name: Featured Posts Widget
Plugin URI: http://www.pomelodesign.com/donate
Description: A simple widget that will display a list of your sticky posts with the featured image, title and excerpt.
Author: Boston Dell-Vandenberg
Version: 1.0
Author URI: http://www.bostondv.com
*/

if ( !class_exists( 'WP_Widget_Featured_Posts' ) ) {
	
	class WP_Widget_Featured_Posts extends WP_Widget {
	
		function WP_Widget_Featured_Posts() {
			
			$widget_ops = array( 'classname' => 'widget_featured_posts', 'description' => __( 'Featured posts on your site' ) );
			$this->WP_Widget( 'sticky-posts', __( 'Featured Posts' ), $widget_ops );
			$this->alt_option_name = 'widget_featured_posts';
	
			add_action( 'save_post', array( &$this, 'flush_widget_cache' ) );
			add_action( 'deleted_post', array( &$this, 'flush_widget_cache' ) );
			add_action( 'switch_theme', array( &$this, 'flush_widget_cache' ) );
			
		}
	
		function widget( $args, $instance ) {

			function get_image_path($src) {
				global $blog_id;
				if(isset($blog_id) && $blog_id > 0) {
					$imageParts = explode('/files/' , $src);
					if(isset($imageParts[1])) {
						$src = '/blogs.dir/' . $blog_id . '/files/' . $imageParts[1];
					}
				}
				return $src;
			}
			
			$cache = wp_cache_get( 'widget_featured_posts', 'widget' );
	
			if ( !is_array( $cache ) )
				$cache = array();
	
			if ( isset( $cache[$args['widget_id']] ) ) {
				echo $cache[$args['widget_id']];
				return;
			}
	
			ob_start();
			extract( $args );
	
			if ( isset( $instance['title'] ) ) $title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );
			
			if ( !$number = (int) $instance['number'] )
				$number = 1;
			else if ( $number < 1 )
				$number = 1;
			else if ( $number > 5 )
				$number = 5;

			if( !$thumb_h =  absint($instance['thumb_h'] ))  $thumb_h = 50;
			if( !$thumb_w =  absint($instance['thumb_w'] ))  $thumb_w = 50;
			if( !$excerpt_length = absint( $instance['excerpt_length'] ) ) $excerpt_length = 10;
			if( !$excerpt_readmore = $instance['excerpt_readmore'] )  $excerpt_readmore = 'Read more &rarr;';

			//Excerpt more filter
			$new_excerpt_more = create_function('$more', 'return " ";');	
			add_filter('excerpt_more', $new_excerpt_more);
			
			// Excerpt length filter
			$new_excerpt_length = create_function('$length', "return " . $excerpt_length . ";");
			if ( $instance["excerpt_length"] > 0 ) add_filter('excerpt_length', $new_excerpt_length);
	
			$r = new WP_Query( 
				array( 
					'showposts' => $number, 
					'nopaging' => 0, 
					'post_status' => 'publish', 
					'caller_get_posts' => 1, 
					'post__in' => get_option( 'sticky_posts' ),
				) 
			);
			
			if ( $r->have_posts() ) :
				
				echo $before_widget;
				if ( $title ) echo $before_title . $title . $after_title;
				echo '<ul>';
				
				while ( $r->have_posts() ) : $r->the_post(); ?>
					
					<li>

						<?php
							if ( function_exists('the_post_thumbnail') &&
									 current_theme_supports("post-thumbnails") &&
									 $instance["show_thumbnail"] &&
									 has_post_thumbnail() ) :
							$thumbnail = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID),'full');
							$plugin_dir = 'featured-posts-widget';
						?>

						<div class="fpw-image">
							<a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>">
								<img src="<?php echo plugin_dir_url( __FILE__ ) . 'thumb.php?src='. get_image_path($thumbnail[0]) .'&h='.$thumb_h.'&w='.$thumb_w.'&&zc=2'; ?>" alt="<?php the_title_attribute(); ?>" width="<?php echo $thumb_w; ?>" height="<?php echo $thumb_h; ?>" />
							</a>
						</div>

						<?php endif; ?>

						<div class="fpw-content">
							
							<?php if ( get_the_title() ) : ?>
								<a class="post-title" href="<?php the_permalink(); ?>" title="<?php echo esc_attr( get_the_title() ? get_the_title() : get_the_ID() ); ?>">
									<?php the_title(); ?>
								</a>
							<?php endif; ?>

							<?php if ( $instance['show_date'] ) : ?>
								<p class="post-date"><?php the_time("j M Y"); ?></p>
							<?php endif; ?>

							<?php if ( $instance['show_excerpt'] ) :
	              if ( $instance['readmore'] ) : $linkmore = ' <a href="'.get_permalink().'" class="more-link">'.$excerpt_readmore.'</a>'; else: $linkmore =''; endif; ?>
								<p class="post-excerpt"><?php echo get_the_excerpt() . $linkmore; ?></p>
							<?php endif; ?>

						</div>

					</li>
					
				<?php
				endwhile;
				echo '</ul>';
				echo $after_widget;
				
				// Reset the global $the_post as this query will have stomped on it
				wp_reset_postdata();
	
			endif;
	
			$cache[$args['widget_id']] = ob_get_flush();
			wp_cache_set( 'widget_featured_posts', $cache, 'widget' );
		}
	
		function update( $new_instance, $old_instance ) {
			
			$instance = $old_instance;
			$instance['title'] = strip_tags( $new_instance['title'] );
			$instance['number'] = (int) $new_instance['number'];
			$instance["show_excerpt"] = esc_attr($new_instance["show_excerpt"]);
			$instance["show_thumbnail"] = esc_attr($new_instance["show_thumbnail"]);
			$instance['show_date'] = esc_attr($new_instance['show_date']);
			$instance["thumb_w"] = absint($new_instance["thumb_w"]);
			$instance["thumb_h"] = absint($new_instance["thumb_h"]);
			$instance["show_readmore"] = esc_attr($new_instance["show_readmore"]);
			$instance["excerpt_length"]=absint($new_instance["excerpt_length"]);
			$instance["excerpt_readmore"]=esc_attr($new_instance["excerpt_readmore"]);
			
			$this->flush_widget_cache();
	
			$alloptions = wp_cache_get( 'alloptions', 'options' );
			if ( isset( $alloptions['widget_featured_posts'] ) )
				delete_option( 'widget_featured_posts' );
	
			return $instance;
			
		}
	
		function flush_widget_cache() {
			
			wp_cache_delete( 'widget_featured_posts', 'widget' );
			
		}
	
		function form( $instance ) {
			
			$title = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
			if ( !isset($instance['number']) || !$number = (int) $instance['number'] ) $number = 1;
			$thumb_h = isset($instance['thumb_h']) ? absint($instance['thumb_h']) : 100;
			$thumb_w = isset($instance['thumb_w']) ? absint($instance['thumb_w']) : 100;
			$excerpt_length = isset($instance['excerpt_length']) ? absint($instance['excerpt_length']) : 10;
			$excerpt_readmore = isset($instance['excerpt_readmore']) ? esc_attr($instance['excerpt_readmore']) : 'Read more &rarr;';
			?>

			<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></p>
	
			<p><label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of posts to show:' ); ?></label>
			<input id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="text" value="<?php echo $number; ?>" size="3" /></p>

			<p>
				<input class="checkbox" id="<?php echo $this->get_field_id( 'show_date' ); ?>" name="<?php echo $this->get_field_name( 'show_date' ); ?>" type="checkbox" <?php checked( (bool) $instance["show_date"], true ); ?> />
				<label for="<?php echo $this->get_field_id( 'show_date' ); ?>"><?php _e( 'Show date?' ); ?></label>
			</p>

			<p>
				<input class="checkbox" id="<?php echo $this->get_field_id( 'show_excerpt' ); ?>" name="<?php echo $this->get_field_name( 'show_excerpt' ); ?>" type="checkbox" <?php checked( (bool) $instance["show_excerpt"], true ); ?> />
				<label for="<?php echo $this->get_field_id( 'show_excerpt' ); ?>"><?php _e( 'Show excerpt?' ); ?></label>
			</p>

			<p><label for="<?php echo $this->get_field_id("excerpt_length"); ?>"><?php _e( 'Excerpt length (in words):' ); ?></label>
        <input style="text-align: center;" type="text" id="<?php echo $this->get_field_id("excerpt_length"); ?>" name="<?php echo $this->get_field_name("excerpt_length"); ?>" value="<?php echo $excerpt_length; ?>" size="3" />
      </p>

			<p>
				<label for="<?php echo $this->get_field_id('show_readmore'); ?>">
				<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id("show_readmore"); ?>" name="<?php echo $this->get_field_name("show_readmore"); ?>"<?php checked( (bool) $instance["show_readmore"], true ); ?> />
				<?php _e( 'Include read more link in excerpt' ); ?>
				</label>
			</p>
				
			<p>
				<label for="<?php echo $this->get_field_id('excerpt_readmore'); ?>"><?php _e( 'Excerpt read more text:' ); ?></label>
				<input class="widefat" type="text" id="<?php echo $this->get_field_id('excerpt_readmore'); ?>" name="<?php echo $this->get_field_name("excerpt_readmore"); ?>" value="<?php echo $excerpt_readmore; ?>" />
			</p>

			<?php if ( function_exists('the_post_thumbnail') && current_theme_supports( 'post-thumbnails' ) ) : ?>

				<p>
					<input class="checkbox" id="<?php echo $this->get_field_id( 'show_thumbnail' ); ?>" name="<?php echo $this->get_field_name( 'show_thumbnail' ); ?>" type="checkbox" <?php checked( (bool) $instance["show_thumbnail"], true ); ?> />
					<label for="<?php echo $this->get_field_id( 'show_thumbnail' ); ?>"><?php _e( 'Show thumbnail?' ); ?></label>
				</p>

				<p>
				<label><?php _e('Thumbnail size:'); ?><br />
					<label for="<?php echo $this->get_field_id("thumb_w"); ?>">
						W: <input class="widefat" style="width:40%;" type="text" id="<?php echo $this->get_field_id("thumb_w"); ?>" name="<?php echo $this->get_field_name("thumb_w"); ?>" value="<?php echo $thumb_w; ?>" />
					</label>
					<label for="<?php echo $this->get_field_id("thumb_h"); ?>">
						H: <input class="widefat" style="width:40%;" type="text" id="<?php echo $this->get_field_id("thumb_h"); ?>" name="<?php echo $this->get_field_name("thumb_h"); ?>" value="<?php echo $thumb_h; ?>" />
					</label>
				</label>
				</p>

			<?php endif; ?>

			<?php
			
		}
		
	}
	
	function init_WP_Widget_Featured_Posts() {
	
		register_widget( 'WP_Widget_Featured_Posts' );
		
	}
	
	add_action( 'widgets_init', 'init_WP_Widget_Featured_Posts' );

}