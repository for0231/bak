(function ($, Drupal) {
  "use strict";

  Drupal.behaviors.work_sheet_history = {
    attach: function (context) {
      $('#edit-created-begin, #edit-created-end', context).datepicker({
        changeMonth: true,
        changeYear: true,  
        dateFormat: 'yy-mm-dd',
        monthNamesShort: ['一月', '二月', '三月', '四月', '五月', '六月', '七月', '八月', '九月', '十月', '十一月', '十二月']
      });
      $('#tanchuang tbody tr').click(function(){
        var json =  $('.row-detail',this).html();
        var data = eval('('+json+')');
        $("#wid_code").val(data.code);
        $("#person").val(data.person);
        $("#status").val(data.status);
        $("#type").val(data.type);
        $("#ip").val(data.ip);
        $("#client").val(data.client);
        $("#created").val(data.created);
        $("#abnormal").val(data.abnormal);
        $("#abnormal_res").val(data.abnormal_res);
     })
    }
  }
})(jQuery, Drupal)
