<?php

add_action( 'wp_enqueue_scripts', 'college_theme_parent_style' );
function college_theme_parent_style() {
	
	// Enqueue Parent Style
	wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/static/css/style.min.css' );
} 

add_action( 'wp_enqueue_scripts', 'college_theme_child_style', 999 );
function college_theme_child_style() {

	// Customized Style
	wp_enqueue_style( 'child-style',  get_stylesheet_directory_uri() . '/style.css');
}

?>