(function ($, Drupal) {

  "use strict";

  Drupal.behaviors.product_default_business = {
    attach: function (context) {

      $('#edit-business-submit').once().click(function() {
        //获取增加的业务值
        var business_data = $('#edit-business-data').val();
        if(business_data == '') {
          return false;
        }
        var dataText = $('#edit-business-data').find("option:selected").text();

        //获取增加的业务内容值
        var content_value = '';
        var content_text = '';
        var content_ctls = $('#business_content_wrapper .business-ctl');
        for(var i=0; i<content_ctls.length; i++) {
          var ctl_obj = $(content_ctls[i]);
          if(ctl_obj.val() == '') {
            alert(Drupal.t('Value cannot be empty'));
            return;
          }
          if(ctl_obj.attr('type') == 'number') {
           if(!(/^(\+|-)?\d+$/.test(ctl_obj.val()))) {
              alert(Drupal.t('Please enter a number'));
              return;
            }
          }
          if(content_value == '') {
            content_value = ctl_obj.val();
            if(ctl_obj.is('SELECT')) {
              content_text = ctl_obj.find("option:selected").text();
            } else {
              content_text = ctl_obj.val();
            }
          } else {
            content_value += ':' + ctl_obj.val();
            if(ctl_obj.is('SELECT')) {
              content_text += 'X' + ctl_obj.find("option:selected").text();
            } else {
              content_text += 'X' + ctl_obj.val();
            }
          }
        }

        //增加业务数据到hidde
        var item_value = business_data + '=' + content_value;
        var default_value_exits = false;
        var default_value_new = "";
        var default_value = $('input[name="default_business_value"]').val();
        if(default_value != '') {
          var default_value_arr = default_value.split(",");
          for(var i=0; i<default_value_arr.length; i++) {
            var item_value_old = default_value_arr[i];
            if(item_value == item_value_old) {
              return;
            }
            var business_data_old = item_value_old.split("=")[0];
            if(business_data_old == business_data){
              default_value_exits = true;
              if(default_value_new == '')
               default_value_new = item_value;
              else
               default_value_new += ',' + item_value;
            } else {
              if(default_value_new == '')
               default_value_new = item_value_old;
              else
               default_value_new += ',' + item_value_old;
            }
          }
        }
        var htmlContent = '<span>'+ dataText +'：'+ content_text +' </span><a class="remove-business" href="javascript:void(0)">Remove</a>';
        if(default_value_exits) {
          $('div[business-id = "'+ business_data +'"]').html(htmlContent);
        } else {
          if(default_value_new == '')
            default_value_new = item_value;
          else
            default_value_new += ',' + item_value;
          $('#display_business_wrapper').append('<div business-id = "'+ business_data +'">'+ htmlContent +'</div>');
        }
        $('input[name="default_business_value"]').val(default_value_new);
        $('.remove-business').once().click(removeBusiness);
      });

      $('.remove-business').once().click(removeBusiness);

      function removeBusiness() {
        var business_id = $(this).parent().attr('business-id');
        var default_value_new = "";
        var default_value = $('input[name="default_business_value"]').val();
        var default_value_arr = default_value.split(",");
        for(var i=0; i<default_value_arr.length; i++) {
          var item_value_old = default_value_arr[i];
          var business_data_old = item_value_old.split("=")[0];
          if(business_data_old != business_id) {
            if(default_value_new == '')
              default_value_new = item_value_old;
            else
              default_value_new += ',' + item_value_old;
          }
        }
        $('input[name="default_business_value"]').val(default_value_new);
        $(this).parent().remove();
      }
    }
  }
})(jQuery, Drupal);
