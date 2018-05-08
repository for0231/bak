(function ($, Drupal) {
  "use strict";

  Drupal.behaviors.work_sheet_filter = {
    attach: function (context) {
      $('li.delete').click(function(){
        return confirm('确定要执行删除操作！');
      });
    }
  }
})(jQuery, Drupal)
