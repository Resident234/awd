// Mixed Chart - Line and Column - Enhanced Design
var lineColumnOptions = {
  series: [{
    name: 'Revenue',
    type: 'column',
    data: [23, 11, 22, 27, 13, 22, 37, 21, 44, 22, 30, 21]
  }, {
    name: 'Profit',
    type: 'line',
    data: [30, 25, 36, 30, 45, 35, 64, 52, 59, 36, 39, 51]
  }],
  chart: {
    height: 350,
    type: 'line',
    fontFamily: 'Poppins, sans-serif',
    background: '#f8f9fa',
    toolbar: {
      show: false,
    },
    dropShadow: {
      enabled: true,
      top: 3,
      left: 2,
      blur: 4,
      opacity: 0.1
    }
  },
  stroke: {
    width: [0, 4],
    curve: 'smooth'
  },
  colors: ['#158156', '#5bb51c'],
  plotOptions: {
    bar: {
      columnWidth: '60%',
      borderRadius: 4,
      dataLabels: {
        position: 'top'
      }
    }
  },
  fill: {
    opacity: [0.85, 1],
    type: ['solid', 'gradient'],
    gradient: {
      shade: 'light',
      type: "vertical",
      opacityFrom: 0.9,
      opacityTo: 0.5,
      stops: [0, 100]
    }
  },
  dataLabels: {
    enabled: false,
  },
  labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
  markers: {
    size: 5,
    strokeWidth: 0,
    hover: {
      size: 7
    }
  },
  grid: {
    borderColor: '#e0e6ed',
    strokeDashArray: 5,
    xaxis: {
      lines: {
        show: true
      }
    }
  },
  xaxis: {
    type: 'category',
    axisBorder: {
      show: false,
    },
    axisTicks: {
      show: false,
    },
    labels: {
      style: {
        fontWeight: 500
      }
    }
  },
  yaxis: {
    title: {
      text: 'Value (thousands)',
      style: {
        fontWeight: 500
      }
    },
    min: 0,
    tickAmount: 5,
    labels: {
      formatter: value => value + 'k'
    }
  },
  tooltip: {
    shared: true,
    intersect: false,
    theme: 'dark',
    y: {
      formatter: function (y) {
        if (typeof y !== "undefined") {
          return y.toFixed(0) + "k";
        }
        return y;
      }
    }
  },
  legend: {
    position: 'top',
    horizontalAlign: 'center',
    offsetX: 0,
    fontSize: '14px',
    markers: {
      width: 10,
      height: 10,
      radius: 50
    }
  }
};

var lineColumnMixedChart = new ApexCharts(document.querySelector("#lineColumnMixedChart"), lineColumnOptions);
lineColumnMixedChart.render();

