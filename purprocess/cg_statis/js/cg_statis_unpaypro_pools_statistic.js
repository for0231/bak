(function($, Drupal, drupalSettings) {
  "use strict";
  Drupal.behaviors.paypres = {
    attach: function (context) {

      $("#part_trend_statis").jqGrid({
        url: Drupal.url('ajax/cg_statis/paypro/statis/unpaypro/collection'),
        datatype: "json",// 这个参数
			  height : 'auto',
        colNames: ['配件ID','配件名称','配件类型','单位','需求数量','需求单号','币种','单价','付款单号','付款币种','付款单金额','付款申请人','付款单日期','创建人','创建时间'],
        colModel:[
          {name: 'nid', index: 'nid', width: 20, editable: false},
          {name: 'name', index: 'name', width: 40, editable: false},
          {name: 'parttype', index: 'parttype', width: 40, editable: false},
          {name: 'unit', index: 'unit', width: 20, editable: false},
          {name: 'num', index: 'num', width: 20, editable: false},
          {name: 'rno', index: 'rno', width: 40, editable: false},
          {name: 'ftype', index: 'ftype', width: 20, editable: false},
          {name: 'unitprice', index: 'unitprice', width: 40, editable: false},
          {name: 'paypre_no', index: 'paypre_no', width: 40, editable: false},
          {name: 'pay_ftype', index: 'pay_ftype', width: 40, editable: false},          
          {name: 'amount', index: 'amount', width: 40, editable: false},
          {name: 'uid', index: 'uid', width: 40, editable: false},
          {name: 'pay_time', index: 'pay_time', width: 40, editable: false},
          {name: 'created_uid', index: 'created_uid', width: 40, editable: false},
          {name: 'created', index: 'created', width: 40, editable: false},
        ],
				rowNum : 50,
				rowList : [50, 100, 500],
				pager : '#part_trend_statisnav',
				sortname : 'id',
				autowidth : true,
        viewrecords: true,
        recordpos: 'right',
        multiselect: false,
     });
      $("#part_trend_statis").jqGrid('navGrid', "#part_trend_statisnav", {
        edit : false,
        add : false,
        del : false,
        search: false,
        refresh: true
      });


			$(window).on('resize.jqGrid', function() {
				//$("#gbox_statis_1_t").jqGrid('setGridWidth', $("#content").width());
				$("#part_trend_statis").jqGrid('setGridWidth', $("#content").width());
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

