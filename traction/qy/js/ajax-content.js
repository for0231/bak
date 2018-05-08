(function ($, Drupal) {
  "use strict";

  Drupal.behaviors.ajax_content = {
    attach: function (context) {
      var lock = 0;
      var go_second = 1;
      var is_search = false;
      var condition = {};
      var centent = $('.ajax-content');
      var url = centent.attr('ajax-path');
      var time = centent.attr('ajax-time');
      var last_alarm = 0;
      if(time == null) {
        time = 2000;
      }
      if(centent.attr('ajax-refresh') == 'true') {
        lock = 1;
      }
      function getContent(){
        $.ajax({
          type: "GET",
          url: url,
          data: condition,
          dataType: "html",
          success: function(data) {
            centent.html(data);

            $('li.delete a').click(function(){
               return confirm('确定要删除此条信息？');
            });

            $('li.pager__item a').click(function() {
              url = $(this).attr('href');
              condition = {};
              getContent();
              return false;
            });
            //提示声音
            var is_tips = false;
            //实现间隔多少秒叫一次
            var alarms = $('tr.alarm');
            for(var i = 0; i<alarms.length;i++) {
              var alarm = $(alarms[i]);
              var current_time = alarm.attr('current-time'); //本此请求服务器当前时间
              var delay_time = alarm.attr('delay-time'); //报警问隔时间
              var first_alarm = alarm.attr('first-alarm'); //超alarm值起始时间
              if(last_alarm == 0) {
                last_alarm = first_alarm;
              }
              if(current_time - last_alarm >= delay_time) {
                last_alarm = current_time;
                $('#chatAudio')[0].play();
                is_tips = true;
                break;
              }
            }
            if(alarms.length == 0) {
              last_alarm = 0;
            }
            //不为空就提示
            if(!is_tips) {
              var alarms = $('.notnullalarm');
              if(alarms.length > 0) {
                $('#chatAudio')[0].play();
              }
            }
          },
          complete: function() {
            is_search = false;
            if(lock == 1){
              var time_tds = $("table td[begin-second]");
              if(time_tds.length > 0) {
                go_second = 1;
                temporalChange(time_tds);
              } else {
                setTimeout(getContent, time);
              }
            }
          }
        });
      }
      getContent();
      /**
       * 牵引列表当有一个时间为零才刷新
       */
      function temporalChange(time_tds) {
        var ajax_date = false;
        var time_rows = time_tds.length;
        for(var i = 0; i < time_rows; i++) {
          var self_td = $(time_tds[i]);
          var begin_second = self_td.attr('begin-second');
          if(begin_second == '') {
            continue;
          }
          var n = begin_second - go_second;
          if(n <= 0) {
            ajax_date = true;
            break;
          }
          var str = time2string(n);
          self_td.html(str);
        }
        if(ajax_date) {
          setTimeout(getContent, time);
        } else {
          if(lock == 1) {
            if(is_search) {
              getContent();
            } else {
              setTimeout(function(){
                go_second++;
                temporalChange(time_tds);
              }, 1000);
            }
          }
        }
      }

      function time2string(second){
        var day = Math.floor(second/(3600*24));
        var second = second%(3600*24);//除去整天之后剩余的时间
        var hour = Math.floor(second/3600);
        var second = second%3600;//除去整小时之后剩余的时间
        var minute = Math.floor(second/60);
        var second = second%60;//除去整分钟之后剩余的时间
        if(second <= 0){return '00分' + '00秒';}
        if(minute<10)minute = '0' + minute;
        if(second<10)second = '0' +second;
        //返回字符串
        if(day==0){
          if(hour==0) return minute + '分' + second + '秒';
          return hour + '小时' + minute + '分' + second + '秒';
        } else {
          return day + '天'+ hour + '小时'+ minute + '分' + second + '秒';
        }
      }

      $('#search').click(function(){
        is_search = true;
        url = centent.attr('ajax-path');
        var filter_value = {};
        $(".list-filter *[id^='filter_']").each(function() {
          var id = $(this).attr('id');
          var value = $.trim($(this).val());
          if(value != '') {
            filter_value[id] = value;
          }
        });
        var type = $(this).attr('action-type');
        var filter_ip = filter_value['filter_ip'];
        if(type=='checkip') {
          if(filter_ip != null && filter_ip != '') {
            var arr = filter_ip.split('\n');
            var re=/^(\d+)\.(\d+)\.(\d+)\.(\d+)$/;//正则表达式
            for(var i=0;i<arr.length;i++) {
              if(re.test(arr[i])) {
                if(!(RegExp.$1<256 && RegExp.$2<256 && RegExp.$3<256 && RegExp.$4<256)) {
                  alert('输入的[' + arr[i] + ']不是IP格式');
                  return false;
                }
              } else {
                alert('输入的[' + arr[i] + ']不是IP格式');
                return false;
              }
            }
            $('td.value').html('<font color="red">Finding..</font>');
          }
          var wallinfo = '';
          $('input[name="wallinfo"]:checked').each(function(){
            if(wallinfo == '') {
              wallinfo = $(this).val();
            } else {
              wallinfo += '-' + $(this).val();
            }
          });
          if(wallinfo != '') {
            filter_value['wallinfo'] = wallinfo;
          }
        }
        if(filter_ip != null && filter_ip != '') {
          filter_value['filter_ip'] = filter_ip.replace(/\./g, '-');
        }
        condition = filter_value;
        if(lock == 0) {
          getContent();
        }
      });

      $('#refresh').click(function(){
        if(lock == 1) {
          lock = 0;
          $(this).val('开始刷新');
        } else {
          lock = 1;
          $(this).val('停止刷新');
          getContent();
        }
      });
    }
  }
})(jQuery, Drupal)
