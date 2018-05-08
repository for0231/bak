(function($, Drupal, drupalSettings) {
  "use strict";
  Drupal.behaviors.paypres = {
    attach: function (context) {

      $("#paypro_aggretotal_statis").jqGrid({
        url: Drupal.url('ajax/paypro/statis/aggretotal/collection'),
        datatype: "json",
			  height : 'auto',
        colNames: ['ID', '物品类别', '物品名称', '数量', '币种', '金额'],
        colModel:[
          {name: 'id', index: 'id', width: 10, editable: false},
          {name: 'type', index: 'type', width: 40, editable: false},
          {name: 'name', index: 'name', width: 140, editable: false},
          {name: 'num', index: 'num', width: 40, editable: false},
          {name: 'ftype', index: 'ftype', width: 40, editable: false},
          {name: 'amount', index: 'amount', width: 100, editable: false},
        ],
				rowNum : 50,
				rowList : [50, 100, 500],
				pager : '#paypro_aggretotal_statisnav',
				sortname : 'id',
				autowidth : true,
        viewrecords: true,
        recordpos: 'right',
        multiselect: false,
        grouping: true,
        groupingView: {
          groupField: ['type'],
          groupSummary: [true],
          groupDataSorted: true,
        },
        footerrow: true,
        userDataOnFooter: true
     });

      $("#paypro_aggretotal_statis").jqGrid('navGrid', "#paypro_aggretotal_statisnav", {
        edit : false,
        add : false,
        del : false,
        search: false,
        refresh: true
      });


			$(window).on('resize.jqGrid', function() {
				$("#paypro_aggretotal_statis").jqGrid('setGridWidth', $("#content").width());
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


