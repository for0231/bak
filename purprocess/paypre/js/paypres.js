(function($, Drupal, drupalSettings) {
  "use strict";

  Drupal.behaviors.paypres = {
    attach: function (context) {

      $("#paypredetails").jqGrid({
        url: Drupal.url('ajax/paypre/'+ drupalSettings.paypre.id +'/samecnos/details'),
        datatype: "json",
			  height : 'auto',
        colNames: ['ID', '付款单编号', '付款单名称', '合同号', '部门', '建单人', '币种', '预付金额', '应付金额', '总金额', '工单状态', '审批状态', '创建时间'],
        colModel:[
          {name: 'id', index: 'id', width: 40, editable: false},
          {name: 'no', index: 'no', editable: false},
          {name: 'title', index: 'title', editable: false},
          {name: 'contact_no', index: 'contact_no', editable: false},
          {name: 'depart', index: 'depart', editable: false},
          {name: 'uid', index: 'uid', editable: false},
          {name: 'ftype', index: 'ftype', editable: false},
          {name: 'amount', index: 'amount', editable: false},
          {name: 'pre_amount', index: 'pre_amount', editable: false},
          {name: 'all_amount', index: 'all_amount', editable: false},
          {name: 'status', index: 'status', editable: false},
          {name: 'audit', index: 'audit', editable: false},
          {name: 'created', index: 'created', editable: false},
        ],
				rowNum : 10,
				rowList : [10, 20, 50],
				pager : '#paypredetailsnav',
				sortname : 'id',
				autowidth : true,
        viewrecords: true,
        recordpos: 'right',
        multiselect: false,
      	subGrid: true,
        subGridOptions: {
          "plusicon"  : "ui-icon-triangle-1-e",
          "minusicon" : "ui-icon-triangle-1-s",
          "openicon"  : "ui-icon-arrowreturn-1-e"
        },
        subGridRowExpanded: function(subgrid_id, row_id) {
          var subgrid_table_id, pager_id;
          subgrid_table_id = subgrid_id+"_ct";
          pager_id = "cp_"+subgrid_table_id;
          $("#"+subgrid_id).html("<table id='"+subgrid_table_id+"' class='scroll'></table><div id='"+pager_id+"' class='scroll'></div>");
          $("#"+subgrid_table_id).jqGrid({
            url: Drupal.url('ajax/paypre/purchase/statistic/collection'+"?id="+row_id),
            datatype: "json",
            colNames: ['ID', '采购单编号', '采购单名称', '部门', '建单人', '金额', '工单状态', '审批状态', '创建时间'],
            colModel: [
              {name: 'id', index: 'id', width: 40, editable: false},
              {name: 'no', index: 'no', width: 200, editable: false},
              {name: 'title', index: 'title', editable: false},
              {name: 'depart', index: 'depart', width: 40, editable: false},
              {name: 'uid', index: 'uid', width: 40, editable: false},
              {name: 'amount', index: 'amount', width: 100, editable: false},
              {name: 'status', index: 'status', editable: false},
              {name: 'audit', index: 'audit', editable: false},
              {name: 'created', index: 'created', editable: false},
            ],
              rowNum: 50,
              pager: pager_id,
              sortname: 'id',
              sortorder: "asc",
              height: '100%',
              caption: '采购单',

              multiselect: false,
              subGrid: true,
              subGridOptions: {
                "plusicon"  : "ui-icon-triangle-1-e",
                "minusicon" : "ui-icon-triangle-1-s",
                "openicon"  : "ui-icon-arrowreturn-1-e"
              },
              subGridRowExpanded: function(subgrid_id, row_id) {
                var subgrid_table_id, pager_id;
                subgrid_table_id = subgrid_id+"_pt";
                pager_id = "pp_"+subgrid_table_id;
                $("#"+subgrid_id).html("<table id='"+subgrid_table_id+"' class='scroll'></table><div id='"+pager_id+"' class='scroll'></div>");
                $("#"+subgrid_table_id).jqGrid({
                  url: Drupal.url('ajax/part/statistic/collection'+"?id="+row_id),
                  datatype: "json",
                  colNames: ['ID', '配件名称', '类型', '部门', '建单人', '币种', '金额', '单价', '数量', '运费', '需求单', '需求单状态', '需求单审批状态', '创建时间'],
                  colModel: [
                    {name: 'id', index: 'id', width: 40, editable: false},
                    {name: 'no', index: 'no', width: 200, editable: false},
                    {name: 'parttype', index: 'parttype', width: 200, editable: false},
                    {name: 'depart', index: 'depart', width: 40, editable: false},
                    {name: 'uid', index: 'uid', width: 40, editable: false},
                    {name: 'ftype', index: 'ftype', width: 40, editable: false},
                    {name: 'amount', index: 'amount', width: 100, editable: false},
                    {name: 'unitprice', index: 'unitprice', width: 100, editable: false},
                    {name: 'num', index: 'num', width: 100, editable: false},
                    {name: 'wuliufee', index: 'wuliufee', width: 100, editable: false},
                    {name: 'rno', index: 'rno', editable: false},
                    {name: 'status', index: 'status', editable: false},
                    {name: 'audit', index: 'audit', editable: false},
                    {name: 'created', index: 'created', editable: false},
                  ],
                    rowNum: 50,
                    pager: pager_id,
                    sortname: 'id',
                    sortorder: "asc",
                    height: '100%',
                    caption: '配件列表',
                    multiselect: false,
                });
                $("#"+subgrid_table_id).jqGrid('navGrid',"#"+pager_id,{edit:false,add:false,del:false})
             }
        });
        $("#"+subgrid_table_id).jqGrid('navGrid',"#"+pager_id,{edit:false,add:false,del:false})
     }
     });

      //  付款单详情.
      /*
      $("#paypredetails").jqGrid({
        url: Drupal.url('ajax/paypre/'+ drupalSettings.paypre.id +'/details'),
        datatype: "json",
			  height : 'auto',
        colNames: ['ID', '采购编号', '部门', '建单人', '币种', '金额', '工单状态', '审批状态', '创建时间'],
        colModel:[
          {name: 'id', index: 'id', width: 40, editable: false},
          {name: 'no', index: 'no', editable: false},
          {name: 'depart', index: 'depart', editable: false},
          {name: 'uid', index: 'uid', editable: false},
          {name: 'ftype', index: 'ftype', editable: false},
          {name: 'amount', index: 'amount', editable: false},
          {name: 'status', index: 'status', editable: false},
          {name: 'audit', index: 'audit', editable: false},
          {name: 'created', index: 'created', editable: false},
        ],
				rowNum : 10,
				rowList : [10, 20, 50],
				pager : '#paypredetailsnav',
				sortname : 'id',
				autowidth : true,
        viewrecords: true,
        recordpos: 'right',
        multiselect: false,
      	subGrid: true,
        subGridOptions: {
          "plusicon"  : "ui-icon-triangle-1-e",
          "minusicon" : "ui-icon-triangle-1-s",
          "openicon"  : "ui-icon-arrowreturn-1-e"
        },
        subGridRowExpanded: function(subgrid_id, row_id) {
          var subgrid_table_id, pager_id;
          subgrid_table_id = subgrid_id+"_pt";
          pager_id = "pp_"+subgrid_table_id;
          $("#"+subgrid_id).html("<table id='"+subgrid_table_id+"' class='scroll'></table><div id='"+pager_id+"' class='scroll'></div>");
          $("#"+subgrid_table_id).jqGrid({
            url: Drupal.url('ajax/part/statistic/collection'+"?id="+row_id),
            datatype: "json",
            colNames: ['ID', '配件名称', '类型', '部门', '建单人', '币种', '金额', '需求单', '需求单状态', '需求单审批状态', '创建时间'],
            colModel: [
              {name: 'id', index: 'id', width: 40, editable: false},
              {name: 'no', index: 'no', width: 200, editable: false},
              {name: 'parttype', index: 'parttype', width: 200, editable: false},
              {name: 'depart', index: 'depart', width: 40, editable: false},
              {name: 'uid', index: 'uid', width: 40, editable: false},
              {name: 'ftype', index: 'ftype', width: 40, editable: false},
              {name: 'amount', index: 'amount', width: 100, editable: false},
              {name: 'rno', index: 'rno', editable: false},
              {name: 'status', index: 'status', editable: false},
              {name: 'audit', index: 'audit', editable: false},
              {name: 'created', index: 'created', editable: false},
            ],
              rowNum: 50,
              pager: pager_id,
              sortname: 'id',
              sortorder: "asc",
              height: '100%',
              caption: '配件列表',
              multiselect: false,
          });
          $("#"+subgrid_table_id).jqGrid('navGrid',"#"+pager_id,{edit:false,add:false,del:false})
       }
     });
      */

      $("#paypredetails").jqGrid('navGrid', "#paypredetailsnav", {
        edit : false,
        add : false,
        del : false,
        search: false,
        refresh: true
      });

      $("#check_audit").once().click(function() {
        console.log('fdaf');
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
			$(window).on('resize.jqGrid', function() {
        // detail
				$("#paypredetails").jqGrid('setGridWidth', $("#content").width());

        if ($(window).width() <= 460) {
          $("#paypredetails").jqGrid('hideCol', ['id', 'depart', 'uid', 'status', 'audit', 'created']);
        } else {
          $("#paypredetails").jqGrid('showCol', ['id', 'depart', 'uid', 'status', 'audit', 'created']);
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

