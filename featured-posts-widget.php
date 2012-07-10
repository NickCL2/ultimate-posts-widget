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
	
			$title = apply_filters( 'widget_title', $instance['title'] );
			$number = $instance['number'];
			$cpt = $instance['types'];		
			if (!empty($cpt)) $types = explode(',', $cpt);
			$categories = $instance['cats'];		
			if (!empty($categories)) $cats = explode(',', $categories);
			$atcat = $instance['atcat'];
			$thumb_w = $instance['thumb_w'];
			$thumb_h = $instance['thumb_h'];
			$excerpt_length = $instance['excerpt_length'];
			$excerpt_readmore = $instance['excerpt_readmore'];
			$sticky = $instance['sticky'];
			$order = $instance['order'];

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

			// If sticky
			if ($sticky) {
				$sticky_option = get_option( 'sticky_posts' );
			}

			//Excerpt more filter
			$new_excerpt_more = create_function('$more', 'return " ";');	
			add_filter('excerpt_more', $new_excerpt_more);
			
			// Excerpt length filter
			$new_excerpt_length = create_function('$length', "return " . $excerpt_length . ";");
			if ( $instance["excerpt_length"] > 0 ) add_filter('excerpt_length', $new_excerpt_length);

			echo $before_widget;
			if ( $title ) echo $before_title . $title . $after_title;

			$args = array(
				'showposts' => $number,
				'orderby' => $order,
				'post__in' => $sticky_option,
				'category__in' => $cats,
				'post_type' => $types
			);

			$r = new WP_Query( $args );
			
			if ( $r->have_posts() ) :
				
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
				
				// Reset the global $the_post as this query will have stomped on it
				wp_reset_postdata();

			else :

				echo __('No posts found.');
	
			endif;

			echo $after_widget;
	
			$cache[$args['widget_id']] = ob_get_flush();
			wp_cache_set( 'widget_featured_posts', $cache, 'widget' );
		}
	
		function update( $new_instance, $old_instance ) {
			$instance = $old_instance;
			
			//Let's turn that array into something the Wordpress database can store
			$types = implode(',', (array)$new_instance['types']);
			$cats = implode(',', (array)$new_instance['cats']);

			$instance['title'] = strip_tags( $new_instance['title'] );
			$instance['number'] = strip_tags( $new_instance['number'] );
			$instance['types'] = $types;
			$instance['cats'] = $cats;
			$instance['atcat'] = strip_tags( $new_instance['atcat'] );
			$instance['show_excerpt'] = strip_tags( $new_instance['show_excerpt'] );
			$instance['show_thumbnail'] = strip_tags( $new_instance['show_thumbnail'] );
			$instance['show_date'] = strip_tags( $new_instance['show_date'] );
			$instance['thumb_w'] = strip_tags( $new_instance['thumb_w'] );
			$instance['thumb_h'] = strip_tags( $new_instance['thumb_h'] );
			$instance['show_readmore'] = strip_tags( $new_instance['show_readmore'] );
			$instance['excerpt_length'] = strip_tags( $new_instance['excerpt_length'] );
			$instance['excerpt_readmore'] = strip_tags( $new_instance['excerpt_readmore'] );
			$instance['sticky'] = strip_tags( $new_instance['sticky'] );
			$instance['order'] = strip_tags( $new_instance['order'] );
			
			
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

			// instance exist? if not set defaults
			if ( $instance ) {
				$title  = $instance['title'];
				$number = $instance['number'];
				$types  = $instance['types'];
				$cats = $instance['cats'];
				$atcat = $instance['atcat'];
				$thumb_w = $instance['thumb_w'];
				$thumb_h = $instance['thumb_h'];
				$excerpt_length = $instance['excerpt_length'];
				$excerpt_readmore = $instance['excerpt_readmore'];
				$sticky = $instance['sticky'];
				$order = $instance['order'];
			} else {
				//These are our defaults
				$title  = '';
				$number = '5';
				$types  = 'post';
				$cats = '';
				$atcat = false;
				$thumb_w = 100;
				$thumb_h = 100;
				$excerpt_length = 10;
				$excerpt_readmore = 'Read more &rarr;';
				$sticky = false;
				$order = 'date';
			}

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

			<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></p>
	
			<p><label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of posts:' ); ?></label>
			<input id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="text" value="<?php echo $number; ?>" size="2" /></p>

			<p>
				<input class="checkbox" id="<?php echo $this->get_field_id( 'show_date' ); ?>" name="<?php echo $this->get_field_name( 'show_date' ); ?>" type="checkbox" <?php checked( (bool) $instance["show_date"], true ); ?> />
				<label for="<?php echo $this->get_field_id( 'show_date' ); ?>"><?php _e( 'Show date' ); ?></label>
			</p>

			<p>
				<input class="checkbox" id="<?php echo $this->get_field_id( 'show_excerpt' ); ?>" name="<?php echo $this->get_field_name( 'show_excerpt' ); ?>" type="checkbox" <?php checked( (bool) $instance["show_excerpt"], true ); ?> />
				<label for="<?php echo $this->get_field_id( 'show_excerpt' ); ?>"><?php _e( 'Show excerpt' ); ?></label>
			</p>

			<p>
				<label for="<?php echo $this->get_field_id("excerpt_length"); ?>"><?php _e( 'Excerpt length (in words):' ); ?></label>
				<input style="text-align: center;" type="text" id="<?php echo $this->get_field_id("excerpt_length"); ?>" name="<?php echo $this->get_field_name("excerpt_length"); ?>" value="<?php echo $excerpt_length; ?>" size="3" />
			</p>

			<p>
				<label for="<?php echo $this->get_field_id('show_readmore'); ?>">
				<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id("show_readmore"); ?>" name="<?php echo $this->get_field_name("show_readmore"); ?>"<?php checked( (bool) $instance["show_readmore"], true ); ?> />
				<?php _e( 'Show read more link' ); ?>
				</label>
			</p>
				
			<p class="<?php echo $this->get_field_id('excerpt_readmore'); ?>">
				<label for="<?php echo $this->get_field_id('excerpt_readmore'); ?>"><?php _e( 'Read more text:' ); ?></label>
				<input class="widefat" type="text" id="<?php echo $this->get_field_id('excerpt_readmore'); ?>" name="<?php echo $this->get_field_name("excerpt_readmore"); ?>" value="<?php echo $excerpt_readmore; ?>" />
			</p>

			<?php if ( function_exists('the_post_thumbnail') && current_theme_supports( 'post-thumbnails' ) ) : ?>

				<p>
					<input class="checkbox" id="<?php echo $this->get_field_id( 'show_thumbnail' ); ?>" name="<?php echo $this->get_field_name( 'show_thumbnail' ); ?>" type="checkbox" <?php checked( (bool) $instance["show_thumbnail"], true ); ?> />
					<label for="<?php echo $this->get_field_id( 'show_thumbnail' ); ?>"><?php _e( 'Show thumbnail' ); ?></label>
				</p>

				<p>
					<label><?php _e('Thumbnail size:'); ?></label>
					<br />
					<label for="<?php echo $this->get_field_id('thumb_w'); ?>">
						W: <input class="widefat" style="width:40%;" type="text" id="<?php echo $this->get_field_id('thumb_w'); ?>" name="<?php echo $this->get_field_name('thumb_w'); ?>" value="<?php echo $thumb_w; ?>" />
					</label>
					<label for="<?php echo $this->get_field_id('thumb_h'); ?>">
						H: <input class="widefat" style="width:40%;" type="text" id="<?php echo $this->get_field_id('thumb_h'); ?>" name="<?php echo $this->get_field_name('thumb_h'); ?>" value="<?php echo $thumb_h; ?>" />
					</label>
				</p>

			<?php endif; ?>

			<p>
				<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('sticky'); ?>" name="<?php echo $this->get_field_name('sticky'); ?>" <?php checked( (bool) $instance['sticky'], true ); ?> />
				<label for="<?php echo $this->get_field_id('sticky'); ?>"> <?php _e('Show only sticky posts');?></label>
			</p>

			<p>
				<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('atcat'); ?>" name="<?php echo $this->get_field_name('atcat'); ?>" <?php checked( (bool) $instance['atcat'], true ); ?> />
				<label for="<?php echo $this->get_field_id('atcat'); ?>"> <?php _e('Show posts only from current category');?></label>
			</p>

			<p>
			<label for="<?php echo $this->get_field_id('cats'); ?>"><?php _e( 'Select categories:' ); ?></label>
			<select name="<?php echo $this->get_field_name('cats'); ?>[]" id="<?php echo $this->get_field_id('cats'); ?>" class="widefat" style="height: auto;" size="<?php echo $c ?>" multiple>
				<?php 
				$categories = get_categories( 'hide_empty=0' );
				foreach ($categories as $category ) { ?>
					<option value="<?php echo $category->term_id; ?>" <?php if( in_array($category->term_id, $cats)) { echo 'selected="selected"'; } ?>><?php echo $category->cat_name;?></option>
				<?php }	?>
			</select>
			</p>

			<p>
			<label for="<?php echo $this->get_field_id('types'); ?>"><?php _e( 'Select post type(s):' ); ?></label>
			<select name="<?php echo $this->get_field_name('types'); ?>[]" id="<?php echo $this->get_field_id('types'); ?>" class="widefat" style="height: auto;" size="<?php echo $n ?>" multiple>
				<?php 
				$args = array( 'public' => true );
				$post_types = get_post_types( $args, 'names' );
				foreach ($post_types as $post_type ) { ?>
					<option value="<?php echo $post_type; ?>" <?php if( in_array($post_type, $types)) { echo 'selected="selected"'; } ?>><?php echo $post_type;?></option>
				<?php }	?>
			</select>
			</p>

			

			<p>
			<label for="<?php echo $this->get_field_id('order'); ?>"><?php _e( 'Order by:' ); ?></label>
			<select name="<?php echo $this->get_field_name('order'); ?>" id="<?php echo $this->get_field_id('order'); ?>" class="widefat">
				<option value="date" <?php if( $order == 'date') { echo 'selected="selected"'; } ?>><?php _e('Date'); ?></option>
				<option value="title" <?php if( $order == 'title') { echo 'selected="selected"'; } ?>><?php _e('Title'); ?></option>
				<option value="comment_count" <?php if( $order == 'comment_count') { echo 'selected="selected"'; } ?>><?php _e('Comments'); ?></option>
				<option value="rand" <?php if( $order == 'rand') { echo 'selected="selected"'; } ?>><?php _e('Random'); ?></option>
			</select>
			</p>

			<script>

				(function($) {

					$(document).ready(function() {

						// Hide excerpt length if not checked
						if ($("#<?php echo $this->get_field_id( 'show_excerpt' ); ?>").not(':checked')) {
							$("#<?php echo $this->get_field_id( 'excerpt_length' ); ?>").parent('p').hide();
						}

						// Toggle excerpt length on check
						$("#<?php echo $this->get_field_id( 'show_excerpt' ); ?>").click(function() {
							$("#<?php echo $this->get_field_id( 'excerpt_length' ); ?>").parent('p').toggle();
						});

						// Hide read more excerpt if not checked
						if ($("#<?php echo $this->get_field_id( 'show_readmore' ); ?>").not(':checked')) {
							$("#<?php echo $this->get_field_id( 'excerpt_readmore' ); ?>").parent('p').hide();
						}

						// Toggle read more excerpt on check
						$("#<?php echo $this->get_field_id( 'show_readmore' ); ?>").click(function() {
							$("#<?php echo $this->get_field_id( 'excerpt_readmore' ); ?>").parent('p').toggle();
						});

						// Hide thumbnail size if not checked
						if ($("#<?php echo $this->get_field_id( 'show_thumbnail' ); ?>").not(':checked')) {
							$("#<?php echo $this->get_field_id( 'thumb_w' ); ?>").parents('p').hide();
						}

						// Toggle thumbnail size on check
						$("#<?php echo $this->get_field_id( 'show_thumbnail' ); ?>").click(function() {
							$("#<?php echo $this->get_field_id( 'thumb_w' ); ?>").parents('p').toggle();
						});

					});

				})(jQuery);

			</script>

			<?php
			
		}
		
	}
	
	function init_WP_Widget_Featured_Posts() {
	
		register_widget( 'WP_Widget_Featured_Posts' );
		
	}
	
	add_action( 'widgets_init', 'init_WP_Widget_Featured_Posts' );

}