/**
 * @file
 */

(function ($, Drupal) {

  "use strict";

  Drupal.behaviors.sop_task_iband_form = {
    attach: function (context, settings) {
      // if(context.id == 'sop-task-iband-form') {.
        var search_option = $('#edit-ipb-search-content').find('option');
        var select_option = $('#edit-aips-id').find('option');
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

      var search_bips_label = $(".form-item-bips label[for='edit-bips-id']");
      var search_bips_label_origin = search_bips_label.html();
      var search_bips_select = $(".form-item-bips select").find("option");
      var search_bips_select_length = search_bips_select.length;
      search_bips_label.html(search_bips_label_origin + "(<font color=red><b>" + search_bips_select_length + "</b></font>)");

      var search_aips_label = $(".form-item-aips label[for='edit-aips-id']");
      var search_aips_label_origin = search_aips_label.html();
      var search_aips_select = $(".form-item-aips select").find("option");
      var search_aips_select_length = search_aips_select.length;
      search_aips_label.html(search_aips_label_origin + "(<font color=red><b>" + search_aips_select_length + "</b></font>)");

      var search_sips_label = $(".form-item-sips label[for='edit-sips-id']");
      var search_sips_label_origin = search_sips_label.html();
      var search_sips_select = $(".form-item-sips select").find("option");
      var search_sips_select_length = search_sips_select.length;
      search_sips_label.html(search_sips_label_origin + "(<font color=red><b>" + search_sips_select_length + "</b></font>)");

      // }.
      $('#edit-bips-segment-id').once().dblclick(function() {
        var select_obj = $(this);
        var s_text = select_obj.find("option:selected").text();
      });
      $('#edit-bips-id').once().dblclick(function() {
        var select_obj = $(this);
        var s_value = select_obj.val();
        if (s_value == null) {
          return;
        }
        var s_text = select_obj.find("option:selected").text();
        $('#edit-sips-id').append("<option value='" + s_value + "'>" + s_text + "</option>");
        $(this).find("option:selected").remove();
      });
      $('#edit-sips-id').once().dblclick(function() {
        var select_obj = $(this);
        var s_value = select_obj.val();
        if (s_value == null) {
          return;
        }
        var s_text = select_obj.find("option:selected").text();
        $('#edit-bips-id').append("<option value='" + s_value + "'>" + s_text + "</option>");
        $(this).find("option:selected").remove();
      });
      $('#ipb_search_wrapper select').once().dblclick(function(){
        var search_obj = $(this);
        var s_value = search_obj.val();
        if (s_value == null) {
          return;
        }
        var s_text = search_obj.find("option:selected").text();
        search_obj.find("option:selected").remove();
        $('#edit-aips-id').prepend("<option value='" + s_value + "'>" + s_text + "</option>");

      });
      // @todo 后期再加上动态改变IP个数的相关信息
      $('.form-item-aips select').on('changed',function(){
        // $(this).css("background-color","#F00");
        // alert('adf');
      });
      $('#edit-aips-id').once().dblclick(function(){
        var select_obj = $(this)
        var s_value = select_obj.val();
        if (s_value == null) {
          return;
        }
        var s_text = select_obj.find("option:selected").text();
        $('#ipb_search_wrapper select').append("<option value='" + s_value + "'>" + s_text + "</option>");
        $(this).find("option:selected").remove();

      });

      $('#edit-busi-save-submit').once().click(function(){
         $('#edit-bips-id').find('option').attr('selected', 'selected');
         $('#edit-aips-id').find('option').attr('selected', 'selected');
         $('#edit-sips-id').find('option').attr('selected', 'selected');
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
