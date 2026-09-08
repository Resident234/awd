// Basic Line Chart
var options = {
  chart: {
    height: 300,
    type: 'line',
    zoom: {
      enabled: false
    },
    toolbar: {
      show: false
    },
    fontFamily: 'Inter, sans-serif',
    animations: {
      enabled: true,
      easing: 'easeinout',
      speed: 800
    }
  },
  series: [{
    name: "Data Series",
    data: [10, 41, 35, 51, 49, 62, 69, 91, 148]
  }],
  dataLabels: {
    enabled: false
  },
  stroke: {
    curve: 'smooth', // changed from straight to smooth
    width: 3,
  },
  grid: {
    borderColor: '#e0e6ed',
    strokeDashArray: 5,
    xaxis: {
      lines: {
        show: true
      }
    },
    yaxis: {
      lines: {
        show: true,
      }
    },
    padding: {
      top: 10,
      right: 10,
      bottom: 10,
      left: 10
    },
  },
  xaxis: {
    categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep'],
    labels: {
      style: {
        fontFamily: 'Inter, sans-serif',
        colors: '#555'
      },
    }
  },
  yaxis: {
    labels: {
      style: {
        fontFamily: 'Inter, sans-serif',
        colors: '#555'
      },
      formatter: function (value) {
        return value.toFixed(0); // Avoid decimal places
      }
    }
  },
  colors: ['#158156'],
  markers: {
    size: 4,
    strokeWidth: 0,
    hover: {
      size: 7
    }
  },
  tooltip: {
    theme: 'dark',
    marker: {
      show: true,
    },
    x: {
      show: true,
    },
    y: {
      formatter: function (value) {
        return value.toFixed(0);
      }
    }
  },
  responsive: [{
    breakpoint: 576,
    options: {
      chart: {
        height: 250
      },
      markers: {
        size: 3
      }
    }
  }]
};

var chart = new ApexCharts(
  document.querySelector("#basicLineChart"),
  options
);

chart.render();

// Line Chart With Labels
var optionsLineWithLabels = {
  chart: {
    height: 300,
    type: 'line',
    zoom: {
      enabled: false
    },
    toolbar: {
      show: false
    },
    fontFamily: 'Inter, sans-serif',
    animations: {
      enabled: true,
      easing: 'easeinout',
      speed: 800
    }
  },
  series: [{
    name: "High",
    data: [45, 52, 38, 24, 33, 26, 21, 20, 6, 8, 15, 10]
  },
  {
    name: "Low",
    data: [35, 41, 62, 42, 13, 18, 29, 37, 36, 51, 32, 35]
  }],
  dataLabels: {
    enabled: true,
  },
  stroke: {
    curve: 'straight',
    width: 2,
  },
  grid: {
    borderColor: '#e0e6ed',
    strokeDashArray: 5,
    xaxis: {
      lines: {
        show: true
      }
    },
    yaxis: {
      lines: {
        show: true,
      }
    },
    padding: {
      top: 10,
      right: 10,
      bottom: 10,
      left: 10
    },
  },
  xaxis: {
    categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
    labels: {
      style: {
        fontFamily: 'Inter, sans-serif',
        colors: '#555'
      },
    }
  },
  yaxis: {
    labels: {
      style: {
        fontFamily: 'Inter, sans-serif',
        colors: '#555'
      },
    }
  },
  colors: ['#158156', '#2b9934'],
  markers: {
    size: 4,
    strokeWidth: 0,
    hover: {
      size: 7
    }
  },
  tooltip: {
    theme: 'dark',
    marker: {
      show: true,
    },
    x: {
      show: true,
    }
  },
  legend: {
    position: 'top',
    horizontalAlign: 'right',
    fontSize: '14px',
    fontFamily: 'Inter, sans-serif',
    markers: {
      width: 10,
      height: 10,
    },
  },
  responsive: [{
    breakpoint: 576,
    options: {
      chart: {
        height: 250
      },
      markers: {
        size: 3
      }
    }
  }]
};

var chartLineWithLabels = new ApexCharts(
  document.querySelector("#lineChartWithLabels"),
  optionsLineWithLabels
);

chartLineWithLabels.render();

