// Pie Chart with ApexCharts
document.addEventListener('DOMContentLoaded', function () {
  const pieChartOptions = {
    chart: {
      type: 'pie',
      height: 350,
      fontFamily: 'Poppins, sans-serif',
      toolbar: {
        show: false
      },
      animations: {
        enabled: true,
        easing: 'easeinout',
        speed: 800
      }
    },
    tooltip: {
      enabled: true,
      style: {
        fontSize: '12px'
      },
      y: {
        formatter: function (value) {
          return `${value.toLocaleString()} units`;
        }
      }
    },
    colors: ['#05668d', '#028090', '#00a896', '#02c39a', '#e56b6f'],
    series: [44, 55, 13, 43, 22],
    labels: ['Team A', 'Team B', 'Team C', 'Team D', 'Team E'],
    legend: {
      position: 'bottom',
      horizontalAlign: 'center',
      fontSize: '14px',
      markers: {
        width: 10,
        height: 10,
        radius: 50
      },
      itemMargin: {
        horizontal: 10,
        vertical: 5
      }
    },
    plotOptions: {
      pie: {
        donut: {
          size: '0%'
        },
        expandOnClick: true
      }
    },
    dataLabels: {
      enabled: true,
      formatter: function (val) {
        return val.toFixed(1) + '%';
      },
      style: {
        fontSize: '12px',
        colors: ['#fff'],
        textShadow: '0px 1px 2px rgba(0, 0, 0, 0.5)'
      },
      dropShadow: {
        enabled: true,
        blur: 3,
        opacity: 0.4
      }
    },
    stroke: {
      width: 2,
      colors: ['#fff']
    },
    responsive: [
      {
        breakpoint: 480,
        options: {
          chart: {
            height: 280
          },
          legend: {
            position: 'bottom'
          }
        }
      }
    ]
  };

  // Initialize the chart
  const pieChart = new ApexCharts(
    document.querySelector('#pieChart'),
    pieChartOptions
  );

  pieChart.render();
});

// Monochrome Pie Chart with ApexCharts
document.addEventListener('DOMContentLoaded', function () {
  const monochromePieChartOptions = {
    chart: {
      type: 'pie',
      height: 317,
      fontFamily: 'Poppins, sans-serif',
      toolbar: {
        show: false
      },
      animations: {
        enabled: true,
        easing: 'easeinout',
        speed: 800
      }
    },
    tooltip: {
      enabled: true,
      style: {
        fontSize: '12px'
      },
      y: {
        formatter: function (value) {
          return `${value.toLocaleString()} users`;
        }
      }
    },
    colors: ['#05668d', '#028090', '#00a896', '#02c39a', '#e56b6f'],
    series: [30, 25, 20, 15, 10],
    labels: ['Category A', 'Category B', 'Category C', 'Category D', 'Category E'],
    legend: {
      position: 'right',
      fontSize: '14px',
      markers: {
        width: 12,
        height: 12,
        radius: 2
      }
    },
    plotOptions: {
      pie: {
        startAngle: 0,
        endAngle: 360,
        expandOnClick: true
      }
    },
    dataLabels: {
      enabled: false
    },
    stroke: {
      width: 3,
      colors: ['#fff']
    },
    responsive: [
      {
        breakpoint: 768,
        options: {
          chart: {
            height: 300
          },
          legend: {
            position: 'bottom'
          }
        }
      }
    ]
  };

  // Initialize the monochrome pie chart
  const monochromePieChart = new ApexCharts(
    document.querySelector('#monochromePieChart'),
    monochromePieChartOptions
  );

  monochromePieChart.render();
});

// Gradient Pie Chart with ApexCharts
document.addEventListener('DOMContentLoaded', function () {
  const gradientPieChartOptions = {
    chart: {
      type: 'pie',
      height: 350,
      fontFamily: 'Poppins, sans-serif',
      toolbar: {
        show: false
      },
      animations: {
        enabled: true,
        easing: 'easeinout',
        speed: 800
      }
    },
    tooltip: {
      enabled: true,
      style: {
        fontSize: '12px'
      },
      y: {
        formatter: function (value) {
          return `${value.toLocaleString()} items`;
        }
      }
    },
    colors: ['#05668d', '#028090', '#00a896', '#02c39a', '#e56b6f'],
    fill: {
      type: 'gradient',
      gradient: {
        shade: 'dark',
        type: 'vertical',
        shadeIntensity: 0.5,
        gradientToColors: ['#05668d', '#028090', '#00a896', '#02c39a', '#e56b6f'],
        stops: [0, 100]
      }
    },
    series: [35, 25, 18, 15, 7],
    labels: ['Product A', 'Product B', 'Product C', 'Product D', 'Product E'],
    legend: {
      position: 'bottom',
      horizontalAlign: 'center',
      fontSize: '14px',
      markers: {
        width: 10,
        height: 10,
        radius: 6
      },
      itemMargin: {
        horizontal: 10,
        vertical: 5
      }
    },
    plotOptions: {
      pie: {
        startAngle: 30,
        endAngle: 390,
        expandOnClick: true
      }
    },
    dataLabels: {
      enabled: true,
      formatter: function (val) {
        return val.toFixed(1) + '%';
      },
      style: {
        fontSize: '12px',
        colors: ['#fff'],
        textShadow: '0px 1px 2px rgba(0, 0, 0, 0.5)'
      }
    },
    stroke: {
      width: 2,
      colors: ['#fff']
    },
    responsive: [
      {
        breakpoint: 480,
        options: {
          chart: {
            height: 280
          },
          legend: {
            position: 'bottom'
          }
        }
      }
    ]
  };

  // Initialize the gradient pie chart
  const gradientPieChart = new ApexCharts(
    document.querySelector('#gradientPieChart'),
    gradientPieChartOptions
  );

  gradientPieChart.render();
});

