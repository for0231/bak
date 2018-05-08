//var myChart = echarts.init(document.getElementById('referer-charts'));

(function ($, Drupal) {
  "use strict";
  Drupal.behaviors.referer_statist_echarts = {
    attach: function (context) {
      var chart_data = $('#chart-data').val();
      if(chart_data == '[]') {
        return;
      }
      var json = JSON.parse(chart_data);
      var chart_obj = document.getElementById('referer-charts');
      var myChart = echarts.init(chart_obj);
      var option = {
        tooltip: {
          trigger: 'item',
          formatter: "{a} <br/>{b}: {c} ({d}%)"
        },
        legend: {
          orient: 'vertical',
          x: 'left',
          data: json.title
        },
        series: [{
          name:'访问来源',
          type:'pie',
          selectedMode: 'single',
          radius: [0, '30%'],
          label: {
            normal: {
              position: 'inner'
            }
          },
          labelLine: {
            normal: {
              show: false
            }
          },
          data:json.data1
        },{
          name:'注册情况',
          type:'pie',
          radius: ['40%', '55%'],
          data:json.data2
        }]
      };
      myChart.setOption(option);
    }
  }
})(jQuery, Drupal);
