// Basic Polar Area Chart
// This chart displays a polar area chart which is a variation of a pie chart
// where sectors have equal angles but varying radii

document.addEventListener('DOMContentLoaded', function () {
  var options = {
    series: [14, 23, 21, 17, 15, 10],
    chart: {
      type: 'polarArea',
      height: 350,
      toolbar: {
        show: false
      }
    },
    stroke: {
      colors: ['#ffffff']
    },
    fill: {
      opacity: 0.8
    },
    colors: ['#05668d', '#028090', '#00a896', '#02c39a', '#e56b6f'],
    responsive: [{
      breakpoint: 480,
      options: {
        chart: {
          width: 280
        },
        legend: {
          position: 'bottom'
        }
      }
    }],
    labels: ['Category A', 'Category B', 'Category C', 'Category D', 'Category E', 'Category F'],
    legend: {
      position: 'bottom'
    }
  };

  var chart = new ApexCharts(document.querySelector("#basicPolarAreaChart"), options);
  chart.render();
});

// Monochrome Polar Area Chart
// This chart displays a polar area chart with a monochromatic color scheme
document.addEventListener('DOMContentLoaded', function () {
  var options = {
    series: [42, 39, 35, 29, 26, 21],
    chart: {
      type: 'polarArea',
      height: 350,
      toolbar: {
        show: false
      }
    },
    stroke: {
      colors: ['#ffffff']
    },
    fill: {
      opacity: 0.8,
      type: 'gradient'
    },
    colors: ['#05668d', '#028090', '#00a896', '#02c39a', '#e56b6f'],
    responsive: [{
      breakpoint: 480,
      options: {
        chart: {
          width: 280
        },
        legend: {
          position: 'bottom'
        }
      }
    }],
    labels: ['Revenue', 'Sales', 'Customers', 'Projects', 'Retention', 'Growth'],
    legend: {
      position: 'bottom'
    },
    plotOptions: {
      polarArea: {
        rings: {
          strokeWidth: 1,
          strokeColor: '#e9e9e9'
        }
      }
    },
    theme: {
      monochrome: {
        enabled: true,
        shadeTo: 'light',
        shadeIntensity: 0.6,
        color: '#00a896'
      }
    }
  };

  var chart = new ApexCharts(document.querySelector("#monochromePolarAreaChart"), options);
  chart.render();
});

// Advanced Polar Area Chart
// This chart displays a polar area chart with custom design features
document.addEventListener('DOMContentLoaded', function () {
  var options = {
    series: [32, 27, 23, 19, 15, 13],
    chart: {
      type: 'polarArea',
      height: 350,
      toolbar: {
        show: false
      }
    },
    stroke: {
      width: 2,
      colors: ['#ffffff']
    },
    colors: ['#05668d', '#028090', '#00a896', '#02c39a', '#e56b6f'],
    responsive: [{
      breakpoint: 480,
      options: {
        chart: {
          width: 280
        },
        legend: {
          position: 'bottom'
        }
      }
    }],
    labels: ['Marketing', 'Development', 'Support', 'Research', 'Sales', 'Operations'],
    legend: {
      position: 'right'
    },
    plotOptions: {
      polarArea: {
        rings: {
          strokeWidth: 1,
          strokeColor: '#e9e9e9'
        },
        spokes: {
          strokeWidth: 1,
          connectorColors: '#e9e9e9'
        }
      }
    },
    dataLabels: {
      enabled: true,
      formatter: function (val, opts) {
        return opts.w.config.series[opts.seriesIndex] + '%';
      },
      style: {
        fontSize: '12px'
      }
    }
  };

  var chart = new ApexCharts(document.querySelector("#advancedPolarAreaChart"), options);
  chart.render();
});