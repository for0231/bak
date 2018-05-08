(function ($, Drupal) {
  Drupal.behaviors.onlinechart = {
    attach: function (context) {
	    $("#down").click(function() {
		     $('#down').hide();
		    $('#up').show();
	    });

	    $("#up").click(function() {
		    $('#up').hide();
		    $('#down').show();
	    });
    }
  }
})(jQuery, Drupal)

