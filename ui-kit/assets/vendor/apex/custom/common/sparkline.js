// Graph 1 - Orders
var options1 = {
  series: [80],
  chart: {
    type: "radialBar",
    width: 64,
    height: 64,
    sparkline: { enabled: true },
  },
  colors: ["#27ba89"],
  plotOptions: {
    radialBar: {
      hollow: {
        margin: 0,
        size: "50%",
      },
      track: {
        margin: 0,
        background: "#dbe1ec",
      },
      dataLabels: {
        show: true,
        name: { show: false },
        value: {
          fontSize: "11px",
          fontWeight: 600,
          offsetY: 4,
          formatter: function (val) {
            return val;
          },
        },
      },
    },
  },
  fill: {
    type: "gradient",
    gradient: {
      shade: "light",
      type: "vertical",
      gradientToColors: ["#34d399"],
      stops: [0, 100],
    },
  },
};
new ApexCharts(document.querySelector("#orders"), options1).render();

// Graph 2 - Sales
var options2 = {
  series: [70],
  chart: {
    type: "radialBar",
    width: 64,
    height: 64,
    sparkline: { enabled: true },
  },
  colors: ["#ff7d5c"],
  plotOptions: {
    radialBar: {
      hollow: {
        margin: 0,
        size: "50%",
      },
      track: {
        margin: 0,
        background: "#dbe1ec",
      },
      dataLabels: {
        show: true,
        name: { show: false },
        value: {
          fontSize: "11px",
          fontWeight: 600,
          offsetY: 4,
          formatter: function (val) {
            return val;
          },
        },
      },
    },
  },
  fill: {
    type: "gradient",
    gradient: {
      shade: "light",
      type: "vertical",
      gradientToColors: ["#fb923c"],
      stops: [0, 100],
    },
  },
};
new ApexCharts(document.querySelector("#sales"), options2).render();