(function ($, Drupal) {
  "use strict";

  Drupal.behaviors.audioload = {
    attach: function (context) {
      var centent = $('.ajax-content');
      var path = centent.attr('audio-path');
      $('<audio id="chatAudio"><source src="/'+ path +'/tip.mp3" type="audio/mpeg"></audio>').appendTo('body');
    }
  }
})(jQuery, Drupal)
