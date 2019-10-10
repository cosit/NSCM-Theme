<?php
/**
 * An Enum class meant to make generating the SQL statements for the Faculty/Staff page
 * more human-readable.
 * 
 * @author Mike W. Leavitt
 * @version 1.0.0
 */
if( !class_exists( 'FacultyStaffQueryRef' ) ) {

    abstract class FacultyStaffQueryRef {

        const __default = self::DEPT_ALL;

        const DEPT_ALL = 1;
        const DEPT_ADMIN = 2;
        const DEPT_STAFF = 3;
        const DEPT_SUB_GENERAL = 4;
        const DEPT_USER = 5;
        const USER_OFFICE = 6;
        const USER_EDU = 7;
        const USER_PUB = 8;
        const TERM_GET = 9;
        const COURSE_LIST = 10;
        const ACAD_CATS = 11;
    }
}
?>