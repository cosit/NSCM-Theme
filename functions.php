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

//register category_slugs and post_tag in the query
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
//end of registration


//Jobs posting function
function cah_jobs_check($atts){
	
	$atts = shortcode_atts( array(
		'dept' => '/CAH\-/',
		'jumb_class'=>'bg-inverse'
	), $atts);
	
	$outputHTML=""; $i=1;

	
	$rss_url = 'https://www.jobswithucf.com/all_jobs.atom';
						$ch = curl_init();
						curl_setopt($ch, CURLOPT_HEADER, 0);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);			
						//curl_setopt($ch, CURLOPT_SSLVERSION,3); 
						curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
						curl_setopt($ch, CURLOPT_URL, $rss_url);
						$curlResponse = curl_exec($ch);
						curl_close($ch);
						$xmlObject = simplexml_load_string($curlResponse);				
	
	 if($xmlObject) {
			
			foreach($xmlObject->entry as $entry) {	
				
			if (preg_match($atts['dept'],$entry->author->name))
				{
				if($i==1)
				$outputHTML .="<div class='jumbotron {$atts['jumb_class']} mt-0 mb-0'>
								<div class='container'><h2 class='font-condensed text-uppercase'>Job Openings</h2>";

				$outputHTML .= "<h5><a class='text-primary' href='{$entry->id}'>".$entry->title."</a></h5>";
				$outputHTML .= "<p class='small pb-4'>".substr($entry->content, 0, 450)."...</p>";
				$i++;
			
				}
			}//endforeach
			if(!empty($outputHTML))
			$outputHTML .="<a class='btn btn-primary' href='https://www.jobswithucf.com/'>Jobs with UCF</a></div></div>";
	 }
	
	return $outputHTML;
}
add_shortcode( 'cah_jobs', 'cah_jobs_check' );

//Jobs section
function cah_job_section_check($atts){
	$atts = shortcode_atts( array(
		'slug'=>'jobs',
		'class'=>'auto-section',
		'title'=>'Jobs',
		'section_id'=>'jobs',
		'dept' => '/CAH\-/',
		'jumb_class'=>'bg-inverse'
	), $atts);
	$jobs_opening = "";
	
	$dept = $atts['dept'];
	$jumb_class = $atts['jumb_class'];
	$slug = $atts['slug'];
	$class = $atts['class'];
	$title = $atts['title'];
	$section_id = $atts['section_id'];
	
	$jobs_opening = cah_jobs_check(array('dept'=>$dept));
	$outHTML = "";
	
	if(!empty($jobs_opening))
	{
		$outHTML = "<section id='$section_id' class='ucf-section ucf-section-jobs $class' data-section-link-title='$title'  role='region' aria-label='$title'>";
		$outHTML .=cah_jobs_check(array('dept'=>$dept,'jumb_class'=>$jumb_class));
		$outHTML .="</section>";
	}
	
	return $outHTML;
}
add_shortcode( 'cah_job_section', 'cah_job_section_check' );
//end of jobs section

// Custom get_header_title function
function get_header_title( $obj ) {
	$title = '';

	if ( is_home() || is_front_page() ) {
		$title = get_field( 'homepage_header_title', $obj->ID );

		if ( ! $title ) {
			$title = get_bloginfo( 'name' );
		}
	}
	elseif ( is_search() ) {
		$title = __( 'Search Results for:' );
		$title .= ' ' . esc_html( stripslashes( get_search_query() ) );
	}
	elseif ( is_404() ) {
		$title = __( '404 Not Found' );
	}
	elseif ( is_single() || is_page() ) {
		if ( $obj->post_type === 'person' ) {
			$title = get_field( 'page_header_title', $obj->ID ) ?: get_theme_mod_or_default( 'person_header_title' );
		}
		else {
			$title = get_field( 'page_header_title', $obj->ID );
		}

		if ( ! $title ) {
			$title = single_post_title( '', false );
		}
	}
	elseif ( is_category() ) {
		$title = __( 'Category Archives:' );
		$title .= ' ' . single_term_title( '', false );
	}
	elseif ( is_tag() ) {
		$title = __( 'Tag Archives:' );
		$title .= ' ' . single_term_title( '', false );
	}
	elseif ( is_tax() ) {
		$tax_name = '';
		$tax = get_taxonomy( $obj->taxonomy );
		if ( $tax ) {
			$tax_name = $tax->labels->singular_name . ' ';
		}
		$title = __( $tax_name . 'Archives:' );
		$title .= ' ' . single_term_title( '', false );
	}

	return $title;
}

// Custom get_header_subtitle function
function get_header_subtitle( $obj ) {
	$subtitle = '';

	if ( is_single() || is_page() ) {
		if ( $obj->post_type === 'person' ) {

			$subtitle = get_field( 'page_header_subtitle', $obj->ID ) ?: get_theme_mod_or_default( 'person_header_subtitle' );			
		}
		else {
			$subtitle = get_field( 'page_header_subtitle', $obj->ID );
		}
	}

	return $subtitle;
}

/**
 * Generate custom search form using Athena Framework and Bootstrap
 *
 * @param string $form Form HTML.
 * @return string Modified form HTML.
 */
function nscm_my_search_form( $form ) {
    $form = '<form role="search" method="get" id="searchform" class="form-inline" action="' . home_url( '/' ) . '" >
    <div class="input-group w-100"><input type="text" value="' . get_search_query() . '" name="s" id="s" class="form-control" placeholder="Search for" />
    <div class="input-group-append"><button type="submit" id="searchsubmit" value="'. esc_attr__( 'Search' ) .'" class="btn btn-primary" />Search</button></div>
    </div>
    </form><hr class="mt-5 mb-4"/>';
 
    return $form;
}
add_filter( 'get_search_form', 'nscm_my_search_form' );

/**
 * Create shortcode for generating custom search form
 **/
function nscm_show_search_form() {
	return get_search_form();
}
add_shortcode('show_search_form', 'nscm_show_search_form');

/**
 * Exclude the UCF Section CPT from search results
 **/
function nscm_exclude_cpts_search() {
	global $wp_post_types;
	if( post_type_exists( 'ucf_section' ) )
		$wp_post_types['ucf_section']->exclude_from_search = true;
}
add_action( 'init', 'nscm_exclude_cpts_search' );

/**
 * Add a Bootstrap class to the Previous and Next pagination links
 **/
function posts_link_attributes() {
    return 'class="page-link"';
}
add_filter('next_posts_link_attributes', 'posts_link_attributes');
add_filter('previous_posts_link_attributes', 'posts_link_attributes');
