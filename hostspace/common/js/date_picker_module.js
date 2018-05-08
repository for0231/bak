(function ($) {
  Drupal.behaviors.select_date = {
    attach: function (context, settings) {
  	  $('#edit-start, #edit-expire', context).datepicker({ dateFormat: 'yy-mm-dd' });
    }
  }
})(jQuery, Drupal);
