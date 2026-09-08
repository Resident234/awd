// Bubble Chart
var options = {
  chart: {
    height: '100%',
    type: 'bubble',
    toolbar: {
      show: false,
    },
    animations: {
      enabled: true,
      easing: 'easeinout',
      speed: 800,
      animateGradually: {
        enabled: true,
        delay: 150
      },
      dynamicAnimation: {
        enabled: true,
        speed: 350
      }
    }
  },
  dataLabels: {
    enabled: false
  },
  series: [
    {
      name: 'Group A',
      data: generateBubbleData(new Date('11 Feb 2022 GMT').getTime(), 20, {
        min: 10,
        max: 60
      })
    },
    {
      name: 'Group B',
      data: generateBubbleData(new Date('11 Feb 2022 GMT').getTime(), 20, {
        min: 10,
        max: 60
      })
    },
    {
      name: 'Group C',
      data: generateBubbleData(new Date('11 Feb 2022 GMT').getTime(), 20, {
        min: 10,
        max: 60
      })
    },
    {
      name: 'Group D',
      data: generateBubbleData(new Date('11 Feb 2022 GMT').getTime(), 20, {
        min: 10,
        max: 60
      })
    }
  ],
  fill: {
    type: 'gradient',
    gradient: {
      shade: 'light',
      type: "vertical",
      shadeIntensity: 0.5,
      inverseColors: true,
      opacityFrom: 0.8,
      opacityTo: 0.9,
      stops: [0, 70, 100]
    }
  },
  colors: ['#158156', '#5bb51c', '#1982c4', '#ffca3a'],
  xaxis: {
    tickAmount: 12,
    type: 'datetime',
    labels: {
      rotate: 0,
      style: {
        colors: '#888ea8',
      }
    }
  },
  yaxis: {
    max: 70,
    labels: {
      style: {
        colors: '#888ea8',
      }
    }
  },
  theme: {
    mode: 'light',
    palette: 'palette1',
    monochrome: {
      enabled: false,
      color: '#5bb51c',
      shadeTo: 'light',
      shadeIntensity: 0.65
    },
  },
  legend: {
    position: 'top',
    horizontalAlign: 'center',
    offsetY: 0,
    offsetX: 0,
    labels: {
      colors: '#888ea8',
    }
  },
  tooltip: {
    theme: 'dark',
    y: {
      formatter: function (val) {
        return val
      }
    }
  }
}

// Function to generate bubble data
function generateBubbleData(baseval, count, yrange) {
  var i = 0;
  var series = [];
  while (i < count) {
    // Randomize values for more visually appealing bubbles
    var x = Math.floor(Math.random() * (15 - 1 + 1)) + 1;
    var y = Math.floor(Math.random() * (yrange.max - yrange.min + 1)) + yrange.min;
    var z = Math.floor(Math.random() * (80 - 10 + 1)) + 10;

    series.push([baseval + x * 86400000, y, z]);
    i++;
  }
  return series;
}

// Initialize the chart
var chart = new ApexCharts(
  document.querySelector("#bubbleChart"),
  options
);

chart.render();