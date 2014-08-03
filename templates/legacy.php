<?php if ($upw_query->have_posts()) : ?>

  <ul>

  <?php while ($upw_query->have_posts()) : $upw_query->the_post(); ?>

    <?php $current_post = ($post->ID == $current_post_id && is_single()) ? 'current-post-item' : ''; ?>

    <li <?php post_class($current_post); ?>>

      <?php if (current_theme_supports('post-thumbnails') && $instance['show_thumbnail'] && has_post_thumbnail()) : ?>
        <div class="upw-image">
          <a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>">
            <?php the_post_thumbnail($instance['thumb_size']); ?>
          </a>
        </div>
      <?php endif; ?>

      <div class="upw-content">

        <?php if (get_the_title() && $instance['show_title']) : ?>
          <p class="post-title">
            <a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>">
              <?php the_title(); ?>
            </a>
          </p>
        <?php endif; ?>

        <?php if ($instance['show_date']) : ?>
          <p class="post-date">
            <?php the_time($instance['date_format']); ?>
          </p>
        <?php endif; ?>

        <?php if($instance['show_author']) : ?>
          <p class="post-author">
            <span class="post-author-label"><?php _e('By', 'upw'); ?>:</span>
            <?php the_author_posts_link(); ?>
          </p>
        <?php endif; ?>

        <?php if ($instance['show_excerpt']) : ?>
          <?php
          $linkmore = '';
          if ($instance['show_readmore']) {
            $linkmore = ' <a href="'.get_permalink().'" class="more-link">'.$excerpt_readmore.'</a>';
          }
          ?>
          <p class="post-excerpt"><?php echo get_the_excerpt() . $linkmore; ?></p>
        <?php endif; ?>

        <?php if ($instance['show_content']) : ?>
          <p class="post-content"><?php the_content() ?></p>
        <?php endif; ?>

        <?php if ($instance['show_cats']) : ?>
          <p class="post-cats">
            <span class="post-cats-label"><?php _e('Categories', 'upw'); ?>:</span>
            <span class="post-cats-list"><?php the_category(', '); ?></span>
          </p>
        <?php endif; ?>

        <?php if ($instance['show_tags']) : ?>
          <p class="post-tags">
            <span class="post-tags-label"><?php _e('Tags', 'upw'); ?>:</span>
            <?php the_tags('<span class="post-tags-list">', ', ', '</span>'); ?>
          </p>
        <?php endif; ?>

        <?php if ($custom_fields) {
          $custom_field_name = explode(',', $custom_fields);
          foreach ($custom_field_name as $name) { 
            $name = trim($name);
            $custom_field_values = get_post_meta($post->ID, $name, true);
            if ($custom_field_values) {
              echo '<p class="post-meta post-meta-'.$name.'">';
              if (!is_array($custom_field_values)) {
                echo $custom_field_values;
              } else {
                $last_value = end($custom_field_values);
                foreach ($custom_field_values as $value) {
                  echo $value;
                  if ($value != $last_value) echo ', ';
                }
              }
              echo '</p>';
            }
          } 
        } ?>

      </div>

    </li>

  <?php endwhile; ?>
  
  </ul>

  <?php if ($instance['show_morebutton']) : ?>

    <div class="upw-more">
      <a href="<?php echo $instance['morebutton_url']; ?>" class="button"><?php echo $instance['morebutton_text']; ?></a>
    </div>

  <?php endif; ?>

<?php else : ?>

  <p class="upw-not-found">
    <?php _e('No posts found.', 'upw'); ?>
  </p>

<?php endif; ?>