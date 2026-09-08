document.addEventListener('DOMContentLoaded', function () {
  const basicRadarOptions = {
    series: [{
      name: 'Series 1',
      data: [80, 50, 30, 40, 100, 20],
    }],
    chart: {
      height: 350,
      type: 'radar',
      toolbar: {
        show: false
      }
    },
    colors: ['#05668d', '#028090', '#00a896', '#02c39a', '#e56b6f'],
    xaxis: {
      categories: ['January', 'February', 'March', 'April', 'May', 'June']
    },
    yaxis: {
      show: false
    },
    plotOptions: {
      radar: {
        polygons: {
          strokeColors: '#e9e9e9',
          fill: {
            colors: ['#f8f8f8', '#fff']
          }
        }
      }
    },
    stroke: {
      width: 2
    },
    fill: {
      opacity: 0.1
    },
    markers: {
      size: 4
    }
  };

  const basicRadarChart = new ApexCharts(
    document.querySelector("#basicRadarChart"),
    basicRadarOptions
  );

  basicRadarChart.render();
});

document.addEventListener('DOMContentLoaded', function () {
  const polarRadarOptions = {
    series: [{
      name: 'Series 1',
      data: [20, 45, 75, 35, 60, 40],
    }, {
      name: 'Series 2',
      data: [35, 70, 30, 50, 25, 55],
    }],
    chart: {
      height: 350,
      type: 'radar',
      toolbar: {
        show: false
      }
    },
    colors: ['#05668d', '#028090', '#00a896', '#02c39a', '#e56b6f'],
    xaxis: {
      categories: ['January', 'February', 'March', 'April', 'May', 'June']
    },
    yaxis: {
      show: false
    },
    plotOptions: {
      radar: {
        size: 140,
        polygons: {
          strokeColors: '#e9e9e9',
          fill: {
            colors: ['#f8f8f8', '#fff']
          }
        }
      }
    },
    stroke: {
      width: 2
    },
    fill: {
      opacity: 0.1
    },
    markers: {
      size: 4
    },
    legend: {
      position: 'bottom'
    }
  };

  const polarRadarChart = new ApexCharts(
    document.querySelector("#polarRadarChart"),
    polarRadarOptions
  );

  polarRadarChart.render();
});

document.addEventListener('DOMContentLoaded', function () {
  // Multi Radar Chart Configuration
  const multiRadarOptions = {
    series: [
      {
        name: 'Product A',
        data: [45, 52, 38, 24, 33, 65],
      },
      {
        name: 'Product B',
        data: [56, 41, 64, 35, 28, 47],
      },
      {
        name: 'Product C',
        data: [30, 60, 42, 58, 50, 40],
      }
    ],
    chart: {
      height: 350,
      type: 'radar',
      toolbar: {
        show: false
      },
      dropShadow: {
        enabled: true,
        blur: 2,
        left: 1,
        top: 1,
        opacity: 0.2
      },
      animations: {
        enabled: true,
        easing: 'easeinout',
        speed: 800,
      }
    },
    colors: ['#05668d', '#028090', '#00a896', '#02c39a', '#e56b6f'],
    xaxis: {
      categories: ['January', 'February', 'March', 'April', 'May', 'June'],
      labels: {
        style: {
          fontSize: '12px',
          fontWeight: 500
        }
      }
    },
    yaxis: {
      show: false
    },
    plotOptions: {
      radar: {
        polygons: {
          strokeColors: '#e9e9e9',
          strokeWidth: 1,
          connectorColors: '#e9e9e9',
          fill: {
            colors: ['#f8f8f8', '#fff']
          }
        }
      }
    },
    stroke: {
      width: 2,
      curve: 'smooth'
    },
    fill: {
      opacity: 0.2
    },
    markers: {
      size: 4,
      hover: {
        size: 6
      }
    },
    legend: {
      position: 'bottom',
      horizontalAlign: 'center',
      fontWeight: 500,
      markers: {
        width: 12,
        height: 12,
        radius: 6
      }
    },
    tooltip: {
      shared: true,
      intersect: false,
      y: {
        formatter: function (value) {
          return value.toFixed(0);
        }
      }
    },
    responsive: [
      {
        breakpoint: 768,
        options: {
          chart: {
            height: 300
          },
          markers: {
            size: 3
          }
        }
      }
    ]
  };

  // Initialize and render the chart
  const multiRadarChart = new ApexCharts(
    document.querySelector("#multiRadarChart"),
    multiRadarOptions
  );

  multiRadarChart.render();
});

