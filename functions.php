<?php

function my_theme_enqueue_styles() {
    // Parent theme name
    $parent_style = 'Colleges-Theme-master'; 

    // Enqueue Parent Theme Style
    wp_enqueue_style( $parent_style, get_template_directory_uri() . '/static/css/style.min.css' );
    // Enqueue Child Theme Style
    wp_enqueue_style( 'style',
        get_stylesheet_directory_uri() . '/style.css',
        array( $parent_style ),
        wp_get_theme()->get('Version')
    );
}
add_action( 'wp_enqueue_scripts', 'my_theme_enqueue_styles' );

function themeslug_enqueue_script() { 
	// Re-enqueue the tether script because it's a dependency in the parent theme script below
	wp_enqueue_script( 'tether', 'https://cdnjs.cloudflare.com/ajax/libs/tether/1.4.0/js/tether.min.js', null, null, true );
	// Re-enqueue the parent theme script so it looks in the right directory
	wp_enqueue_script( 'script', get_template_directory_uri() . '/static/js/script.min.js', array( 'jquery', 'tether' ), null, true );
}
add_action( 'wp_enqueue_scripts', 'themeslug_enqueue_script' );

// Register category_slugs in the query
function add_tax_query_to_posts_endpoint( $args, $request ) {
	$params = $request->get_params();
	$tax_query = array();
	if ( isset( $params['category_slugs'] ) ) {
		$tax_query[] =
			array(
				'taxonomy' => 'category',
				'field'    => 'slug',
				'terms'    => $params['category_slugs']
			);
	}
	if ( isset( $params['tag_slugs'] ) ) {
		$tax_query[] =
			array(
				'taxonomy' => 'post_tag',
				'field'    => 'slug',
				'terms'    => $params['tag_slugs']
			);
	}
	if ( count( $tax_query ) > 0 ) {
		$args['tax_query'] = $tax_query;
	}
	return $args;
}
add_action( 'rest_post_query', 'add_tax_query_to_posts_endpoint', 2, 10 );
//end of category_slugs registration
?>