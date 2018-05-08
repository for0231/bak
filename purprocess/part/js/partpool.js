(function ($, Drupal, drupalSettings) {
  "use strict";

  Drupal.behaviors.partspool = {
    attach: function (context) {
      // 需求池列表.
      $("#partspool").jqGrid({
        url: Drupal.url('ajax/part/pool'),
        datatype: "json",
			  height : 'auto',
        colNames: ['ID', '型号', '所属分类', '需求单名称(点击查看详情)', '使用地点', '期望交付日期', '待处理数量' , '采购数量'],
        colModel:[
          {name: 'id', index: 'id', width: 40, editable: false},
          {name: 'name', index: 'name', editable: false},
          {name: 'parttype', index: 'parttype', editable: false},
          {name: 'title', index: 'title', editable: false},
          {name: 'locate', index: 'locate', width: 70, editable: false},
          {name: 'requiredate',index:'requiredate', width: 100, editable: false, sorttype:"date"},
          {name: 'num', index: 'num', width: 70, editable: false},
          {name: 'ccsnum', index: 'ccsnum', width: 80, editable: true},
        ],
				rowNum : 10,
				rowList : [10, 20, 50, 100, 1000, 5000],
				pager : '#partspoolnav',
				sortname : 'id',
				autowidth : true,
        toolbarfilter : true,
        viewrecords: true,
        recordpos: 'right',
        multiselect: true,
        caption: "需求池",
     });

      $("#partspool").jqGrid('navGrid', "#partspoolnav", {
        edit : false,
        add : false,
        del : false,
        search: false,
        refresh:true,
      },{
        reloadAfterSubmit: false // del options
      });

      $("#partspool").jqGrid('inlineNav', "#partspoolnav");


      // 需求池统计列表.
      $("#partspoolstatis").jqGrid({
        url: Drupal.url('ajax/part/pool/statis'),
        datatype: "json",
			  height : 'auto',
        colNames: ['配件名称', '需求数量', '采购审批中数量', '采购中数量', '待处理数量'],
        colModel:[
          {name: 'name', index: 'name', editable: false},
          {name: 'rnum', index: 'rnum', editable: false},
          {name: 'csnum', index: 'csnum', editable: false},
          {name: 'cnum', index: 'cnum', editable: false},
          {name: 'wnum', index: 'wnum', editable: false},
        ],
				rowNum : 10,
				rowList : [10, 20, 50],
				pager : '#partspoolstatisnav',
				sortname : 'id',
				autowidth : true,
        toolbarfilter : true,
        viewrecords: true,
        recordpos: 'right',
        caption: "需求状态统计表",
     });

      $("#partspoolstatis").jqGrid('navGrid', "#partspoolstatisnav", {
        edit : false,
        add : false,
        del : false,
        search: true,
        refresh:true,
      },{
        reloadAfterSubmit: false // del options
      });

      $('#createcaigou').once().click(function(){
        var s;
        s = $("#partspool").jqGrid('getGridParam', 'selarrrow');
        var choices = { 'choices': [s] };
        var ajaxDialog = Drupal.ajax({
          dialog: {
            title: '为采购单添加名称',
            width: 'auto',
          },
          dialogType: 'modal',
          url: Drupal.url('admin/purchase/add'),
          submit: {
            data: choices
          },
        });

        ajaxDialog.execute();
        return false;
      });


      /*
      $("#createcaigou").once().click(function() {
        var s;
        s = $("#partspool").jqGrid('getGridParam', 'selarrrow');
        var a = { 'choices': [s] };
        $.ajax({
          type: "POST",
          url: Drupal.url('ajax/purchase/pool/parts/create'),
          data: a,
          success: function(msg) {
            alert(msg);
            $("#partspool").trigger("reloadGrid");
          }
        });
      });*/


			$(window).on('resize.jqGrid', function() {
        // pool
				$("#partspool").jqGrid('setGridWidth', $("#content").width());
				$("#partspoolstatis").jqGrid('setGridWidth', $("#content").width());

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

