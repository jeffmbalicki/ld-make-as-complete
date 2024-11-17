

jQuery(document).ready(function($) {

    console.log('loaded');    var table = new DataTable('#markcomplete');

    $(document).on('click','.mark_as_complete', function() {
        var button = $(this);
        var user_id = $(this).attr('data-user-id');
        var course_id = $(this).attr('data-course-id');
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
            action: 'mark_as_complete', user_id : user_id,  course_id : course_id, 
                
            },
            success: function(response) {
        
            button.html('completed');
            },
            error: function(xhr, status, error) {
            console.log(error);
            }
        });
    });

});