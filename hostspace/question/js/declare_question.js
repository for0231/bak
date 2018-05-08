(function ($, Drupal) {
  "use strict";

  Drupal.behaviors.question_declare = {
    attach: function (context, settings) {
      if(context.id == 'question-form') {
        var ip_search_sel = $('.ip_search_sel').find('option');
        var ip_selected = $('.ip_selected').find('option');
        var ip_selected_length = ip_selected.length;
        for(var i=0; i<ip_selected_length; i++) {
          var st_value = ip_selected[i].value;
          var ip_search_sel_lenght = ip_search_sel.length;
          for(var j=0; j<ip_search_sel_lenght; j++) {
            var ip_search_sel_value = ip_search_sel[j].value;
            if(ip_search_sel_value == st_value) {
              $(ip_search_sel[j]).remove();
              break;
            }
          }
        }
      }
      // 选择IP到左侧下拉框
      $('.ip_search_sel').once().dblclick(function(){
        var select_obj = $(this);
        var select_value = select_obj.val();
        var select_label = select_obj.find("option:selected").text();
        if(select_value != '' && select_value != null) {
          select_obj.find("option:selected").remove();
          $('.ip_selected').prepend("<option value='"+ select_value +"'>"+ select_label +"</option>");	
        }
      });
      // 将选择的IP移除
      $('.ip_selected').once().dblclick(function(){
        var selected_obj = $(this);
        var selected_value = selected_obj.val();
        var selected_label = selected_obj.find("option:selected").text();
        if(selected_value != '' && selected_value != null) {
          selected_obj.find("option:selected").remove();
          $('.ip_search_sel').prepend("<option value='"+ selected_value +"'>"+ selected_label +"</option>");
        }
      });
      // 将选择的IP全部选中
      $('#declare-question').once().click(function() {
        $('.ip_selected').find('option').attr('selected', 'selected');
      });
    }
  }
})(jQuery, Drupal);
