(function($, Drupal, drupalSettings) {
  "use strict";
  Drupal.behaviors.paypres = {
    attach: function (context) {
      $("#part_trend_statis").jqGrid({
        url: Drupal.url('ajax/cg_statis/paypro/statis/details/collection'),
        datatype: "json",
			  height : 'auto',
        colNames: ['配件ID','配件名称','单位','需求数量','需求单号','创建人','创建时间','需求时间','预计交付时间','采购单号','采购人','采购日期','供应商','申请付款日期','收款方','付款单号','支付日期','付款方','币种','金额','支付单号','物流单','所属公司','存放地点'],
        colModel:[
         {name: 'nid', index: 'nid', width: 10, editable: false},
         {name: 'name', index: 'name', width: 10, editable: false},
         {name: 'unit', index: 'unit', width: 10, editable: false},
         {name: 'num', index: 'num', width: 10, editable: false},
         {name: 'rno', index: 'rno', width: 10, editable: false},
         {name: 'uid', index: 'uid', width: 10, editable: false},
         {name: 'created', index: 'created', width: 10, editable: false},
         {name: 'requiredate', index: 'requiredate', width: 10, editable: false},
         {name: 'plandate', index: 'plandate', width: 10, editable: false},
         {name: 'cno', index: 'cno', width: 10, editable: false},
         {name: 'cg_user', index: 'cg_user', width: 10, editable: false},
         {name: 'cg_time', index: 'cg_time', width: 10, editable: false},
         {name: 'gongying', index: 'gongying', width: 10, editable: false},
         {name: 'accept_time', index: 'accept_time', width: 10, editable: false},
         {name: 'acceptname', index: 'acceptname', width: 10, editable: false},
         {name: 'paypre_no', index: 'paypre_no', width: 10, editable: false},
         {name: 'pay_time', index: 'pay_time', width: 10, editable: false},
         {name: 'fname', index: 'fname', width: 10, editable: false},
         {name: 'ftype', index: 'ftype', width: 10, editable: false},
         {name: 'amount', index: 'amount', width: 10, editable: false},
         {name: 'zhifu_no', index: 'zhifu_no', width: 10, editable: false},
         {name: 'ship_supply_no', index: 'ship_supply_no', width: 10, editable: false},
         {name: 'company', index: 'company', width: 10, editable: false},
         {name: 'locate_id', index: 'locate_id', width: 10, editable: false},
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

