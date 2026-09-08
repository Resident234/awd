// Theme colors
const colors = {
  blue: "#5387e4",
  cyan: "#64c1fe",
  orange: "#ff9446",
  green: "#27ba89",
  yellow: "#fbcc41",
  purple: "#8067dc",
  red: "#ff7d5c"
};

document.addEventListener("DOMContentLoaded", function () {
  const calendarEl = document.getElementById("selectableCalendar");

  // Get current year & month
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, "0");

  let colorValues = Object.values(colors);
  let colorIndex = 0;

  // Helper to cycle through colors
  const getNextColor = () => {
    const color = colorValues[colorIndex % colorValues.length];
    colorIndex++;
    return color;
  };



  // Expanded event list for nearly every day
  const baseEvents = [
    { title: "Morning Standup", day: 1, time: "09:00:00" },
    { title: "Project Kickoff", day: 1, time: "14:00:00" },

    { title: "Team Meeting", day: 2, time: "11:00:00" },

    { title: "Workshop", day: 3, time: "10:00:00" },
    { title: "Client Call", day: 3, time: "15:00:00" },

    { title: "All Day Event", day: 4 },

    { title: "Long Event", day: 5, endDay: 7 },

    { title: "Code Review", day: 6, time: "14:00:00" },
    { title: "Design Review", day: 6, time: "16:00:00" },

    { title: "Birthday", day: 7, time: "16:00:00" },

    { title: "Conference", day: 8, endDay: 10 },

    { title: "Lunch", day: 9, time: "12:00:00" },
    { title: "Weekly Planning", day: 9, time: "15:30:00" },

    { title: "Workshop", day: 10, time: "10:30:00" },

    { title: "Meeting", day: 11, time: "09:30:00" },
    { title: "Presentation", day: 11, time: "14:00:00" },

    { title: "Interview", day: 12, time: "11:00:00" },
    { title: "Product Launch", day: 12, time: "17:00:00" },

    { title: "Hackathon", day: 13 },

    { title: "Birthday", day: 14, time: "07:00:00" },

    { title: "All Hands", day: 15, time: "16:00:00" },
    { title: "Training", day: 15, time: "18:00:00" },

    { title: "Team Outing", day: 16 },

    { title: "Strategy Meeting", day: 17, time: "09:30:00" },
    { title: "Lunch", day: 17, time: "12:00:00" },
    { title: "1:1 Meeting", day: 17, time: "15:30:00" },

    { title: "Release Prep", day: 18, time: "11:00:00" },

    { title: "Demo Day", day: 19, time: "10:00:00" },
    { title: "Retrospective", day: 19, time: "16:00:00" },

    { title: "Code Merge", day: 20, time: "13:00:00" },

    { title: "Conference", day: 21, endDay: 22 },

    { title: "Client Visit", day: 23, time: "09:00:00" },
    { title: "Evening Meetup", day: 23, time: "18:00:00" },

    { title: "Hack Session", day: 24, time: "10:00:00" },

    { title: "Board Meeting", day: 25, time: "11:00:00" },

    { title: "Town Hall", day: 26, time: "15:00:00" },

    { title: "Offsite", day: 27 },

    { title: "Click for Google", day: 28, url: "http://google.com/" },
    { title: "Networking Event", day: 28, time: "18:30:00" },

    { title: "Final Testing", day: 29, time: "14:00:00" },

    { title: "Wrap-up", day: 30, time: "16:00:00" },

    { title: "Planning for Next Month", day: 31, time: "10:00:00" }
  ];

  // Map into FullCalendar format with dynamic colors
  const events = baseEvents.map(evt => ({
    title: evt.title,
    start: `${year}-${month}-${String(evt.day).padStart(2, "0")}${evt.time ? "T" + evt.time : ""}`,
    ...(evt.endDay && { end: `${year}-${month}-${String(evt.endDay).padStart(2, "0")}` }),
    ...(evt.url && { url: evt.url }),
    color: getNextColor()
  }));

  const calendar = new FullCalendar.Calendar(calendarEl, {
    headerToolbar: {
      left: "prev,next today",
      center: "title",
      right: "dayGridMonth,timeGridWeek,timeGridDay",
    },

    height: 500,           // Total calendar height
    contentHeight: 450,    // Event area height
    fixedWeekCount: false, // Removes empty weeks in month view

    initialDate: now,
    navLinks: true,
    selectable: true,
    selectMirror: true,
    select: function (arg) {
      const title = prompt("Event Title:");
      if (title) {
        calendar.addEvent({
          title: title,
          start: arg.start,
          end: arg.end,
          allDay: arg.allDay,
          color: getNextColor()
        });
      }
      calendar.unselect();
    },
    eventClick: function (arg) {
      if (confirm("Are you sure you want to delete this event?")) {
        arg.event.remove();
      }
    },
    editable: true,
    dayMaxEvents: true,
    events: events
  });

  calendar.render();
});
