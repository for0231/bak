(function ($, Drupal, drupalSettings) {
  "use strict";

  Drupal.behaviors.unparts = {
    attach: function (context) {


      $("#unpartspool").jqGrid({
        url: Drupal.url('ajax/part/un/pool'),
        datatype: "json",
			  height : 'auto',
        colNames: ['ID', '型号', '待处理数量' , '期望交付日期', '使用地点', '需求单号'],
        colModel:[
          {name: 'id', index: 'id', width: 40, editable: false},
          {name: 'name', index: 'name', editable: false},
          {name: 'num', index: 'num', editable: false},
          {name: 'requiredate',index:'requiredate', width: 200, editable: false, sorttype:"date"},
          {name: 'locate', index: 'locate', editable: false},
          {name: 'rno', index: 'rno', editable: false},
        ],
				rowNum : 10,
				rowList : [10, 20, 50, 100, 1000, 5000],
				pager : '#unpartspoolnav',
				sortname : 'id',
				autowidth : true,
        toolbarfilter : true,
        viewrecords: true,
        recordpos: 'right',
        multiselect: true,
        caption: "清洗池",
        editurl: Drupal.url('ajax/part/un/pool/delete'),
     });

      $("#unpartspool").jqGrid('navGrid', "#unpartspoolnav", {
        edit : false,
        add : false,
        del : true,
        search: false,
        refresh:true,
      },{
        reloadAfterSubmit: false // del options
      });

      //$("#unpartspool").jqGrid('inlineNav', "#unpartspoolnav");


			$(window).on('resize.jqGrid', function() {
        // un pool
				$("#unpartspool").jqGrid('setGridWidth', $("#content").width());
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
