(function ($, Drupal) {

  "use strict";

  Drupal.behaviors.hostclient_list_builder = {
    attach: function (context, settings) {
      $('td.show-more').dblclick(function(){
        var td_self = $(this);
        if(td_self.attr('js-open') == 'close') {
          td_self.find('li.more-ipb').show();
          td_self.attr('js-open', 'open');
          td_self.attr('title', Drupal.t('Double click to hidden.')); //双击关闭一些IP
        } else {
          td_self.find('li.more-ipb').hide();
          td_self.attr('js-open', 'close');
          td_self.attr('title', Drupal.t('Double click the show.')); //双击显示所有IP
        }
      });

      $('#stop-multi').click(function(){
        var str_server = '';
        $('td input:checked').each(function(){
          var row = $(this).parents('tr');
          var server_td = row.find('td:eq(1)');
          str_server += server_td.html() + ' '
        });
        if(str_server == '') {
          alert(Drupal.t('Please select the server.'));
          return false;
        }
        return confirm(Drupal.t('Are you sure  want to stop the server：') + str_server + '?');
      });
    }
  }
})(jQuery, Drupal);
