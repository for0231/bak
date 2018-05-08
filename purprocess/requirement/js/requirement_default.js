(function ($, Drupal) {
  "use strict";

  /**
   * @description 以下是控制台通用javascript
   */
  Drupal.behaviors.requirement_default_collection = {
    attach: function (context, settings) {
      var mode = $('.list-mode input:checked').val();

      var timestamp=new Date().getTime();
      var centent = $('.ajax-content');
      var url = centent.attr('ajax-path');

      function getContent(exectime){
        var parm = {};
        parm['mode'] = mode;
        $.ajax({
          type: "GET",
          url: url,
          data: parm,
          dataType: "html",
          success: function(data) {
            centent.html(data);
            $('li.delete a').once().click(function(){
              return confirm('确定要执行删除操作！');
            });
          },
          complete: function() {
            Drupal.behaviors.bootstrapDropdown.attach(context);
          }
        });
      }


      getContent(timestamp);


      $('.list-mode input.form-radio').once().click(function(){
        mode = $(this).val();
        timestamp=new Date().getTime();
        getContent(timestamp);
      });

      // 解决时间弹出窗
      $('#edit-begin, #edit-end', context).datepicker({
        changeMonth: true,
        changeYear: true,
        dateFormat: 'yy-mm-dd',
        monthNamesShort: ['一月', '二月', '三月', '四月', '五月', '六月', '七月', '八月', '九月', '十月', '十一月', '十二月']
      });

    }
  }
})(jQuery, Drupal)

