(function ($, Drupal) { 
  "use strict";
  
  Drupal.behaviors.route_view = {
    attach: function (context) {
      $('li.delete a').click(function(){
        return confirm('确定要删除此条信息？');
      });
      $("input[name='mode_command']").click(function(){
        var val = $(this).val();
        var command = $('#edit-blackhole-command').val();
        if(val == 2) {
          if(command == '') {
            $('.form-item-blackhole-command .description ').html('命令结果：prefix-set <span>AS58453-withdraw<spa>');
          } else {
            $('.form-item-blackhole-command .description ').html('命令结果：prefix-set <span>'+ command +'<spa>');
          }
        } else {
          if(command == '') {
            $('.form-item-blackhole-command .description ').html('命令结果：set protocols static route %ip/32 <span>blackhole</span>');
          } else {
            $('.form-item-blackhole-command .description ').html('命令结果：set protocols static route %ip/32 <span>'+ command +'<spa>');
          }
        }
      });
      $("input[name='blackhole_command']").keyup(function(){
        var value = $(this).val();
        $('.form-item-blackhole-command .description span').html(value);
      });
    }
  }
})(jQuery, Drupal)
  
