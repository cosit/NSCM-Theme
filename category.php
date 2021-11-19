<?php get_header(); ?>

<div class="container mt-4 mt-sm-4 mt-md-5 mb-5 ucf-news modern">
	
	<?php the_archive_description( '<div class="lead mb-4 mb-sm-4 mb-md-5">', '</div>' ); ?>

	<?php get_search_form(); ?>

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
						<a href="<?php esc_url(the_permalink()); ?>"><?php the_excerpt(); ?></a>
					</div>
				</div>
			</article>
		</div>
	<?php endwhile; ?>
	<?php 
		$args = array(
			'mid_size'  => 1,
			'prev_next' => true,
			'prev_text' => 'Previous',
			'next_text' => 'Next',
			'type'      => 'plain',
			'screen_reader_text' => __( 'Posts navigation' ),
		);
		// Generate paginated link markup
		$links = paginate_links( $args );

		if( $links ) echo $links;
	?>
</div>

<?php get_footer(); ?>
