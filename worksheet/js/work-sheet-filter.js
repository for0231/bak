(function ($, Drupal) {
  "use strict";

  Drupal.behaviors.work_sheet_filter = {
    attach: function (context) {
      $('#filter_begin, #filter_end', context).datepicker({
        changeMonth: true,
        changeYear: true,  
        dateFormat: 'yy-mm-dd',
        monthNamesShort: ['一月', '二月', '三月', '四月', '五月', '六月', '七月', '八月', '九月', '十月', '十一月', '十二月']
      });
      $('#filter_submit',context).click(function(){
        var url = location.pathname;
        var parms = Array();
        parms['keyword'] = $('#filter_keyword').val();
        parms['type'] = $('#filter_type').val();
        parms['creater'] = $('#filter_creater').val();
        parms['hander'] = $('#filter_hander').val();
        parms['begin'] = $('#filter_begin').val();
        parms['end'] = $('#filter_end').val();
        var parm_str = '';
        var tmp_value = '';
        for(var parm in parms) {
          tmp_value = parms[parm];
          if($.trim(tmp_value) != '' && tmp_value != 'all') {
            if(parm_str == '') {
              parm_str += parm + '=' + parms[parm];
            } else {
              parm_str += "&" + parm + '=' + parms[parm];
            }
          }
        }
        if(parm_str=='') {
          alert('请输入查询条件');
          return;
        }
        location.href = url + "?" + parm_str;
      })
    }
  }
})(jQuery, Drupal)
