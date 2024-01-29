<?php

define( "DEPT", 37 );

require_once 'faculty-staff-includes/dbconfig.php';
require_once 'faculty-staff-includes/query-ref.php';
require_once 'faculty-staff-includes/query-lib.php';
require_once 'faculty-staff-includes/faculty-staff-functions.php';
//require_once 'faculty-staff-includes/print-faculty.php';
include_once 'includes/child-header-functions.php';

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
 * Generate custom search form using Athena Framework and Bootstrap.
 * Only returns results with the post CPT
 *
 * @param string $form Form HTML.
 * @return string Modified form HTML.
 */
function nscm_my_search_form( $form ) {
    $form = '<form role="search" method="get" id="searchform" class="form-inline" action="' . home_url( '/' ) . '" >
    <div class="input-group w-100"><input type="text" value="' . get_search_query() . '" name="s" id="s" class="form-control" placeholder="Search for" />
	<input type="hidden" value="post" name="post_type" id="post_type" />
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

// Functions to handle loading resources for the faculty/staff page
add_action( 'init', array( 'FacultyStaffHelper', 'action_hooks' ), 10, 0 );

/**
 * Set the excerpt_length to 25 words instead of 55
 */
function custom_excerpt_length( $length ) {
	return 25;
}
add_filter( 'excerpt_length', 'custom_excerpt_length', 999 );

/**
 * Set the end of the excerpt to '...' instead of [...]
 */
add_filter('excerpt_more', function($more) {
	return '...';
});

/**
 * Add a new 'modern' layout for the UCF Post List plugin
 *  
 * @author	Jonathan Hendricker
 * @since	1.0.21
 */
if ( ! function_exists( 'nscm_add_modern_layout' ) ) {
	function nscm_add_modern_layout( $layouts ) {
		$layouts['modern'] = 'Modern Layout';
		return $layouts;
	}
	add_action( 'ucf_post_list_get_layouts', 'nscm_add_modern_layout', 10, 1 );
}

/**
 * Add a new 'modern' layout before function for the UCF Post List plugin
 *  
 * @author	Jonathan Hendricker
 * @since	1.0.21
 */
if ( !function_exists( 'ucf_post_list_display_modern_before' ) ) {

	function ucf_post_list_display_modern_before( $content, $posts, $atts ) {
		ob_start();
	?>
	<div class="ucf-post-list ucf-post-list-modern" id="post-list-<?php echo $atts['list_id']; ?>">
	<?php
		return ob_get_clean();
	}

	add_filter( 'ucf_post_list_display_modern_before', 'ucf_post_list_display_modern_before', 10, 3 );

}

/**
 * Add a new 'modern' layout title function for the UCF Post List plugin
 *  
 * @author	Jonathan Hendricker
 * @since	1.0.21
 */
if ( !function_exists( 'ucf_post_list_display_modern_title' ) ) {

	function ucf_post_list_display_modern_title( $content, $posts, $atts ) {
		$formatted_title = '';

		if ( $list_title = $atts['list_title'] ) {
			$formatted_title = '<h2 class="ucf-post-list-title">' . $list_title . '</h2>';
		}

		return $formatted_title;
	}

	add_filter( 'ucf_post_list_display_modern_title', 'ucf_post_list_display_modern_title', 10, 3 );

}

/**
 * Add a new 'modern' layout display function for the UCF Post List plugin
 *  
 * @author	Jonathan Hendricker
 * @since	1.0.21
 */
if ( !function_exists( 'ucf_post_list_display_modern' ) ) {

	function ucf_post_list_display_modern( $content, $posts, $atts ) {
		if ( ! is_array( $posts ) && $posts !== false ) { $posts = array( $posts ); }
		ob_start();
	
		// Display only if there are found posts and the UCF Post List Common class has been instantiated
		if ( $posts && class_exists( 'UCF_Post_List_Common' ) ): ?>
			
			<?php foreach ( $posts as $item ): 

			$date = "<em>".date( "M d, Y", strtotime( $item->post_date ) )."</em>";
			$size = 'thumbnail';

			$categories = get_the_category($item);
			$separator = ' ';
			$category_output = '';
			if ( ! empty( $categories ) ) {
				foreach( $categories as $category ) {
					$category_output .= '<span class="badge badge-primary mb-1">' . esc_html( $category->name ) . '</span>' . $separator;
				}				
			}

			// Calling a newly created function to handle getting the image. This differs from the default because 
			// it returns a specific size for the fallback image and not the full size.
			$item_img = nscm_get_image_or_fallback( $item, $size );
			//$item_img_srcset = UCF_Post_List_Common::get_image_srcset( $item );
						
			$item_excerpt= get_the_excerpt( $item );
			
			?>

			<div class="media-background-container hover-parent p-3 mb-3" style="margin-left: -1rem; margin-right: -1rem;">
				<div class="media-background hover-child-show fade" style="background-color: rgba(204, 204, 204, .25);"></div>

				<div class="media">
					<?php if ( $item_img ) : ?>
					<div class=" d-flex w-25 mr-3" style="max-width: 150px;">
						<img src="<?php echo $item_img; ?>" class="ucf-post-list-thumbnail-image" alt="<?php echo $item->post_title; ?>">
					</div>
					<?php endif; ?>
					<div class=" media-body">
						<div>
							<a class="d-block stretched-link text-decoration-none h5 mb-0 pb-1" href="<?php echo get_permalink( $item->ID ); ?>" style="color: inherit;">
								<?php echo $item->post_title; ?>
							</a>
							<?php 
								echo ( !empty($category_output) ? "<div class='font-size-sm'>".trim( $category_output, $separator )."</div>" : '' );

								echo ( !empty($date) ? "<div class='font-size-sm mb-2'>$date</div>" : '' );								
							?>								
							<div>
								<?php echo wp_trim_words( $item_excerpt, 25 ); ?>
							</div>							
						</div>
					</div>
				</div>
			</div>
			
			<?php endforeach; ?>

		<?php else: ?>
			<div class="ucf-post-list-error">No results found.</div>
		<?php endif; ?>
	<?php
		return ob_get_clean();
	}
	add_filter( 'ucf_post_list_display_modern', 'ucf_post_list_display_modern', 10, 3 );

}

/**
 * Add a new 'modern' layout after function for the UCF Post List plugin
 *  
 * @author	Jonathan Hendricker
 * @since	1.0.21
 */
if ( !function_exists( 'ucf_post_list_display_modern_after' ) ) {

	function ucf_post_list_display_modern_after( $content, $posts, $atts ) {
		ob_start();
	?>
	</div>
	<?php
		return ob_get_clean();
	}

	add_filter( 'ucf_post_list_display_modern_after', 'ucf_post_list_display_modern_after', 10, 3 );

}

/**
 * Alternative to UCF_Post_List_Common::get_image_or_fallback that will 
 * return a specific size for the fallback image instead of the full 
 * size fallback image.
 * 
 * @author	Jonathan Hendricker
 * @since	1.0.21
 * @param object $item | WP_Post object
 * @param mixed $size | image size (accepts any valid image size, or an array of width and height values in pixels, in that order)
 * @return string | image url
 */
if ( !function_exists( 'nscm_get_image_or_fallback' ) ) {
	function nscm_get_image_or_fallback( $item, $size='large' ) {
		$img    = null;
		$img_id = UCF_Post_List_Common::get_image_id_or_fallback( $item );

		if ( $img_id !== intval( UCF_Post_List_Config::get_option_or_default( 'ucf_post_list_fallback_image' ) ) ) {
			$img = wp_get_attachment_image_src( $img_id, $size );
			$img = $img ? $img[0] : null;
		}

		if ( $img === null ) {
			$img = wp_get_attachment_image_url( $img_id, $size );
		}

		return $img;
	}
}
?>