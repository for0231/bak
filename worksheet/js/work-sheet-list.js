(function ($, Drupal) {
  "use strict";

  Drupal.behaviors.work_sheet_list = {
    attach: function (context, settings) {
      var mode = $('.list-mode input:checked').val();
      var order = $('.list-order input:checked').val();
      var type_level = '';
      if($('.list-mode .type-level').length > 0) {
        type_level = $('.list-mode .type-level').val();
      }
      var timestamp=new Date().getTime();
      var centent = $('.ajax-content');
      var url = centent.attr('ajax-path');
      function getContent(exectime){
        var parm = {};
        parm['mode'] = mode;
        parm['order'] = order;
        if(type_level != '') {
          parm['type'] = type_level;
        }
        $.ajax({
          type: "GET",
          url: url,
          data: parm,
          dataType: "html",
          success: function(data) {
            centent.html(data);
            $('li.delete a').click(function(){
              return confirm('确定要执行删除操作！');        
            });
          },
          complete: function() {
            Drupal.behaviors.dropButton.attach(context, settings);
            if(exectime == timestamp) {
              setTimeout(function(){
                getContent(exectime);
              }, 6000);
            }
          }
        });
      }
      getContent(timestamp);
      $('.list-mode input.form-radio').click(function(){
        mode = $(this).val();
        timestamp=new Date().getTime();
        getContent(timestamp);
      });
      $('.list-order input.form-radio').click(function(){
        order = $(this).val();
        timestamp=new Date().getTime();
        getContent(timestamp);
      });
      $('.list-mode select.type-level').change(function(){
        type_level = $(this).val();
        timestamp = new Date().getTime();
        getContent(timestamp);
      })
    }
  }
})(jQuery, Drupal)
