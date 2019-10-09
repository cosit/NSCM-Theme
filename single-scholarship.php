<style>
    /* Fix overflowing images */
    img {
        max-width: 100%;
        height: auto;
    }

    a.btn {
        font-size: .9rem;
    }
</style>

<?php get_header();
//error_reporting(E_ALL);
//ini_set('display_errors', 1);
?>

<?php
global $post;
$custom = get_post_custom($post->ID);
$external_url = $custom["external_url"][0];
$deadline = $custom["deadline"][0];
?>


<?php the_post(); ?>

<div class="container mb-5 mt-3 mt-lg-5" style="min-height:250px;">
    <article class="<?php echo $post->post_status; ?> post-list-item">


        <?php
        /* if ( has_post_thumbnail() )

        the_post_thumbnail('medium', array( 'class' => 'rounded float-left mr-3' )); */


        if($external_url != "")
            echo "<p class=\"float-lg-right\"><a class='btn btn-primary' target='_blank' href='{$external_url}'>Visit Scholarship Website</a></p>";

        if($deadline != "") {
            echo '<span class="h3">Deadline: ';
            echo date("F j, Y", strtotime($deadline));

            echo '</span><br><br>';
        }

        the_content();

        ?>

    </article>

</div>


<?php get_footer(); ?>

