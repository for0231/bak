/**
 * @file
 */

(function ($, Drupal) {
  "use strict";

  var autocomplete = Drupal.autocomplete;

  var parentSource = autocomplete.options.source;

  Drupal.autocomplete.options.source = function(request, response) {
    if (this.element.attr('js_mip')) {
      var obj_select = $('#sop_task_server_pid').parent().find('select');
      if (obj_select.val() == '') {
        alert(Drupal.t('请选择配置类型,再选择管理IP'));
      }
      else {
        request.term = request.term + '$' + obj_select.val();
      }
    }
    parentSource.call(this,request, response);
  }

})(jQuery, Drupal);
