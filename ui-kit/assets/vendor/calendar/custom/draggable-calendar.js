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

// Helper: get date string for current month
function dateStr(day, time) {
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, "0");
  const dayStr = String(day).padStart(2, "0");
  return `${year}-${month}-${dayStr}${time ? "T" + time : ""}`;
}

document.addEventListener("DOMContentLoaded", function () {
  /* initialize external events */
  var containerEl = document.getElementById("external-events-list");

  new FullCalendar.Draggable(containerEl, {
    itemSelector: ".fc-event",
    eventData: function (eventEl) {
      // Extract the fc-event-* color class
      const classList = Array.from(eventEl.classList);
      const colorClass = classList.find(cls => cls.startsWith("fc-event-"));

      let eventColor = null;
      if (colorClass) {
        const colorKey = colorClass.replace("fc-event-", ""); // e.g., green
        eventColor = colors[colorKey] || null;
      }

      return {
        title: eventEl.innerText.trim(),
        color: eventColor
      };
    }
  });

  /* initialize calendar */
  var calendarEl = document.getElementById("draggableCalendar");
  var calendar = new FullCalendar.Calendar(calendarEl, {
    headerToolbar: {
      left: "prev,next today",
      center: "title",
      right: "dayGridMonth,timeGridWeek,timeGridDay,listWeek",
    },
    initialDate: new Date(),
    editable: true,
    droppable: true,
    drop: function (arg) {
      if (document.getElementById("drop-remove").checked) {
        arg.draggedEl.parentNode.removeChild(arg.draggedEl);
      }
    },
    events: [
      { title: "Quarterly Strategy Meeting", start: dateStr(4), color: colors.red },
      { title: "Advanced Product Workshop", start: dateStr(7), end: dateStr(8), color: colors.blue },
      { title: "CEO Birthday Bash", start: dateStr(9), color: colors.green },
      { title: "Senior Developer Interview", start: dateStr(12), color: colors.green },
      { title: "Flight to Tokyo – Client Visit", start: dateStr(14, "10:30:00"), color: colors.purple },
      { title: "UX Design Panel Discussion", start: dateStr(21, "17:30:00"), color: colors.red },
      { title: "Team Anniversary Celebration", start: dateStr(25, "19:00:00"), color: colors.green },
      { title: "Extended Annual Leave", start: dateStr(28), end: dateStr(30), color: colors.blue },
      { title: "Tech Conference – Singapore", start: dateStr(15), end: dateStr(20), color: colors.orange },
      { title: "Innovation Hackathon", start: dateStr(5), end: dateStr(9), color: colors.cyan },
      { title: "Outdoor Team Retreat", start: dateStr(23), end: dateStr(24), color: colors.cyan },
      { title: "Board of Directors Meeting", start: dateStr(3), color: colors.yellow },
      { title: "Client Contract Signing", start: dateStr(17, "11:00:00"), color: colors.purple },
      { title: "Marketing Campaign Launch", start: dateStr(19), color: colors.orange },
      { title: "End-of-Month Review", start: dateStr(30, "15:00:00"), color: colors.blue }
    ],
  });

  calendar.render();
});