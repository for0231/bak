(function ($,Drupal){
  Drupal.behaviors.online_content = {
    attach: function (context) {
      var url = $('.ajax-content').attr('content-path');
      function getContent() {
        $.ajax({
          type: "GET",
          url: url,
          dataType: "html",
          success: function(data) {
            $('.ajax-content').html(data)
            setTimeout
          },
          complete: function() {
            setTimeout(getContent, 1000);
          }
        });
      }
      getContent();
    }   
  }
})(jQuery, Drupal)
