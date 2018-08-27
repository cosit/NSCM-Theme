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
		'dept' => 'CAH-',
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
				
			if (strpos($entry->author->name,$atts['dept']) !== false || strpos($entry->title,$atts['dept']) !== false )
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
		'dept' => 'CAH-',
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
?>