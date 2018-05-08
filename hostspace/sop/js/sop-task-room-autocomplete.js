/**
 * @file
 */

(function ($, Drupal) {
  "use strict";

  var autocomplete = Drupal.autocomplete;

  var parentSource = autocomplete.options.source;

  Drupal.autocomplete.options.source = function(request, response) {
    if (this.element.attr('js_room_mip')) {
      var obj = $('#sop_task_room_client');
      if (obj.val() == '') {
        alert(Drupal.t('请选择客户'));
      }
      else {
        request.term = request.term + '$' + obj.val();
      }
    }
    parentSource.call(this,request, response);
  }

})(jQuery, Drupal);
