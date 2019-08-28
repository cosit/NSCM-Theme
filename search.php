<?php
/**
 * Template Name: Basic
 * Template Post Type: degree
 */
?>
<?php get_header(); ?>
<div class="container mt-3 mt-sm-4 mt-md-5 mb-5">
<?php 
	get_search_form();

if (have_posts() ):
?>

	<div class="ucf-news modern">

		<?php while ( have_posts() ) : the_post(); ?>
			<div class="container mt-3 mb-3">
				<article class="<?php echo $post->post_status; ?> post-list-item">
					
					<div class="summary ucf-news-item">
						<?php

							$thumbnail = get_the_post_thumbnail(get_the_id(), 'thumbnail');
							
							if ( $thumbnail ) 
					            echo "<div class='ucf-news-thumbnail mb-3'><a href='".get_the_permalink()."'>$thumbnail</a></div>";
					    ?>

						<div class='ucf-news-item-content'>
							<h5>
								<a href="<?php esc_url(the_permalink()); ?>"><?php the_title(); ?></a>
							</h5>
							<div class="meta">
								<span class="date"><?php the_time( 'F j, Y' ); ?></span>
								<?php 
									$post_type = get_post_type();

									if( $post_type === 'post' )
										echo "<div class='news-section-title'>".get_the_category_list()."</div>"; ?>
							</div>
							<?php the_excerpt(); ?>
						</div>
					</div>
				</article>
			</div>
		<?php endwhile; ?>
		<div class="pagination">
			<div class="nav-previous alignleft page-item"><?php previous_posts_link( 'Previous' ); ?></div>
			<div class="nav-next alignright page-item"><?php next_posts_link( 'Next' ); ?></div>
		</div>
	</div>
<?php else: ?>
	<div><h5>Sorry, no results matched your criteria.</h5></div>
<?php endif;?>
<?php get_footer(); ?>
