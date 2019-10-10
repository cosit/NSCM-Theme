<?php
/**
 * Template Name: Scholarships Page Template
 * Template Post Type: Page
 */
?>
<?php get_header();  ?>

    <div class="container mb-5" style="min-height:250px;">
        <div>
            <?= get_post_field('post_content', $post->ID) ?>
        </div>

        <div class="row" style="padding:.8em">
            <?

            $args = [
                'post_status' => 'publish',
                'post_type' => 'scholarship',
                'orderby'=> 'title',
                'order' => 'ASC',
                'posts_per_page' => '-1' // show all posts
            ];

            $query = new WP_Query($args); echo "<ul class='cards'>";
            while ($query->have_posts()) : $query->the_post();
            $custom = get_post_custom($post->ID);$excerpt = $custom["excerpt"][0];
            ?>
            <a href="<?php the_permalink(); ?>" class="text-secondary">
                <li>
<!--                    --><?// the_post_thumbnail(array(250, 250), array('class' => 'rounded float-left mr-3')); ?>
                    <h4><? the_title(); ?></h4>

                    <?
                    echo "<br>";
                    the_excerpt();
                    echo "</li>";
                    echo "</a>";
                    endwhile;
                    echo "</ul>";
                    wp_reset_postdata(); // reset the query



                    ?>
        </div>
    </div>
<?php get_footer();?>