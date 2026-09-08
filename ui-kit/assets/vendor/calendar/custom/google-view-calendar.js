document.addEventListener('DOMContentLoaded', function () {
  const colors = {
    blue: "#5387e4",
    cyan: "#64c1fe",
    orange: "#ff9446",
    green: "#27ba89",
    yellow: "#fbcc41",
    purple: "#8067dc",
    red: "#ff7d5c"
  };

  const colorList = Object.values(colors);
  let colorIndex = 0;

  const calendarEl = document.getElementById('googleView');

  const calendar = new FullCalendar.Calendar(calendarEl, {
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,listYear'
    },

    height: 500,           // Total calendar height
    contentHeight: 450,    // Event area height
    fixedWeekCount: false, // Removes empty weeks in month view

    displayEventTime: false,

    googleCalendarApiKey: 'AIzaSyDcnW6WejpTOCffshGDDb4neIrXVUA1EAE',

    // Multiple sources: Global holidays + US holidays
    eventSources: [
      {
        googleCalendarId: 'en.holiday@group.v.calendar.google.com', // Global Holidays
        className: 'global-holiday'
      },
      {
        googleCalendarId: 'en.usa#holiday@group.v.calendar.google.com', // US Holidays
        className: 'us-holiday'
      }
    ],

    eventClick: function (arg) {
      window.open(arg.event.url, 'google-calendar-event', 'width=700,height=600');
      arg.jsEvent.preventDefault();
    },

    eventDidMount: function (info) {
      const eventColor = colorList[colorIndex % colorList.length];
      colorIndex++;

      info.el.style.backgroundColor = eventColor;
      info.el.style.borderColor = eventColor;
      info.el.style.color = "#fff";
      info.el.style.borderRadius = "6px";
      info.el.style.padding = "2px 6px";
    }
  });

  calendar.render();
});