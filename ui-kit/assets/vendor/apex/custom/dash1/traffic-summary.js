var options = {
  chart: {
    height: 360,
    type: "line",
    toolbar: { show: false },
    animations: {
      enabled: true,
      easing: "easeinout",
      speed: 900,
      animateGradually: { enabled: true, delay: 200 },
      dynamicAnimation: { enabled: true, speed: 400 },
    },
  },
  stroke: {
    width: [0, 0, 3],
    curve: "smooth",
  },
  plotOptions: {
    bar: {
      columnWidth: "45%",
      borderRadius: 6,
      endingShape: "rounded",
    },
  },
  dataLabels: { enabled: false },
  series: [
    {
      name: "Web",
      type: "column",
      data: [42, 55, 48, 62, 80, 95, 110, 124, 118, 130, 102, 140],
    },
    {
      name: "Social",
      type: "column",
      data: [25, 32, 28, 40, 54, 61, 70, 66, 72, 80, 76, 90],
    },
    {
      name: "Others",
      type: "line",
      data: [15, 20, 22, 18, 24, 28, 34, 30, 26, 38, 35, 42],
    },
  ],
  colors: [
    "#4f8ef7", // Web
    "#93c5fd", // Social
    "#f97316", // Line (Others)
  ],
  fill: {
    type: ["gradient", "gradient", "solid"],
    gradient: {
      shade: "light",
      type: "vertical",
      shadeIntensity: 0.2,
      gradientToColors: ["#3b82f6", "#60a5fa"],
      inverseColors: false,
      opacityFrom: 0.9,
      opacityTo: 0.6,
      stops: [0, 100],
    },
  },
  markers: {
    size: 5,
    strokeWidth: 2,
    strokeColors: "#fff",
    hover: { size: 8 },
  },
  tooltip: {
    theme: "light",
    shared: true,
    intersect: false,
    y: {
      formatter: function (val) {
        return val.toLocaleString() + "M";
      },
    },
  },
  legend: {
    show: true,
    position: "bottom",
    horizontalAlign: "center",
    fontSize: "13px",
    fontWeight: 500,
    labels: { colors: "#6b7280" },
    itemMargin: { horizontal: 12, vertical: 6 },
    markers: {
      width: 16,
      height: 6,
      radius: 4,
    },
  },
  xaxis: {
    categories: [
      "Jan", "Feb", "Mar", "Apr", "May", "Jun",
      "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"
    ],
    labels: {
      rotate: -45,
      style: { fontSize: "12px", colors: "#6b7280" },
    },
    axisBorder: { show: false },
    axisTicks: { show: false },
  },
  yaxis: {
    labels: {
      offsetX: -10,
      style: { colors: "#9ca3af", fontSize: "12px" },
      formatter: function (val) {
        return val + "M";
      },
    },
  },
  grid: {
    borderColor: "#e5e7eb",
    strokeDashArray: 5,
    padding: { top: 15, bottom: 10, left: 12, right: 12 },
    xaxis: { lines: { show: false } },
    yaxis: { lines: { show: true } },
  },
};

var chart = new ApexCharts(document.querySelector("#trafficSummary"), options);
chart.render();