(function ($) {
  Drupal.behaviors.dialog_message = {
    attach: function (context, settings) {
      var message = $('div[aria-label = "info"]');
      if(message.length == 0) {
        return;
      }
      var $previewDialog = message.appendTo('body');
      Drupal.dialog($previewDialog, {
        title: Drupal.t('Message'),
        buttons: [{
          text: Drupal.t('Close'),
          click: function () {
            $(this).dialog('close');
          }
        }]
     }).showModal();
    }
  }
})(jQuery, Drupal);
