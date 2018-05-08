(function ($, Drupal) {
  "use strict";
  
  Drupal.behaviors.fblink_list = {
    attach: function (context, settings) {
      //查询
      $('#ip-fblink-query').click(function(){
    	  //本地地址
        var iplocal = $('#ip-iplocal').val();
        //远程地址
        var ipremote = $('#ip-ipremote').val();
        var re=/^(\d+)\.(\d+)\.(\d+)\.(\d+)$/;
        if(re.test(iplocal)) {
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
        param['param_iplocal'] = iplocal;
        param['param_ipremote'] = ipremote;
        var url = Drupal.url('admin/fw/fblink/query');
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
            Drupal.tableSelect.call(document.getElementById('fw-link-content'));
          }
        });
      });
      //重置
      $('#ip-fblink-reset').click(function(){
        var list = [];
        $('#fw-link-content tr td input:checked').each(function(){
          var _tr = $(this).parents('tr');
          var fw = _tr.find('td:eq(1)').html();
          var local_ip = _tr.find('td:eq(2)').html();
          var remote_ip = _tr.find('td:eq(3)').html();
          list.push(fw+'|'+local_ip + '-' + remote_ip);
        });
        if(list.length == 0 ){
          alert('请先选择再重置！')
          return false;
        }
        var _btn = $(this);
        _btn.prop('disabled', true);
        var param = {};
        param['param_filter'] = list.join(',');
        var url = Drupal.url('admin/fw/fblink/reset');
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
            alert('重置成功');
            $('#ip-fblink-query').click();
          }
        });
      });
    }
  }
})(jQuery, Drupal)