document.addEventListener('DOMContentLoaded', function () {
  // Polygon Radar Chart Configuration
  const polygonRadarOptions = {
    series: [
      {
        name: 'Performance',
        data: [70, 85, 60, 80, 55, 90, 75]
      }
    ],
    chart: {
      height: 350,
      type: 'radar',
      toolbar: {
        show: false
      }
    },
    colors: ['#8B5CF6'],
    xaxis: {
      categories: ['Speed', 'Reliability', 'Comfort', 'Safety', 'Efficiency', 'Design', 'Quality']
    },
    yaxis: {
      show: false
    },
    plotOptions: {
      radar: {
        polygons: {
          strokeColors: '#e9e9e9',
          strokeWidth: 1,
          connectorColors: '#e9e9e9',
          fill: {
            colors: ['#05668d', '#028090', '#00a896', '#02c39a', '#e56b6f']
          }
        }
      }
    },
    stroke: {
      width: 3
    },
    fill: {
      opacity: 0.3
    },
    markers: {
      size: 5,
      shape: 'circle',
      hover: {
        size: 8
      }
    },
    tooltip: {
      y: {
        formatter: function (val) {
          return val + '%';
        }
      }
    }
  };

  // Initialize and render the chart
  const polygonRadarChart = new ApexCharts(
    document.querySelector("#polygonRadarChart"),
    polygonRadarOptions
  );

  polygonRadarChart.render();
});

document.addEventListener('DOMContentLoaded', function () {
  // Colored Radar Chart Configuration
  const coloredRadarOptions = {
    series: [
      {
        name: 'Performance Score',
        data: [80, 65, 90, 75, 85, 70, 60]
      }
    ],
    chart: {
      height: 350,
      type: 'radar',
      toolbar: {
        show: false
      },
      background: '#f8f9fa'
    },
    colors: ['#EF4444'],
    xaxis: {
      categories: ['Technology', 'Marketing', 'Sales', 'Customer Service', 'Operations', 'Finance', 'HR'],
      labels: {
        style: {
          colors: ['#05668d', '#028090', '#00a896', '#02c39a', '#e56b6f', '#8B5CF6', '#F59E0B'],
          fontWeight: 600
        }
      }
    },
    yaxis: {
      show: false
    },
    plotOptions: {
      radar: {
        polygons: {
          strokeColors: '#e9e9e9',
          strokeWidth: 1,
          connectorColors: '#e9e9e9',
          fill: {
            colors: undefined
          }
        }
      }
    },
    stroke: {
      width: 3
    },
    fill: {
      opacity: 0.2
    },
    markers: {
      size: 5,
      colors: ['#EF4444'],
      strokeColor: '#fff',
      strokeWidth: 2
    },
    tooltip: {
      y: {
        formatter: function (val) {
          return val + '/100';
        }
      }
    },
    dataLabels: {
      enabled: true,
      background: {
        enabled: true,
        borderRadius: 2
      }
    }
  };

  // Initialize and render the chart
  const coloredRadarChart = new ApexCharts(
    document.querySelector("#coloredRadarChart"),
    coloredRadarOptions
  );

  coloredRadarChart.render();
});

document.addEventListener('DOMContentLoaded', function () {
  // Advanced Marker Radar Chart Configuration
  const markerRadarOptions = {
    series: [
      {
        name: 'Weekly Traffic',
        data: [45, 67, 89, 34, 56, 72, 51]
      },
      {
        name: 'Conversions',
        data: [78, 32, 45, 67, 83, 49, 60]
      }
    ],
    chart: {
      height: 350,
      type: 'radar',
      toolbar: {
        show: false
      },
      dropShadow: {
        enabled: true,
        blur: 3,
        left: 1,
        top: 1,
        opacity: 0.15
      },
      animations: {
        enabled: true,
        easing: 'easeinout',
        speed: 800
      }
    },
    colors: ['#05668d', '#028090', '#00a896', '#02c39a', '#e56b6f', '#8B5CF6', '#F59E0B'],
    xaxis: {
      categories: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
      labels: {
        style: {
          fontSize: '12px',
          fontWeight: 500
        }
      }
    },
    yaxis: {
      show: false
    },
    plotOptions: {
      radar: {
        polygons: {
          strokeColors: '#e9e9e9',
          strokeWidth: 1,
          fill: {
            colors: ['#f8f8f8', '#fff']
          }
        }
      }
    },
    stroke: {
      width: 2,
      curve: 'smooth'
    },
    fill: {
      opacity: 0.2
    },
    markers: {
      size: 6,
      strokeWidth: 2,
      hover: {
        size: 9,
        sizeOffset: 3
      },
      shape: 'circle'
    },
    legend: {
      position: 'bottom',
      horizontalAlign: 'center',
      offsetY: 5,
      fontWeight: 500
    },
    tooltip: {
      shared: true,
      intersect: false,
      y: {
        formatter: function (val) {
          return val + ' units';
        }
      }
    },
    responsive: [
      {
        breakpoint: 768,
        options: {
          chart: {
            height: 300
          },
          markers: {
            size: 4
          }
        }
      }
    ]
  };

  // Initialize and render the chart
  const markerRadarChart = new ApexCharts(
    document.querySelector("#markerRadarChart"),
    markerRadarOptions
  );

  markerRadarChart.render();
});
