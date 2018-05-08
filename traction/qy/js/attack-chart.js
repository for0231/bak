(function ($, Drupal) { 
  "use strict";
  
  Drupal.behaviors.attack_chart = {
    attach: function (context) {
      $('#edit-stime, #edit-etime', context).datepicker({
        changeMonth: true,
        changeYear: true,  
        dateFormat: 'yy-mm-dd',
        monthNamesShort: ['一月', '二月', '三月', '四月', '五月', '六月', '七月', '八月', '九月', '十月', '十一月', '十二月']
      });

      function getTitle() {
        var stime = $('#edit-stime').val();
        var arr = stime.split('-');
        if(arr.length > 2) {
          return arr[0] + '年'+ arr[1] +'月流量图';
        } else {
          return '流量图';
        }
      }

      Highcharts.setOptions({
        global : {
          useUTC : false
        }
      });
      $('#chartcontainer').highcharts({
        title: {
          text: getTitle()
        },
        xAxis:{
          type: 'datetime',
          dateTimeLabelFormats: {
            day: '%Y-%m-%d'
          }
        },
        yAxis:{
          title: {
            text: '流量'
          },
          //tickInterval: 256,
          minorTickInterval: 'auto',
          labels: { 
            formatter: function() {
              if(this.value >= 1000) {
                return (this.value / 1000) + 'Gbps'; 
              } else if (this.value <= 1) {
                return (this.value * 1000) + 'Kbps';
              } else {
                return this.value + 'Mbps'; 
              }
            }
          }
        },
        legend: { 
          layout: 'vertical',
          align: 'right',
          verticalAlign: 'middle',
          borderWidth: 0
        },
        tooltip: {
          xDateFormat: '%Y-%m-%d %H:%M:%S',
          shared: true
        },
        series : [{
          type: 'areaspline',
          name : 'BPS(MBPS)',
          data : []
        }],
        credits:{
          enabled:false // 禁用版权信息
        }
      });
      
      $('#search', context).click(function(){
        var ip = $('#edit-ip').val();
        var stime = $('#edit-stime').val();
        var etime = $('#edit-etime').val();
        if(ip == '' || stime == '' || etime == '') {
          alert('请输入IP和时间！');
          return;
        }
        var arr = ip.split('\n');
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
        var chart = $('#chartcontainer').highcharts();
        var series = chart.series[0];
        var parameter = {};
        parameter['ip'] = ip.replace(/\./g, '--');
        parameter['stime'] = stime;
        parameter['etime'] = etime;
        var url = Drupal.url('admin/wd/chart/data');
        $.ajax({
          type: "GET",
          url: url,
          data: parameter,
          dataType: "json",
          success: function(data) {
            if(data == 'no') {
              alert('没有查到此IP的数据!');
              return
            }
            if(data == 'time') {
              alert('请选择输入时间格式!');
              return;
            }
            if(data == 'timeequal') {
              alert('不能跨月查询！');
              return;
            }
            series.setData(data);
            chart.setTitle({ text: getTitle()});
          }
        });
      });
    }
  }
})(jQuery, Drupal)
  
