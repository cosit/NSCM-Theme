(function($){
    "use strict";

    const ajaxUrl = pageVars.url,
        sec = pageVars.security,
        dept = pageVars.dept;
    
    const hTxtBeg = '<h2 class="pl-2 mb-4 heading-underline">',
        hTxtEnd = '</h2>';
    
    const getStaff = (text = 'A&ndash;Z List', sub = 0, uId = 0) => {

        console.log(uId);

        const postData = {
            action: 'print_faculty_staff',
            security: sec,
            dept_id: dept,
            sub_dept: sub,
            id: uId
        };

        $.ajax({
            url: ajaxUrl,
            method: 'POST',
            data: postData,
            success: result => {
                
                if(!uId) {
                    $('#cah-faculty-staff').html(hTxtBeg + text + hTxtEnd + result);
                    addHandlers();

                } else {
                    $('#cah-faculty-staff').html(result);
                }
            }
        }).fail(result => { console.log(result); });
    }

    const addHandlers = () => {

        $('.cah-staff-list a').click( function(e) {

            e.preventDefault();
            const patt = new RegExp( /\?id=(\d+)/ );
            const userId = parseInt( patt.exec( $(this).attr('href') )[1] );
            
            getStaff('', 0, userId );
        });
    }

    $(document).ready( $ => {

        $('.dropdown-menu a:first-child').addClass('active');
        $('.dropdown-menu').dropdown('toggle');
        $('#courseTab ul li:first-child a').tab('show');

        getStaff();

        $('.dropdown-menu a').click( function(e) {
            e.stopPropagation();
            $('.dropdown-menu a').removeClass('active');
            $(this).addClass('active');
            const textClicked = $(this).text();

            getStaff(textClicked, $(this).attr('id'));
        });
    })
})(jQuery);