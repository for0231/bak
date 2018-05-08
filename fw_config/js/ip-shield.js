(function ($, Drupal) {
  "use strict";

  Drupal.behaviors.ip_shield = {
    attach: function (context, settings) {
      var filter_ip = '';
      /**
       * 通过IP查询防护
       */
      $('#edit-query').click(function() {
        var ip = $('#edit-ip').val();
        var re=/^(\d+)\.(\d+)\.(\d+)\.(\d+)$/;
        if(re.test(ip)) {
          if(!(RegExp.$1<256 && RegExp.$2<256 && RegExp.$3<256 && RegExp.$4<256)) {
            alert('请输入正确的IP！');
            return false;
          }
        } else {
          alert('请输入正确的IP！');
          return false;
        }
        var _btn = $(this);
        _btn.prop('disabled', true);
        var url = Drupal.url('admin/fw/ip/shield/query');
        var parameter = {};
        parameter['hostaddr'] = ip.replace(/\./g, '-');
        $.ajax({
          type: "GET",
          url: url,
          data: parameter,
          dataType: "json",
          success: function(data) {
            _btn.removeAttr('disabled');
            if(data.status == 'false') {
              alert(data.msg);
              return false;
            }
            setChecked('edit-param-ignore', data.ignore);
            setChecked('edit-param-forbid', data.forbid);
            setChecked('edit-param-forbid-overflow', data.forbid_overflow);
            setChecked('edit-param-reject-foreign-access', data.reject_foreign);
            $('#edit-param-set').val(data.param_set);
            $('#edit-filter-set').val(data.filter_set);
            $('#edit-portpro-set-tcp').val(data.portpro_set_tcp);
            $('#edit-portpro-set-udp').val(data.portpro_set_udp);
            for(var item in data.param_plugin) {
              var key = 'edit-param-plugin-' + item.replace('_', '-');
              var val = data.param_plugin[item];
              setChecked(key, val);
            }
            filter_ip = ip;
            alert('查询成功');
          }
        });
        return false;        
      });

      function setChecked(id, val) {
        if(val == 'checked') {
          $('#' + id).prop("checked", "true");
          return;
        } 
        if($('#' + id).prop('checked')) {
          $('#' + id).removeAttr("checked");
        }
      }
      /**
       * IP防护操作保存
       */
      $('#ip-shield-save').click(function() {
        var ip = $('#edit-ip').val();
        if(ip == '' || ip != filter_ip) {
          alert('请先查询再提交！')
          return false;
        }
        var _btn = $(this);
        _btn.prop('disabled', true);
        var param = getFormValue();
        param['param_setting_addr'] = ip;
        var url = Drupal.url('admin/fw/ip/shield/save');
        $.ajax({
          type: "POST",
          url: url,
          data: param,
          dataType: "json",
          success: function(data) {
            _btn.removeAttr('disabled');
            alert(data.msg);
          }
        });
        return false;
      });
      /**
       * 构建表单值。
       */
      function getFormValue() {
        var param = {};
        $('#edit-param input:checked').each(function(){
          var key = 'param_' + $(this).val();
          param[key] = 'ON';
        });
        var param_set = $('#edit-param-set').val();
        param['param_param_set'] = checkValue(param_set);
        var filter_set = $('#edit-filter-set').val();
        param['param_filter_set'] = checkValue(filter_set);
        var set_tcp = $('#edit-portpro-set-tcp').val();
        param['param_portpro_set_tcp'] = checkValue(set_tcp);
        var set_udp = $('#edit-portpro-set-udp').val();
        param['param_portpro_set_udp'] = checkValue(set_udp);
        $('#edit-param-plugin input:checked').each(function(){
          var key = 'param_plugin_' + $(this).val();
          param[key] = 'ON';
        });
        return param;
      }
      /**
       * 检查值
       */
      function checkValue(val) {
        if(val == '') {
          return 0;
        }
        var patrn = /^\d+(\.\d+)?$/;
        if(patrn.test(val)) {
          return val;
        }
        return 0;
      }
      /**
       * 批量操作
       */
      $('#multiple-shield-save').click(function(){
        var ips = $('#edit-ip').val();
        var strips = $.trim(ips)
        var arr = strips.split('\n');        
        var re=/^(\d+)\.(\d+)\.(\d+)\.(\d+)$/;
        for(var i=0;i<arr.length;i++) {
          if(re.test(arr[i])) {
            if(!(RegExp.$1<256 && RegExp.$2<256 && RegExp.$3<256 && RegExp.$4<256)) {
              alert('输入的[' + arr[i] + ']不是IP格式');
              return false;
            }
          } else {
            alert('输入的[' + arr[i] + ']不是IP格式');
            return false;
          }
        }
        var _btn = $(this);
        _btn.prop('disabled', true);
        var param = getFormValue();
        var url = Drupal.url('admin/fw/ip/shield/save');
        var index = arr.length;
        var msg = '批量操作结果：';
        for(var i=0;i<index;i++) {
          var ip = arr[i];
          param['param_setting_addr'] = ip;
          $.ajax({
            type: "POST",
            url: url,
            data: param,
            dataType: "json",
            async: false,
            success: function(data) {
              if(data.status == 'false') {
                msg += "\n" + ip + '：' + data.msg;
                return false;
              }
              msg += "\n" + ip + '：' + data.msg;
            }
          });
        }
        _btn.removeAttr('disabled');
        alert(msg);
        return false;
      });

      $('#ip-shield-filter').click(function() {
        var ips = $('#shield-ips').val();
        var strips = $.trim(ips)
        var arr = strips.split('\n');        
        var re=/^(\d+)\.(\d+)\.(\d+)\.(\d+)$/;
        for(var i=0;i<arr.length;i++) {
          if(re.test(arr[i])) {
            if(!(RegExp.$1<256 && RegExp.$2<256 && RegExp.$3<256 && RegExp.$4<256)) {
              alert('输入的[' + arr[i] + ']不是IP格式');
              return false;
            }
          } else {
            alert('输入的[' + arr[i] + ']不是IP格式');
            return false;
          }
        }
        //查询
        var tbody = $('#filter-return').find('tbody');
        tbody.children().remove();
        var url = Drupal.url('admin/fw/ip/shield/query');
        for(var i=0;i<arr.length;i++) {
          var parameter = {};
          parameter['hostaddr'] = arr[i].replace(/\./g, '-');
          $.ajax({
            type: "GET",
            url: url,
            data: parameter,
            dataType: "json",
            success: function(data) {
              if(data.status == 'false') {
                alert(data.msg);
                return false;
              }
              addRow(data);
            }
          });
        }
      });

      function addRow(data) {
        var tbody = $('#filter-return').find('tbody');      
        var tr = '<tr>'
        tr += '<td>'+data.ip+'</td>';
        if(data.ignore == 'checked') {
          tr += '<td><input type="checkbox" checked="checked"/></td>';
        } else {
          tr += '<td><input type="checkbox" /></td>';
        }
        if(data.forbid == 'checked') {
          tr += '<td><input type="checkbox" checked="checked"/></td>';
        } else {
          tr += '<td><input type="checkbox" /></td>';
        }
        if(data.forbid_overflow == 'checked') {
          tr += '<td><input type="checkbox" checked="checked"/></td>';
        } else {
          tr += '<td><input type="checkbox" /></td>';
        }
        if(data.reject_foreign == 'checked') {
          tr += '<td><input type="checkbox" checked="checked"/></td>';
        } else {
          tr += '<td><input type="checkbox" /></td>';
        }
        tr += '<td>'+ data.param_set +'</td>';
        tr += '<td>'+ data.filter_set +'</td>';
        tr += '<td>'+ data.portpro_set_tcp +'</td>';
        tr += '<td>'+ data.portpro_set_udp +'</td>';
        for(var item in data.param_plugin) {
          var val = data.param_plugin[item];
          if(val == 'checked') {
            tr += '<td><input type="checkbox" checked="checked"/></td>';
          } else {
            tr += '<td><input type="checkbox" /></td>';
          }
        }
        tr += '</tr>';
        tbody.append(tr);
      }
      /**
       * 更新IP库
       */
      $("#update-ip-library").click(function(){
        var _btn = $(this);
        _btn.prop('disabled', true);
        var url = Drupal.url('admin/fw/ip/library');
        $.ajax({
          type: "GET",
          url: url,
          dataType: "json",
          success: function(data) {
            _btn.removeAttr('disabled');
            alert(data.msg);
          }
        });
        return false;
      });
    }
  }
})(jQuery, Drupal)
