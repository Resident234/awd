function createRadialChart(selector, value, color) {
  new ApexCharts(document.querySelector(selector), {
    chart: {
      type: "radialBar",
      sparkline: { enabled: true },
      height: 80,
      width: 80,
    },
    series: [value],
    plotOptions: {
      radialBar: {
        hollow: { size: "30%" },
        track: { background: "#e6eaf1" },
        dataLabels: {
          show: true,
          name: { show: false }, // hide "series name"
          value: {
            show: true,
            fontSize: "13px",
            fontWeight: 600,
            color: color,
            offsetY: 4,
            formatter: function (val) {
              return val + "%"; // add % sign
            },
          },
        },
      },
    },
    colors: [color],
    fill: {
      type: "gradient",
      gradient: {
        shade: "light",
        type: "vertical",
        gradientToColors: [color],
        stops: [0, 100],
      },
    },
  }).render();
}

function createLineSpark(selector, data, color) {
  new ApexCharts(document.querySelector(selector), {
    chart: {
      type: "line",
      sparkline: { enabled: true },
      height: 40,
    },
    stroke: {
      curve: "smooth",
      width: 3,
    },
    colors: [color],
    series: [
      {
        name: "Sales",
        data: data,
      },
    ],
    tooltip: { enabled: false },
  }).render();
}

// Q1
createRadialChart("#q1Radial", 76, "#5387e4");
createLineSpark("#q1LineSpark", [22, 26, 20, 29, 33, 43, 38, 31, 42, 35, 40, 43], "#5387e4");

// Q2
createRadialChart("#q2Radial", 82, "#27ba89");
createLineSpark("#q2LineSpark", [30, 35, 28, 33, 40, 45, 42, 50, 55, 60, 57, 62], "#27ba89");

// Q3
createRadialChart("#q3Radial", 68, "#fbcc41");
createLineSpark("#q3LineSpark", [18, 22, 20, 25, 28, 35, 32, 36, 38, 40, 37, 39], "#fbcc41");

// Q4
createRadialChart("#q4Radial", 90, "#ff7d5c");
createLineSpark("#q4LineSpark", [40, 45, 42, 50, 48, 55, 60, 58, 62, 65, 64, 68], "#ff7d5c");
