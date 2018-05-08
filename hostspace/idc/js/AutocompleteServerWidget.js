(function ($, Drupal) {
  "use strict";

  var autocomplete = Drupal.autocomplete;

  var parentSource = autocomplete.options.source;

  Drupal.autocomplete.options.source = function(request, response) {
    if(this.element.attr('js_name')) {
      var obj_select = this.element.parent().prev().find('select');
      if(obj_select.val() == '') {
        request.term = '';
      } else {
        request.term = request.term + '$' + obj_select.val();
      }
      if ($('#autocomplete_server_room').val() == '') {
        request.term = '';
      } else {
        request.term = request.term + '$' + $('#autocomplete_server_room').val();
      }
    }
    parentSource.call(this,request, response);
  }
})(jQuery, Drupal);
