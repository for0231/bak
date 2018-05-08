(function($, Drupal) {
  "use strict";

  Drupal.behaviors.paypropayprespool = {
    attach: function (context) {
      //  付款池列表.
      $("#payprepool").jqGrid({
        url: Drupal.url('ajax/paypro/pools'),
        datatype: "json",
			  height : 'auto',
        colNames: ['ID', '付款单号', '付款单名称', '合同号', '收款账号', '申请人', '创建日期', '币种', '本次预付金额', '剩余应付金额', '总金额',  '处理状态', '审批状态'],
        colModel:[
          {name: 'id', index: 'id', width: 40, editable: false},
          {name: 'no', index: 'no', editable: false},
          {name: 'title', index: 'title', editable: false},
          {name: 'contact_no', index: 'contact_no', editable: false},
          {name: 'acceptaccount', index: 'acceptaccount', editable: false},
          {name: 'uid', index: 'uid', editable: false},
          {name: 'created', index: 'created', editable: false},
          {name: 'ftype', index: 'ftype', editable: false},
          {name: 'amount', index: 'amount', editable: false},
          {name: 'pre_amount', index: 'pre_amount', editable: false},
          {name: 'all_amount', index: 'all_amount', editable: false},
          {name: 'status', index: 'status', width: 80, editable: false},
          {name: 'audit', index: 'audit', width: 80, editable: false},
        ],
				rowNum : 10,
				rowList : [10, 20, 50],
				pager : '#payprepoolnav',
				sortname : 'id',
				autowidth : true,
        toolbarfilter : true,
        multiselect: true,
        viewrecords: true,
        recordpos: 'right',
        //editurl: Drupal.url('ajax/purchase/pools/operate'),
        reloadAfterSubmit: true,
        caption: "付款池列表",
     });
      $("#payprepool").jqGrid('navGrid', "#payprepoolnav", {
        edit : false,
        add : false,
        del : false,
        search: false,
        refresh: true
      });
      /*
      $("#addpaypro").once().click(function() {
        var s;
        s = $("#payprepool").jqGrid('getGridParam', 'selarrrow');
        var a = { 'choices': [s] };
        $.ajax({
          type: "POST",
          url: Drupal.url('ajax/paypro/pool/paypre/create'),
          data: a,
          success: function(msg) {
            alert(msg);
            $("#payprepool").trigger("reloadGrid");
          }
        });
      });*/

      $('#addpaypro').once().click(function(){
        var s;
        s = $("#payprepool").jqGrid('getGridParam', 'selarrrow');
        var choices = { 'choices': [s] };
        var ajaxDialog = Drupal.ajax({
          dialog: {
            title: '为支付单添加名称',
            width: 'auto',
          },
          dialogType: 'modal',
          url: Drupal.url('admin/paypro/add'),
          submit: {
            data: choices
          },
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
