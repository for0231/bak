(function ($, Drupal) {
  "use strict";

  Drupal.behaviors.user_cart = {
    attach: function (context) {
      $('.operation a').once().click(function() {
        return confirm(Drupal.t('Are you sure you want to delete this entry?'));
      });
      $('a.look-config').once().click(function(){
        var obj = $(this).parents('.config-name').find('.business-list');
        if(obj.is(":hidden")) {
          obj.show();
        } else {
          obj.hide();
        }
      });
      function sumPrice() {
        var boxs = $("td.table-select input:checked");
        var sum = 0;
        for(var i=0;i<boxs.length;i++) {
          var checkbox_tr = $(boxs[i]).parents('tr');
          var price_td = checkbox_tr.find('td.price');
          var price = parseFloat(price_td.html().substr(1))
          sum += price;
        }
        var price_label = $('.order-price label');
        price_label.html('￥' + sum);
      }
      sumPrice();

      $("th.select-all input").once().click(function(){
        var price_label = $('.order-price label');
        if(this.checked) {
          var sum = 0;
          $("td.price").each(function(){
            var price = parseFloat($(this).html().substr(1))
            sum += price;
          });
          price_label.html('￥' + sum);
        } else {
          price_label.html('￥0');
        }
      });

      $("td.table-select input").once().click(function() {
         sumPrice();
      });
      $('input.edit-by-number').once().keyup(function(){
        this.value=this.value.replace(/\D/g, '1');
      });
    }
  }
})(jQuery, Drupal);
