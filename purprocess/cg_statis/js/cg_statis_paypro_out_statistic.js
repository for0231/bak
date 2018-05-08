(function($, Drupal) {
  "use strict";
  Drupal.behaviors.paypro_out_statistic = {
    attach: function (context) {
      //公共调用方法
      function Merger(gridName, CellName) {
          //得到显示到界面的id集合
          var mya = $("#" + gridName + "").getDataIDs();
          //当前显示多少条
          var length = mya.length;
          for (var i = 0; i < length; i++) {
              //从上到下获取一条信息
              var before = $("#" + gridName + "").jqGrid('getRowData', mya[i]);
              //定义合并行数
              var rowSpanTaxCount = 1;
              for (var j = i + 1; j <= length; j++) {
                  //和上边的信息对比 如果值一样就合并行数+1 然后设置rowspan 让当前单元格隐藏
                  var end = $("#" + gridName + "").jqGrid('getRowData', mya[j]);

                  if (before[CellName] == end[CellName]) {
                      rowSpanTaxCount++;
                      $("#" + gridName).setCell(mya[j], CellName, "", { display: "none" });

                  } else {
                      rowSpanTaxCount = 1;
                      break;
                  }
                  $("#" + CellName + "" + mya[i] + "").attr("rowspan", rowSpanTaxCount);
              }
          }
      }
      $("#statis").jqGrid({
        url: Drupal.url('ajax/cg_statis/paypro/out/statistic/collection'),
        datatype: "json",
			  height : 'auto',
        colNames: ['ID', '收款方', '收款银行', '收款账号', '币种', '金额总计', '已付', '未付', '开始时间', '截止时间'],
        colModel:[
          {name: 'id', index: 'id', sorttype: "int", width: 30, editable: false},
          {name: 'name', index: 'name', editable: false},
          {name: 'bank', index: 'bank', editable: false},
          {name: 'account', index: 'account', editable: false},
          {name: 'ftype', index: 'ftype', editable: false},
          {name: 'amount', index: 'amount', editable: false},
          {name: 'has', index: 'has', editable: false},
          {name: 'not', index: 'not', editable: false},
          {name: 'begin', index: 'begin', editable: false},
          {name: 'end', index: 'end', editable: false},
        ],
				rowNum : 10,
				rowList : [10, 50, 100, 10000],
				pager : '#statisnav',
				sortname : 'id',
				toolbarfilter : true,
				viewrecords : true,
				sortorder : "desc",
				autowidth : true,
        onSelectRow: function(id) {
        },
        reloadAfterSubmit: true,
        caption: '供应商收款统计',
     });


      $("#statis").jqGrid(
        'navGrid',
        '#statisnav',
        {edit:false,add:false,del:false},
        {},
        {},
        {},
        {multipleSearch:true, multipleGroup:true}
      );

      $("#outstatis").jqGrid({
        url: Drupal.url('ajax/cg_statis/paypro/out/fk/statistic/collection'),
        datatype: "json",
			  height : 'auto',
        colNames: ['ID', '公司名', '银行', '账号', '币种', '金额', '已付', '未付', '开始时间', '截止时间'],
        colModel:[
          {name: 'id', index: 'id', sorttype: "int", width: 30, editable: false},
          {name: 'name', index: 'name', editable: false},
          {name: 'bank', index: 'bank', editable: false},
          {name: 'account', index: 'account', editable: false},
          {name: 'ftype', index: 'ftype', editable: false},
          {name: 'amount', index: 'amount', editable: false},
          {name: 'has', index: 'has', editable: false},
          {name: 'not', index: 'not', editable: false},
          {name: 'begin', index: 'begin', editable: false},
          {name: 'end', index: 'end', editable: false},
        ],
				rowNum : 10,
				rowList : [10, 50, 100, 10000],
				pager : '#outstatisnav',
				sortname : 'id',
				toolbarfilter : true,
				viewrecords : true,
				sortorder : "desc",
				autowidth : true,
        onSelectRow: function(id) {
        },
        reloadAfterSubmit: true,
        caption: '我司付款统计',
     });


      $("#outstatis").jqGrid(
        'navGrid',
        '#outstatisnav',
        {edit:false,add:false,del:false},
        {},
        {},
        {},
        {multipleSearch:true, multipleGroup:true}
      );


      $("#parts").jqGrid({
        url: Drupal.url('ajax/cg_statis/part/closer'),
        datatype: "json",
			  height : 'auto',
        colNames: ['ID', '名称', '类型', '需求数量', '需求单号', '需求日期', '采购单号', '预计交付日期', '付款单号', '支付单号', '物流单', '创建人', '创建时间'],
        colModel:[
          {name: 'id', index: 'id', width: 20, editable: false},
          {name: 'name', index: 'name', editable: false},
          {name: 'parttype', index: 'parttype', editable: false},
          {name: 'num', index: 'num', width: 40, editable: false},
          {name: 'rno', index: 'rno', width: 40, editable: false},
          {name: 'requiredate', index: 'requiredate', width: 60,  editable: false},
          {name: 'cno', index: 'cno', width: 40,  editable: false},
          {name: 'plandate', index: 'plandate',  width: 60, editable: false},
          {name: 'fno', index: 'fno', width: 40,  editable: false},
          {name: 'pno', index: 'pno', width: 40,  editable: false},
          {name: 'ship_supply_no', index: 'ship_supply_no', width: 40,  editable: false},
          {name: 'uid', index: 'uid', width: 40,  editable: false},
          {name: 'created', index: 'created', width: 40,  editable: false},
        ],
				rowNum : 10,
				rowList : [10, 100, 500],
				pager : '#partsnav',
				sortname : 'id',
				autowidth : true,
        toolbarfilter : true,
        viewrecords: true,
        recordpos: 'right',
        caption: "需求配件明细表",
     });

      $("#parts").jqGrid('navGrid', "#partsnav", {
        edit : false,
        add : false,
        del : false,
        search: true,
        refresh:true,
      },{
        reloadAfterSubmit: false // del options
      });

      $("#zijin").jqGrid({
        url: Drupal.url('ajax/cg_statis/paypro/zijin/total/collection'),
        datatype: "json",
			  height : 'auto',
        colNames: ['ID', '申请人', '申请部门', '物品名称', '物品类别', '供应商', '数量', '单价', '币种', '本次付款金额', '是否为预算', '应付金额', '已付金额', '所属公司', '存放地点', '原因/备注', '预计付款公司', '实际付款时间', '实际与计划差异', '备注', '创建时间'],
        colModel:[
          {name: 'id', index: 'id', width: 20, editable: false},
          {name: 'uid', index: 'uid', width: 40,  editable: false},
          {name: 'depart', index: 'depart', width: 40,  editable: false},
          {name: 'name', index: 'name', width: 40,  editable: false,
              //①给当前想合并的单元格设置id
              cellattr: function(rowId, tv, rawObject, cm, rdata) {
                  return 'id=\'name' + rowId + "\'";
              }
          },
          {name: 'parttype', index: 'parttype', width: 40,  editable: false,
              //①给当前想合并的单元格设置id
              cellattr: function(rowId, tv, rawObject, cm, rdata) {
                  return 'id=\'parttype' + rowId + "\'";
              }
          },
          {name: 'supply_id', index: 'supply_id', width: 40,  editable: false},
          {name: 'num', index: 'num', width: 40,  editable: false},
          {name: 'unitprice', index: 'unitprice', width: 40,  editable: false},
          {name: 'ftype', index: 'ftype', width: 40,  editable: false},
          {name: 'thispaypre', index: 'thispaypre', width: 40,  editable: false},
          {name: 'ispre', index: 'ispre', width: 40,  editable: false},
          {name: 'amount', index: 'amount', width: 40,  editable: false},
          {name: 'haspay', index: 'haspay', width: 40,  editable: false},
          {name: 'tocompany', index: 'tocompany', width: 40,  editable: false},
          {name: 'located', index: 'located', width: 40,  editable: false},
          {name: 'thewhy', index: 'thewhy', width: 40,  editable: false},
          {name: 'planpayforcompany', index: 'planpayforcompany', width: 40,  editable: false},
          {name: 'paydate', index: 'paydate', width: 40,  editable: false},
          {name: 'diffamount', index: 'diffamount', width: 40,  editable: false},
          {name: 'description', index: 'description', width: 40,  editable: false},
          {name: 'created', index: 'created', width: 40,  editable: false},
        ],
				rowNum : 500,
				rowList : [500, 1000, 5000],
				pager : '#zijinnav',
				sortname : 'id',
        gridview: true,
        viewrecords: true,
        height: '100%',
        caption: "资金总结表",
        gridComplete: function() {
            //②在gridComplete调用合并方法
            var gridName = "zijin";
            //Merger(gridName, 'name');
            //Merger(gridName, 'parttype');
        }
     });

      $("#zijin").jqGrid('navGrid', "#zijinnav", {
        edit : false,
        add : false,
        del : false,
        search: false,
        refresh:true,
      },{
        reloadAfterSubmit: false // del options
      });
			$(window).on('resize.jqGrid', function() {
				$("#statis").jqGrid('setGridWidth', $("#content").width());
				$("#outstatis").jqGrid('setGridWidth', $("#content").width());
				$("#parts").jqGrid('setGridWidth', $("#content").width());
				$("#zijin").jqGrid('setGridWidth', $("#content").width());
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

