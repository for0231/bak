(function ($, Drupal) {

  "use strict";

  Drupal.behaviors.product_list = {
    attach: function (context, settings) {
     $('.info-all').click(function(){
        var pr = $(this).parents('.fuwuqi_01');
        var str = $(this).html();
        if(str == '+') {
          pr.find('.hide').css('display', 'table');
          $(this).html('—');
        } else {
          pr.find('.hide').css('display', 'none');
          $(this).html('+');
        }
     });
    }
  }

})(jQuery, Drupal);