// Gradient Line Chart
var optionsGradientLine = {
  chart: {
    height: 300,
    type: 'line',
    zoom: {
      enabled: false
    },
    toolbar: {
      show: false
    },
    fontFamily: 'Inter, sans-serif',
    animations: {
      enabled: true,
      easing: 'easeinout',
      speed: 800
    },
    dropShadow: {
      enabled: true,
      top: 3,
      left: 2,
      blur: 4,
      opacity: 0.2
    }
  },
  series: [{
    name: "Performance",
    data: [28, 45, 35, 60, 42, 75, 65, 90, 82]
  }],
  dataLabels: {
    enabled: false
  },
  stroke: {
    curve: 'smooth',
    width: 4,
  },
  grid: {
    borderColor: '#e0e6ed',
    strokeDashArray: 5,
    xaxis: {
      lines: {
        show: true
      }
    },
    yaxis: {
      lines: {
        show: true,
      }
    },
    padding: {
      top: 10,
      right: 10,
      bottom: 10,
      left: 10
    },
  },
  xaxis: {
    categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep'],
    labels: {
      style: {
        fontFamily: 'Inter, sans-serif',
        colors: '#555'
      },
    }
  },
  yaxis: {
    labels: {
      style: {
        fontFamily: 'Inter, sans-serif',
        colors: '#555'
      },
      formatter: function (value) {
        return value.toFixed(0);
      }
    }
  },
  colors: ['#158156'],
  fill: {
    type: 'gradient',
    gradient: {
      shade: 'light',
      gradientToColors: ['#5bb51c'],
      shadeIntensity: 1,
      type: 'vertical',
      opacityFrom: 0.9,
      opacityTo: 0.7,
      stops: [0, 90, 100]
    },
  },
  markers: {
    size: 5,
    strokeWidth: 0,
    colors: ['#158156', '#5bb51c'],
    strokeColors: '#ffffff',
    hover: {
      size: 8
    }
  },
  tooltip: {
    theme: 'dark',
    marker: {
      show: true,
    },
    x: {
      show: true,
    },
    y: {
      formatter: function (value) {
        return value.toFixed(0);
      }
    }
  },
  responsive: [{
    breakpoint: 576,
    options: {
      chart: {
        height: 250
      },
      markers: {
        size: 3
      }
    }
  }]
};

var gradientLineChart = new ApexCharts(
  document.querySelector("#gradientLineChart"),
  optionsGradientLine
);

gradientLineChart.render();

// Multi Line Chart with Different Colors
var optionsMultiLine = {
  chart: {
    height: 300,
    type: 'line',
    zoom: {
      enabled: false
    },
    toolbar: {
      show: false
    },
    fontFamily: 'Inter, sans-serif',
    animations: {
      enabled: true,
      easing: 'easeinout',
      speed: 800
    }
  },
  series: [
    {
      name: "Desktop",
      data: [45, 52, 38, 45, 19, 23, 40, 44, 28]
    },
    {
      name: "Mobile",
      data: [29, 37, 45, 36, 28, 32, 27, 38, 30]
    },
    {
      name: "Tablet",
      data: [15, 23, 21, 19, 32, 25, 18, 24, 21]
    }
  ],
  dataLabels: {
    enabled: false
  },
  stroke: {
    width: 3,
    curve: 'smooth',
    dashArray: [0, 5, 8]
  },
  grid: {
    borderColor: '#e0e6ed',
    strokeDashArray: 5,
    xaxis: {
      lines: {
        show: true
      }
    },
    yaxis: {
      lines: {
        show: true,
      }
    },
    padding: {
      top: 10,
      right: 10,
      bottom: 10,
      left: 10
    },
  },
  xaxis: {
    categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep'],
    labels: {
      style: {
        fontFamily: 'Inter, sans-serif',
        colors: '#555'
      },
    }
  },
  yaxis: {
    labels: {
      style: {
        fontFamily: 'Inter, sans-serif',
        colors: '#555'
      },
      formatter: function (value) {
        return value.toFixed(0);
      }
    }
  },
  colors: ['#158156', '#5bb51c', '#ffca3a'],
  markers: {
    size: 4,
    strokeWidth: 0,
    hover: {
      size: 7
    }
  },
  tooltip: {
    theme: 'dark',
    marker: {
      show: true,
    },
    x: {
      show: true,
    }
  },
  legend: {
    position: 'top',
    horizontalAlign: 'right',
    fontSize: '14px',
    fontFamily: 'Inter, sans-serif',
    markers: {
      width: 10,
      height: 10,
    },
  },
  responsive: [{
    breakpoint: 576,
    options: {
      chart: {
        height: 250
      },
      markers: {
        size: 3
      }
    }
  }]
};

var multiLineChart = new ApexCharts(
  document.querySelector("#multiLineChart"),
  optionsMultiLine
);

multiLineChart.render();

// Dashed Line Chart
var optionsDashedLine = {
  chart: {
    height: 300,
    type: 'line',
    zoom: {
      enabled: false
    },
    toolbar: {
      show: false
    },
    fontFamily: 'Inter, sans-serif',
    animations: {
      enabled: true,
      easing: 'easeinout',
      speed: 800
    }
  },
  series: [{
    name: "Session Duration",
    data: [45, 52, 38, 24, 33, 26, 21, 20, 6, 8, 15, 10]
  },
  {
    name: "Page Views",
    data: [35, 41, 62, 42, 13, 18, 29, 37, 36, 51, 32, 35]
  }],
  dataLabels: {
    enabled: false
  },
  stroke: {
    width: [3, 3],
    curve: 'straight',
    dashArray: [0, 8]
  },
  grid: {
    borderColor: '#e0e6ed',
    strokeDashArray: 5,
    xaxis: {
      lines: {
        show: true
      }
    },
    yaxis: {
      lines: {
        show: true,
      }
    },
    padding: {
      top: 10,
      right: 10,
      bottom: 10,
      left: 10
    },
  },
  xaxis: {
    categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
    labels: {
      style: {
        fontFamily: 'Inter, sans-serif',
        colors: '#555'
      },
    }
  },
  yaxis: {
    labels: {
      style: {
        fontFamily: 'Inter, sans-serif',
        colors: '#555'
      },
      formatter: function (value) {
        return value.toFixed(0);
      }
    }
  },
  colors: ['#158156', '#5bb51c'],
  markers: {
    size: 4,
    strokeWidth: 0,
    hover: {
      size: 7
    }
  },
  tooltip: {
    theme: 'dark',
    marker: {
      show: true,
    },
    x: {
      show: true,
    }
  },
  legend: {
    position: 'top',
    horizontalAlign: 'right',
    fontSize: '14px',
    fontFamily: 'Inter, sans-serif',
    markers: {
      width: 10,
      height: 10,
    },
  },
  responsive: [{
    breakpoint: 576,
    options: {
      chart: {
        height: 250
      },
      markers: {
        size: 3
      }
    }
  }]
};

