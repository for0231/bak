(function ($, Drupal) {

  "use strict";

  Drupal.behaviors.user_letter = {
    attach: function (context) {
      $('.isShowContent').click(function() {

        var parent_obj = $(this).parents('.zhanneix');
        var content_obj = parent_obj.find('.zhanneirong');

        // 该邮件是否已经被阅读
        var is_read_obj = parent_obj.find('.is_read');
        var is_read = is_read_obj.val();
        // 没有被阅读则发送ajax请求修改阅读状态
        if(is_read != 1) {
          // 查看的信件的编号
          var id = parent_obj.find('.letter_id').val();
          $.ajax({
            type : 'POST',
            url : Drupal.url('letter/setRead'),
            data : {'letter_id': id},
            dataType: 'json',
            success : function(msg){
              // 显示新的未读信件的数量
              $('.count').html(msg);
              // 修改读取后的信件的图标
              var img_obj = parent_obj.find('.unread');
              img_obj.attr('src', '/themes/xunyun/images/hasread.jpg');
            }
          });
        }
        // 显示/隐藏显示信件内容的div
        if(content_obj.is(':hidden')) {
          content_obj.show();
        } else {
          content_obj.hide();
        }
      });
    }
  }
})(jQuery, Drupal);
