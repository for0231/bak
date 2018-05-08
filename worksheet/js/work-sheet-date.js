(function ($, Drupal) {
  "use strict";
  Drupal.behaviors.work_sheet_date = {
    attach: function (context, settings) {
      $('#edit-btime,#edit-etime').datetimepicker({
        timeFormat: "HH:mm:ss", 
        dateFormat: "yy-mm-dd"
      });
      $('#chartcontainer').highcharts({
        chart: {
          type: 'line'
        },
        title: {
          text: '工单未完成和已完成数量统计图'
        },
        subtitle: {
          text: '数据来源: WorldClimate.com'
        },
        xAxis: {
          labels: {
            formatter: function () {
              return Highcharts.dateFormat('%Y-%m-%d %H:%M:%S',this.value*1000);
            }
          }
        },
        yAxis: {
          title: {
            text: '数量'
          }
        },
        tooltip: {
            shared: false,
            formatter:function(){
              var value = this.y;
              var time = new Date(this.x*1000).toLocaleString();
              return time+'（'+value+'个）';
            }
        },
        plotOptions: {
            spline: {
                marker: {
                    enabled: false
                }
            }
        },
        series: [{
          name: '未完成',
          marker: {
            symbol: 'square'
          },
          data: []
        }, {
          name: '已完成',
          marker: {
            symbol: 'diamond'
          },
          data: []
        }],
        credits:{
          enabled:false // 禁用版权信息
        }
      });
      $('#search', context).click(function(){
        var begin = $('#edit-btime').val();
        var end = $('#edit-etime').val();
        var interval = $('#interval').val();
        if(begin == '' || end == '' || interval == '') {
          alert('请输入时间间隔和查询时间段！');
          return;
        }
        var chart = $('#chartcontainer').highcharts();
        var series1 = chart.series[0];
        var series2 = chart.series[1];
        var parameter = {};
        parameter['begin'] = begin;
        parameter['end'] = end;
        parameter['interval'] = interval;
        var url = Drupal.url('admin/worksheet/sop/curvedata');
        $.ajax({
          type: "GET",
          url: url,
          data: parameter,
          dataType: "json",
          success: function(data) {
            if(data == 'time') {
              alert('请选择输入时间格式!');
              return;
            }
            if(data == 'false') {
              alert('查询的时间间隔和时间段不匹配');
              return;
            }
            series1.setData(data[0]);
            series2.setData(data[1]);
          }
        });
      });
    }
  }
})(jQuery, Drupal)