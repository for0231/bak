(function ($, Drupal) {
  "use strict";

  Drupal.behaviors.work_sheet_operate = {
    attach: function (context) {
      var is_reason = false;
      $('#edit-abnormal-audit,#edit-abnormal-accept,#edit-abnormal-quality').click(function(){
        var _self = $(this);
        if(is_reason) {
          return true;
        }
        Drupal.dialog('<div><textarea id="abnormal_reason" style="width:100%;height:100%;"></textarea></div>', {
          title: '异常原因',
          width: 400,
          height: 300,
          buttons: [{
            text: ' 提交 ',
            click: function() {
              var reason = $("#abnormal_reason", this).val();
              if(reason == '') {
                alert('异常原因不能为空');
                return false;
              }
              $('#other_info').val(reason);
              is_reason = true;
              _self.click();
            }
          },{
            text: ' 关闭 ',
            click: function() {
              $(this).dialog('close');
            }
          }]
        }).showModal();
        return false;
      });
      $('#edit-deliver-info').click(function(){
        var info = $('#deliver-info-show').html();
        var html = '<div><textarea id="deliver-info-text" style="width:100%;height:100%;">'+ info +'</textarea></div>';
        Drupal.dialog(html, {
          title: '交付信息',
          width: 500,
          height: 400,
          buttons: [{
            text: '复制交付信息',
            click: function() {
              var testarea = $("#deliver-info-text", this);
              testarea.select();
              document.execCommand("Copy");
            }
          }]
        }).showModal();
        return false;
      });
    }
  }
})(jQuery, Drupal)
