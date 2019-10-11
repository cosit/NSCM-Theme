<?php
/**
 * A static helper class both to register all our scripts and things with WordPress and to
 * handle the heavy lifting for our back-end functionality. Many of the core functions are
 * updated/streamlined versions of code by Mannong Pang.
 * 
 * @author Mike W. Leavitt
 * @version 2.0.0
 */

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


        /**
         * Register our action hooks with Wordpress. All the methods are found in this class.
         * (Called with add_action() from functions.php).
         * 
         * @author Mike W. Leavitt
         * @since 2.0.0
         * 
         * @return void
         */
        public static function action_hooks() {
            add_action( 'wp_enqueue_scripts', array( __CLASS__, 'load_scripts' ), 9, 0 );
            add_action( 'wp_default_scripts', array( __CLASS__, 'jquery_footer' ), 5, 1 );
            add_action( 'wp_ajax_print_faculty_staff', array( __CLASS__, 'print_faculty' ), 10, 0 );
            add_action( 'wp_ajax_nopriv_print_faculty_staff', array( __CLASS__, 'print_faculty' ), 10, 0 );
        }


        /**
         * Load our JavaScript and pass the values we need with wp_localize_script().
         * 
         * @author Mike W. Leavitt
         * @since 2.0.0
         * 
         * @return void
         */
        public static function load_scripts() {

            // Don't want to load this if we're in the Dashboard.
            if( is_admin() ) return;

            // Load our CSS
            wp_enqueue_style( 'faculty-staff-style', get_stylesheet_directory_uri() .'/faculty-staff-includes/static/css/faculty-staff.min.css' );

            // Load our JS. Come on, you know how this works by now... :P
            wp_enqueue_script( 'faculty-staff-script', get_stylesheet_directory_uri() . '/faculty-staff-includes/static/js/faculty-staff.min.js', array( 'jquery', 'script' ), '1.0.0', TRUE );

            // Stuff to pass to our page's JavaScript. The "security" field is a nonce
            // we're creating to make sure it's still the user making requests on the
            // back-end.
            wp_localize_script( 'faculty-staff-script', 'pageVars', array(
                    'url' => admin_url( 'admin-ajax.php' ), // The URL we'll use in $.ajax()
                    'dept' => DEPT, // I'm not sure we actually use this one.
                    // A nonce for an added layer of back-end security.
                    'security' => wp_create_nonce( 'ajax-nonce' )
                )
            );
        }


        /**
         * Make WordPress load JQuery in the footer, if at all possible, to speed up load times.
         * If another script's dependenccy requires JQuery to be loaded in the header, this won't
         * really have any effect.
         * 
         * @author Mike W. Leavitt
         * @since 2.0.0
         * 
         * @param object $wp_scripts | The script handler object thingy for WordPress.
         * 
         * @return void
         */
        public static function jquery_footer( $wp_scripts ) {

            // We don't want to load this if we're in the Dashboard.
            if( is_admin() ) return;

            // Modifying the jQuery entries in the $wp_scripts object to load in the footer.
            $wp_scripts->add_data( 'jquery', 'group', 1 );
            $wp_scripts->add_data( 'jquery-core', 'group', 1 );
            $wp_scripts->add_data( 'jquery-migrate', 'group', 1 );
        }


        /**
         * Our AJAX back-end handler, called by admin-ajax.php for both wp_ajax_print_faculty_staff and 
         * wp_ajax_nopriv_print_faculty_staff (since we'll want the same behavior regardless of
         * whether the user is logged in).
         * 
         * @author Mike W. Leavitt
         * @since 2.0.0
         * 
         * @return void;
         */
        public static function print_faculty() {

            // Check the nonce. Calls die() if it fails.
            check_ajax_referer( 'ajax-nonce', 'security' );

            // Set the subdepartment ID and/or user ID, if applicable.
            // Probably won't ever have both set at once, but preferences subdepartment.
            $sub_dept = isset( $_POST[ 'sub_dept'] ) ? $_POST[ 'sub_dept' ] : 0;
            $user_id = isset( $_POST[ 'id' ] ) ? $_POST[ 'id' ] : 0;

            // Gets the relevant staff query result.
            $result = self::get_NSCM_staff( DEPT, intval( $sub_dept ), intval( $user_id ) );

            // If we've defined a subdepartment, we'll go get that.
            if( intval( $sub_dept ) !== 0 )
                echo self::print_staff( $result, TRUE );

            // Or, if we've defined a user, we'll get them instead.
            else if( intval( $user_id ) !== 0 )
                echo self::staff_detail( $result );

            // Barring that, we'll get e'er'body.
            else
                echo self::print_staff( $result );

            // Important to remind the script to die at the end, for security's sake.
            die();
        }


        /**
         * Connect to the database, or return the active connection, if one exists.
         * 
         * @author Mike W. Leavitt
         * @since 2.0.0
         * 
         * @return mysqli_connection $db_connection | The static class member connection object.
         */
        public static function db_get() {
            // Global database variables, set in dbconfig.php
            global $db_user, $db_pass, $db, $db_server;

            // If we've already got a connection open, just return that.
            if( self::$db_connection ) return self::$db_connection;

            // Otherwise, create one, and kill the script if it doesn't work.
            self::$db_connection = mysqli_connect( $db_server, $db_user, $db_pass ) or exit( 'Could not connect to server.' );
            mysqli_set_charset( self::$db_connection, 'utf8' ); // Set charset to UTF-8.
            // Select the appropriate database.
            mysqli_select_db( self::$db_connection, $db ) or exit( 'Could not select database.' );

            // Return the new connection.
            return self::$db_connection;
        }


        /**
         * Closes the database connection, when we're done.
         * 
         * @author Mike W. Leavitt
         * @since 2.0.0
         * 
         * @return void
         */
        public static function db_close() {
            global $db_connection;

            // If there's a database connection, close it, and set the global to FALSE.
            if( $db_connection !== FALSE ) mysqli_close( $db_connection );
            $db_connection = FALSE;
        }


        /**
         * Instantiates the FacultyStaffQueryLib helper object, for concocting the SQL strings
         * we'll need.
         * 
         * @author Mike W. Leavitt
         * @since 2.0.0
         * 
         * @param int|string $dept | The department ID that we'll be getting. Defaults to the value
         *                              of the DEPT constant, if defined (usually in the
         *                              theme's functions.php)
         * 
         * @return FacultyStaffQueryLib $query_lib | The FacultyStaffQueryLib object instance.
         */
        public static function get_fsq_lib( $dept = DEPT ) : FSQLib {

            // If we haven't instantiated this yet, go ahead and do it.
            if( is_null( self::$query_lib) || !is_a( self::$query_lib, 'FacultyStaffQueryLib' ) )
                self::$query_lib = new FSQLib( $dept );
            
            // If we have, return it. This saves us having to create and destroy it every time.
            return self::$query_lib;
        }


        /**
         * Runs a given query, then validates and returns the results.
         * 
         * @author Mike W. Leavitt
         * @since 2.0.0
         * 
         * @param string $sql | The SQL query we'll be running.
         * 
         * @return mysqli_result|bool $result|FALSE | Return the query result, or FALSE on error.
         */
        private static function _run_query( string $sql )  {

            // Do the thing.
            $result = mysqli_query( self::db_get(), $sql );

            // Check to see if something went wrong, and return FALSE if it did.
            if( self::_validate( $result, $sql, TRUE ) ) return $result;
            else return FALSE;

        }


        /**
         * Makes sure there was a result, and dies with a message to the user if there wasn't.
         * 
         * @author Mike W. Leavitt
         * @since 2.0.0
         * 
         * @param mysqli_result $result | The result of an SQL query.
         * @param string        $sql    | The SQL query string to print if $debug is TRUE.
         * @param bool          $debug  | Whether or not to display verbose query messaging for debug
         *                                  purposes.
         * 
         * @return bool (anon) | Whether the SQL validates or not. Since the process dies on failure,
         *                          only ever actually returns TRUE.
         */
        private static function _validate( $result, string $sql, bool $debug = FALSE ) : bool {
            $msg = "";

            // If there's a problem with the query, $result will evaluate to FALSE. If we
            // call this with DEBUG on, then a problem will cause output to the screen.
            if( !$result ) {
                
                if( $debug ) {
                    $msg .= "<p>Invalid query: " . mysqli_error( self::db_get() ) . "</p>";
                    $msg .= "<p>Query: " . $sql . "</p>";

                } else {
                    $msg .= "There was a database problem. Please report this to <a href=\"mailto:cahweb@ucf.edu\">cahweb@ucf.edu</a>";
                }

                die( $msg );

            } else return TRUE; // If the variable exists, though, everything's fine.
        }


        /**
         * A function to get the categories for the Filter menu, called from page-faculty-staff.php
         * 
         * @author Mike W. Leavitt
         * @since 2.0.0
         * 
         * @param int|string $dept | The department we're looking for. Defaults to the value of the
         *                              DEPT constant (usually defined in the theme's functions.php).
         * 
         * @return mysqli_result|bool $result | The result of the query, or FALSE if no result.
         */
        public static function get_menu_categories( int $dept = DEPT) {
            // Grab/create the SQL library object.
            self::get_fsq_lib( $dept );

            // Use it to get the SQL
            $sql = self::$query_lib->get_query_str( FSQEnum::ACAD_CATS );
            
            // Run the query for the menu categories.
            if( $result = self::_run_query( $sql ) ) return $result;
            else return FALSE;
        }


        /**
         * The main "switchboard" of the class. Determines which query string to retrieve from the
         * FacultyStaffQueryLib object and run.
         * 
         * @author Mike W. Leavitt
         * @since 2.0.0
         * 
         * @param int|string $dept      | Department number. Defaults to value of DEPT constant.
         * @param int|string $sub_dept  | The subdepartment, in case we're trying to be more specific.
         * @param int|string $user_id   | The user ID, in case we're looking for a specific staff member.
         * 
         * @return mysqli_result|bool $result | The results of the query, or FALSE if no result.
         */
        public static function get_NSCM_staff( $dept = DEPT, $sub_dept = 0, $user_id = 0 ) {

            // Just in case it hasn't been created yet.
            self::get_fsq_lib( $dept );

            $sql = "";

            // 1 is for Administration
            if( $sub_dept === 1 ) 
                $sql = self::$query_lib->get_query_str( FSQEnum::DEPT_ADMIN );

            // 2 is for Advising
            else if( $sub_dept === 2 )
                $sql = self::$query_lib->get_query_str( FSQEnum::DEPT_STAFF );

            // This catches any of the department-specific subdepartments
            else if( $sub_dept !== 0 )
                $sql = self::$query_lib->get_query_str( FSQEnum::DEPT_SUB_GENERAL, $sub_dept );

            // This handles a request for a single staff member's info.
            else if( $user_id !== 0 )
                $sql = self::$query_lib->get_query_str( FSQEnum::DEPT_USER, $user_id );

            // This is the general query for e'er'body.
            else
                $sql = self::$query_lib->get_query_str( FSQEnum::DEPT_ALL );

            // If we get a result, return it, or return FALSE.
            if( $result = self::_run_query( $sql ) ) return $result;
            else return FALSE;
        }


        /**
         * Prints a list of staff members--either the entire department or a single subdepartment.
         * 
         * @author Mike W. Leavitt
         * @since 2.0.0
         * 
         * @param mysqli_result $result | The results from get_NSCM_staff() that we'll be sifting through.
         * @param bool          $format | Whether or not to show the staff photo. Defaults to FALSE.
         * 
         * @return string (anon) | The buffered HTML output of the list, to be put on the page.
         */
        public static function print_staff( $result, bool $format = FALSE ) : string {

            ob_start(); // Starts an output buffer, so I can write clearer HTML.
            ?>
            <div class="row">
            <?php
            
            // Iterate through all the entries.
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
                                <?= self::_get_staff_img( //Get image
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
                            
                            $title = isset( $row['title_dept-short'] ) ? $row['title_dept_short'] : $row['title'];

                            // Back to non-A-Z List stuff.
                            if( $format ) :
                            ?>
                                        <div class="fs-list">
                                            <small>
                                                <p class="staff-title">
                                        
                                        <?php 
                                        // $title = isset( $row[ 'title_dept_short' ] ) ? $row[ 'title_dept_short' ] : $row[ 'title' ];
                                        
                                        // Putting Directors at the front. It's good to be the king.
                                        if( $title == 'Director' ) :
                                        ?>
                                                    <span class="fa fa-star mr-1 text-primary" aria-hidden="true"></span>
                                        <?php endif; ?>
                                                    <em><?= $title ?></em>
                                                </p>
                                                <?= $row[ 'email' ] ?><br />

                                        <?php 
                                        /* Can add Interests field, if desired...

                                        // Print research interests, if available.
                                        if( !empty( $row[ 'interests' ] ) || !empty( $row[ 'prog_interests' ] ) ) {

                                            $interests = html_entity_decode( !empty( $row[ 'interests' ] ) ? $row[ 'interests' ] : $row[ 'prog_interests' ], ENT_QUOTES, "utf-8" );

                                            // Processes, normalizes, and or abbreviates the interests.
                                            $interests_out = self::_interests_short( $interests );
                                            ?>
                                                <p class="fs-interest"><em>Interests:</em> <?= $interests_out ?></p>
                                            <?php
                                        }
                                        */
                                        ?>
                                            </small>
                                        </div>
                                        
                            <?php else : ?>
                                        <small>
                                            <span class="staff-title">
                                                <em><?= $title ?></em>
                                            </span><br />
                                            <?= $row[ 'email' ] ?><br />
                                            <?= isset( $row[ 'phone' ] ) ? self::_format_phone_us( $row[ 'phone' ] ) : "" ?>
                                        </small>
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
            // Free up the result memory.
            mysqli_free_result( $result );
            ?>
            </div> <?php // Close row ?>
            <?php

            self::db_close(); // Close out the database connection, to save on memory.

            return ob_get_clean(); // Return the buffered HTML
        }


        /**
         * Shows a single staff member's entry with more details, in case the user clicks on one.
         * 
         * @author Mike W. Leavitt
         * @since 2.0.0
         * 
         * @param mysqli_result $result | The result of the query from get_NSCM_staff().
         * 
         * @return string (anon) | The buffered HTML output of the entry, to be put on the page.
         */
        public static function staff_detail( $result ) : string {

            // For an individual staff member, we should only have one result, and we don't really care about the rest.
            $row = mysqli_fetch_assoc( $result );

            // We don't need the result anymore, just the row.
            mysqli_free_result( $result );

            ob_start(); // Start the output buffer.
            ?>
            <div class="staff-detail row flex-column">
                    <div class="media">
                        <?= self::_get_staff_img( // Get the faculty portrait.
                            $row[ 'fullname' ],
                            (!empty( $row[ 'photo_path'] ) ? $row[ 'photo_path' ] : "profilephoto.jpg" ),
                            (!empty( $row[ 'photo_extra' ] ) ? $row[ 'photo_extra' ] : "" ),
                            2
                        ); ?>
                        <div class="media-body">
                            <h4><?= $row[ 'fullname' ] ?></h4>
                            <span><em>
                                <?php 
                                if( !empty( $row[ 'title_dept' ] ) ) // Display their title
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
                                $loc = self::_office_location( $room_id ); // Get office number
                                
                                // Display office number, based on what came back from the query.
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

                            if( !empty( $row[ 'has_cv' ] ) ) : // Display resume/CV, if available ?>
                                
                                <a href="https://cah.ucf.edu/common/files/cv/<?= $row[ 'id' ] ?>.pdf">View CV</a><br />

                            <?php elseif( !empty( $row[ 'resume_path' ] ) ) : ?>

                                <a href="<?= $row[ 'resume_path' ] ?>"<?= !preg_match( "ucf.edu", $row[ 'homepage' ] ) ? ' rel="external"' : '' ?>>View CV</a><br />

                            <?php endif; ?>
                        </div>
                    </div>

                <?php if( !empty( $row[ 'biography' ] ) ) : // Display Bio ?>
                    <div class="pt-2">
                        <?= $row[ 'biography' ] ?>
                    </div>
                <?php endif;

                $education = self::_get_education( $row[ 'id' ] ); // Get educational background.
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

                if( !empty( $row[ 'interests' ] ) ) : // Show full list of research interests.
                    $interests = html_entity_decode( $row['interests'], ENT_QUOTES, "utf-8" );
                ?>
                    <h3 class="heading-underline">Research Interests</h3>
                    <?php if( preg_match( "<ul>", $row['research'] ) ) :?>
                    <?= $interests ?>
                    <?php else : ?>
                    <p><?= $interests ?></p>
                    <?php endif; ?>

                <?php endif;

                if( !empty( $row[ 'research' ] ) ) : // Show recent research.
                    $research = html_entity_decode( $row['research'], ENT_QUOTES, "utf-8" );
                ?>
                    <h3 class="heading-underline">Recent Research Activities</h3>

                    <?php if( preg_match( "<ul>", $row['research'] ) ) :?>
                    <?= $research ?>
                    <?php else : ?>
                    <p><?= $research ?></p>
                    <?php endif; ?>
                
                <?php endif;

                // Get and display publications, if the user has listed any in Manager.
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

                <?php if( !empty($courseHTML = self::_get_course_list( self::parse_int( $row[ 'id' ] ) ) ) ) : // Get a list of courses the staff member is teaching, if any. ?>
                    
                    <h3 class="heading-underline">Courses</h3>
                    <?= $courseHTML ?>

                <?php endif; ?>
            </div>

            <?php
            
            self::db_close(); // Close the database connection.

            return ob_get_clean(); // Return the buffered HTML.
        }


        /**
         * Returns and integer. Probably deprectaed, actually--I don't think any of the things use it
         * anymore...
         * 
         * @author Mike W. Leavitt
         * @since 2.0.0
         * 
         * @param int|string $value | The value to be integerized.
         * 
         * @return int $value | The same value, only cast to an integer.
         */
        public static function parse_int( $value ) : int {

            if( !is_numeric( $value ) ) return 0;
            else return intval( $value );
        }


        /**
         * Gets the appropriate staff image, formatted as HTML.
         * 
         * @author Mike W. Leavitt
         * @since 2.0.0
         * 
         * @param string $fullname | The staff member's name.
         * @param string $filename | The staff member's profile photo, from their photo_path entry.
         *                              Defaults to "profilephoto.jpg," an empty silhouette.
         * @param string $extra    | The value of the staff member's photo_extra field, if any. Defaults
         *                              to an empty string.
         * @param int    $size     | The size code for the image. Defaults to 5.
         * 
         * @return string (anon) | Buffered HTML string containing the approprite <img> tag.
         */
        private static function _get_staff_img( $fullname, string $filename = "profilephoto.jpg", string $extra = "", int $size = 5 ) : string {

            // Set the base URL and classes we want every image to have.
            $resize_url = "https://cah.ucf.edu/common/resize.php";
            $classes = array( 'img-circle', 'mr-3' );

            // Add the extra class for the blank, generic silhouette photo.
            if( $filename == 'profilephoto.jpg' ) array_push( $classes, 'd-flex' );

            ob_start(); // Start output buffer and create <img> tag to return.
            ?>
            <img class="<?= implode( " ", $classes ); ?>" src="<?= $resize_url ?>?filename=<?= $filename ?><?= $extra ?>&sz=<?= $size ?>" alt="<?= $fullname ?>">
            <?php

            return ob_get_clean(); // Return buffered HTML
        }


        /**
         * Formats a phone number, depending on its length. We're assuming a US number, for the sake
         * of expediency and convenience.
         * 
         * @author Mike W. Leavitt
         * @since 2.0.0
         * 
         * @param string $phone | The phone number to parse.
         * 
         * @return string $phone | The parsed and rearranged phone number, or the original if it doesn't
         *                          meet standard US length requirements.
         */
        private static function _format_phone_us( string $phone ) {

            if( !isset( $phone ) ) return "";

            $phone = preg_replace( "/[^0-9]/", "", $phone );
            switch( strlen( $phone ) ) {

                case 7: // It's a "local" number, without an area code or country code.
                    return preg_replace( "/(\d{3})(\d{4})/", "$1-$2", $phone );
                    break;
                case 10: // It's a US number with area code, but no country code.
                    return preg_replace( "/(\d{3})(\d{3})(\d{4})/", "($1) $2-$3", $phone );
                    break;
                case 11: // It's a US number with the leading country code, as well.
                    return preg_replace( "/(\d)(\d{3})(\d{3})(\d{4})/", "+$1 ($2) $3-$4", $phone );
                    break;
                default: // It's not a US number, so just bounce it back.
                    return $phone;
                    break;
            }
        }


        /**
         * Retrieves information about the staff member's office location. Called by staff_detail().
         * 
         * @author Mike W. Leavitt
         * @since 2.0.0
         * 
         * @param int|string $room_id | The id number of the room in the `rooms` table.
         * 
         * @return array $row | We should only have one result, so we return the row as an array.
         */
        private static function _office_location( $room_id ) {

            // If we've gotten this far, we've definitely instantiated the FSQLib object.
            $sql = self::$query_lib->get_query_str( FSQEnum::USER_OFFICE, $room_id );

            // If we find stuff, we only care about the first row (there should only be one anyway).
            if( $result = self::_run_query( $sql ) ) {
                $row = mysqli_fetch_assoc( $result );
                mysqli_free_result( $result );

                return $row;
            }
        }


        /**
         * Get the staff member's education history, if listed. Called by staff_detail().
         * 
         * @author Mike W. Leavitt
         * @since 2.0.0
         * 
         * @param int|string $user_id | The staff member's ID in the `users` table.
         * 
         * @return mysqli_result|bool $result | Returns the results of the query, or FALSE if no result.
         */
        private static function _get_education( $user_id ) {

            // Again, if we're here, we already have this.
            $sql = self::$query_lib->get_query_str( FSQEnum::USER_EDU, $user_id );

            // Do the thing! (A lot of these helper functions look alike.)
            if( $result = self::_run_query( $sql ) ) return $result;
            else return FALSE;
        }


        /**
         * Retrieves the staff member's listed publications, if any. Called by staff_detail().
         * 
         * @author Mike W. Leavitt
         * @since 2.0.0
         * 
         * @param int|string $user_id  | The staff member's ID in the `users` table.
         * @param bool       $approved | Whether or not the publication is approved. Defaults to TRUE.
         * 
         * @return mysqli_result|bool $result | Returns the results of the query, or FALSE if no result.
         */
        private static function _get_publications( $user_id, bool $approved = TRUE ) {

            // Grab the query library object and get some SQL.
            $sql = self::$query_lib->get_query_str( FSQEnum::USER_PUB, $user_id, $approved );

            // Run the query.
            if( $result = self::_run_query( $sql ) ) return $result;
            else return FALSE;
        }


        /**
         * Retrieves the list of courses for the upcoming academic year. Called by staff_detail().
         * A lot of these extra parameters aren't used in the current iteration of this script,
         * but they might come in handy later on, for other purposes.
         * 
         * @author Mike W. Leavitt
         * @since 2.0.0
         * 
         * @param int|string $user_id                 | The staff member's ID in the `users` table.
         * @param string     $term                    | The specific term, if applicable. Defaults to
         *                                                  empty.
         * @param string     $career                  | The specific career (Undergraduate or Graduate).
         *                                                  Defaults to empty.
         * @param string     $catalog_ref_filter_any  | A filter for the course results.
         * @param string     $catalog_ref_filter_none | Another filter for the results, but negative.
         * @param string     $prefix_filter_any       | Another filter, for specific course prefixes.
         * 
         * @return string (anon) | Buffered HTML containing the tabbed course list, sorted by semester.
         */
        private static function _get_course_list( $user_id = 0, $term = "", $career = "", $catalog_ref_filter_any = "", $catalog_ref_filter_none = "", $prefix_filter_any = "" ) {

            // Initialize some stuff, since we'll be referring to it throughout:

            $terms = array(); // Will be a list of the terms we'll be grabbing courses for
            $term_courses = array(); // Will be a list of the courses, sorted by term
            $current_term = ""; // The current term, so we know where to begin.

            $sql_term = ""; // Beginning of SQL statement we'll be using later on
            $sql_aux = ""; // End of the same string.

            $summer_flag = FALSE; // Checks whether to display/look for summer courses.

            // The location where we keep what syllabi we have.
            $syllabus_url_base = "https://cah.ucf.edu/common/files/syllabi/";

            $career = ""; // Graduate or Undergraduate, in case we need to narrow things down.

            // If we don't have a target term, get the current one, then get the others.
            if( empty( $term ) ) {
                $sql = self::$query_lib->get_query_str( FSQEnum::TERM_GET );
                $result = self::_run_query( $sql );

                while( $row = mysqli_fetch_assoc( $result ) ) {

                    if( $row[ 'term' ] != '-' ) {

                        array_push( $terms, $row[ 'term' ] ); // Add to the array

                        if( empty( $sql_term ) ) {
                            $sql_term = "`term` IN ("; // Define the SQL

                        } else {
                            $sql_term .= ",";
                        }
                        $sql_term .= "'{$row['term']}'";
                    }
                }

                if( !empty( $sql_term ) ) $sql_term .= ") "; // Close the parentheses, if need be

                $current_term = self::_get_semester(); // Grab the current term

            } else {
                // If term is defined, just add it to the $terms array and add it to the SQL
                array_push( $terms, $term );
                $current_term = $term;
                $sql_term = "`term` = '" . mysqli_real_escape_string( self::db_get(), $term ) . "'";
            }

            // Any course prefixes/numbers we explicitly want *in* the query
            if( !empty( $catalog_ref_filter_any ) ) {

                if( !empty( $sql_filter = self::_parse_filters( $catalog_ref_filter_any ) ) )
                    $sql_aux .= " AND $sql_filter";
            }

            // Any course prefixes/numbers we explicitly *don't* want in the query
            if( !empty( $catalog_ref_filter_none ) ) {

                if( !empty( $sql_filter = self::_parse_filters( $catalog_ref_filter_none, FALSE ) ) )
                    $sql_aux .= " AND $sql_filter";
            }

            // If we just want prefixes
            if( !empty( $prefix_filter_any ) ) {

                if( !empty( $sql_filter = self::_parse_filters( $prefix_filter_any, TRUE, TRUE ) ) )
                    $sql_aux .= " AND $sql_filter";
            }

            // Generate the SQL and run the query.
            $sql = self::$query_lib->get_query_str( FSQEnum::COURSE_LIST, $user_id, $sql_term, $sql_aux, $career );
            $result = self::_run_query( $sql );
            
            if( $result->num_rows == 0) {
                mysqli_free_result( $result );
                self::db_close();
                return ""; // If we find nothing, we return empty.
            }

            // Iterate through the courses.
            while( $row = mysqli_fetch_assoc( $result ) ) {

                $term_idx = trim( $row[ 'term' ] );

                //if( preg_match( "/summer/i", $term_idx ) )
                if( stripos( $term_idx, "summer" ) )
                    $summer_flag = TRUE;
                
                // If this is the first time we're doing this, open the table and create the
                // header row
                if( empty( $term_courses[ $term_idx ] ) ) {
                    
                    ob_start(); // Start output buffer
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
                    $term_courses[ $term_idx ] = ob_get_clean(); // Store buffered HTML
                }

                ob_start(); // Start output buffer again, and spit out the row.
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
                $term_courses[ $term_idx ] .= ob_get_clean(); // Store buffered HTML
            }

            // Free up the memory from the result.
            mysqli_free_result( $result );

            ob_start(); // Start output buffer again, and create the nav tabs.
            ?>
            <div style="width: 100%;">
                <ul class="nav nav-tabs" id="courseTab" role="tablist">
            <?php
            $term_labels = str_replace( " ", "", $terms );

            // Using a standard for loop to avoid problems with PHP's foreach iterator getting
            // stuck at the last item.
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

            // Same thing here, for the same reason, only creating the nav panes this time
            for( $c = 0; $c < count( $terms ); $c++ ) {
                ?>
                    <div class="pt-3 tab-pane <?= !strcmp( $current_term, $terms[ $c ] ) ? "active" : "" ?>" id="<?= $term_labels[ $c ] ?>" role="tabpanel">

                <?php if( !empty( $term_courses[ $terms[ $c ] ] ) ) : // Print info if we've got it ?>

                    <?= $term_courses[ $terms[ $c ] ] ?></div>
                        </tbody>
                    </table>
                </div>

                <?php else: ?>

                     <p>No courses found for <?= $terms[ $c ] ?></p></div>

                <?php endif;
            }

            return ob_get_clean(); // Return buffered HTML
        }


        /**
         * Determines the proper semester to start displaying. Called from _get_course_list().
         * 
         * @author Mike W. Leavitt
         * @since 2.0.0
         * 
         * @return string $term | The first term to show, with semester and four-digit year.
         */
        private static function _get_semester() {

            $now = getdate();
            $term = "";

            switch( $now[ 'mon' ] ) {

                case 10:
                case 11:
                case 12: // The Fall has already started, so start with Spring the following year.
                    $term = "Spring " . ( intval( $now['year'] ) + 1 );
                    break;

                case 1:
                case 2: // Spring again, but without adding to the year.
                    $term = "Spring {$now['year']}";
                    break;

                case 3:
                case 4:
                case 5:
                case 6: // The Spring semester, Summer is upcoming.
                    $term = "Summer {$now['year']}";
                    break;

                default: // Otherwise, we're focused on Fall
                    $term = "Fall {$now['year']}";
                    break;
            }

            return $term; // Return whatever we've come up with.
        }


        /**
         * Handles the modifications to the SQL statements called for by the given filter(s). Called
         * from _get_course_list().
         * 
         * @author Mike W. Leavitt
         * @since 2.0.0
         * 
         * @param string|array $catalog_ref | The filter(s) to use.
         * @param bool $in | Whether this filter will explicitly include or exclude things. Defaults
         *                      to TRUE.
         * @param bool $prefix_only | Wether to filter only the course prefixes. Defaults to FALSE.
         * 
         * @return string $sql_filter | The additional SQL, to further refine the course query.
         */
        private static function _parse_filters( $catalog_ref, bool $in = TRUE, bool $prefix_only = FALSE ) {

            // Shows whether we're only looking for the prefixes, or the entire course number.
            if( $prefix_only )
                $statement_begin = "`prefix` ";
            else
                $statement_begin = "CONCAT( `prefix`, `catalog_number` ) ";

            // If it's an array, store it.
            if( is_array( $catalog_ref ) )
                    $filters = $catalog_ref;
                
            // If not, make it an array and store it.
            else
                $filters = explode( ",", $catalog_ref );

            // Initialize.
            $sql_filter = "";

            foreach( $filters as $filter ) { // Go through and apply each filter.

                if( !empty( $sql_filter ) ) $sql_filter .= " , ";

                // Put "NOT" before "IN()" if $in === FALSE
                else $sql_filter = $statement_begin . ( !$in ? "NOT " : "" ) . "IN(";

                $sql_filter .= "'" . strtoupper( $filter ) . "'";
            }

            // If we found anything, close the parentheses.
            if( !empty( $sql_filter ) ) $sql_filter .= ")";

            // Return the filter part of the SQL
            return $sql_filter;
        }


        /**
         * Retrieves and formats a truncated version of a faculty or staff member's stated
         * research interests. Current limit is 45 characters. Called from staff_detail().
         * 
         * @author Mike W. Leavitt
         * @since 2.0.0
         * 
         * @param string $interests | The string drawn from the field in staff_detail().
         * 
         * @return string $interests_out | The formatted and normalized string of interests.
         */
        private static function _interests_short( string $interests ) {

            // If it's formatted as an unordered list, do this:
            if( stripos( $interests, "<ul>" ) !== FALSE ) {

                $interest_arr = array();
                
                // NEVER USE REGEX TO PARSE HTML
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

                } else { // If something goes wrong with the parser, do it this way instead.
                    $interests = strip_tags( $interests, "<li>" );
                    $interest_arr = explode( "<li>", $interests );
                }

            } else {
                // If it's in paragraph form, strip out the tags we don't want, so we just
                // have plain text.
                $interests = strip_tags( $interests );
                $interests = str_ireplace( "<p>", "", $interests );
                $interests = str_ireplace( "</p>", "", $interests );
                $interests = str_replace( "<br />", "", $interests );
                $interests = str_replace( "<br>", "", $interests );

                // Break up any one of the weird ways the users delimit their lists of interests.
                // Using strpos() and substr_count() because it's cheaper than any of the preg_*
                // family of functions.
                if( strpos( $interests, ";" ) !== FALSE )
                    $interest_arr = explode( ";", $interests );
                else if( strpos( $interests, "," ) !== FALSE )
                    $interest_arr = explode( ",", $interests );
                else if( strpos( $interests, "." ) !== FALSE && ( substr_count( $interests, "." ) > 1 || strpos( $interests, ".", -1 ) === FALSE ) )
                    $interest_arr = explode( ".", $interests );
            }

            // Format everything nicely, with commas and stuff, putting in an ellipsis if the
            // list isn't complete.
            $interests_out = "";
            foreach( $interest_arr as $idx => $interest ) {

                $interests_out .= trim( $interest );

                if( $idx + 1 == count( $interest_arr ) ) {
                    $interests_out .= ".";
                    break;
                }

                if( strlen( $interests_out ) >= 45 ) { 
                    $interests_out .= "&hellip;";
                    break;

                } else {
                    $interests_out .= ", ";
                }
            }

            // Return the shiny, new formatted string.
            return $interests_out;
        }
    }
}
?>