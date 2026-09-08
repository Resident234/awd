// Basic Bar Chart
var basicBarOptions = {
  series: [{
    name: 'Monthly Sales',
    data: [420, 550, 480, 620, 780, 850, 920, 1050, 1250, 1400, 1150, 980]
  }],
  chart: {
    type: 'bar',
    height: 350,
    toolbar: {
      show: false,
    },
    fontFamily: 'inherit',
    background: '#f8f9fa'
  },
  plotOptions: {
    bar: {
      borderRadius: 6,
      columnWidth: '55%',
      endingShape: 'rounded',
      distributed: false,
      dataLabels: {
        position: 'top'
      }
    }
  },
  colors: ["#5387e4", "#27ba89", "#ff7d5c", "#fbcc41", "#64c1fe", "#8a94a4", "#9c6ade"],
  dataLabels: {
    enabled: false
  },
  states: {
    hover: {
      filter: {
        type: 'darken',
        value: 0.9
      }
    }
  },
  xaxis: {
    categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
    axisBorder: {
      show: false
    },
    axisTicks: {
      show: false
    },
    labels: {
      style: {
        fontSize: '12px'
      }
    }
  },
  yaxis: {
    title: {
      text: 'Revenue ($)',
      style: {
        fontWeight: 500
      }
    },
    labels: {
      formatter: function (val) {
        return '$' + val.toLocaleString();
      }
    }
  },
  tooltip: {
    y: {
      formatter: function (val) {
        return '$ ' + val.toLocaleString()
      }
    },
    theme: 'dark'
  },
  grid: {
    borderColor: '#e0e0e0',
    strokeDashArray: 5,
    padding: {
      left: 10,
      right: 10
    }
  },
  fill: {
    opacity: 0.9,
    type: 'gradient',
    gradient: {
      shade: 'light',
      type: 'vertical',
      shadeIntensity: 0.2,
      opacityFrom: 0.9,
      opacityTo: 0.8,
    }
  }
};
var basicBarChart = new ApexCharts(document.querySelector("#basicBarChart"), basicBarOptions);
basicBarChart.render();

// Grouped Bar Chart
var groupedBarOptions = {
  series: [{
    name: 'Net Profit',
    data: [44, 55, 57, 56, 61, 58, 63].map(val => Math.round(val * (1 + Math.random() * 0.3)))
  }, {
    name: 'Revenue',
    data: [76, 85, 101, 98, 87, 105, 91].map(val => Math.round(val * (1 + Math.random() * 0.25)))
  }, {
    name: 'Operating Cost',
    data: [35, 41, 36, 26, 45, 48, 52]
  }],
  chart: {
    type: 'bar',
    height: 350,
    toolbar: {
      show: false,
    },
    fontFamily: 'inherit',
    background: '#f8f9fa'
  },
  plotOptions: {
    bar: {
      horizontal: false,
      columnWidth: '60%',
      endingShape: 'rounded',
      borderRadius: 4
    },
  },
  dataLabels: {
    enabled: false
  },
  stroke: {
    show: true,
    width: 2,
    colors: ['transparent']
  },
  colors: ["#5387e4", "#27ba89", "#ff7d5c", "#fbcc41", "#64c1fe", "#8a94a4", "#9c6ade"],
  xaxis: {
    categories: ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
  },
  yaxis: {
    title: {
      text: 'Amount (thousands $)',
      style: {
        fontWeight: 500
      }
    }
  },
  fill: {
    opacity: 0.9,
    type: 'gradient',
    gradient: {
      shade: 'light',
      type: 'vertical',
      shadeIntensity: 0.2,
      opacityFrom: 0.9,
      opacityTo: 0.8,
    }
  },
  tooltip: {
    y: {
      formatter: function (val) {
        return "$ " + val.toLocaleString() + " thousands"
      }
    },
    theme: 'dark'
  },
  legend: {
    position: 'top'
  }
};
var groupedBarChart = new ApexCharts(document.querySelector("#groupedBarChart"), groupedBarOptions);
groupedBarChart.render();