// Multiple Y-Axis Chart - Enhanced with better series configuration
var multipleYAxisOptions = {
  series: [{
    name: 'Income',
    type: 'column',
    data: [1.4, 2.3, 1.8, 2.5, 1.9, 2.6, 3.0, 2.2, 3.5, 2.4, 2.9, 3.1]
  }, {
    name: 'Expenses',
    type: 'column',
    data: [1.1, 0.8, 1.2, 1.7, 1.3, 1.8, 2.2, 1.6, 2.6, 1.8, 2.1, 2.5]
  }, {
    name: 'Cashflow',
    type: 'line',
    data: [0.3, 1.5, 0.6, 0.8, 0.6, 0.8, 0.8, 0.6, 0.9, 0.6, 0.8, 0.6]
  }],
  chart: {
    height: 350,
    type: 'line',
    fontFamily: 'Poppins, sans-serif',
    background: '#f8f9fa',
    toolbar: {
      show: false,
    },
    dropShadow: {
      enabled: true,
      top: 3,
      left: 2,
      blur: 4,
      opacity: 0.1
    },
    animations: {
      enabled: true,
      easing: 'easeinout',
      speed: 800
    }
  },
  stroke: {
    width: [0, 0, 4],
    curve: 'smooth',
    lineCap: 'round'
  },
  colors: ['#158156', '#5bb51c', '#ffca3a'],
  plotOptions: {
    bar: {
      columnWidth: '50%',
      borderRadius: 4,
      dataLabels: {
        position: 'top'
      }
    }
  },
  fill: {
    opacity: [0.85, 0.85, 1],
    type: ['solid', 'solid', 'gradient'],
    gradient: {
      shade: 'light',
      type: "vertical",
      opacityFrom: 0.9,
      opacityTo: 0.5,
      stops: [0, 100],
      colorStops: [
        {
          offset: 0,
          color: '#ffca3a',
          opacity: 0.9
        },
        {
          offset: 100,
          color: '#8ac926',
          opacity: 0.5
        }
      ]
    }
  },
  dataLabels: {
    enabled: false
  },
  labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
  markers: {
    size: 5,
    strokeWidth: 0,
    hover: {
      size: 7,
      sizeOffset: 3
    }
  },
  grid: {
    borderColor: '#e0e6ed',
    strokeDashArray: 5,
    padding: {
      top: 0,
      right: 20,
      bottom: 0,
      left: 15
    },
    xaxis: {
      lines: {
        show: true
      }
    }
  },
  xaxis: {
    type: 'category',
    axisBorder: {
      show: false,
    },
    axisTicks: {
      show: false,
    },
    labels: {
      style: {
        fontWeight: 500,
        colors: '#555'
      }
    }
  },
  yaxis: [
    {
      title: {
        text: 'Income & Expenses (M)',
        style: {
          fontWeight: 500,
          fontSize: '12px',
          color: '#555'
        }
      },
      min: 0,
      max: 4,
      tickAmount: 4,
      labels: {
        formatter: value => `${value.toFixed(1)}M`,
        style: {
          colors: '#555'
        }
      }
    },
    {
      opposite: true,
      title: {
        text: 'Cashflow (M)',
        style: {
          fontWeight: 500,
          fontSize: '12px',
          color: '#555'
        }
      },
      min: 0,
      max: 2,
      tickAmount: 4,
      labels: {
        formatter: value => `${value.toFixed(1)}M`,
        style: {
          colors: '#555'
        }
      }
    }
  ],
  tooltip: {
    shared: true,
    intersect: false,
    theme: 'dark',
    x: {
      show: true,
      format: 'dd MMM'
    },
    y: {
      formatter: function (value) {
        return typeof value !== "undefined" ? `${value.toFixed(1)}M` : value;
      }
    }
  },
  legend: {
    position: 'top',
    horizontalAlign: 'center',
    offsetX: 0,
    fontSize: '14px',
    markers: {
      width: 10,
      height: 10,
      radius: 50,
      strokeWidth: 0
    },
    itemMargin: {
      horizontal: 10,
      vertical: 5
    }
  },
  responsive: [
    {
      breakpoint: 576,
      options: {
        legend: {
          fontSize: '12px'
        }
      }
    }
  ]
};

var multipleYAxisChart = new ApexCharts(document.querySelector("#multipleYAxisChart"), multipleYAxisOptions);
multipleYAxisChart.render();

// Mixed Line Area Graph - Enhanced with gradients and animations
var lineAreaOptions = {
  series: [{
    name: 'Website Traffic',
    type: 'area',
    data: [31, 40, 28, 51, 42, 109, 100, 70, 85, 110, 90, 95]
  }, {
    name: 'Social Media',
    type: 'line',
    data: [20, 32, 45, 32, 34, 52, 41, 55, 40, 60, 55, 70]
  }, {
    name: 'Referrals',
    type: 'line',
    data: [10, 15, 25, 20, 30, 40, 35, 45, 30, 50, 45, 60]
  }],
  chart: {
    height: 350,
    type: 'line',
    fontFamily: 'Poppins, sans-serif',
    background: '#f8f9fa',
    toolbar: { show: false },
    dropShadow: {
      enabled: true,
      top: 3,
      left: 2,
      blur: 4,
      opacity: 0.1
    }
  },
  stroke: {
    width: [0, 3, 3],
    curve: 'smooth'
  },
  colors: ['#158156', '#5bb51c', '#ffca3a'],
  fill: {
    type: ['gradient', 'solid', 'solid'],
    gradient: {
      shade: 'light',
      type: "vertical",
      opacityFrom: 0.8,
      opacityTo: 0.2
    }
  },
  labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
  markers: {
    size: 4,
    hover: { size: 7 }
  },
  grid: {
    borderColor: '#e0e6ed',
    strokeDashArray: 5,
    xaxis: { lines: { show: true } }
  },
  xaxis: {
    type: 'category',
    axisBorder: { show: false },
    axisTicks: { show: false }
  },
  yaxis: {
    title: {
      text: 'Visitors (thousands)',
      style: { fontWeight: 500 }
    },
    min: 0,
    max: 120,
    tickAmount: 6,
    labels: { formatter: value => `${value}k` }
  },
  tooltip: {
    shared: true,
    theme: 'dark',
    y: { formatter: value => `${value}k visitors` }
  },
  legend: {
    position: 'top',
    horizontalAlign: 'center',
    fontSize: '14px',
    markers: {
      width: 10,
      height: 10,
      radius: 50
    }
  },
  responsive: [{
    breakpoint: 576,
    options: {
      yaxis: { show: false },
      legend: {
        position: 'bottom',
        fontSize: '12px'
      }
    }
  }]
};

