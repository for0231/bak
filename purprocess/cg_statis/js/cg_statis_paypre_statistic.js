(function($, Drupal, drupalSettings) {
  "use strict";
  Drupal.behaviors.paypre = {
    attach: function (context) {

      $("#statis").jqGrid({
        url: Drupal.url('ajax/cg_statis/paypre/statistic/collection'),
        datatype: "json",
			  height : 'auto',
        colNames: ['ID', '供应商', '币种', '总额', '已付款', '未付款', '开始时间', '截止时间'],
        colModel:[
          {name: 'id', index: 'id', sorttype: "int", width: 30, editable: false},
          {name: 'name', index: 'name', editable: false},
          {name: 'ftype', index: 'ftype', editable: false},
          {name: 'num', index: 'num', editable: false},
          {name: 'had', index: 'had', editable: false},
          {name: 'not', index: 'not', editable: false},
          {name: 'begin', index: 'begin', editable: false},
          {name: 'end', index: 'end', editable: false},
        ],
				rowNum : 10,
				rowList : [10, 50, 100],
				pager : '#statisnav',
				sortname : 'id',
				toolbarfilter : true,
				viewrecords : true,
				sortorder : "desc",
				autowidth : true,
        onSelectRow: function(id) {
        },
        reloadAfterSubmit: true,
     });

      // 解决时间弹出窗
      $('#edit-begin, #edit-end', context).datepicker({
        changeMonth: true,
        changeYear: true,
        dateFormat: 'yy-mm-dd',
        monthNamesShort: ['一月', '二月', '三月', '四月', '五月', '六月', '七月', '八月', '九月', '十月', '十一月', '十二月']
      });

      $("#statis").jqGrid(
        'navGrid',
        '#statisnav',
        {edit:false,add:false,del:false},
        {},
        {},
        {},
        {multipleSearch:true, multipleGroup:true}
      );
			$(window).on('resize.jqGrid', function() {
				$("#statis").jqGrid('setGridWidth', $("#content").width());
			})



      // remove classes
      $(".ui-jqgrid").removeClass("ui-widget ui-widget-content");
      $(".ui-jqgrid-view").children().removeClass("ui-widget-header ui-state-default");
      $(".ui-jqgrid-labels, .ui-search-toolbar").children().removeClass("ui-state-default ui-th-column ui-th-ltr");
      $(".ui-jqgrid-pager").removeClass("ui-state-default");
      $(".ui-jqgrid").removeClass("ui-widget-content");

      // add classes
      $(".ui-jqgrid-htable").addClass("table table-bordered table-hover");
      $(".ui-jqgrid-btable").addClass("table table-bordered table-striped");

      $(".ui-pg-div").removeClass().addClass("btn btn-sm btn-primary");
      $(".ui-icon.ui-icon-plus").removeClass().addClass("fa fa-plus");
      $(".ui-icon.ui-icon-pencil").removeClass().addClass("fa fa-pencil");
      $(".ui-icon.ui-icon-trash").removeClass().addClass("fa fa-trash-o");
      $(".ui-icon.ui-icon-search").removeClass().addClass("fa fa-search");
      $(".ui-icon.ui-icon-refresh").removeClass().addClass("fa fa-refresh");
      $(".ui-icon.ui-icon-disk").removeClass().addClass("fa fa-save").parent(".btn-primary").removeClass("btn-primary").addClass("btn-success");
      $(".ui-icon.ui-icon-cancel").removeClass().addClass("fa fa-times").parent(".btn-primary").removeClass("btn-primary").addClass("btn-danger");

      $(".ui-icon.ui-icon-seek-prev").wrap("<div class='btn btn-sm btn-default'></div>");
      $(".ui-icon.ui-icon-seek-prev").removeClass().addClass("fa fa-backward");

      $(".ui-icon.ui-icon-seek-first").wrap("<div class='btn btn-sm btn-default'></div>");
      $(".ui-icon.ui-icon-seek-first").removeClass().addClass("fa fa-fast-backward");

      $(".ui-icon.ui-icon-seek-next").wrap("<div class='btn btn-sm btn-default'></div>");
      $(".ui-icon.ui-icon-seek-next").removeClass().addClass("fa fa-forward");

      $(".ui-icon.ui-icon-seek-end").wrap("<div class='btn btn-sm btn-default'></div>");
      $(".ui-icon.ui-icon-seek-end").removeClass().addClass("fa fa-fast-forward");


    }
  }
})(jQuery, Drupal, drupalSettings);