var dashedLineChart = new ApexCharts(
  document.querySelector("#dashedLineChart"),
  optionsDashedLine
);

dashedLineChart.render();

// Step Line Chart
var optionsStepLine = {
  chart: {
    height: 300,
    type: 'line',
    zoom: {
      enabled: false
    },
    toolbar: {
      show: false
    },
    fontFamily: 'Inter, sans-serif',
    animations: {
      enabled: true,
      easing: 'easeinout',
      speed: 800
    }
  },
  series: [{
    name: "Step Series",
    data: [34, 44, 54, 21, 12, 43, 33, 23, 66, 66, 58]
  }],
  dataLabels: {
    enabled: false
  },
  stroke: {
    curve: 'stepline',
    width: 3,
  },
  grid: {
    borderColor: '#e0e6ed',
    strokeDashArray: 5,
    xaxis: {
      lines: {
        show: true
      }
    },
    yaxis: {
      lines: {
        show: true,
      }
    },
    padding: {
      top: 10,
      right: 10,
      bottom: 10,
      left: 10
    },
  },
  xaxis: {
    categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov'],
    labels: {
      style: {
        fontFamily: 'Inter, sans-serif',
        colors: '#555'
      },
    }
  },
  yaxis: {
    labels: {
      style: {
        fontFamily: 'Inter, sans-serif',
        colors: '#555'
      },
      formatter: function (value) {
        return value.toFixed(0);
      }
    }
  },
  colors: ['#5bb51c'],
  markers: {
    size: 4,
    strokeWidth: 0,
    hover: {
      size: 7
    }
  },
  tooltip: {
    theme: 'dark',
    marker: {
      show: true,
    },
    x: {
      show: true,
    }
  },
  responsive: [{
    breakpoint: 576,
    options: {
      chart: {
        height: 250
      },
      markers: {
        size: 3
      }
    }
  }]
};

var stepLineChart = new ApexCharts(
  document.querySelector("#stepLineChart"),
  optionsStepLine
);

stepLineChart.render();

// Zoomable Line Chart
var optionsZoomableLine = {
  chart: {
    height: 300,
    type: 'line',
    zoom: {
      enabled: true,
      type: 'x',
      autoScaleYaxis: true
    },
    toolbar: {
      show: true,
      tools: {
        download: true,
        selection: true,
        zoom: true,
        zoomin: true,
        zoomout: true,
        pan: true,
        reset: true
      }
    },
    fontFamily: 'Inter, sans-serif',
    animations: {
      enabled: true,
      easing: 'easeinout',
      speed: 800
    }
  },
  series: [{
    name: "Data",
    data: [45, 52, 38, 45, 19, 23, 40, 44, 28, 38, 30, 35, 28, 42, 31, 46, 24, 39, 29, 33, 27, 36]
  }],
  dataLabels: {
    enabled: false
  },
  stroke: {
    curve: 'straight',
    width: 3,
  },
  grid: {
    borderColor: '#e0e6ed',
    strokeDashArray: 5,
    xaxis: {
      lines: {
        show: true
      }
    },
    yaxis: {
      lines: {
        show: true,
      }
    },
    padding: {
      top: 10,
      right: 10,
      bottom: 10,
      left: 10
    },
  },
  xaxis: {
    categories: Array.from({ length: 22 }, (_, i) => `Day ${i + 1}`),
    labels: {
      style: {
        fontFamily: 'Inter, sans-serif',
        colors: '#555'
      },
    }
  },
  yaxis: {
    labels: {
      style: {
        fontFamily: 'Inter, sans-serif',
        colors: '#555'
      },
      formatter: function (value) {
        return value.toFixed(0);
      }
    }
  },
  colors: ['#158156'],
  markers: {
    size: 3,
    strokeWidth: 0,
    hover: {
      size: 6
    }
  },
  tooltip: {
    theme: 'dark',
    marker: {
      show: true,
    },
    x: {
      show: true,
    }
  },
  responsive: [{
    breakpoint: 576,
    options: {
      chart: {
        height: 250
      },
      markers: {
        size: 2
      }
    }
  }]
};

var zoomableLineChart = new ApexCharts(
  document.querySelector("#zoomableLineChart"),
  optionsZoomableLine
);

zoomableLineChart.render();