// Semi-Circle Pie Chart with ApexCharts
document.addEventListener('DOMContentLoaded', function () {
  const semiCirclePieChartOptions = {
    chart: {
      type: 'pie',
      height: 340,
      fontFamily: 'Poppins, sans-serif',
      toolbar: {
        show: false
      },
      animations: {
        enabled: true,
        easing: 'easeinout',
        speed: 800
      }
    },
    tooltip: {
      enabled: true,
      style: {
        fontSize: '12px'
      },
      y: {
        formatter: function (value) {
          return `${value.toLocaleString()} sales`;
        }
      }
    },
    colors: ['#05668d', '#028090', '#00a896', '#02c39a', '#e56b6f'],
    series: [42, 23, 15, 12, 8],
    labels: ['Region A', 'Region B', 'Region C', 'Region D', 'Region E'],
    legend: {
      position: 'bottom',
      horizontalAlign: 'center',
      fontSize: '14px',
      markers: {
        width: 10,
        height: 10,
        radius: 50
      },
      itemMargin: {
        horizontal: 10,
        vertical: 5
      }
    },
    plotOptions: {
      pie: {
        startAngle: -90,
        endAngle: 90,
        expandOnClick: true,
        offsetY: 10
      }
    },
    dataLabels: {
      enabled: true,
      formatter: function (val) {
        return val.toFixed(1) + '%';
      },
      style: {
        fontSize: '12px',
        colors: ['#fff'],
        textShadow: '0px 1px 2px rgba(0, 0, 0, 0.5)'
      }
    },
    stroke: {
      width: 2,
      colors: ['#fff']
    },
    responsive: [
      {
        breakpoint: 480,
        options: {
          chart: {
            height: 280
          },
          legend: {
            position: 'bottom'
          }
        }
      }
    ]
  };

  // Initialize the semi-circle pie chart
  const semiCirclePieChart = new ApexCharts(
    document.querySelector('#semiCirclePieChart'),
    semiCirclePieChartOptions
  );

  semiCirclePieChart.render();
});

// Patterned Pie Chart with ApexCharts
document.addEventListener('DOMContentLoaded', function () {
  const patternedPieChartOptions = {
    chart: {
      type: 'pie',
      height: 350,
      fontFamily: 'Poppins, sans-serif',
      toolbar: {
        show: false
      },
      animations: {
        enabled: true,
        easing: 'easeinout',
        speed: 800
      }
    },
    tooltip: {
      enabled: true,
      style: {
        fontSize: '12px'
      },
      y: {
        formatter: function (value) {
          return `${value.toLocaleString()} orders`;
        }
      }
    },
    colors: ['#05668d', '#028090', '#00a896', '#02c39a', '#e56b6f'],
    fill: {
      type: 'pattern',
      opacity: 1,
      pattern: {
        style: ['circles', 'slantedLines', 'horizontalLines', 'verticalLines', 'squares'],
        width: 6,
        height: 6,
        strokeWidth: 2
      }
    },
    series: [40, 30, 15, 10, 5],
    labels: ['Category A', 'Category B', 'Category C', 'Category D', 'Category E'],
    legend: {
      position: 'bottom',
      horizontalAlign: 'center',
      fontSize: '14px',
      markers: {
        width: 12,
        height: 12,
        radius: 6
      },
      itemMargin: {
        horizontal: 10,
        vertical: 5
      }
    },
    plotOptions: {
      pie: {
        expandOnClick: true
      }
    },
    dataLabels: {
      enabled: true,
      formatter: function (val) {
        return val.toFixed(1) + '%';
      },
      style: {
        fontSize: '12px',
        colors: ['#fff'],
        textShadow: '0px 1px 2px rgba(0, 0, 0, 0.5)'
      }
    },
    stroke: {
      width: 2,
      colors: ['#fff']
    },
    responsive: [
      {
        breakpoint: 480,
        options: {
          chart: {
            height: 280
          },
          legend: {
            position: 'bottom'
          }
        }
      }
    ]
  };

  // Initialize the patterned pie chart
  const patternedPieChart = new ApexCharts(
    document.querySelector('#patternedPieChart'),
    patternedPieChartOptions
  );

  patternedPieChart.render();
});
