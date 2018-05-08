(function ($, Drupal) {
  "use strict";
  
  Drupal.behaviors.respool_time = {
    attach: function (context) {
      var type = $('#edit-type').val();
      $('#edit-start-time,#edit-end-time,#edit-rent-time',context).datepicker({
        changeMonth: true,
        changeYear: true,  
        dateFormat: 'yy-mm-dd',
        monthNamesShort: ['一月', '二月', '三月', '四月', '五月', '六月', '七月', '八月', '九月', '十月', '十一月', '十二月']
      });
    }
  }
  
})(jQuery, Drupal)
