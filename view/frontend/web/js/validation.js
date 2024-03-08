define([
    'jquery',
    'moment',
    'mageUtils',
    'jquery/validate',
    'validation',
    'mage/translate'
], function ($, moment, utils) {
    'use strict';

    $.validator.addMethod(
        'validate-due-date-format',
        function (value, element, params) {
            var dateFormat = utils.normalizeDate(params.dateFormat);

            if (value === '') {
                return true;
            }

            return moment(value, dateFormat, true).isValid();
        },
        $.mage.__('Invalid date')
    );

    $.validator.addMethod(
        'validate-due-date',
        function (value, element, params) {
            var dateFormat = utils.convertToMomentFormat(params.dateFormat);

            if (value === '') {
                return true;
            }

            return moment(value, dateFormat).isAfter(moment().subtract(1, 'days'));
        },
        $.mage.__('The Product due date should not be less than today.')
    );

    $.validator.addMethod(
        'validate-pomodoro-duration',
        function (value, element, params) {
            return (value >= 10 && value <= 120);
        },
        $.mage.__('The Duration should be between 10 and 120 minutes.')
    );
});
