/**
 * Template Name: Color
 * version: 1.1.0
 * Author: Kevin
 */
(function ($, window) {
  $(document).ready(function() {

var handlePageContentView = function() {
  "use strict";
  $.when($("#page-loader").addClass("hide")).done(function() {
      $("#page-container").addClass("in");
  });
};
var generateSlimScroll = function(e) {
	var t = $(e).attr("data-height");
	t = !t ? $(e).height() : t;
	var n = {
		height: t,
		alwaysVisible: true
	};
	if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
		n.wheelStep = 3;
		n.touchScrollStep = 10;
	}
  $(e).slimScroll(n);
};
var handleSlimScroll = function() {
	"use strict";
	$("[data-scrollbar=true]").each(function() {
		generateSlimScroll($(this));
	});
};
var handleSidebarMenu = function() {
	"use strict";
	$(".sidebar .nav > .has-sub > a").click(function() {
		var e = $(this).next(".sub-menu");
		var t = ".sidebar .nav > li.has-sub > .sub-menu";
		if ($(".page-sidebar-minified").length === 0) {
			$(t).not(e).slideUp(250, 
				function() {
					$(this).closest("li").removeClass("expand");	
			});
			$(e).slideToggle(250,
			function() {
					var e = $(this).closest("li");
					if ($(e).hasClass("expand")) {
							$(e).removeClass("expand");
					} else {
							$(e).addClass("expand");
					}
			});
		}
	});
  $(".sidebar .nav > .has-sub .sub-menu li.has-sub > a").click(function() {
    if ($(".page-sidebar-minified").length === 0) {
      var e = $(this).next(".sub-menu");
      $(e).slideToggle(250);
    }
  });
};
var handleSidebarMinify = function() {
	$("[data-click=sidebar-minify]").click(function(e) {
		e.preventDefault();
		var t = "page-sidebar-minified";
		var n = "#page-container";
		if ($(n).hasClass(t)) {
			$(n).removeClass(t);
			if ($(n).hasClass("page-sidebar-fixed")) {
				generateSlimScroll($('#sidebar [data-scrollbar="true"]'));
			}
		} else {
			$(n).addClass(t);
			if ($(n).hasClass("page-sidebar-fixed")) {
				$('#sidebar [data-scrollbar="true"]').slimScroll({
					destroy: true
				});
				$('#sidebar [data-scrollbar="true"]').removeAttr("style");
			}
			$("#sidebar [data-scrollbar=true]").trigger("mouseover");
		}
		$(window).trigger("resize");
	});
};
var handleScrollToTopButton = function() {
	"use strict";
	$(document).scroll(function() {
		var e = $(document).scrollTop();
		if (e >= 200) {
			$("[data-click=scroll-top]").addClass("in");
		} else {
			$("[data-click=scroll-top]").removeClass("in");
		}
	});
	$("[data-click=scroll-top]").click(function(e) {
		e.preventDefault();
		$("html, body").animate({
			scrollTop: $("body").offset().top
		}, 500);
	});
};
var handleThemePanelExpand = function() {
    $('[data-click="theme-panel-expand"]').click(function() {
        var e = ".theme-panel";
        var t = "active";
        if ($(e).hasClass(t)) {
            $(e).removeClass(t);
        } else {
            $(e).addClass(t);
        }
    });
};

var handleThemePageStructureControl = function() {
  if ($.cookie && $.cookie("theme")) {
    if ($(".theme-list").length !== 0) {
      $(".theme-list [data-theme]").closest("li").removeClass("active");
      $('.theme-list [data-theme="' + $.cookie("theme") + '"]').closest("li").addClass("active");
    }
    var e = "../css/theme/" + $.cookie("theme") + ".css";
      $("#theme").attr("href", e);
  }

	if ($.cookie && $.cookie("sidebar-styling")) {
		if ($(".sidebar").length !== 0 && $.cookie("sidebar-styling") === "grid") {
			$(".sidebar").addClass("sidebar-grid");
			$('[name=sidebar-styling] option[value="2"]').prop("selected", true);
		}
	}

  if ($.cookie && $.cookie("header-styling")) {
    if ($(".header").length !== 0 && $.cookie("header-styling") === "navbar-inverse") {
      $(".header").addClass("navbar-inverse");
      $('[name=header-styling] option[value="2"]').prop("selected", true);
    }
  }
  if ($.cookie && $.cookie("content-gradient")) {
    if ($("#page-container").length !== 0 && $.cookie("content-gradient") === "enabled") {
      $("#page-container").addClass("gradient-enabled");
      $('[name=content-gradient] option[value="2"]').prop("selected", true);
    }
  }
  if ($.cookie && $.cookie("content-styling")) {
    if ($("body").length !== 0 && $.cookie("content-styling") === "black") {
      $("body").addClass("flat-black");
      $('[name=content-styling] option[value="2"]').prop("selected", true);
     }
  }
  $(".theme-list [data-theme]").click(function() {
      var e = "../css/theme/" + $(this).attr("data-theme") + ".css";
	    $("#theme").attr("href", e);
  	  $(".theme-list [data-theme]").not(this).closest("li").removeClass("active");
   	  $(this).closest("li").addClass("active");
    	$.cookie("theme", $(this).attr("data-theme"));
  });
  $(".theme-panel [name=header-styling]").change(function() {
    var e = $(this).val() === '1' ? "navbar-default" : "navbar-inverse";
    var t = $(this).val() === '1' ? "navbar-inverse" : "navbar-default";
    $("#header").removeClass(t).addClass(e);
    $.cookie("header-styling", e);
  });
  $(".theme-panel [name=sidebar-styling]").change(function() {
    if ($(this).val() === '2') {
      $("#sidebar").addClass("sidebar-grid");
      $.cookie("sidebar-styling", "grid");
    } else {
      $("#sidebar").removeClass("sidebar-grid");
      $.cookie("sidebar-styling", "default");
    }
  });
  $(".theme-panel [name=content-gradient]").change(function() {
    if ($(this).val() === '2') {
      $("#page-container").addClass("gradient-enabled");
      $.cookie("content-gradient", "enabled");
    } else {
      $("#page-container").removeClass("gradient-enabled");
      $.cookie("content-gradient", "disabled");
    }
  });
  $(".theme-panel [name=content-styling]").change(
    function() {
      if ($(this).val() === '2') {
        $("body").addClass("flat-black");
        $.cookie("content-styling", "black");
      } else {
        $("body").removeClass("flat-black");
        $.cookie("content-styling", "default");
    }
  });
  $(".theme-panel [name=sidebar-fixed]").change(function() {
    if ($(this).val() === '1') {
      if ($(".theme-panel [name=header-fixed]").val() === '2') {
         alert("Default Header with Fixed Sidebar option is not supported. Proceed with Fixed Header with Fixed Sidebar.");
         $('.theme-panel [name=header-fixed] option[value="1"]').prop("selected", true);
         $("#header").addClass("navbar-fixed-top");
         $("#page-container").addClass("page-header-fixed");
       }
       $("#page-container").addClass("page-sidebar-fixed");
       if (!$("#page-container").hasClass("page-sidebar-minified")) {
         generateSlimScroll($('.sidebar [data-scrollbar="true"]'));
       }
    } else {
      $("#page-container").removeClass("page-sidebar-fixed");
      if ($(".sidebar .slimScrollDiv").length !== 0) {
        if ($(window).width() <= 979) {
          $(".sidebar").each(function() {
          if (!($("#page-container").hasClass("page-with-two-sidebar") && $(this).hasClass("sidebar-right"))) {
             $(this).find(".slimScrollBar").remove();
             $(this).find(".slimScrollRail").remove();
             $(this).find('[data-scrollbar="true"]').removeAttr("style");
             var e = $(this).find('[data-scrollbar="true"]').parent();
             var t = $(e).html();
             $(e).replaceWith(t);
           }
        });
       } else if ($(window).width() > 979) {
         $('.sidebar [data-scrollbar="true"]').slimScroll({
           destroy: true
         });
         $('.sidebar [data-scrollbar="true"]').removeAttr("style");
       }
    }
    if ($("#page-container .sidebar-bg").length === 0) {
      $("#page-container").append('<div class="sidebar-bg"></div>');
    }
   }
 });

   $(".theme-panel [name=header-fixed]").change(function() {
     if ($(this).val() === '1') {
       $("#header").addClass("navbar-fixed-top");
       $("#page-container").addClass("page-header-fixed");
       $.cookie("header-fixed", true);
     } else {
       if ($(".theme-panel [name=sidebar-fixed]").val() === '1') {
         alert("Default Header with Fixed Sidebar option is not supported. Proceed with Default Header with Default Sidebar.");
         $('.theme-panel [name=sidebar-fixed] option[value="2"]').prop("selected", true);
         $("#page-container").removeClass("page-sidebar-fixed");
         if ($("#page-container .sidebar-bg").length === 0) {
            $("#page-container").append('<div class="sidebar-bg"></div>');
         }
       }
       $("#header").removeClass("navbar-fixed-top");
       $("#page-container").removeClass("page-header-fixed");
       $.cookie("header-fixed", false);
     }
   });
};


var handleLocalStorage = function() {
  "use strict";
  if (typeof Storage !== "undefined") {
    var e = window.location.href;
    e = e.split("?");
    e = e[0];
    var t = localStorage.getItem(e);
    if (t) {
      t = JSON.parse(t);
      var n = 0;
      $(".panel").parent('[class*="col-"]').each(function() {
        var e = t[n];
        var r = $(this);
        $.each(e, function(e, t) {
        var n = '[data-sortable-id="' + t.id + '"]';
        if ($(n).length !== 0) {
          var i = $(n).clone();
          $(n).remove();
          $(r).append(i);
        }
      });
      n++;
     });
    }
  } else {
    alert("Your browser is not supported with the local storage");
  }
};

var handleResetLocalStorage = function() {
	"use strict";
	$("[data-click=reset-local-storage]").click(function(e) {
		e.preventDefault();
    var t = "" + 
										 '<div class="modal fade" data-modal-id="reset-local-storage-confirmation">' + 
										 '    <div class="modal-dialog">' +
										 '        <div class="modal-content">' + 
										 '            <div class="modal-header">' + 
										 '                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>' + 
										 '                <h4 class="modal-title"><i class="fa fa-refresh m-r-5"></i> Reset Local Storage Confirmation</h4>' + 
										 "            </div>" + 
										 '            <div class="modal-body">' + 
										 '                <div class="alert alert-info m-b-0">Would you like to RESET all your saved widgets and clear Local Storage?</div>' + 
										 "            </div>" + 
										 '            <div class="modal-footer">' + 
										 '                <a href="javascript:;" class="btn btn-sm btn-white" data-dismiss="modal"><i class="fa fa-close"></i> No</a>' + 
										 '                <a href="javascript:;" class="btn btn-sm btn-inverse" data-click="confirm-reset-local-storage"><i class="fa fa-check"></i> Yes</a>' + 
										 "            </div>" + 
										 "        </div>" + 
										 "    </div>" + 
										 "</div>";
     $("body").append(t);
     $('[data-modal-id="reset-local-storage-confirmation"]').modal("show");
	});
  $('[data-modal-id="reset-local-storage-confirmation"]').on("hidden.bs.modal",
    function() {
      $('[data-modal-id="reset-local-storage-confirmation"]').remove();
  });
  $("[data-click=confirm-reset-local-storage]").click(function(e) {
    e.preventDefault();
    var t = window.location.href;
    t = t.split("?");
    t = t[0];
    localStorage.removeItem(t);
    window.location.href = document.URL;
	});
};
var handleMobileSidebarToggle = function() {
    var e = false;
    $(".sidebar").on("click touchstart",
    function(t) {
        if ($(t.target).closest(".sidebar").length !== 0) {
            e = true;
        } else {
            e = false;
            t.stopPropagation();
        }
    });
    $(document).on("click touchstart",
    function(t) {
        if ($(t.target).closest(".sidebar").length === 0) {
            e = false;
        }
        if (!t.isPropagationStopped() && e !== true) {
            if ($("#page-container").hasClass("page-sidebar-toggled")) {
                $("#page-container").removeClass("page-sidebar-toggled");
            }
            if ($(window).width() < 979) {
                if ($("#page-container").hasClass("page-with-two-sidebar")) {
                    $("#page-container").removeClass("page-right-sidebar-toggled");
                }
            }
        }
    });
    $("[data-click=right-sidebar-toggled]").click(function(e) {
        e.stopPropagation();
        var t = "#page-container";
        var n = "page-right-sidebar-collapsed";
        n = $(window).width() < 979 ? "page-right-sidebar-toggled" : n;
        if ($(t).hasClass(n)) {
            $(t).removeClass(n);
        } else {
            $(t).addClass(n);
        }
        if ($(window).width() < 480) {
            $("#page-container").removeClass("page-sidebar-toggled");
        }
    });
    $("[data-click=sidebar-toggled]").click(function(e) {
        e.stopPropagation();
        var t = "page-sidebar-toggled";
        var n = "#page-container";
        if ($(n).hasClass(t)) {
            $(n).removeClass(t);
        } else {
            $(n).addClass(t);
        }
        if ($(window).width() < 480) {
            $("#page-container").removeClass("page-right-sidebar-toggled");
        }
    });
};
var handleSavePanelPosition = function () {
  "use strict";
  if ($(".ui-sortable").length !== 0) {
    var t = [];
    var n = 0;
    $.when($(".ui-sortable").each(function() {
      var e = $(this).find("[data-sortable-id]");
      if (e.length !== 0) {
        var r = [];
        $(e).each(function() {
        var e = $(this).attr("data-sortable-id");
        r.push({
          	id: e
        	});
      	});
        t.push(r);
      } else {
        t.push([]);
      }
      n++;
    })).done(function() {
      var n = window.location.href;
      n = n.split("?");
      n = n[0];
      localStorage.setItem(n, JSON.stringify(t));
      $(e).find('[data-id="title-spinner"]').delay(500).fadeOut(500,
      function() {
        $(this).remove();
      });
    });
  }
};
var App = function() {
    "use strict";
    return {
        init: function() {
            handlePageContentView();
						handleSlimScroll();
						handleSidebarMenu();
						handleSidebarMinify();
						handleScrollToTopButton();
						handleThemePanelExpand();
						handleThemePageStructureControl();
						handleResetLocalStorage();
						handleLocalStorage();
						handleMobileSidebarToggle();
						/** 主要内容JS **/
						handleSavePanelPosition();
        }
    };
}();
  });
})(jQuery, window);
