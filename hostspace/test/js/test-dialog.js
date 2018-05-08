(function ($, Drupal) {
  Drupal.behaviors.test_dialog = {
    attach: function (context, settings) {
      $('.dialog-static').once().click(function(){
         Drupal.dialog('<div>静态内容</di>', {
           title: 'title',
           buttons: [{
             text: 'Close',
             click: function() {
               $(this).dialog('close');
             }
           }]
         }).showModal();
         return false;
      });

      $('.dialog-dynamic').once().click(function() {
        var ajaxDialog =  Drupal.ajax({
          dialog: {
            title: 'title'
          },
          dialogType: 'dialog',
          url: '/test/dialog/return',
        });
        ajaxDialog.execute();
        return false;
      });
      
      $('.ajax-polling').once().click(function(){
        var xhr = new XMLHttpRequest();
        xhr.onreadystatechange = function() {
          if(this.readyState == 3) { 
            console.log(this.responseText);
          }
        }
        xhr.open('GET', '/test/ajax/polling', true);
        xhr.send(null);
        return false;
      });
    }
  }
})(jQuery, Drupal);
