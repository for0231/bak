(function ($, Drupal) {
  "use strict";
  Drupal.behaviors.work_sheet_date = {
    attach: function (context, settings) {
      var txt2 ='<input type="text" id="manageip-hidden" readonly="true">';
      $("#edit-manage-ip-0-value").after(txt2);
      var manageip = $( "#edit-manage-ip-0-value" ).val();
      var reg=/\./g;
      var manageip2 = manageip.replace(reg,'x');
      var text2 = $('#manageip-hidden');
      text2.val(manageip2);
      /*
      var txt1='<input id="cope-manage-ip" name="op" value="复制ip"  type="submit">';
      var txt2 ='<input type="text" id="manageip-hidden" readonly="true">';
      $("#edit-manage-ip-0-value").after(txt1);
      $("#edit-manage-ip-0-value").after(txt2);
      $('#cope-manage-ip').click(function(){
        var manageip = $( "#edit-manage-ip-0-value" ).val();
        var text2 = $('#manageip-hidden');
        var reg=/\./g;
        var manageip2 = manageip.replace(reg,'x');
        text2.val(manageip2);
        text2.select();
        document.execCommand("Copy");
      });
      */
    }
  }
})(jQuery, Drupal)