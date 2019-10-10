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
            <div class="col-md-4 mb-4" id="dept-menu-div">
                <nav class="navbar navbar-toggleable-sm" role="navigation">
                    <button class="navbar-toggler collapsed bg-primary btn btn-block text-secondary mb-1" type="button" data-toggle="collapse" data-target="#filter-bar" aria-controls="filter-bar" aria-expanded="false" aria-label="Toggle Filter">
                        FILTER &#11206;
                    </button>
                    <div class="collapse navbar-collapse border-0" id="filter-bar">
                        <ul class="nav flex-column nav-pills nav-justified">
                            <li class="nav-item">
                                <a class="nav-link" id="0">
                                    <p>A&ndash;Z List</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="1">
                                    <p>Administration</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="2">
                                    <p>Advising</p>
                                </a>
                            </li>
                            <?php
                            if( $result = FSH::get_menu_categories() ) {
                                while( $row = mysqli_fetch_assoc( $result ) ) {
                                    if( stripos( $row[ 'description' ], "Advising" ) ) continue;

                                    $desc = strlen( $row['description'] ) <= 30 ? $row['description'] : $row['short_description'];
                                    ?>
                                    <li class="nav-item">
                                        <a class="nav-link" id="<?= intval( $row['id'] ) ?>">
                                            <p><?= $desc ?></p>
                                        </a>
                                    </li>
                                    <?php
                                }
                            }
                            ?>
                        </ul>
                    </div>
                </nav>
            </div>
            <div class="col-md-8" id="cah-faculty-staff">
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>