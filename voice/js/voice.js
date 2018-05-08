(function ($, Drupal) {
  "use strict";

  Drupal.behaviors.voice = {
    attach: function (context) {
      var url = Drupal.url('admin/voice/get');
      function parseVoice(){
        $.ajax({
          type : 'POST',
          url : url,
          data:{},
          success : function(data){
            if (data != "false"){
              $("#bgMusic").remove();
                var bgmStr = '<audio id="bgMusic"><source src="'+ data +'"  /></audio>';
                $(document.body).append(bgmStr);
                $("#audiojs_wrapper0").css("display", "block");
                $("#bgMusic")[0].play();
              }
              setTimeout(function(){
                parseVoice();
              }, 5000);
            },
            error : function(){}
        });
      }
      if(context == document) {
        parseVoice();
      }
    }
  }
})(jQuery, Drupal);
