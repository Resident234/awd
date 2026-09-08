// Basic Donut Chart
var options = {
  chart: {
    height: 350,
    type: 'donut',
  },
  series: [44, 55, 41, 17, 15],
  legend: {
    position: 'bottom',
  },
  labels: ['Product A', 'Product B', 'Product C', 'Product D', 'Product E'],
  colors: ['#05668d', '#028090', '#00a896', '#02c39a', '#e56b6f'],
  responsive: [{
    breakpoint: 480,
    options: {
      chart: {
        width: 200
      },
      legend: {
        position: 'bottom'
      }
    }
  }]
}

var basicDonutChart = new ApexCharts(
  document.querySelector("#basicDonutChart"),
  options
);

basicDonutChart.render();

// Gradient Donut Chart
var options2 = {
  chart: {
    height: 350,
    type: 'donut',
  },
  series: [44, 55, 41, 17, 15],
  fill: {
    type: 'gradient',
  },
  legend: {
    position: 'bottom',
  },
  labels: ['Product A', 'Product B', 'Product C', 'Product D', 'Product E'],
  colors: ['#05668d', '#028090', '#00a896', '#02c39a', '#e56b6f'],
  responsive: [{
    breakpoint: 480,
    options: {
      chart: {
        width: 200
      },
      legend: {
        position: 'bottom'
      }
    }
  }]
}

var gradientDonutChart = new ApexCharts(
  document.querySelector("#gradientDonutChart"),
  options2
);

gradientDonutChart.render();


// Monochrome Donut Chart
var options3 = {
  chart: {
    height: 350,
    type: 'donut',
  },
  series: [44, 55, 41, 17, 15],
  plotOptions: {
    pie: {
      dataLabels: {
        offset: -5
      }
    }
  },
  title: {
    text: 'Monochrome Donut'
  },
  fill: {
    type: 'monochrome',
    color: '#4361ee'
  },
  legend: {
    position: 'bottom'
  },
  labels: ['Product A', 'Product B', 'Product C', 'Product D', 'Product E'],
  colors: ['#05668d', '#028090', '#00a896', '#02c39a', '#e56b6f'],
  responsive: [{
    breakpoint: 480,
    options: {
      chart: {
        width: 200
      },
      legend: {
        position: 'bottom'
      }
    }
  }]
}

var monochromeDonutChart = new ApexCharts(
  document.querySelector("#monochromeDonutChart"),
  options3
);

monochromeDonutChart.render();

// Semi Donut Chart
var options4 = {
  chart: {
    height: 350,
    type: 'donut',
  },
  series: [44, 55, 41, 17, 15],
  plotOptions: {
    pie: {
      startAngle: -90,
      endAngle: 90,
      offsetY: 10
    }
  },
  grid: {
    padding: {
      bottom: -80
    }
  },
  title: {
    text: 'Semi Donut Chart'
  },
  legend: {
    position: 'bottom'
  },
  labels: ['Product A', 'Product B', 'Product C', 'Product D', 'Product E'],
  colors: ['#05668d', '#028090', '#00a896', '#02c39a', '#e56b6f'],
  responsive: [{
    breakpoint: 480,
    options: {
      chart: {
        width: 200
      },
      legend: {
        position: 'bottom'
      }
    }
  }]
}

var semiDonutChart = new ApexCharts(
  document.querySelector("#semiDonutChart"),
  options4
);

semiDonutChart.render();

// Patterned Donut Chart
var options5 = {
  chart: {
    height: 350,
    type: 'donut',
  },
  series: [44, 55, 41, 17, 15],
  colors: ['#05668d', '#028090', '#00a896', '#02c39a', '#e56b6f'],
  fill: {
    type: 'pattern',
    opacity: 1,
    pattern: {
      style: ['horizontalLines', 'verticalLines', 'slantedLines', 'squares', 'circles'],
    }
  },
  title: {
    text: 'Patterned Donut Chart'
  },
  legend: {
    position: 'bottom'
  },
  labels: ['Product A', 'Product B', 'Product C', 'Product D', 'Product E'],
  responsive: [{
    breakpoint: 480,
    options: {
      chart: {
        width: 200
      },
      legend: {
        position: 'bottom'
      }
    }
  }]
}

var patternedDonutChart = new ApexCharts(
  document.querySelector("#patternedDonutChart"),
  options5
);

patternedDonutChart.render();


// Interactive Donut Chart
var options6 = {
  chart: {
    height: 350,
    type: 'donut',
  },
  series: [44, 55, 41, 17, 15],
  colors: ['#05668d', '#028090', '#00a896', '#02c39a', '#e56b6f'],
  title: {
    text: 'Interactive Donut Chart'
  },
  legend: {
    position: 'bottom',
    formatter: function (seriesName, opts) {
      return [seriesName, " - ", opts.w.globals.series[opts.seriesIndex]]
    }
  },
  dataLabels: {
    formatter: function (val, opts) {
      return opts.w.globals.series[opts.seriesIndex] + '%'
    }
  },
  plotOptions: {
    pie: {
      donut: {
        size: '65%',
        labels: {
          show: true,
          total: {
            show: true,
            showAlways: true,
            label: 'Total',
            formatter: function (w) {
              return w.globals.seriesTotals.reduce((a, b) => a + b, 0)
            }
          }
        }
      }
    }
  },
  labels: ['Product A', 'Product B', 'Product C', 'Product D', 'Product E'],
  responsive: [{
    breakpoint: 480,
    options: {
      chart: {
        width: 200
      },
      legend: {
        position: 'bottom'
      }
    }
  }]
}

var interactiveDonutChart = new ApexCharts(
  document.querySelector("#interactiveDonutChart"),
  options6
);

interactiveDonutChart.render();