<?php
require_once 'faculty-staff-functions.php';

use FacultyStaffHelper as FSH;

$sub_dept = 0;
$id = 0;

if( isset( $_POST[ 'sub-dept' ] ) ) {

    $sub_dept = FSH::parse_int( $_POST[ 'sub-dept' ] );
    $result = FSH::get_NSCM_staff( DEPT, $sub_dept );

} else if( isset( $_GET[ 'id' ] ) || isset( $_POST[ 'id' ] ) ) {

    $id = FSH::parse_int( isset( $_GET[ 'id' ] ) ? $_GET[ 'id' ] : $_POST[ 'id' ] );
    $result = FSH::get_NSCM_staff( DEPT, 0, $id );

} else {
    $result = FSH::get_NSCM_staff( DEPT );
}

if( $sub_dept == 0 && $id == 0 )
    echo FSH::print_staff( $result );
else if( $sub_dept != 0 )
    echo FSH::print_staff( $result, TRUE );
else if( $id != 0 )
    echo FSH::staff_detail( $result );
?>

<script>
    (function($) {
        $('#courseTab a:first').tab('show');
        $('#courseTab a:first').parent().addClass('pl-5');
    })(jQuery);
</script>