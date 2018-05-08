(function ($, Drupal) {

  "use strict";

  Drupal.behaviors.balance_alarm = {
    
    attach: function (context) {
      var alarm = $('.alarm_flag');
      var chk = $('.switch-check');
      if(alarm.val() == 'ON') {
        chk.prop('checked',true);
      }
      $('.switch-label').click(function() {
        var parent_obj = $(this).parents('.switch');
        var chk = parent_obj.find('.switch-check');
       	var ischeck = chk.prop('checked');
        var uid = parent_obj.find('.alarm_uid').val();
        var flag = '';
        if(ischeck){  // ischeck == true 关闭预警开关
          flag = 'OFF';
        } else {     // ischeck == false  打开预警开关
          flag = 'ON';
        }  
        $.ajax({
          type : 'POST',  
          url : Drupal.url('user/account/alarm'),  
          data : {'uid': uid, 'flag': flag},
          dataType: 'json',
          success : function(msg){
            alert(msg);
          }
        });     
      });
    }
  }
})(jQuery, Drupal);
