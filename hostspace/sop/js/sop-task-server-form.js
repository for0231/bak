/**
 * @file
 */

(function ($, Drupal) {

  "use strict";

  Drupal.behaviors.sop_task_server_form = {
    attach: function (context, settings) {
      if (context.id == 'sop-task-server-form') {
        var search_option = $('#ipb_search_wrapper select').find('option');
        var select_option = $('#edit-ipb-id').find('option');
        var st_lenght = select_option.length;
        for (var i = 0; i < st_lenght; i++) {
          var st_value = select_option[i].value;
          var sh_lenght = search_option.length;
          for (var n = 0; n < sh_lenght; n++) {
            var sh_value = search_option[n].value;
            if (sh_value == st_value) {
              $(search_option[n]).remove();
              break;
            }
          }
        }
      }
      $('#ipb_search_wrapper select').once().dblclick(function(){
        var search_obj = $(this);
        var s_value = search_obj.val();
        if (s_value == null) {
          return;
        }
        var s_text = search_obj.find("option:selected").text();
        search_obj.find("option:selected").remove();
        $('#edit-ipb-id').prepend("<option value='" + s_value + "'>" + s_text + "</option>");
      });

      $('#edit-ipb-id').once().dblclick(function(){
        var select_obj = $(this)
        var s_value = select_obj.val();
        if (s_value == null) {
          return;
        }
        var s_text = select_obj.find("option:selected").text();
        $('#ipb_search_wrapper select').append("<option value='" + s_value + "'>" + s_text + "</option>");
        $(this).find("option:selected").remove();
      });

      $('#edit-submit').once().click(function(){
         $('#edit-ipb-id').find('option').attr('selected', 'selected');
      });
      $('#edit-sop-type').once().change(function(){
        var p1 = $(this).children('option:selected').val();
        if (p1 == 'i5') {
          $('#edit-description-0-value').val('业务IP绑空并还原防火墙\r\n检查交换机端口\r\n带宽调回30M\r\n核实服务器配置\r\n关闭服务器');
        }
        else {
          $('#edit-description-0-value').val('');
        }
      });
    }
  }
})(jQuery, Drupal);
