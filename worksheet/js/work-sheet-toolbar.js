(function ($, Drupal) {
  "use strict";
  
  Drupal.behaviors.work_sheet_toolbar = {
    attach: function (context) {
      var url = location.pathname;
      if(url.indexOf('worksheet') >= 0) {
        var menu = $('.toolbar-menu');
        var pathItem = menu.find('a[href="' + url + '"]');
        if(pathItem.length == 0) {
          setParentMenu(menu, url);
        }
      }
      
      function setParentMenu(menu, url) {
        if(url == '') {
          return;
        }
        var url_arr = url.split('/');
        var item = url_arr.pop();
        if(item == 'worksheet') {
          return;
        }
        var url = url_arr.join('/');
        var pathItem = menu.find('a[href="' + url + '"]');
        if(pathItem.length > 0) {
          var $activeItem = pathItem.addClass('menu-item--active');
          $activeItem.parentsUntil('.root', 'li').addClass('menu-item--active-trail open');
        } else {
          setParentMenu(menu, url);
        }
      }
      //设置和取消值班
      $('.toolbar-tab input.form-checkbox').once().change(function(){
        var _self = $(this);
        var op = 'delete';
        if(_self.is(':checked')) {
          op = 'add';
        }
        var uid = _self.attr('ajax-uid');
        var url = Drupal.url('admin/worksheet/sop/person/'+ uid +'/duty/' + op);
        $.ajax({
          type: "GET",
          url: url,
          dataType: "html",
          success: function(data) {
          },
        });
      });
    }
  }
  
})(jQuery, Drupal)
