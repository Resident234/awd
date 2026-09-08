document.addEventListener("DOMContentLoaded", function () {
  const calendarEl = document.getElementById("dayGrid");

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

  // Get current date
  const currentDate = new Date();

  const calendar = new FullCalendar.Calendar(calendarEl, {
    headerToolbar: {
      left: "prev,next today",
      center: "title",
      right: "dayGridMonth,dayGridWeek,dayGridDay"
    },
    initialDate: currentDate,
    initialView: 'dayGridMonth',
    navLinks: true,
    editable: true,
    dayMaxEvents: true,
    height: 'auto',
    themeSystem: 'bootstrap5',
    buttonText: {
      today: 'Today',
      month: 'Month',
      week: 'Week',
      day: 'Day'
    },
    events: [
      { title: "All Day Event", start: new Date(currentDate.getFullYear(), currentDate.getMonth(), 1), backgroundColor: colors.orange, borderColor: colors.orange, textColor: "#fff" },
      { title: "Long Event", start: new Date(currentDate.getFullYear(), currentDate.getMonth(), 7), end: new Date(currentDate.getFullYear(), currentDate.getMonth(), 10), backgroundColor: colors.blue, borderColor: colors.blue, textColor: "#fff" },
      { groupId: 999, title: "Birthday", start: new Date(currentDate.getFullYear(), currentDate.getMonth(), 9, 16, 0), backgroundColor: colors.cyan, borderColor: colors.cyan, textColor: "#fff" },
      { groupId: 999, title: "Birthday", start: new Date(currentDate.getFullYear(), currentDate.getMonth(), 16, 16, 0), backgroundColor: colors.yellow, borderColor: colors.yellow, textColor: "#000" },
      { title: "Conference", start: new Date(currentDate.getFullYear(), currentDate.getMonth(), 11), end: new Date(currentDate.getFullYear(), currentDate.getMonth(), 13), backgroundColor: colors.orange, borderColor: colors.orange, textColor: "#fff" },
      { title: "Meeting", start: new Date(currentDate.getFullYear(), currentDate.getMonth(), 14, 10, 30), end: new Date(currentDate.getFullYear(), currentDate.getMonth(), 14, 12, 30), backgroundColor: colors.purple, borderColor: colors.purple, textColor: "#fff" },
      { title: "Lunch", start: new Date(currentDate.getFullYear(), currentDate.getMonth(), 16, 12, 0), backgroundColor: colors.green, borderColor: colors.green, textColor: "#fff" },
      { title: "Meeting", start: new Date(currentDate.getFullYear(), currentDate.getMonth(), 18, 14, 30), backgroundColor: colors.cyan, borderColor: colors.cyan, textColor: "#fff" },
      { title: "Interview", start: new Date(currentDate.getFullYear(), currentDate.getMonth(), 21, 17, 30), backgroundColor: colors.yellow, borderColor: colors.yellow, textColor: "#000" },
      { title: "Meeting", start: new Date(currentDate.getFullYear(), currentDate.getMonth(), 22, 20, 0), backgroundColor: colors.blue, borderColor: colors.blue, textColor: "#fff" },
      { title: "Birthday", start: new Date(currentDate.getFullYear(), currentDate.getMonth(), 13, 7, 0), backgroundColor: colors.green, borderColor: colors.green, textColor: "#fff" },
      { title: "Click for Google", url: "http://google.com/", start: new Date(currentDate.getFullYear(), currentDate.getMonth(), 28), backgroundColor: colors.cyan, borderColor: colors.cyan, textColor: "#fff" },
      { title: "Interview", start: new Date(currentDate.getFullYear(), currentDate.getMonth(), 20), backgroundColor: colors.green, borderColor: colors.green, textColor: "#fff" },
      { title: "Product Launch", start: new Date(currentDate.getFullYear(), currentDate.getMonth(), 29), backgroundColor: colors.green, borderColor: colors.green, textColor: "#fff" },
      { title: "Leave", start: new Date(currentDate.getFullYear(), currentDate.getMonth(), 25), backgroundColor: colors.yellow, borderColor: colors.yellow, textColor: "#000" }
    ],
    eventTimeFormat: {
      hour: '2-digit',
      minute: '2-digit',
      meridiem: false
    },
  });

  calendar.render();
});
