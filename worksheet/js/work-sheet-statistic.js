(function ($, Drupal) {
  "use strict";

  Drupal.behaviors.work_sheet_statistic = {
    attach: function (context) {
      $('#edit-begin, #edit-end', context).datepicker({
        changeMonth: true,
        changeYear: true,  
        dateFormat: 'yy-mm-dd',
        monthNamesShort: ['一月', '二月', '三月', '四月', '五月', '六月', '七月', '八月', '九月', '十月', '十一月', '十二月']
      });
    }
  }
})(jQuery, Drupal)
