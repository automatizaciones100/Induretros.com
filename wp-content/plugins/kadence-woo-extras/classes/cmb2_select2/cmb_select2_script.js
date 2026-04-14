(function ($) {
    'use strict';

    $('.pw_select').each(function () {
        $(this).select3({
            allowClear: true
        });
    });

    $.fn.extend({
        select3_sortable: function () {
            var select = $(this);
            $(select).select3();
            var ul = $(select).next('.select3-container').first('ul.select3-selection__rendered');
            ul.sortable({
                containment: 'parent',
                items      : 'li:not(.select3-search--inline)',
                tolerance  : 'pointer',
                stop       : function () {
                    $($(ul).find('.select3-selection__choice').get().reverse()).each(function () {
                        var id = $(this).data('data').id;
                        var option = select.find('option[value="' + id + '"]')[0];
                        $(select).prepend(option);
                    });
                }
            });
        }
    });

    $('.pw_multiselect').each(function () {
        $(this).select3_sortable();
    });

    // Before a new group row is added, destroy Select3. We'll reinitialise after the row is added
    $('.cmb-repeatable-group').on('cmb2_add_group_row_start', function (event, instance) {
        var $table = $(document.getElementById($(instance).data('selector')));
        var $oldRow = $table.find('.cmb-repeatable-grouping').last();

        $oldRow.find('.pw_select3').each(function () {
            $(this).select3('destroy');
        });
    });

    // When a new group row is added, clear selection and initialise Select3
    $('.cmb-repeatable-group').on('cmb2_add_row', function (event, newRow) {
        $(newRow).find('.pw_select').each(function () {
            $('option:selected', this).removeAttr("selected");
            $(this).select3({
                allowClear: true
            });
        });

        $(newRow).find('.pw_multiselect').each(function () {
            $('option:selected', this).removeAttr("selected");
            $(this).select3_sortable();
        });

        // Reinitialise the field we previously destroyed
        $(newRow).prev().find('.pw_select').each(function () {
            $(this).select3({
                allowClear: true
            });
        });

        // Reinitialise the field we previously destroyed
        $(newRow).prev().find('.pw_multiselect').each(function () {
            $(this).select3_sortable();
        });
    });

    // Before a group row is shifted, destroy Select3. We'll reinitialise after the row shift
    $('.cmb-repeatable-group').on('cmb2_shift_rows_start', function (event, instance) {
        var groupWrap = $(instance).closest('.cmb-repeatable-group');
        groupWrap.find('.pw_select3').each(function () {
            $(this).select3('destroy');
        });

    });

    // When a group row is shifted, reinitialise Select3
    $('.cmb-repeatable-group').on('cmb2_shift_rows_complete', function (event, instance) {
        var groupWrap = $(instance).closest('.cmb-repeatable-group');
        groupWrap.find('.pw_select').each(function () {
            $(this).select3({
                allowClear: true
            });
        });

        groupWrap.find('.pw_multiselect').each(function () {
            $(this).select3_sortable();
        });
    });

    // Before a new repeatable field row is added, destroy Select3. We'll reinitialise after the row is added
    $('.cmb-add-row-button').on('click', function (event) {
        var $table = $(document.getElementById($(event.target).data('selector')));
        var $oldRow = $table.find('.cmb-row').last();

        $oldRow.find('.pw_select3').each(function () {
            $(this).select3('destroy');
        });
    });

    // When a new repeatable field row is added, clear selection and initialise Select3
    $('.cmb-repeat-table').on('cmb2_add_row', function (event, newRow) {

        // Reinitialise the field we previously destroyed
        $(newRow).prev().find('.pw_select').each(function () {
            $('option:selected', this).removeAttr("selected");
            $(this).select3({
                allowClear: true
            });
        });

        // Reinitialise the field we previously destroyed
        $(newRow).prev().find('.pw_multiselect').each(function () {
            $('option:selected', this).removeAttr("selected");
            $(this).select3_sortable();
        });
    });
})(jQuery);