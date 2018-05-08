(function ($, Drupal) {
  "use strict";

  Drupal.behaviors.work_sheet_frame = {
    attach: function (context, settings) {
      $('#edit-tid').change(function(a,b){
        var tid = $(this).val();
        if(tid == 140) {
          $('#edit-requirement-0-value').val("核实服务器配置并关机\r\n业务IP绑空已经还原防火墙\r\n交换机端口配置还原\r\n");
        } else {
          $('#edit-requirement-0-value').val("");
        }
      })
    }
  }
})(jQuery, Drupal)
