(function ($, Drupal, drupalSettings) {
  "use strict";

  Drupal.behaviors.purchase = {
    attach: function (context) {
      $('#more-parts').once().click(function(){
        var ajaxDialog = Drupal.ajax({
          dialog: {
            title: '为采购单添加需求物品',
            width: 'auto',
          },
          dialogType: 'modal',
          url: Drupal.url('admin/purchase/part/collection'),
        });

        ajaxDialog.execute();
        return false;
      });

      // @todo
      // 暂时以这种方式补救，
      // 后期想到解决方法后再把下面的代码移植到audit模块里面。

      $("#check_audit").once().click(function() {
        var $url = '';
        $url = Drupal.url('admin/audit/'+ drupalSettings.audit.module + '/' + drupalSettings.audit.id + '/overview');
        var ajaxDialog = Drupal.ajax({
          dialog: {
            title: '审批流程进度详情',
            width: 'auto',
          },
          dialogType: 'modal',
          url: $url,
        });
        ajaxDialog.execute();
        return false;
      });
      $("#check_check_accept").once().click(function() {
        var $url = '';
        $url = Drupal.url('admin/audit/'+ drupalSettings.audit.module + '/' + drupalSettings.audit.id + '/overview');
        var ajaxDialog = Drupal.ajax({
          dialog: {
            title: '审批',
            width: 'auto',
          },
          dialogType: 'modal',
          url: $url,
        });
        ajaxDialog.execute();
        return false;
      });

      var lastsel3;
      // 自定义的caigou-edit表
      $("#caigou-edit").jqGrid({
        url: Drupal.url('ajax/purchase/' + drupalSettings.purchase.id + '/parts'),
        datatype: "json",
			  height : 'auto',
        colNames: ['ID', '名称', '类型', '使用地点', '供应商', '期望交付日期', '预计交付日期', '单位', '币种', '单价', '物流费', '数量', '金额'],
        colModel:[
          {name: 'id', index: 'id', sorttype: "int", width: 30, editable: false},
          {name: 'name', index: 'name', editable: false},
          {name: 'type', index: 'type', editable: false},
          {name: 'locate_id', index: 'locate_id', width: 60, editable: false},
          {name: 'supply_id', index: 'supply_id', editable: true, edittype:"select",editoptions:{value:drupalSettings.purchase.sid}},
          {name: 'sdate',index:'sdate', editable: false, sorttype:"date"},
          {name: 'pdate',index:'pdate', editable: true, sorttype:"date"},
          {name: 'unit', index: 'unit', width: 40, editable: false},
          {name: 'ftype', index: 'ftype', width: 80,  editable: true, edittype:"select",editoptions:{value:drupalSettings.purchase.ftype}},
          {name: 'unitprice', index: 'unitprice', width: 50, editable: true},
          {name: 'wuliufee', index: 'wuliufee', width: 50, editable: true},
          {name: 'num', index: 'num', width: 50, align:"right", editable: false,formatter: 'integer'},
          {name: 'amount', index: 'amount', width: 80, editable: false, align:"right", formatter:'currency',formatoptions:{thousandsSeparator:","}},
        ],
				rowNum : 5,
				rowList : [10, 50, 100, 5000],
				pager : '#pjcaigou-edit',
				sortname : 'id',
				toolbarfilter : true,
				viewrecords : true,
				sortorder : "desc",
				multiselect : true,
				autowidth : true,
        onSelectRow: function(id) {
	        if(id && id!==lastsel3){
            $('#caigou-edit').jqGrid('restoreRow',lastsel3);
            $('#caigou-edit').jqGrid('editRow',id,true,pickdates);
            lastsel3=id;
          }
        },
        editurl: Drupal.url('ajax/purchase/' + drupalSettings.purchase.id + '/parts/operation'),
        reloadAfterSubmit: true,
      	footerrow : true,
        userDataOnFooter : true,
        altRows : true,
        cellEdit : true,
	      cellsubmit : 'remote',
        cellurl : Drupal.url('ajax/purchase/' + drupalSettings.purchase.id + '/parts/operation'),
        footerrow : true,
        userDataOnFooter : true,
        altRows : true,

     });



      $("#caigou-edit").jqGrid('navGrid', "#pjcaigou-edit", {
        edit : true,
        add : false,
        del : true,
        search: false,
        refresh: true
      });



      // 自定义的detail表
      $("#caigou-detail").jqGrid({
        url: Drupal.url('ajax/purchase/' + drupalSettings.purchase.id + '/parts'),
        datatype: "json",
			  height : 'auto',
        colNames: ['ID', '需求单', '名称', '类型', '使用地点', '供应商', '期望交付日期', '预计交付日期', '物流公司', '物流单号', '单位', '币种', '单价', '物流费', '数量', '金额'],
        colModel:[
          {name: 'id', index: 'id', sorttype: "int", width: 30, editable: false},
          {name: 'rno', index: 'rno', width: 120, editable: false},
          {name: 'name', index: 'name', editable: false},
          {name: 'type', index: 'type', editable: false},
          {name: 'locate_id', index: 'locate_id', width: 60, editable: false},
          {name: 'supply_id', index: 'supply_id', width: 100, editable: false, edittype:"select",editoptions:{value:drupalSettings.purchase.sid}},
          {name: 'sdate',index:'sdate', editable: false, sorttype:"date"},
          {name: 'pdate',index:'pdate', editable: true, sorttype:"date"},
          {name: 'wuliu', index: 'wuliu', width: 80, editable: true, edittype:"select",editoptions:{value:drupalSettings.purchase.ships}},
          {name: 'wuliuno', index: 'wuliuno', width: 80, editable: true},
          {name: 'unit', index: 'unit', width: 40, editable: false},
          {name: 'ftype', index: 'ftype', width: 80},
          {name: 'unitprice', index: 'unitprice', width: 50, editable: false},
          {name: 'wuliufee', index: 'wuliufee', width: 50, editable: true},
          {name: 'num', index: 'num', width: 50, align:"right", editable: false,formatter: 'integer'},
          {name: 'amount', index: 'amount', width: 80, editable: false, align:"right", formatter:'currency',formatoptions:{thousandsSeparator:","}},
        ],
				rowNum : 10,
				rowList : [10, 50, 100, 5000],
				pager : '#pjcaigou-detail',
				sortname : 'id',
				toolbarfilter : true,
				viewrecords : true,
				sortorder : "desc",
				autowidth : true,
        onSelectRow: function(id) {
	        if(id && id!==lastsel3){
            $('#caigou-detail').jqGrid('restoreRow',lastsel3);
            $('#caigou-detail').jqGrid('editRow',id,true,pickdates);
            lastsel3=id;
          }
        },
        editurl: Drupal.url('ajax/purchase/' + drupalSettings.purchase.id + '/parts/operation'),
        reloadAfterSubmit: true,
      	footerrow : true,
        userDataOnFooter : true,
        altRows : true,
        cellEdit : true,
	      cellsubmit : 'remote',
        cellurl : Drupal.url('ajax/purchase/' + drupalSettings.purchase.id + '/parts/operation'),
        footerrow : true,
        userDataOnFooter : true,
        altRows : true,
     });


      $("#caigou-detail").jqGrid('navGrid', "#pjcaigou-detail", {
        edit : false,
        add : false,
        del : false,
        search: false,
        refresh: true
      });


			$(window).on('resize.jqGrid', function() {
        // edit
				$("#caigou-edit").jqGrid('setGridWidth', $("#content").width());
        // detail
				$("#caigou-detail").jqGrid('setGridWidth', $("#content").width());

        if ($(window).width() <= 460) {
          $("#caigou-detail").jqGrid('hideCol', ['id', 'type', 'locate_id', 'sdate', 'pdate', 'wuliu',  'wuliuno']);
          $("#caigou-edit").jqGrid('hideCol', ['id', 'type', 'locate_id', 'sdate', 'pdate']);
        } else {
          $("#caigou-detail").jqGrid('showCol', ['id', 'type', 'locate_id', 'sdate', 'pdate', 'wuliu',  'wuliuno']);
          $("#caigou-edit").jqGrid('showCol', ['id', 'type', 'locate_id', 'sdate', 'pdate']);
        }
			})

      function pickdates(id){
        $("#"+id+"_pdate","#caigou-detail").datepicker({dateFormat:"yy-mm-dd"});
      }
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
