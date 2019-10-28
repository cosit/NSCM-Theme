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
        const DEPT_SUB_GENERAL = 3;
        const DEPT_USER = 4;
        const USER_OFFICE = 5;
        const USER_EDU = 6;
        const USER_PUB = 7;
        const TERM_GET = 8;
        const COURSE_LIST = 9;
        const ACAD_CATS = 10;
    }
}
?>