// Stacked Bar Chart
var stackedBarOptions = {
  series: [{
    name: 'Product A',
    data: [44, 55, 41, 67, 22, 43]
  }, {
    name: 'Product B',
    data: [13, 23, 20, 8, 13, 27]
  }, {
    name: 'Product C',
    data: [11, 17, 15, 15, 21, 14]
  }],
  chart: {
    type: 'bar',
    height: 350,
    stacked: true,
    toolbar: {
      show: false
    },
    fontFamily: 'inherit',
    background: '#f8f9fa'
  },
  plotOptions: {
    bar: {
      horizontal: false,
      borderRadius: 6,
      columnWidth: '55%',
      dataLabels: {
        position: 'center'
      }
    },
  },
  colors: ["#5387e4", "#27ba89", "#ff7d5c", "#fbcc41", "#64c1fe", "#8a94a4", "#9c6ade"],
  xaxis: {
    categories: ['Q1', 'Q2', 'Q3', 'Q4', 'Q5', 'Q6'],
    axisBorder: {
      show: false
    },
    axisTicks: {
      show: false
    },
    labels: {
      style: {
        fontSize: '12px'
      }
    }
  },
  yaxis: {
    title: {
      text: 'Quantity Sold',
      style: {
        fontWeight: 500
      }
    },
    labels: {
      formatter: function (val) {
        return val.toLocaleString();
      }
    }
  },
  fill: {
    opacity: 0.9,
    type: 'gradient',
    gradient: {
      shade: 'light',
      type: 'vertical',
      shadeIntensity: 0.2,
      opacityFrom: 0.9,
      opacityTo: 0.8,
    }
  },
  tooltip: {
    y: {
      formatter: function (val) {
        return val.toLocaleString() + " units"
      }
    },
    theme: 'dark'
  },
  legend: {
    position: 'top',
    horizontalAlign: 'center',
    offsetY: 10,
    fontSize: '13px'
  },
  grid: {
    borderColor: '#e0e0e0',
    strokeDashArray: 5,
    padding: {
      left: 10,
      right: 10
    }
  },
  states: {
    hover: {
      filter: {
        type: 'darken',
        value: 0.9
      }
    }
  }
};
var stackedBarChart = new ApexCharts(document.querySelector("#stackedBarChart"), stackedBarOptions);
stackedBarChart.render();

// Horizontal Bar Chart
var horizontalBarOptions = {
  series: [{
    name: 'Revenue',
    data: [430, 520, 485, 625, 770, 865, 980]
  }, {
    name: 'Profit',
    data: [210, 320, 275, 390, 470, 510, 650]
  }],
  chart: {
    type: 'bar',
    height: 350,
    toolbar: {
      show: false
    },
    fontFamily: 'inherit',
    background: '#f8f9fa'
  },
  plotOptions: {
    bar: {
      borderRadius: 4,
      horizontal: true,
      barHeight: '65%',
      distributed: false,
      dataLabels: {
        position: 'top'
      }
    }
  },
  colors: ["#5387e4", "#27ba89", "#ff7d5c", "#fbcc41", "#64c1fe", "#8a94a4", "#9c6ade"],
  dataLabels: {
    enabled: true,
    formatter: function (val) {
      return '$' + val.toLocaleString();
    },
    offsetX: 30,
    style: {
      fontSize: '12px',
      fontWeight: 500,
      colors: ['#333']
    }
  },
  xaxis: {
    categories: ['January', 'February', 'March', 'April', 'May', 'June', 'July'],
    labels: {
      style: {
        fontSize: '12px'
      }
    },
    title: {
      text: 'Amount (USD)',
      style: {
        fontWeight: 600
      }
    }
  },
  yaxis: {
    labels: {
      style: {
        fontSize: '13px',
        fontWeight: 500
      }
    }
  },
  tooltip: {
    y: {
      formatter: function (val) {
        return '$ ' + val.toLocaleString()
      }
    },
    shared: true,
    intersect: false,
    theme: 'dark'
  },
  fill: {
    opacity: 0.9,
    type: 'gradient',
    gradient: {
      shade: 'light',
      type: 'horizontal',
      shadeIntensity: 0.25,
      gradientToColors: ["#5387e4", "#27ba89", "#ff7d5c", "#fbcc41", "#64c1fe", "#8a94a4", "#9c6ade"],
      opacityFrom: 0.9,
      opacityTo: 0.8,
    }
  },
  grid: {
    borderColor: '#e0e0e0',
    strokeDashArray: 5,
    padding: {
      top: 5,
      right: 30,
      bottom: 5,
      left: 15
    }
  },
  legend: {
    position: 'top',
    horizontalAlign: 'right',
    offsetY: 0,
    fontSize: '13px'
  },
  states: {
    hover: {
      filter: {
        type: 'darken',
        value: 0.9
      }
    }
  }
};
var horizontalBarChart = new ApexCharts(document.querySelector("#horizontalBarChart"), horizontalBarOptions);
horizontalBarChart.render();