var lineAreaChart = new ApexCharts(document.querySelector("#lineAreaMixedChart"), lineAreaOptions);
lineAreaChart.render();

// Traffic Sources Chart - Line and Column
var trafficSourceOptions = {
  series: [{
    name: 'Website Blog',
    type: 'column',
    data: [440, 505, 414, 671, 227, 413, 201, 352, 752, 320, 257, 160]
  }, {
    name: 'Social Media',
    type: 'line',
    data: [23, 42, 35, 27, 43, 22, 17, 31, 22, 22, 12, 16]
  }],
  chart: {
    height: 350,
    type: 'line',
    fontFamily: 'Poppins, sans-serif',
    background: '#f8f9fa',
    toolbar: {
      show: false,
    },
    dropShadow: {
      enabled: true,
      top: 3,
      left: 2,
      blur: 4,
      opacity: 0.1
    }
  },
  stroke: {
    width: [0, 4],
    curve: 'smooth'
  },
  colors: ['#158156', '#5bb51c'],
  title: {
    text: 'Traffic Sources',
    align: 'left',
    style: {
      fontSize: '16px',
      fontWeight: 600,
      color: '#333'
    }
  },
  plotOptions: {
    bar: {
      columnWidth: '60%',
      borderRadius: 4
    }
  },
  fill: {
    opacity: [0.85, 1],
    type: ['solid', 'gradient'],
    gradient: {
      shade: 'light',
      type: "vertical",
      opacityFrom: 0.9,
      opacityTo: 0.5,
      stops: [0, 100]
    }
  },
  dataLabels: {
    enabled: false
  },
  labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
  markers: {
    size: 5,
    strokeWidth: 0,
    hover: {
      size: 7
    }
  },
  grid: {
    borderColor: '#e0e6ed',
    strokeDashArray: 5,
    xaxis: {
      lines: {
        show: true
      }
    }
  },
  xaxis: {
    type: 'category',
    axisBorder: {
      show: false,
    },
    axisTicks: {
      show: false,
    },
    labels: {
      style: {
        fontWeight: 500,
        colors: '#555'
      }
    }
  },
  yaxis: [{
    title: {
      text: 'Website Blog (sessions)',
      style: {
        fontWeight: 500,
        fontSize: '12px',
        color: '#555'
      }
    },
    labels: {
      formatter: function (val) {
        return val.toFixed(0);
      },
      style: {
        colors: '#555'
      }
    }
  }, {
    opposite: true,
    title: {
      text: 'Social Media (engagements)',
      style: {
        fontWeight: 500,
        fontSize: '12px',
        color: '#555'
      }
    },
    labels: {
      formatter: function (val) {
        return val.toFixed(0);
      },
      style: {
        colors: '#555'
      }
    }
  }],
  tooltip: {
    shared: true,
    intersect: false,
    theme: 'dark',
    y: {
      formatter: function (y) {
        if (typeof y !== "undefined") {
          return y.toFixed(0);
        }
        return y;
      }
    }
  },
  legend: {
    position: 'top',
    horizontalAlign: 'center',
    offsetX: 0,
    fontSize: '14px',
    markers: {
      width: 10,
      height: 10,
      radius: 50,
      strokeWidth: 0
    }
  },
  responsive: [{
    breakpoint: 576,
    options: {
      yaxis: [{
        labels: {
          rotate: -30
        }
      }, {
        labels: {
          rotate: -30
        }
      }],
      legend: {
        fontSize: '12px'
      }
    }
  }]
};

var lineColumnAreaChart = new ApexCharts(document.querySelector("#lineColumnAreaChart"), trafficSourceOptions);
lineColumnAreaChart.render();
