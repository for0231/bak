(function ($,Drupal){
  Drupal.behaviors.onlinechart = {
    attach: function (context) {
      $('#onlineClick').click(function(){
        $('#onlineChart').hide();
        $('#alertChart').show();        
      });
      $('#onlineClose').click(function() {
        $('#alertChart').hide(); 
        $('#onlineChart').show();
      })
      $('#closeMessage').click(function(){
          $('#alertChart').hide(); 
          $('#onlineChart').show();    	  
      });
      var uid =null;
      $('#alertBtn').click(function(){
        var userName = $('#yourName').val();
        var email = $('#email').val();
        var url = Drupal.url('home/ajax/online');
        var data = {'username':userName, 'email':email};
        function getConn(url, data) {
          $.ajax({
            type: 'POST',
            url: url,
            data: data,
            dataType: 'json',
            success: function(msg){
              var obj = eval("("+msg+")");
              if(obj.ret == 'ok') {
            	  uid = obj.uid;
                $('#alertChartPublish').show();
                getContent(obj.uid);
              } else if (obj.ret == 'no') {
                $('#alertChartWait').hide();
                $('#alertChart').hide(); 
                $('#onlineChart').show();
              } else {
                $('.personnum').html(obj.ret);
                $('#alertChartWait').show();
                var param = {};
                param['uid'] = obj.uid;
                param['num'] = obj.ret;
                getConn(url, param);
              }
            }
          })
        }
        getConn(url, data);
      });
      function getContent(uid) {
          var url = Drupal.url('home/ajax/online/content');
          $.ajax({
            type: 'POST',
            url: url,
            data: {'uid':uid},
            dataType: 'json',
            success: function(content){
          	  $('#ltcontent').empty();
              for(var item in content) {
                var list = "<li>";
                list += "<em>"+content[item].name+"</em>";
                list += "<p>"+content[item].content+"<span>"+content[item].time+"</span></p>";
                list += "</li>";
                
                $('#ltcontent').append(list);
              }
              getContent(uid);
            }
          });
        }
      function getadminContent(uid) {
        var url = Drupal.url('admin/ajax/online/content');
        $.ajax({
          type: 'POST',
          url: url,
          data: {'uid':uid},
          dataType: 'json',
          success: function(content){
        	$('.return_content').empty();
            for(var item in content) {
                var list = "<li>";
                list += "<em>"+content[item].name+"</em>";
                list += "<p>"+content[item].content+"<span>"+content[item].time+"</span></p>";
                list += "</li>";
              $('.return_content').append(list);
            }
            getadminContent(uid);
          }
        });
      }
      var num=null;
      $('#like').click(function(){
    	  var url = Drupal.url('home/ajax/online/like');
          $.ajax({
              type: 'POST',
              url: url,
              data: {'uid':uid},
              dataType: 'json',
              success: function(msg){
                  var obj = eval("("+msg+")");
                  if(obj.ret=='ok'){
                	  if(num){
                		  alert('你已经点赞');
                		  return ;
                	  }
                	  $('#like').css('background-color','red');
                	  alert('点赞成功');
                	  num = 1;
                  }
              }
            });
      });
      $('#dislike').click(function(){
    	  var url = Drupal.url('home/ajax/online/dislike');
          $.ajax({
              type: 'POST',
              url: url,
              data: {'uid':uid},
              dataType: 'json',
              success: function(msg){
                  var obj = eval("("+msg+")");
                  if(obj.ret=='ok'){
                	  if(num==2){
                		  alert('你已经评价');
                		  return;
                	  }
                	  $('#dislike').css('background-color','green');
                	  alert('感谢你的评价');
                	  num = 2;
                  }
              }
            });
      });
      $('#questions').click(function(){
        var userQuestion = $('#ask_content').val();
        var url = Drupal.url('home/ajax/online/send');
        $.ajax({
          type: 'POST',
          url: url,
          data: {'userQuestion':userQuestion},
          dataType: 'json',
          success: function(data){
            var list = "<li>";
            list += "<em>"+data.name+"</em>";
            list += "<p>"+data.content+"<span>"+data.time+"</span></p>";
            list += "</li>";
            $('#ltcontent').append(list);
          }
        });
      });
    }   
  }
})(jQuery, Drupal)
