(function($){
    "use strict";

    // Grab the values we sent with wp_localize_script()
    // sec is the nonce value, for double-checking the validity of the request
    // on the back-end.
    const ajaxUrl = pageVars.url,
        sec = pageVars.security;
    
    // Setting prefabs for boilerplate HTML
    const hTxtBeg = '<h2 class="pl-2 mb-4 heading-underline">',
        hTxtEnd = '</h2>';

    const loadingImg = $('<div></div>').attr({id: 'loading-img', class: 'mt-5'});
    
    
    /**
     * Sends the AJAX request to admin-ajax.php and processes the response.
     * 
     * @author Mike W. Leavitt
     * @since 1.0.0
     * 
     * @param {string} text The text for the <h2> element, if asking for a subdepartment page.
     * @param {int} sub The subdepartment number, corresponding to the listing in the cah.academic_categories table.
     * @param {int} uId The user's id, corresponding to their entry in the cah.users table.
     * 
     * @return {void}
     */
    const getStaff = (text = 'A&ndash;Z List', sub = 0, uId = 0) => {

        // Display the little "loading" gif, so the user understands that
        // the data is coming.
        $('#cah-faculty-staff').html('').append(loadingImg);

        // Setting the data we'll send.
        const postData = {
            action: 'print_faculty_staff',
            security: sec,
            sub_dept: sub,
            id: uId
        };

        // The AJAX request.
        $.ajax({
            url: ajaxUrl,
            method: 'POST',
            data: postData,
            success: result => {
                
                if(!uId) {
                    // If we have no userId, we'll have to add the EventListeners to the various faculty
                    // entries, in addition to providing the section heading.
                    $('#cah-faculty-staff').html(hTxtBeg + text + hTxtEnd + result);
                    addHandlers();

                } else {
                    $('#cah-faculty-staff').html(result);
                }
            }
        }).fail(result => { console.log(result); });
    };


    // Adds EventListeners to the staff list entries, so that clicking on an entry will pull
    // up the user's detailed profile.
    const addHandlers = () => {

        $('.cah-staff-list a').click( function(e) {

            e.preventDefault();
            const patt = new RegExp( /\?id=(\d+)/ );
            const userId = parseInt( patt.exec( $(this).attr('href') )[1] );
            
            getStaff('', 0, userId );
        });
    };


    // Setting up to parse the $_GET variables, for direct linking to subdepartments and/or users.
    let getVars = {};

    // The onLoad function.
    $(document).ready( $ => {

        // Populate the members of our getVars variable, above.
        parseGetVars();

        // The first course tab in an individual user display doesn't show by default, so we make 
        // it pop up.
        $('#courseTab ul li:first-child a').tab('show');

        // Show different stuff depending upon the contents of $_GET. We check for User ID first,
        // so that will take precedence over a subdepartment parameter, if both are present.
        if( getVars.id ) {
            getStaff('', 0, getVars.id);
        }
        else if( getVars.sub ) {
            // We want to make sure we get the right text and set the correct link to active.
            const text = $('#' + getVars.sub).text();
            $('#' + getVars.sub).addClass('active');
            getStaff( text, getVars.sub );
        }
        else {
            // If nothing in particular, just show the A-Z List
            $('#dept-menu-div nav div.collapse .nav.flex-column .nav-item:first-child .nav-link').addClass('active');
            getStaff();
        }

        // Adds the EventListeners to the subdepartment menu navigation buttons.
        $('#dept-menu-div nav div.collapse ul li .nav-link').click( function(e) {
            e.preventDefault();
            $('#dept-menu-div nav div.collapse ul li .nav-link').removeClass('active');
            $(this).addClass('active');
            const textClicked = $(this).text();

            getStaff(textClicked, $(this).attr('id'));
        });
    });


    /**
     * Parses the $_GET variables into an object, for ease of reference later.
     * 
     * @author Mike W. Leavitt
     * @since 1.0.0
     */
    function parseGetVars() {
        let parts = window.location.search.substr(1).split('&');

        parts.forEach(function(part) {
            let pair = part.split('=');

            // Parsing as an {int} because we'll never need anything else in this script.
            getVars[pair[0]] = parseInt(pair[1]);
        });
    }
})(jQuery);