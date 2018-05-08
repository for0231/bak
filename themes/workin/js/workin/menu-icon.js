(function ($, Drupal) {
  "use strict";
	Drupal.behaviors.menu_icon = {
    attach: function (context) {
    	var first=$("nav li").children(":first").attr("title");
    	if(first==="工单系统"){
    		$("nav li").children(":first").attr("id","first");
    		$('#first i').addClass('fa-home');
    		$('#first').parent().next().children("a:first-child").attr('id','sen');
    		$('#sen i').addClass("fa-cube txt-color-blue");
    		$('#sen').parent().next().children("a:first-child").attr('id','shan');
    		$('#shan i').addClass("fa-inbox");
    		$('#shan').parent().next().children("a:first-child").attr('id','si');
    		$('#si i').addClass("fa-bar-chart-o");
    		$('#si').parent().next().children("a:first-child").attr('id','wu');
    		$('#wu i').addClass("fa-table");
    		$('#wu').parent().next().children("a:first-child").attr('id','liu');
    		$('#liu i').addClass("fa-pencil-square-o");
    		$('#liu').parent().next().children("a:first-child").attr('id','qi');
    		$('#qi i').addClass(" fa-desktop");
    		$('#qi').parent().next().children("a:first-child").attr('id','ba');
    		$('#ba i').addClass("fa-list-alt");
    		$('#ba').parent().next().children("a:first-child").attr('id','jiu');
    		$('#first').parent("li").attr('class','open gongdan');
    		$('.gongdan').find('em').attr('class','fa fa-minus-square-o');
    		$('.gongdan').find('ul').attr('class','block');
    		}
    	}
	  }
})(jQuery, Drupal)