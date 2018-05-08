(function($, Drupal, drupalSettings) {
  "use strict";
  Drupal.behaviors.paypro_payment_detail = {
    attach: function (context) {

      /*
      $("#payment_detail").jqGrid({
        url: Drupal.url('ajax/paypro/' + drupalSettings.paypro.id +'/pcord/collection'),
        datatype: "json",
			  height : 'auto',
        colNames: ['ID', '付款银行', '付款账号', '币种', '金额', '备注', '创建人', '创建时间'],
        colModel:[
          {name: 'id', index: 'id', sorttype: "int", width: 30, editable: false},
          {name: 'fbank', index: 'fbank', editable: false},
          {name: 'faccount', index: 'faccount', editable: false},
          {name: 'ftype', index: 'ftype', editable: false},
          {name: 'amount', index: 'amount', editable: false},
          {name: 'description', index: 'description', editable: false},
          {name: 'uid', index: 'uid', editable: false},
          {name: 'created', index: 'created', editable: false},
        ],
				rowNum : 10,
				rowList : [10, 50, 100, 10000],
				pager : '#payment_detailnav',
				sortname : 'id',
				toolbarfilter : true,
				viewrecords : true,
				sortorder : "desc",
				autowidth : true,
        multiselect: true,
        onSelectRow: function(id) {
        },
        editurl: Drupal.url('ajax/paypro/' + drupalSettings.paypro.id +'/pcord/collection'),
        reloadAfterSubmit: true,
        caption: '支付记录列表',
     });

      $("#payment_detail").jqGrid('navGrid', "#payment_detailnav", {
        edit : false,
        add : false,
        del : true,
        search: false,
        refresh: true
      });




			$(window).on('resize.jqGrid', function() {
				$("#payment_detail").jqGrid('setGridWidth', $("#content").width());
			})
      */



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


