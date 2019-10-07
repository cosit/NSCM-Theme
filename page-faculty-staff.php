<?php
/**
 * Template Name: Faculty and Staff Page Template
 * Template Post Type: Page
 * Description: A Page template for displaying Faculty and Staff directory information. Based on previous template by Mannong Pang.
 * Author: Mike W. Leavitt
 * Version: 2.0.0
 */
// require_once 'faculty-staff-includes/faculty-staff-functions.php';

use FacultyStaffHelper as FSH;

get_header();
?>

<div class="container mb-5 mt-3 mt-lg-5" style="min-height: 250px;">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <p id="print-result"></p>
            </div>
        </div>
        <div class="row">
            <div class="col-md-3" id="dept-menu-div">
                <div class="dropdown">
                    <button class="btn btn-secondary dropdown-toggle" type="button" id="sub_dept" data-toggle="dropdown" auto-close="disabled">
                        Filter
                    </button>
                    <div class="dropdown-menu">
                        <a class="dropdown-item" id="0">A&ndash;Z List</a>
                        <a class="dropdown-item" id="1">Administration</a>
                        <a class="dropdown-item" id="2">Advising</a>
                        <?php
                        if( $result = FSH::get_menu_categories() ) {

                            while( $row = mysqli_fetch_assoc( $result ) ) {
                                
                                if( preg_match( "/Advising/i", $row['description'] ) ) continue;

                                $desc = strlen( $row['description'] ) <= 30 ? $row['description'] : $row['short_description'];
                                ?>
                                <a class="dropdown-item" id="<?= intval( $row[ 'id' ] ) ?>"><?= $desc ?></a>
                                <?php
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
            <div class="col-md-9" id="cah-faculty-staff">
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>