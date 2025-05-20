jQuery(document).ready(function($) {
    function disableWeekends(date) {
        const day = date.getDay();
        return [(day !== 0 && day !== 6), ''];
    }

    $('.admin-datepicker').datetimepicker({
        dateFormat: 'yy-mm-dd',
        timeFormat: 'HH:mm',
        minDate: 0,
        beforeShowDay: disableWeekends,
        controlType: 'select',
        oneLine: true
    });
    
    
    /* custom status in event post admin edit screen start */
    if ($('#post_status option[value="scheduled"]').length === 0) {
        $('#post_status').append('<option value="scheduled">Scheduled</option>');
    }

    if ($('#post_status option[value="rejected"]').length === 0) {
        $('#post_status').append('<option value="rejected">Rejected</option>');
    }

    if ($('#hidden_post_status').val() === 'scheduled') {
        $('#post_status').val('scheduled');
        $('.misc-pub-post-status span').text('Scheduled');
    }

    if ($('#hidden_post_status').val() === 'rejected') {
        $('#post_status').val('rejected');
        $('.misc-pub-post-status span').text('Rejected');
    }
    /* custom status in event post admin edit screen end */
    
});
