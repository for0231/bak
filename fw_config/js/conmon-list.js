(function ($, Drupal) {
  "use strict";

  Drupal.behaviors.conmon_list = {
    attach: function (context, settings) {
      $('#ip-fblink-query').click(function(){
        var ip = $('#ip-fblink').val();
        var re=/^(\d+)\.(\d+)\.(\d+)\.(\d+)$/;
        if(re.test(ip)) {
          if(!(RegExp.$1<256 && RegExp.$2<256 && RegExp.$3<256 && RegExp.$4<256)) {
            alert('请输入正确的IP！');
            return false;
          }
        } else {
          alert('请输入正确的IP！');
          return false;
        }
        var _btn = $(this);
        _btn.prop('disabled', true);
        var param = {};
        param['param'] = ip;
        var url = Drupal.url('admin/fw/conmon/query');
        $.ajax({
          type: "POST",
          url: url,
          data: param,
          dataType: "json",
          success: function(data) {
            if(data.status == 'false') {
              alert(data.msg);
            }
            $('#query-content').html(data.content);
            _btn.removeAttr('disabled');
          }
        });
      });
    }
  }
})(jQuery, Drupal)
