document.addEventListener("DOMContentLoaded", function () {
  var calendarEl = document.getElementById("listView");

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

  var calendar = new FullCalendar.Calendar(calendarEl, {
    headerToolbar: {
      left: "prev,next today",
      center: "title",
      right: "listDay,listWeek",
    },
    views: {
      listDay: { buttonText: "List Day" },
      listWeek: { buttonText: "List Week" },
    },
    initialView: "listWeek",
    initialDate: "2025-08-12", // current month
    navLinks: true,
    editable: true,
    dayMaxEvents: true,

    events: [
      { title: "All Day Event", start: "2025-08-01", color: colors.blue },
      { title: "Long Event", start: "2025-08-07", end: "2025-08-10", color: colors.green },
      { groupId: 999, title: "Repeating Event", start: "2025-08-09T16:00:00", color: colors.orange },
      { groupId: 999, title: "Repeating Event", start: "2025-08-16T16:00:00", color: colors.orange },
      { title: "Conference", start: "2025-08-11", end: "2025-08-13", color: colors.purple },
      { title: "Meeting", start: "2025-08-12T10:30:00", end: "2025-08-12T12:30:00", color: colors.red },
      { title: "Lunch", start: "2025-08-12T12:00:00", color: colors.yellow },
      { title: "Meeting", start: "2025-08-12T14:30:00", color: colors.cyan },
      { title: "Happy Hour", start: "2025-08-12T17:30:00", color: colors.red },
      { title: "Dinner", start: "2025-08-12T20:00:00", color: colors.green },
      { title: "Birthday Party", start: "2025-08-13T07:00:00", color: colors.purple },
      { title: "Click for Google", url: "http://google.com/", start: "2025-08-28", color: colors.blue },
    ],
  });

  calendar.render();
});