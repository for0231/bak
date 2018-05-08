(function ($, Drupal, drupalSettings) {
  "use strict";

  Drupal.behaviors.purchase_part_pool = {
    attach: function (context) {
      $("#purchasepartspool").jqGrid({
        url: Drupal.url('ajax/purchase/'+ drupalSettings.purchase.id +'/part/collection'),
        datatype: "json",
			  height : 'auto',
        colNames: ['ID', '名称', '期望交付日期', '数量', '使用地点'],
        colModel:[
          {name: 'id', index: 'id', sorttype: "int", width: 30, editable: false},
          {name: 'name', index: 'name', editable: false},
          {name: 'requiredate',index:'requiredate', editable: false, sorttype:"date"},
          {name: 'num', index: 'num', width: 40, editable: false},
          {name: 'locate_id', index: 'locate_id', editable: false},
        ],
				rowNum : 5,
				rowList : [5, 50, 100],
				pager : '#purchasepartspoolnav',
				sortname : 'id',
				toolbarfilter : true,
				viewrecords : true,
				sortorder : "desc",
				multiselect : true,
				autowidth : true,
        onSelectRow: function(ids) {
          //alert(ids);
        },
        editurl: Drupal.url('ajax/purchase/'+ drupalSettings.purchase.id +'/part/collection'),
        reloadAfterSubmit: true,
        caption: '需求池物品列表',
     });

      $("#purchasepartspool").jqGrid('navGrid', "#purchasepartspoolnav", {
        edit : false,
        add : false,
        del : false,
        search: false,
        refresh: true
      });

			$(window).on('resize.jqGrid', function() {
        // edit
				$("#purchasepartspool").jqGrid('setGridWidth', $("#drupal-modal--body").width());
        // detail
				//$("#jqgrid-detail").jqGrid('setGridWidth', $("#content").width());
			})

      $("#editcaigou").once().click(function() {
        var s;
        s = $("#purchasepartspool").jqGrid('getGridParam', 'selarrrow');
        var a = { 'choices': [s] };
        $.ajax({
          type: "POST",
          url: Drupal.url('ajax/purchase/'+ drupalSettings.purchase.id +'/pool/parts/append'),
          data: a,
          success: function(msg) {
            alert(msg);
            $("#purchasepartspool").trigger("reloadGrid");
          }
        });
      });

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
