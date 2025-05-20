jQuery(document).ready(function($) {
    function disableWeekends(date) {
        var day = date.getDay();
        return [(day != 0 && day != 6), ''];
    }

    $("#event_start").datetimepicker({
        dateFormat: 'yy-mm-dd',
        timeFormat: 'HH:mm',
        minDate: 0,
        beforeShowDay: disableWeekends,
        onSelect: function(selectedDateTime) {
            $("#event_end").datetimepicker("option", "minDate", selectedDateTime);
        }
    });

    $("#event_end").datetimepicker({
        dateFormat: 'yy-mm-dd',
        timeFormat: 'HH:mm',
        minDate: 0,
        beforeShowDay: disableWeekends
    });
});
