(function ($, Drupal, drupalSettings) {

  "use strict";

  Drupal.behaviors.dropmanage = {
    attach: function (context, settings) {
      var msg = $("#downmsg_emessage");
      var msg_content = $('#donwmsg_content');
      var msg_btn = $('#msg_hidden_btn');

      function msg_status(is_first) {
        if(msg_content.is(":hidden")) {
          msg_content.show();
          msg_btn.removeClass('open');
          msg_btn.addClass('close');
          msg.animate({bottom: 30}, 1000);
        } else {
          msg.animate({bottom: 30}, 0, function(){
            msg_content.hide();
            msg_btn.removeClass('close');
            msg_btn.addClass('open');
          });
        }
        if(is_first) {
          msg.delay(3000);
          msg_status(false)
        }
      }
      msg_btn.click(function(){
        msg_status(false);
      });
      msg_status(true);
    }
  }
})(jQuery, Drupal, drupalSettings);
