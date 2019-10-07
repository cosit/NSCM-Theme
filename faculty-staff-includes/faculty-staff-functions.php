<?php

require_once 'dbconfig.php';
require_once 'query-lib.php';
require_once 'query-ref.php';

use FacultyStaffQueryLib as FSQLib;
use FacultyStaffQueryRef as FSQEnum;

if( !class_exists( __CLASS__ ) ) {

    class FacultyStaffHelper {

        private static $db_connection; // Will hold the MySQL connection object
        private static $query_lib; // Will hold the FacultyStaffQueryLib object instance.


        private function __construct() { /* Prevent instantiation */ }


        public static function action_hooks() : void {
            add_action( 'wp_enqueue_scripts', array( __CLASS__, 'load_scripts' ), 9, 0 );
            add_action( 'wp_default_scripts', array( __CLASS__, 'jquery_footer' ), 5, 1 );
            add_action( 'wp_ajax_print_faculty_staff', array( __CLASS__, 'print_faculty' ), 10, 0 );
            add_action( 'wp_ajax_nopriv_print_faculty_staff', array( __CLASS__, 'print_faculty' ), 10, 0 );
        }


        public static function load_scripts() : void {

            wp_enqueue_script( 'faculty-staff-script', get_stylesheet_directory_uri() . '/faculty-staff-includes/faculty-staff.js', array( 'jquery', 'script' ), '1.0.0', TRUE );

            wp_localize_script( 'faculty-staff-script', 'pageVars', array(
                    'url' => admin_url( 'admin-ajax.php' ),
                    'dept' => DEPT,
                    'security' => wp_create_nonce( 'ajax-nonce' )
                )
            );
        }


        public static function jquery_footer( $wp_scripts ) : void {

            if( is_admin() ) return;

            $wp_scripts->add_data( 'jquery', 'group', 1 );
            $wp_scripts->add_data( 'jquery-core', 'group', 1 );
            $wp_scripts->add_data( 'jquery-migrate', 'group', 1 );
        }


        public static function print_faculty() {

            check_ajax_referer( 'ajax-nonce', 'security' );

            $sub_dept = isset( $_POST[ 'sub_dept'] ) ? $_POST[ 'sub_dept' ] : 0;
            $user_id = isset( $_POST[ 'id' ] ) ? $_POST[ 'id' ] : 0;

            $result = self::get_NSCM_staff( DEPT, $sub_dept, $user_id );

            if( $sub_dept != 0 )
                echo self::print_staff( $result, TRUE );

            else if( $user_id != 0 )
                echo self::staff_detail( $result );

            else
                echo self::print_staff( $result );

            die();
            /*
            if( isset( $_POST[ 'sub-dept' ] ) ) {

                $sub_dept = self::parse_int( $_POST[ 'sub-dept' ] );
                $result = self::get_NSCM_staff( DEPT, $sub_dept );

            } else if( isset( $_GET[ 'id' ] ) || isset( $_POST[ 'id' ] ) ) {

                $id = self::parse_int( isset( $_GET[ 'id' ] ) ? $_GET[ 'id' ] : $_POST[ 'id' ] );
                $result = self::get_NSCM_staff( DEPT, 0, $id );

            } else {
                $result = self::get_NSCM_staff( DEPT );
            }

            if( $sub_dept == 0 && $id == 0 )
                echo self::print_staff( $result );
            else if( $sub_dept != 0 )
                echo self::print_staff( $result, TRUE );
            else if( $id != 0 )
                echo self::staff_detail( $result );

            die();
            */
        }


        public static function db_get() : object {
            global $db_user, $db_pass, $db, $db_server;

            if( self::$db_connection ) return self::$db_connection;

            self::$db_connection = mysqli_connect( $db_server, $db_user, $db_pass ) or exit( 'Could not connect to server.' );
            mysqli_set_charset( self::$db_connection, 'utf8' );
            mysqli_select_db( self::$db_connection, $db ) or exit( 'Could not select database.' );

            return self::$db_connection;
        }


        public static function db_close() : void {
            global $db_connection;
            if( $db_connection !== FALSE ) mysqli_close( $db_connection );
            $db_connection = FALSE;
        }


        public static function get_fsq_lib( $dept = DEPT ) {

            if( is_null( self::$query_lib) || !is_a( self::$query_lib, 'FacultyStaffQueryLib' ) )
                self::$query_lib = new FSQLib( $dept );
            
            return self::$query_lib;
        }


        private static function _run_query( string $sql ) {

            $result = mysqli_query( self::db_get(), $sql );

            if( self::_validate( $result, $sql, TRUE ) ) return $result;
            else return FALSE;

        }


        private static function _validate( $result, string $sql, bool $debug = FALSE ) : bool {
            $msg = "";

            if( !$result ) {
                
                if( $debug ) {
                    $msg .= "<p>Invalid query: " . mysqli_error( self::db_get() ) . "</p>";
                    $msg .= "<p>Query: " . $sql . "</p>";

                } else {
                    $msg .= "There was a database problem. Please report this to <a href=\"mailto:cahweb@ucf.edu\">cahweb@ucf.edu</a>";
                }

                die( $msg );

            } else return TRUE;
        }


        public static function get_menu_categories( int $dept = DEPT) {
            self::get_fsq_lib( $dept );

            $sql = self::$query_lib->get_query_str( FSQEnum::ACAD_CATS );
            
            if( $result = self::_run_query( $sql ) ) return $result;

            else return FALSE;
        }


        public static function get_NSCM_staff( $dept = 37, int $sub_dept = 0, int $user_id = 0 ) {

            // Just in case it hasn't been created yet.
            self::get_fsq_lib( $dept );

            $sql = "";

            if( $sub_dept === 1 ) 
                $sql = self::$query_lib->get_query_str( FSQEnum::DEPT_ADMIN );

            else if( $sub_dept === 2 )
                $sql = self::$query_lib->get_query_str( FSQEnum::DEPT_STAFF );

            else if( $sub_dept !== 0 )
                $sql = self::$query_lib->get_query_str( FSQEnum::DEPT_SUB_GENERAL, $sub_dept );

            else if( $user_id !== 0 )
                $sql = self::$query_lib->get_query_str( FSQEnum::DEPT_USER, $user_id );

            else
                $sql = self::$query_lib->get_query_str( FSQEnum::DEPT_ALL );

            if( $result = self::_run_query( $sql ) ) return $result;
            else return FALSE;

            /*
            // GET ALL THE THINGS
            $sql = "SELECT `u`.`lname`, `u`.`id`, `u`.`phone`, `u`.`photo_path`, `u`.`photo_extra`, `u`.`email`, `u`.`location`, `u`.`room_id`, `u`.`office`, `u`.`interests`, `u`.`activities`, `u`.`awards`, `u`.`duties`, `u`.`research`, `u`.`has_cv`, `u`.`homepage`, `u`.`biography`, REPLACE( CONCAT_WS( ' ', `u`.`fname`, `u`.`mname`, `u`.`lname`), ' ', ' ') AS fullname, `t`.`description` AS title, `d_s`.`description`, `t`.`title_group`, `u_d`.`prog_title_dept` AS title_dept, `u_d`.`prog_title_dept_short` AS title_dept_short FROM `users` AS `u`, `departments` AS `d`, `users_departments` AS `u_d` LEFT JOIN `departments_sub` AS `d_s` ON `u_d`.`subdepartment_id` = `d_s`.`id` WHERE `u_d`.`department_id` = $dept AND `u`.`active` = 1 AND `u`.`show_web` = 1 AND `u`.`id` = `u_d`.`user_id` AND `t`.`id` = `u_d`.`title_id` AND `d`.`id` = `u_d`.`department_id`";


            if( $sub_dept === 1 ) // Administration
                $sql .= " AND `t`.`title_group` IN(' Administrative Faculty' )";

            else if( $sub_dept === 2 ) // Advising Staff
                $sql .= " AND `u_d`.`title_id` IN( 67, 84, 121, 85, 53 )";
            
            else if( $sub_dept !== 0 ) // Everyone else
                $sql .= " AND `u_d`.`subdepartment_id` = $sub_dept";
            
            // Check to see if it's an individual
            if( $user_id !== 0 )
                $sql .= " AND `u`.`id` = $user_id LIMIT 1";
            
            else
                $sql .= " ORDER BY `u`.`lname`";

            // If we're in the Admin section, put the directors first
            if( $sub_dept === 1 )
                $sql = "SELECT * FROM ( $sql ) AS NSCM_Admin ORDER BY (CASE WHEN title = 'Director' THEN 0 WHEN title != 'Director' THEN 1 END)";
            
            // Execute the query.
            $result = mysqli_query( self::db_get(), $sql );
            if( self::_validate( $result, $sql ) )
                return $result;
            else
                return FALSE;
            */
        }


        public static function print_staff( $result, bool $format = FALSE ) : string {

            global $post;

            ob_start();
            ?>
            <div class="row">
            <?php
            
            while( $row = mysqli_fetch_assoc( $result ) ) {
                ?>
                <div class="col-lg-6 col-md-12">
                    <div class="cah-staff-list">
                        <a href="<?= home_url('faculty-staff') ?>?id=<?= $row[ 'id' ] ?>">
                            <div class="staff-list">
                            <?php
                            // If not A-Z List, then show photo.
                            if( $format ) :
                            ?>
                                <div class="media">
                                <?= self::_get_staff_img( 
                                    $row[ 'fullname' ], 
                                    ( !empty( $row[ 'photo_path' ] )  ? $row[ 'photo_path' ]  : "446.jpg" ), 
                                    ( !empty( $row[ 'photo_extra' ] ) ? $row[ 'photo_extra' ] : "" ), 
                                    5
                                ); 
                                ?>
                                    <div class="media-body">

                            <?php endif; // We'll print their name, regardless of format: ?>

                                        <strong><?= $row[ 'fullname' ] ?></strong><br />

                            <?php
                            // Back to non-A-Z List stuff.
                            if( $format ) :
                            ?>
                                        <div class="fs-list">
                                        
                                        <?php 
                                        $title = isset( $row[ 'title_dept_short' ] ) ? $row[ 'title_dept_short' ] : $row[ 'title' ];
                                        
                                        if( $title == 'Director' ) :
                                        ?>
                                            <span class="fa fa-star mr-1 text-primary" aria-hidden="true"></span>
                                        <?php endif; ?>
                                            <em><?= $title ?></em><br />
                                            <?= $row[ 'email' ] ?><br />

                                        <?php // Print research interests, if available.

                                        if( !empty( $row[ 'interests' ] ) || !empty( $row[ 'prog_interests' ] ) ) {

                                            $interests = html_entity_decode( !empty( $row[ 'interests' ] ) ? $row[ 'interests' ] : $row[ 'prog_interests' ], ENT_QUOTES, "utf-8" );

                                            if( stripos( $interests, "<ul>" ) !== FALSE ) {

                                                $interest_arr = array();
                                                
                                                libxml_use_internal_errors( TRUE );
                                                try {
                                                    $xml = new SimpleXMLElement( "<body>$interests</body>");

                                                } catch( Exception $e ) {
                                                    $xml = NULL;
                                                }

                                                if( $xml != NULL ) {
                                                    $xml_parse = $xml->xpath( 'ul/li' );
                                                    foreach( $xml_parse as $interest ) {
                                                        array_push( $interest_arr, trim( $interest ) );
                                                    }

                                                } else {
                                                    $interests = strip_tags( $interests, "<li>" );
                                                    $interest_arr = explode( "<li>", $interests );
                                                }

                                            } else {
                                                $interests = strip_tags( $interests );
                                                $interests = str_ireplace( "<p>", "", $interests );
                                                $interests = str_ireplace( "</p>", "", $interests );
                                                $interests = str_replace( "<br />", "", $interests );
                                                $interests = str_replace( "<br>", "", $interests );

                                                if( preg_match( ";", $interests ) )
                                                    $interest_arr = explode( "; ", $interests );
                                                else if ( preg_match( ",", $interests ) )
                                                    $interest_arr = explode( ", ", $interests );
                                                else if ( preg_match( ".", $interests) && !preg_match( "/.$/", $interests ) )
                                                    $interest_arr = explode( ". ", $interests );
                                            }

                                            $interests_out = "";
                                            foreach( $interest_arr as $idx => $interest ) {
                                                if( strlen( $interests_out ) < 45 && $idx + 1 != count( $interest_arr ) )
                                                    $interests_out .= $interest . ", ";
                                                else if ( $idx + 1 == count( $interest_arr ) )
                                                    $interests_out .= ".";
                                                else
                                                    $interests_out .= "&hellip;";
                                            }
                                            ?>
                                            <span class="fs-interest"><em>Interests:</em> <?= $interests_out ?></span><br />
                                            <?php
                                        }
                                        ?>
                                        </div>
                                        
                            <?php else : ?>
                                        <?= $row[ 'email' ] ?><br />
                                        <?= isset( $row[ 'phone' ] ) ? self::_format_phone_us( $row[ 'phone' ] ) : "" ?>
                            <?php endif; ?>

                            <?php if( $format ) : // Closing out the extra media <div> elements ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            </div>
                        </a>
                    </div>
                </div> <?php // Close column ?>
            <?php
            }
            ?>
            </div> <?php // Close row ?>
            <?php

            self::db_close();
            return ob_get_clean();
        }


        public static function staff_detail( $result ) : string {

            $row = mysqli_fetch_assoc( $result );

            ob_start();
            ?>
            <div class="row flex-column">
                    <div class="media">
                        <?= self::_get_staff_img( $row[ 'fullname' ],
                            (!empty( $row[ 'photo_path'] ) ? $row[ 'photo_path' ] : "446.jpg" ),
                            (!empty( $row[ 'photo_extra' ] ) ? $row[ 'photo_extra' ] : "" ),
                            2
                        ); ?>
                        <div class="media-body">
                            <h4><?= $row[ 'fullname' ] ?></h4>
                            <span class="small"><em>
                                <?php 
                                if( !empty( $row[ 'title_dept' ] ) )
                                    echo $row[ 'title_dept' ];
                                else
                                    echo $row[ 'title' ];
                                ?>
                            </em></span><br />
                            <a href="mailto:<?= $row[ 'email' ] ?>"><?= $row[ 'email' ] ?></a><br />

                            <?php if( $row[ 'phone' ] ) echo self::_format_phone_us( $row[ 'phone' ] ); ?>
                            <br />
                            <?php if( $row[ 'office' ] ) echo "Office Hours: {$row['office']}"; ?>
                            <br />
                            <?php
                            if( !empty($row[ 'room_id' ]) ) {
                                $room_id = $row[ 'room_id' ];
                                $loc = self::_office_location( $room_id );
                                
                                if( !empty( $loc[ 'building_number' ] ) ) : ?>
                                    Campus Location: <a href="https://map.ucf.edu/locations/<?= $loc[ 'building_number' ] ?>" target="_blank">
                                <?php endif; ?>

                                <?= $loc[ 'short_description' ] . $loc[ 'room_number' ]; ?>

                                <?php if( !empty( $loc[ 'building_number' ] ) ) : ?>
                                    </a>
                                <?php endif; ?>
                                    <br />
                                <?php
                            } else if( $row[ 'location' ] ) {
                                ?>
                                    Campus Location: <?= $row[ 'location' ] ?><br />
                                <?php
                            }

                            if( !empty( $row[ 'has_cv' ] ) ) : ?>
                                
                                <a href="https://cah.ucf.edu/common/files/cv/<?= $row[ 'id' ] ?>.pdf">View CV</a><br />

                            <?php elseif( !empty( $row[ 'resume_path' ] ) ) : ?>

                                <a href="<?= $row[ 'resume_path' ] ?>"<?= !preg_match( "ucf.edu", $row[ 'homepage' ] ) ? ' rel="external"' : '' ?>>View CV</a><br />

                            <?php endif; ?>
                        </div>
                    </div>

                <?php if( !empty( $row[ 'biography' ] ) ) : ?>
                    <div class="pt-2">
                        <?= $row[ 'biography' ] ?>
                    </div>
                <?php endif;

                $education = self::_get_education( $row[ 'id' ] );
                if( $education->num_rows > 0 ) :
                ?>
                    <h3 class="heading-underline">Education</h3>
                        <ul>
                    <?php while( $edu_row = mysqli_fetch_assoc( $education ) ) : ?>
                            <li>
                                <?= trim( $edu_row[ 'short_description' ] ) 
                                    . ( !empty( $edu_row[ 'field' ] ) ? " in " . trim( $edu_row[ 'field' ] ) : "" )
                                    . ( !empty( $edu_row[ 'institution' ] ) ? " from " . trim( $edu_row[ 'institution' ] ) : "" )
                                    . ( !empty( $edu_row[ 'year' ] ) ? " ({$edu_row['year']})" : "" )
                                ?>
                            </li>
                    <?php endwhile; ?>
                        </ul>
                <?php endif;

                if( !empty( $row[ 'interests' ] ) ) :
                    $interests = html_entity_decode( $row['interests'], ENT_QUOTES, "utf-8" );
                ?>
                    <h3 class="heading-underline">Research Interests</h3>
                    <?php if( preg_match( "<ul>", $row['research'] ) ) :?>
                    <?= $interests ?>
                    <?php else : ?>
                    <p><?= $interests ?></p>
                    <?php endif; ?>

                <?php endif;

                if( !empty( $row[ 'research' ] ) ) :
                    $research = html_entity_decode( $row['research'], ENT_QUOTES, "utf-8" );
                ?>
                    <h3 class="heading-underline">Recent Research Activities</h3>

                    <?php if( preg_match( "<ul>", $row['research'] ) ) :?>
                    <?= $research ?>
                    <?php else : ?>
                    <p><?= $research ?></p>
                    <?php endif; ?>
                
                <?php endif;

                $publications = self::_get_publications( self::parse_int( $row[ 'id' ] ) );
                if( $publications->num_rows > 0 ) :
                ?>
                    <h3 class="heading-underline">Selected Publications</h3>

                    <?php
                    $pub_type = "";
                    $i = 0;

                    while( $pub_row = mysqli_fetch_assoc( $publications ) ) :
                        if( $i != 0 && strcmp( $pub_type, $pub_row[ 'pubtype' ] ) ) : ?>
                        </ul>
                        <?php endif; ?>

                        <?php if( strcmp( $pub_type, $pub_row[ 'pubtype' ] ) ) :?>
                        <h4 class="pt-4"><?= $pub_row[ 'pubtype' ] ?></h4>
                        <ul>
                        <?php endif; ?>

                            <li><?= ( $pub_row[ 'forthcoming' ] ? '<em>Forthcoming</em> ' : '') . $pub_row[ 'publish_date' ] . " " . html_entity_decode( $pub_row[ 'citation' ], ENT_QUOTES, "utf-8"); ?></li>
                        <?php
                        $i++;
                        $pub_type = $pub_row[ 'pubtype' ];

                    endwhile; ?>
                        </ul>
                <?php endif; ?>

                <?php if( !empty($courseHTML = self::_get_course_list( self::parse_int( $row[ 'id' ] ) ) ) ) : ?>
                    
                    <h3 class="heading-underline">Courses</h3>
                    <?= $courseHTML ?>

                <?php endif; ?>
            </div>

            <?php
            self::db_close();
            return ob_get_clean();
        }


        public static function parse_int( $value ) : int {

            if( !is_numeric( $value ) ) return 0;
            else return intval( $value );
        }


        private static function _get_staff_img( string $fullname, string $filename = "profilephoto.jpg", string $extra = "", int $size = 5 ) : string {

            $resize_url = "https://cah.ucf.edu/common/resize.php";
            $classes = array( 'img-circle', 'mr-3' );

            if( $filename == 'profilephoto.jpg' ) array_push( $classes, 'd-flex' );

            ob_start();
            ?>
            <img class="<?= implode( " ", $classes ); ?>" src="<?= $resize_url ?>?filename=<?= $filename ?><?= $extra ?>&sz=<?= $size ?>" alt="<?= $fullname ?>">
            <?php

            return ob_get_clean();
        }


        private static function _format_phone_us( string $phone ) {

            if( !isset( $phone ) ) return "";

            $phone = preg_replace( "/[^0-9]/", "", $phone );
            switch( strlen( $phone ) ) {

                case 7:
                    return preg_replace( "/(\d{3})(\d{4})/", "$1-$2", $phone );
                    break;
                case 10:
                    return preg_replace( "/(\d{3})(\d{3})(\d{4})/", "($1) $2-$3", $phone );
                    break;
                case 11:
                    return preg_replace( "/(\d)(\d{3})(\d{3})(\d{4})/", "+$1 ($2) $3-$4", $phone );
                    break;
                default:
                    return $phone;
                    break;
            }
        }


        private static function _office_location( $room_id ) {

            $sql = self::$query_lib->get_query_str( FSQEnum::USER_OFFICE, $room_id );

            if( $result = self::_run_query( $sql ) ) {
                $row = mysqli_fetch_assoc( $result );
                mysqli_free_result( $result );

                return $row;
            }
            /*
            $sql = "SELECT `room_number`, `buildings`.`short_description`, `building_numbers` FROM `rooms` LEFT JOIN `buildings` ON `building_id` = `buildings`.`id` WHERE `rooms`.`id` = $room_id";

            $result = mysqli_query( self::db_get(), $sql );
            self::_validate( $result, $sql );
            $row = mysqli_fetch_assoc( $result );
            mysqli_free_result( $result );

            return $row;
            */
        }


        private static function _get_education( $user_id ) {

            $sql = self::$query_lib->get_query_str( FSQEnum::USER_EDU, $user_id );

            if( $result = self::_run_query( $sql ) ) return $result;
            else return FALSE;

            /*
            $sql = "SELECT * FROM `education` LEFT JOIN `degrees` ON `education`.`degrees_id` = `degrees`.`id` WHERE `user_id` = $user_id ORDER BY `year` DESC";

            $result = mysqli_query( self::db_get(), $sql );

            if( self::_validate( $result, $sql ) ) return $result;
            else return FALSE;
            */
        }


        private static function _get_publications( $user_id, bool $approved = TRUE ) {

            $sql = self::$query_lib->get_query_str( FSQEnum::USER_PUB, $user_id, $approved );

            if( $result = self::_run_query( $sql ) ) return $result;
            else return FALSE;

            /*
            $sql = "SELECT `publications`.`id`, `photo_path`, `forthcoming`, DATE_FORMAT( `publish_date`, '%M %Y' ) AS pubdate, `citation`, `plural_description` AS pubtype FROM `publications` LEFT JOIN `publications_categories` ON `publications`.`publication_id` = `publications_categories`.`id` WHERE `user_id` = $user_id AND `approved` = $approved ORDER BY `level`, `pubtype`, `publish_date`, `desc`, `citation`";

            $result = mysqli_query( self::db_get(), $sql );

            if( self::_validate( $result, $sql ) ) return $result;
            else return FALSE;
            */
        }

        private static function _get_course_list( $user_id = 0, $term = "", $career = "", $catalog_ref_filter_any = "", $catalog_ref_filter_none = "", $prefix_filter_any = "" ) {

            $terms = array();
            $term_courses = array();
            $current_term = "";

            $sql_term = "";
            $sql_aux = "";

            $summer_flag = FALSE;

            $syllabus_url_base = "https://cah.ucf.edu/common/files/syllabi/";

            $career = "";

            if( empty( $term ) ) {
                $sql = self::$query_lib->get_query_str( FSQEnum::TERM_GET );
                $result = self::_run_query( $sql );

                while( $row = mysqli_fetch_assoc( $result ) ) {

                    if( $row[ 'term' ] != '-' ) {

                        array_push( $terms, $row[ 'term' ] );

                        if( empty( $sql_term ) ) {
                            $sql_term = "`term` IN (";

                        } else {
                            $sql_term .= ",";
                        }
                        $sql_term .= "'{$row['term']}'";
                    }
                }

                if( !empty( $sql_term ) ) $sql_term .= ") ";

                $current_term = self::_get_semester();

            } else {
                array_push( $terms, $term );
                $current_term = $term;
                $sql_term = "`term` = '" . mysqli_real_escape_string( self::db_get(), $term ) . "'";
            }

            if( !empty( $catalog_ref_filter_any ) ) {

                if( !empty( $sql_filter = self::_parse_filters( $catalog_ref_filter_any ) ) )
                    $sql_aux .= " AND $sql_filter";
            }

            if( !empty( $catalog_ref_filter_none ) ) {

                if( !empty( $sql_filter = self::_parse_filters( $catalog_ref_filter_none, FALSE ) ) )
                    $sql_aux .= " AND $sql_filter";
            }

            if( !empty( $prefix_filter_any ) ) {

                if( !empty( $sql_filter = self::_parse_filters( $prefix_filter_any, TRUE, TRUE ) ) )
                    $sql_aux .= " AND $sql_filter";
            }

            $sql = self::$query_lib->get_query_str( FSQEnum::COURSE_LIST, $user_id, $sql_term, $sql_aux, $career );
            $result = self::_run_query( $sql );
            
            if( $result->num_rows == 0) return "";

            while( $row = mysqli_fetch_assoc( $result ) ) {

                $term_idx = trim( $row[ 'term' ] );

                if( preg_match( "/summer/i", $term_idx ) )
                    $summer_flag = TRUE;
                
                if( empty( $term_courses[ $term_idx ] ) ) {
                    
                    ob_start();
                    ?>
                    <table class="table table-condensed table-bordered table-striped volumes" cellspacing="0" title="<?= $term_idx ?> Offered Courses">
                        <thead>
                            <tr>
                                <th>Course Number</th>
                                <th>Course</th>
                                <th>Title</th>
                                <th>Mode</th>
                                
                                <?php if( $summer_flag ) : ?>
                                <th>Session</th>
                                <?php endif; ?>

                                <th>Date and Time</th>
                                <th>Syllabus</th>
                            </tr>
                        </thead>
                        <tbody>
                    <?php
                    $term_courses[ $term_idx ] = ob_get_clean();
                }

                ob_start();
                ?>
                            <tr>
                                <td><?= $row[ 'number' ] ?></td>
                                <td><?= trim( $row[ 'catalogref' ] ); ?></td>
                                <td><?= trim( $row[ 'title' ] ); ?></td>
                                <td><?= trim( $row[ 'instruction_mode' ] ); ?></td>

                                <?php if( $summer_flag ) : ?>
                                <td><?= trim( $row[ 'session' ] ); ?>
                                <?php endif; ?>

                                <td><?= trim( $row[ 'dateandtime' ] ); ?></td>
                                <td>
                                <?php if( !empty( $row[ 'syllabus_file' ] ) ) : ?>
                                    <a href="<?= $syllabus_url_base . str_replace( " ", "", $row[ 'catalogref' ] ) . $row[ 'section' ] . $row[ 'term' ] . ".pdf" ?>" rel="external">Aviailable</a>
                                <?php else: ?>
                                    Unavailable
                                <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="<?= $summer_flag ? 7 : 6 ?>">
                                    <?= $row[ 'description' ] ?>
                                </td>
                            </tr>
                <?php
                $term_courses[ $term_idx ] .= ob_get_clean();
            }

            ob_start();
            ?>
            <div style="width: 100%;">
                <ul class="nav nav-tabs" id="courseTab" role="tablist">
            <?php
            $term_labels = str_replace( " ", "", $terms );

            for( $c = 0; $c < count( $terms ); $c++ ) {
                ?>
                    <li class="nav-item">
                        <a class="nav-link <?= !strcmp( $current_term, $terms[ $c ] ) ? "active" : "" ?>" data-toggle="tab" href="#<?= $term_labels[ $c ] ?>" role="tab" aria-controls="<?= $term_labels[ $c ] ?>"><?= $terms[ $c ] ?></a>
                    </li>
                <?php
            }
            ?>
                </ul>
            </div>

            <div class="tab-content">
            <?php

            for( $c = 0; $c < count( $terms ); $c++ ) {
                ?>
                    <div class="pt-3 tab-pane <?= !strcmp( $current_term, $terms[ $c ] ) ? "active" : "" ?>" id="<?= $term_labels[ $c ] ?>" role="tabpanel">

                <?php if( !empty( $term_courses[ $terms[ $c ] ] ) ) : ?>

                    <?= $term_courses[ $terms[ $c ] ] ?></div>
                        </tbody>
                    </table>
                </div>

                <?php else: ?>

                     <p>No courses found for <?= $terms[ $c ] ?></p></div>

                <?php endif;
            }

            self::db_close();

            return ob_get_clean();
        }


        private static function _get_semester() {

            $now = getdate();
            $term = "";

            switch( $now[ 'mon' ] ) {

                case 10:
                case 11:
                case 12:
                    $term = "Spring " . ( intval( $now['year'] ) + 1 );
                    break;

                case 1:
                case 2:
                    $term = "Spring {$now['year']}";
                    break;

                case 3:
                case 4:
                case 5:
                case 6:
                    $term = "Summer {$now['year']}";
                    break;

                default:
                    $term = "Fall {$now['year']}";
                    break;
            }

            return $term;
        }


        private static function _parse_filters( $catalog_ref, bool $in = TRUE, bool $prefix_only = FALSE ) {
            
            $sql_filter = "";

            if( $prefix_only )
                $statement_begin = "`prefix` ";
            else
                $statement_begin = "CONCAT( `prefix`, `catalog_number` ) ";

            if( is_array( $catalog_ref ) )
                    $filters = $catalog_ref;
                
            else
                $filters = explode( ",", $catalog_ref );

            $sql_filter = "";

            foreach( $filters as $filter ) {

                if( !empty( $sql_filter ) ) $sql_filter .= " , ";

                else $sql_filter = $statement_begin . ( !$in ? "NOT " : "" ) . "IN(";

                $sql_filter .= "'" . strtoupper( $filter ) . "'";
            }

            if( !empty( $sql_filter ) ) $sql_filter .= ")";

            return $sql_filter;
        }
    }
}
?>