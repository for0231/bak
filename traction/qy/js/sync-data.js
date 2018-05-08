(function ($, Drupal) {
  "use strict";

  Drupal.behaviors.sync_data = {
    attach: function (context) {
      $('#edit-submit').click(function(){
        if(confirm('确定要同步数据')) {
          var _dialog = Drupal.dialog('<div>同步请求发送成功, 正在处理....</div>', {
            title: '数据同步',
            width: 400,
            height: 100,
          });
          _dialog.showModal();
          $('.ui-dialog button').hide();
          var user = $('#edit-sync-user').val();
          var pass = $('#edit-sync-password').val();
          var url = $(this).attr('sync-url');
          $.ajax({
            type: "GET",
            url: url,
            data: "user="+user+"&pass="+pass+"&callback=?",
            dataType: "JSONP",
            jsonp: "callback",
            success: function(data) {
              _dialog.close();
              alert(data.info);
            },
            error: function() {
              _dialog.close();
              alert('更新失败！');
            }
          });
        }
        return false;
      })
    }
  }
})(jQuery, Drupal)