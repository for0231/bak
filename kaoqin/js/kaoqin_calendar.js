(function($, Drupal, drupalSettings) {
  "use strict";
  Drupal.behaviors.kaoqin_upon = {
    attach: function (context) {

			    var date = new Date();
			    var d = date.getDate();
			    var m = date.getMonth();
			    var y = date.getFullYear();

			    var hdr = {
			        left: 'title',
			        //center: 'month,agendaWeek,agendaDay',
			        center: '月, 周, 日',
			        right: 'prev,today,next'
			    };

			    var initDrag = function (e) {
			        // create an Event Object (http://arshaw.com/fullcalendar/docs/event_data/Event_Object/)
			        // it doesn't need to have a start or end

			        var eventObject = {
			            title: $.trim(e.children().text()), // use the element's text as the event title
			            description: $.trim(e.children('span').attr('data-description')),
			            icon: $.trim(e.children('span').attr('data-icon')),
			            className: $.trim(e.children('span').attr('class')) // use the element's children as the event class
			        };
			        // store the Event Object in the DOM element so we can get to it later
			        e.data('eventObject', eventObject);

			        // make the event draggable using jQuery UI
			        e.draggable({
			            zIndex: 999,
			            revert: true, // will cause the event to go back to its
			            revertDuration: 0 //  original position after the drag
			        });
			    };

			    var addEvent = function (title, priority, description, icon) {
			        title = title.length === 0 ? "Untitled Event" : title;
			        description = description.length === 0 ? "No Description" : description;
			        icon = icon.length === 0 ? " " : icon;
			        priority = priority.length === 0 ? "label label-default" : priority;

			        var html = $('<li><span class="' + priority + '" data-description="' + description + '" data-icon="' +
			            icon + '">' + title + '</span></li>').prependTo('ul#external-events').hide().fadeIn();

			        $("#event-container").effect("highlight", 800);

			        initDrag(html);
			    };


			    /* initialize the external events
				 -----------------------------------------------------------------*/

			    $('#external-events > li').each(function () {
			        initDrag($(this));
			    });

			    $('#add-event').click(function () {
			        var title = $('#title').val(),
			            priority = $('input:radio[name=priority]:checked').val(),
			            description = $('#description').val(),
			            icon = $('input:radio[name=iconselect]:checked').val();

			        addEvent(title, priority, description, icon);
			    });

			    /* initialize the calendar
				 -----------------------------------------------------------------*/

			    $('#calendar').fullCalendar({

			        header: hdr,
			        editable: true,
              weekMode: 'liquid',
			        droppable: true, // this allows things to be dropped onto the calendar !!!
              dragOpacity: {
                  '': .6
              },
              buttonText: {
                  today: '本月',
                  month: '月',
                  agendaWeek: '周',
                  agendaDay: '日'
              },
              monthNames: ["1月", "2月", "3月", "4月", "5月", "6月", "7月", "8月", "9月", "10月", "11月", "12月"],
              monthNamesShort: ["1", "2", "3", "4", "5", "6", "7", "8", "9", "10", "11", "12"],
              dayNames: ["星期日", "星期一", "星期二", "星期三", "星期四", "星期五", "星期六"],
              dayNamesShort: ["周日", "周一", "周二", "周三", "周四", "周五", "周六"],
              firstDay: 1,

              allDayText: '全天',



			        drop: function (date, allDay) { // this function is called when something is dropped
			            // retrieve the dropped element's stored Event Object
			            var originalEventObject = $(this).data('eventObject');

			            // we need to copy it, so that multiple events don't have a reference to the same object
			            var copiedEventObject = $.extend({}, originalEventObject);

			            // assign it the date that was reported
			            copiedEventObject.start = date;
			            copiedEventObject.allDay = allDay;

                  var parameters = {};
                  parameters['className'] = copiedEventObject.className;
                  parameters['description'] = copiedEventObject.description;
                  parameters['icon'] = copiedEventObject.icon;
                  parameters['title'] = copiedEventObject.title;
                  parameters['event_start'] = copiedEventObject.start._i;



			            // render the event on the calendar
			            // the last `true` argument determines if the event "sticks" (http://arshaw.com/fullcalendar/docs/event_rendering/renderEvent/)

                  $.ajax({
                    type: "POST",
                    url: Drupal.url('ajax/kaoqin/upon/create'),
                    data: parameters,
                    success: function(data) {
                      console.log(data);
                      if (data > 0) {
      			            $('#calendar').fullCalendar('renderEvent', copiedEventObject, true);
                      } else {
                        alert('添加失败!');
                      }
                    }
                  });

                  // is the "remove after drop" checkbox checked?
                  if ($('#drop-remove').is(':checked')) {
                      // if so, remove the element from the "Draggable Events" list
                      $(this).remove();
                  }

			        },

			        select: function (start, end, allDay) {
			            var title = prompt('Event Title:');
			            if (title) {
			                calendar.fullCalendar('renderEvent', {
			                        title: title,
			                        start: start,
			                        end: end,
			                        allDay: allDay
			                    }, true // make the event "stick"
			                );
			            }
			            calendar.fullCalendar('unselect');
			        },
              events: {
                  url: Drupal.url('ajax/kaoqin/upon/list'),
                  error: function() {
                      alert('there was an error while fetching events!');
                  },
              },

			        eventRender: function (event, element, icon) {
			            if (!event.description == "") {
			                element.find('.fc-title').append("<br/><span class='ultra-light'>" + event.description +
			                    "</span>");
			            }
			            if (!event.icon == "") {
			                element.find('.fc-title').append("<i class='air air-top-right fa " + event.icon +
			                    " '></i>");
			            }
			        },
              // 点击时触发
              eventClick: function(event) {
                console.log(event);
              },

              // 移动时触发
              eventDrop: function(event) {
                var parameters = {};
                parameters['_id'] = event._id;
                parameters['event_start'] = event.start._i;
                if (event.end) {
                  parameters['event_end'] = event.end._i;
                }
                $.ajax({
                  type: "POST",
                  url: Drupal.url('ajax/kaoqin/upon/update'),
                  data: parameters,
                  success: function(data) {
                    if (data > 0) {
                      //$('#calendar').fullCalendar('renderEvent', event, true);
                    } else {
                      alert('失败！');
                    }
                  }
                });
                $('#calendar').fullCalendar('updateEvent', event);
              },

              // 拉伸时触发
              eventResize: function(event) {
                var parameters = {};
                parameters['_id'] = event._id;
                parameters['event_start'] = event.start._i;
                if (event.end) {
                  parameters['event_end'] = event.end._i;
                }
                $.ajax({
                  type: "POST",
                  url: Drupal.url('ajax/kaoqin/upon/update'),
                  data: parameters,
                  success: function(data) {
                    if (data > 0) {
                      //$('#calendar').fullCalendar('renderEvent', event, true);
                    } else {
                      alert('失败！');
                    }
                  }
                });
                //$('#calendar').fullCalendar('updateEvent', event);
              },


			        windowResize: function (event, ui) {
			          $('#calendar').fullCalendar('render');
                $('#calendar').fullCalendar('updateEvent', event);
			        }

			    });

			  /* hide default buttons */
			  $('.fc-right, .fc-center').hide();


				$('#calendar-buttons #btn-prev').click(function () {
				    $('.fc-prev-button').click();
				    return false;
				});

				$('#calendar-buttons #btn-next').click(function () {
				    $('.fc-next-button').click();
				    return false;
				});

				$('#calendar-buttons #btn-today').click(function () {
				    $('.fc-today-button').click();
				    return false;
				});

				$('#mt').click(function () {
				    $('#calendar').fullCalendar('changeView', 'month');
				});

				$('#ag').click(function () {
				    $('#calendar').fullCalendar('changeView', 'agendaWeek');
				});

				$('#td').click(function () {
				    $('#calendar').fullCalendar('changeView', 'agendaDay');
				});


    }
  }
})(jQuery, Drupal, drupalSettings);

