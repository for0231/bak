(function ($, Drupal, drupalSettings) {
  "use strict";

  Drupal.behaviors.requirement = {
    attach: function (context) {
      $("input[name^='spinner']").spinner();
      $('#more-parts').once().click(function(){
        var ajaxDialog = Drupal.ajax({
          dialog: {
            title: '添加需求物品',
            width: 'auto',
          },
          dialogType: 'modal',
          url: Drupal.url('admin/part/requirement/' + drupalSettings.requirement.rid + '/add'),
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

      // requirement edit
      $("#rejqgrid").jqGrid({
        url: Drupal.url('ajax/requirement/' + drupalSettings.requirement.rid + '/parts'),
        datatype: "json",
			  height : 'auto',
        styleUI: 'Bootstrap',//设置jqgrid的全局样式为bootstrap样式
        colNames: ['ID', '名称', '类型', '财务编号', '数量', '单位', '使用地点'],
        colModel:[
          {name: 'id', index: 'id', sorttype: "int", width: 30, editable: false},
          {name: 'name', index: 'name', editable: false},
          {name: 'type', index: 'type', editable: false},
          {name: 'caiwunos', index: 'caiwunos', editable: false},
          {name: 'num', index: 'num', width: 40, editable: false},
          {name: 'unit', index: 'unit', width: 20, editable: false},
          {name: 'locate', index: 'locate', width: 40, editable: false},
        ],
				rowNum : 10,
				rowList : [10, 50, 100],
				pager : '#repjqgrid',
				sortname : 'id',
				toolbarfilter : true,
				viewrecords : true,
				sortorder : "desc",
				multiselect : true,
				autowidth : true,
        onSelectRow: function(id) {
        },
        editurl: Drupal.url('ajax/requirement/' + drupalSettings.requirement.rid + '/parts/delete'),
        reloadAfterSubmit: true,
        afterSaveCell: function(rowid, cellname, value, iRow, iCol) {
          console.log(rowid);
        }
     });


      $("#rejqgrid").jqGrid('navGrid', "#repjqgrid", {
        edit : false,
        add : false,
        del : true,
        search: false,
        refresh: true
      });

      // requirement detail.
      $("#jqgrid-detail").jqGrid({
        url: Drupal.url('ajax/requirement/' + drupalSettings.requirement.rid + '/parts/detail'),
        datatype: "json",
			  height : 'auto',
        colNames: ['ID', '名称', '类型', '财务编号', '预交付日期', '数量', '单位', '使用地点', '物流状态', '物流公司', '物流单'],
        colModel:[
          {name: 'id', index: 'id', sorttype: "int", width: 30, editable: false},
          {name: 'name', index: 'name', editable: false},
          {name: 'type', index: 'type', editable: false},
          {name: 'caiwunos', index: 'caiwunos', editable: false},
          {name: 'sdate',index:'sdate', editable: false, sorttype:"date"},
          {name: 'num', index: 'num', width: 40, editable: false},
          {name: 'unit', index: 'unit', width: 20, editable: false},
          {name: 'locate', index: 'locate', width: 40, editable: false},
          {name: 'status', index: 'status', width: 40, editable: false},
          {name: 'wuliu', index: 'wuliu', width: 40, editable: false},
          {name: 'wuliuno', index: 'wuliuno', width: 40, editable: false},
        ],
				rowNum : 10,
				rowList : [10, 50, 100],
				pager : '#pjqgrid-detail',
				sortname : 'id',
				toolbarfilter : true,
				viewrecords : true,
				sortorder : "desc",
				autowidth : true,
        onSelectRow: function(id) {
        },
        reloadAfterSubmit: true,
        afterSaveCell: function(rowid, cellname, value, iRow, iCol) {
          console.log(rowid);
        }
     });
      $("#jqgrid-detail").jqGrid('navGrid', "#pjqgrid-detail", {
        edit : false,
        add : false,
        del : false,
        search: false,
        refresh: true
      });

			$(window).on('resize.jqGrid', function() {
        // edit
				$("#rejqgrid").jqGrid('setGridWidth', $("#content").width());
        // detail
				$("#jqgrid-detail").jqGrid('setGridWidth', $("#content").width());

        if ($(window).width() <= 460) {
          $("#jqgrid-detail").jqGrid('hideCol', ['id', 'caiwunos', 'sdate', 'status', 'wuliuno']);
          $("#rejqgrid").jqGrid('hideCol', ['id', 'caiwunos', 'unit', 'locate']);
        } else {
          $("#jqgrid-detail").jqGrid('showCol', ['id', 'caiwunos', 'sdate', 'status', 'wuliuno']);
          $("#rejqgrid").jqGrid('showCol', ['id', 'caiwunos', 'unit', 'locate']);
        }
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
