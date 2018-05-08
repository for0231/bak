(function ($, Drupal) {
  "use strict";

  Drupal.behaviors.work_calendar = {
    attach: function (context, settings) {
      $('.form-calendar td.calendar-day').once().dblclick(function(){
        var _self = $(this);
        var calendar = _self.parents('.form-calendar');
        var url = calendar.attr('change-url');
        var year = calendar.attr('js-year');
        var month = calendar.attr('js-month');
        var day = _self.attr('js-day');
        var work = 1;
        if(_self.hasClass('unwork')) {
          var work = 0;
          url += '?year='+ year +'&month='+ month +'&day=' + day + '&work=1';
        } else {
          url += '?year='+ year +'&month='+ month +'&day=' + day + '&work=0';
        }
        $.ajax({
          type: "GET",
          url: url,
          dataType: "text",
          success: function(data) {
            if(data == 'OK') {
              if(work==1) {
                _self.removeClass('work');
                _self.addClass('unwork');
                _self.html('<div><span>休</span><b>'+ day +'</b></div>');
              } else {
                _self.removeClass('unwork');
                _self.addClass('work');
                _self.html('<div><b>'+ day +'</b></div>');
              }
            }
          }
        });
      });
      
    }
  }
})(jQuery, Drupal)
