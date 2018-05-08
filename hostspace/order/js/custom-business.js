(function ($, Drupal) {

  "use strict";

  Drupal.behaviors.custom_business = {
    attach: function (context) {
      //当选择高防中将数量默认为1；
      $('.select-group select').once().change(function() {
        var input_obj = $(this).parents('.select-group').find('input');
        input_obj.val("1");
        input_obj.blur();
      });

      $('#add_to_cart').once().click(function() {
        var self = $(this);
        var calculation = $("div.ajax-progress");
        if(calculation.length > 0) {
          setTimeout(function(){
            self.click();
          }, 100);
          return false;
        }
        return true;
      });
    }
  }
})(jQuery, Drupal);