// Negative Bar Chart
var negativeBarOptions = {
  series: [{
    name: 'Cash Flow',
    data: [1.45, 5.42, 5.9, -0.42, -12.6, -18.1, 18.2, 14.4, -8.5, -9.7, 11.3, 7.8]
  }],
  chart: {
    type: 'bar',
    height: 350,
    toolbar: {
      show: false,
    },
    fontFamily: 'inherit',
    background: '#f8f9fa'
  },
  plotOptions: {
    bar: {
      borderRadius: 8,
      columnWidth: '65%',
      colors: {
        ranges: [{
          from: -100,
          to: 0,
          color: "#ff7d5c",
        }],
        backgroundBarOpacity: 0.15,
        backgroundBarRadius: 8
      }
    }
  },
  colors: ["#5387e4", "#27ba89", "#ff7d5c", "#fbcc41", "#64c1fe", "#8a94a4", "#9c6ade"],
  dataLabels: {
    enabled: true,
    formatter: function (val) {
      return val > 0 ? '+' + val.toFixed(1) : val.toFixed(1);
    },
    offsetY: val => val < 0 ? 20 : -20,
    style: {
      fontSize: '12px',
      fontWeight: 'bold',
      colors: [function ({ value }) {
        return value < 0 ? '#fff' : '#333';
      }]
    }
  },
  yaxis: {
    title: {
      text: 'Cash Flow (thousand $)',
      style: {
        fontWeight: 500
      }
    },
    labels: {
      formatter: function (val) {
        return val.toFixed(1);
      }
    }
  },
  xaxis: {
    categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
    labels: {
      rotate: -45,
      style: {
        fontSize: '12px',
        fontWeight: 500
      }
    }
  },
  tooltip: {
    y: {
      formatter: function (val) {
        return "$ " + val.toFixed(2) + " thousands"
      }
    },
    theme: 'dark'
  },
  grid: {
    borderColor: '#e0e0e0',
    strokeDashArray: 5
  },
  fill: {
    opacity: 1,
    type: 'gradient',
    gradient: {
      shade: 'light',
      type: 'vertical',
      shadeIntensity: 0.3,
      opacityFrom: 1,
      opacityTo: 0.8,
      colorStops: [
        [
          {
            offset: 0,
            color: '#158156',
            opacity: 1
          },
          {
            offset: 100,
            color: '#5ba789',
            opacity: 1
          }
        ],
        [
          {
            offset: 0,
            color: '#ff595e',
            opacity: 1
          },
          {
            offset: 100,
            color: '#e02127',
            opacity: 1
          }
        ]
      ]
    }
  },
  states: {
    hover: {
      filter: {
        type: 'darken',
        value: 0.15
      }
    }
  },
  annotations: {
    yaxis: [{
      y: 0,
      strokeDashArray: 0,
      borderColor: '#616161', // Darker gray
      borderWidth: 2,
      opacity: 0.8,
      label: {
        text: 'Breakeven',
        position: 'left',
        textAnchor: 'start',
        style: {
          color: '#616161',
          fontSize: '11px',
          fontWeight: 600,
          background: '#f8f9fa'
        }
      }
    }]
  }
};

var negativeBarChart = new ApexCharts(document.querySelector("#negativeBarChart"), negativeBarOptions);
negativeBarChart.render();

// Patterned Bar Chart
var patternedBarOptions = {
  series: [{
    name: 'Marine',
    data: [44, 55, 41, 37, 22]
  }, {
    name: 'Technical',
    data: [53, 32, 33, 52, 13]
  }, {
    name: 'Logistics',
    data: [12, 17, 11, 9, 15]
  }],
  chart: {
    type: 'bar',
    height: 350,
    stacked: true,
    toolbar: {
      show: false
    }
  },
  stroke: {
    width: 1,
    colors: ['#fff']
  },
  plotOptions: {
    bar: {
      columnWidth: '45%'
    }
  },
  colors: ["#5387e4", "#27ba89", "#ff7d5c", "#fbcc41", "#64c1fe", "#8a94a4", "#9c6ade"],
  fill: {
    type: 'pattern',
    opacity: 1,
    pattern: {
      style: ['circles', 'horizontalLines', 'verticalLines'],
    }
  },
  dataLabels: {
    enabled: false
  },
  xaxis: {
    categories: ['2018', '2019', '2020', '2021', '2022'],
  },
  legend: {
    position: 'top'
  }
};
var patternedBarChart = new ApexCharts(document.querySelector("#patternedBarChart"), patternedBarOptions);
patternedBarChart.render();