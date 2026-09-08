// Generate wave-like up & down data
const generateWaveData = (count, start = 20, maxChange = 5) => {
  const data = [];
  let value = start;
  for (let i = 0; i < count; i++) {
    // Randomly pick up or down
    const direction = Math.random() > 0.5 ? 1 : -1;
    let change = Math.round(Math.random() * maxChange) * direction;
    value = Math.max(10, value + change); // keep above 10
    data.push(value);
  }
  return data;
};

const waveData = generateWaveData(60, 22, 6);
const days = Array.from({ length: 60 }, (_, i) => "Day " + (i + 1));

var options = {
  chart: {
    type: "area",
    height: 300,
    toolbar: { show: false },
    zoom: { enabled: false },
    dropShadow: {
      enabled: true,
      top: 4,
      left: 0,
      blur: 6,
      opacity: 0.08
    }
  },
  series: [
    {
      name: "Sales",
      data: waveData
    }
  ],
  stroke: {
    curve: "smooth",
    width: 3,
    colors: ["#5387e4"]
  },
  fill: {
    type: "gradient",
    gradient: {
      shadeIntensity: 1,
      opacityFrom: 0.4,
      opacityTo: 0.05,
      stops: [0, 95, 100],
      colorStops: [
        { offset: 0, color: "#5387e4", opacity: 0.4 },
        { offset: 100, color: "#5387e4", opacity: 0.05 }
      ]
    }
  },
  dataLabels: { enabled: false },
  markers: {
    size: 3,
    strokeColors: "#fff",
    strokeWidth: 2,
    hover: { size: 5 }
  },
  tooltip: {
    theme: "light",
    followCursor: true,
    x: {
      formatter: function (val, opts) {
        return days[opts.dataPointIndex];
      }
    },
    y: {
      formatter: (val) => val + " units"
    }
  },
  grid: {
    borderColor: "#e0e0e0",
    strokeDashArray: 4,
    padding: { top: 0, right: 8, bottom: 0, left: 8 }
  },
  xaxis: {
    categories: days,
    labels: {
      formatter: function (val, index) {
        return index % 5 === 0 ? val : "";
      },
      rotate: -45,
      style: { colors: "#888", fontSize: "11px" }
    },
    axisBorder: { show: false },
    axisTicks: { show: false },
    tooltip: { enabled: true }
  },
  yaxis: {
    min: 0,
    max: Math.ceil(Math.max(...waveData) / 10) * 10,
    tickAmount: 5,
    labels: {
      style: { colors: "#888", fontSize: "11px" }
    }
  },
  legend: { show: true, position: "top", horizontalAlign: "right" },
};

var chart = new ApexCharts(document.querySelector("#overallSales"), options);
chart.render();
