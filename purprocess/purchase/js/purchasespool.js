(function ($, Drupal) {
  "use strict";

  Drupal.behaviors.purchasespool = {
    attach: function (context) {

      //  采购池列表.
      $("#purchasespool").jqGrid({
        url: Drupal.url('ajax/purchase/pools'),
        datatype: "json",
			  height : 'auto',
        colNames: ['ID', '采购单号', '采购单名称', '供应商', '币种', '总数量', '平均单价', '金额'],
        colModel:[
          {name: 'id', index: 'id', width: 20, editable: false},
          {name: 'no', index: 'no', editable: false},
          {name: 'title', index: 'title', editable: false},
          {name: 'supply_id', index: 'supply_id', editable: false},
          {name: 'ftype', index: 'ftype', editable: false},
          {name: 'part_nums', index: 'part_nums', editable: false},
          {name: 'aver_price', index: 'aver_price', editable: false},
          {name: 'amount', index: 'amount', editable: false},
        ],
				rowNum : 10,
				rowList : [10, 20, 50],
				pager : '#purchasespoolnav',
				sortname : 'id',
				autowidth : true,
        toolbarfilter : true,
        multiselect: true,
        viewrecords: true,
        recordpos: 'right',
        editurl: Drupal.url('ajax/purchase/pools/operate'),
        reloadAfterSubmit: true,
        caption: "采购池列表",
     });

      $("#purchasespool").jqGrid('navGrid', "#purchasespoolnav", {
        edit : false,
        add : false,
        del : true,
        search: false,
        refresh:true,
      },{
        reloadAfterSubmit: false // del options
      });

      /*
      $("#addfukuan").once().click(function() {
        var s;
        s = $("#purchasespool").jqGrid('getGridParam', 'selarrrow');
        var a = { 'choices': [s] };
        $.ajax({
          type: "POST",
          url: Drupal.url('ajax/paypre/pool/purchase/create'),
          data: a,
          success: function(msg) {
            alert(msg);
            $("#purchasespool").trigger("reloadGrid");
          }
        });
      });
      */
      $('#addfukuan').once().click(function(){
        var s;
        s = $("#purchasespool").jqGrid('getGridParam', 'selarrrow');
        var choices = { 'choices': [s] };
        var ajaxDialog = Drupal.ajax({
          dialog: {
            title: '为付款单添加名称',
            width: 'auto',
          },
          dialogType: 'modal',
          url: Drupal.url('admin/paypre/add'),
          submit: {
            data: choices
          },
        });

        ajaxDialog.execute();
        return false;
      });

			$(window).on('resize.jqGrid', function() {
        // pool
				$("#purchasespool").jqGrid('setGridWidth', $("#content").width());
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

})(jQuery, Drupal);
