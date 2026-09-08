// Sparkline 1
document.addEventListener('DOMContentLoaded', () => {
  const sparkline1 = document.getElementById('sparkline1');
  if (!sparkline1) return;

  const sparklineOptions = {
    series: [{
      data: [25, 66, 41, 89, 63, 25, 44, 12, 36, 9, 54]
    }],
    chart: {
      type: 'line',
      width: 100,
      height: 35,
      sparkline: {
        enabled: true
      },
      toolbar: {
        show: false
      }
    },
    colors: ['#05668d', '#028090', '#00a896', '#02c39a', '#e56b6f'],
    stroke: {
      curve: 'smooth',
      width: 2,
    },
    tooltip: {
      fixed: {
        enabled: false
      },
      x: {
        show: false
      },
      y: {
        title: {
          formatter: () => ''
        }
      },
      marker: {
        show: false
      }
    }
  };

  new ApexCharts(sparkline1, sparklineOptions).render();
});

// Sparkline 2
document.addEventListener('DOMContentLoaded', () => {
  const sparkline2 = document.getElementById('sparkline2');
  if (!sparkline2) return;

  const sparklineAreaOptions = {
    series: [{
      data: [31, 54, 35, 65, 42, 25, 44, 58, 36, 28, 47]
    }],
    chart: {
      type: 'area',
      width: 100,
      height: 35,
      sparkline: {
        enabled: true
      },
      toolbar: {
        show: false
      }
    },
    colors: ['#028090', '#00a896', '#02c39a', '#e56b6f'],
    stroke: {
      curve: 'smooth',
      width: 2,
    },
    fill: {
      opacity: 0.3,
    },
    tooltip: {
      fixed: {
        enabled: false
      },
      x: {
        show: false
      },
      y: {
        title: {
          formatter: () => ''
        }
      },
      marker: {
        show: false
      }
    }
  };

  new ApexCharts(sparkline2, sparklineAreaOptions).render();
});

// Sparkline 3 - Column
document.addEventListener('DOMContentLoaded', () => {
  const sparkline3 = document.getElementById('sparkline3');
  if (!sparkline3) return;

  const sparklineColumnOptions = {
    series: [{
      data: [25, 66, 41, 89, 63, 25, 44, 12, 36, 9, 54]
    }],
    chart: {
      type: 'bar',
      width: 100,
      height: 35,
      sparkline: {
        enabled: true
      },
      toolbar: {
        show: false
      }
    },
    colors: ['#FF9F43'],
    plotOptions: {
      bar: {
        columnWidth: '60%',
        borderRadius: 2
      }
    },
    tooltip: {
      fixed: {
        enabled: false
      },
      x: {
        show: false
      },
      y: {
        title: {
          formatter: () => ''
        }
      },
      marker: {
        show: false
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

  new ApexCharts(sparkline3, sparklineColumnOptions).render();
});

// Sparkline 4 - Bar
document.addEventListener('DOMContentLoaded', () => {
  const sparkline4 = document.getElementById('sparkline4');
  if (!sparkline4) return;

  const sparklineBarOptions = {
    series: [{
      data: [44, 55, 66, 36, 25, 19, 8]
    }],
    chart: {
      type: 'bar',
      width: 100,
      height: 35,
      sparkline: {
        enabled: true
      },
      toolbar: {
        show: false
      }
    },
    colors: ['#00a896', '#02c39a', '#e56b6f'],
    plotOptions: {
      bar: {
        horizontal: true,
        borderRadius: 2,
        columnWidth: '60%'
      }
    },
    tooltip: {
      fixed: {
        enabled: false
      },
      x: {
        show: false
      },
      y: {
        title: {
          formatter: () => ''
        }
      },
      marker: {
        show: false
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

  new ApexCharts(sparkline4, sparklineBarOptions).render();
});

// Sparkline 5 - Composite
document.addEventListener('DOMContentLoaded', () => {
  const sparkline5 = document.getElementById('sparkline5');
  if (!sparkline5) return;

  const sparklineCompositeOptions = {
    series: [{
      name: 'Line',
      type: 'line',
      data: [25, 35, 19, 44, 28, 55, 37]
    }, {
      name: 'Column',
      type: 'column',
      data: [30, 41, 22, 49, 33, 60, 42]
    }],
    chart: {
      width: 100,
      height: 35,
      sparkline: {
        enabled: true
      },
      toolbar: {
        show: false
      }
    },
    colors: ['#02c39a', '#e56b6f'],
    stroke: {
      curve: 'smooth',
      width: [2, 0],
    },
    plotOptions: {
      bar: {
        columnWidth: '60%',
        borderRadius: 2
      }
    },
    tooltip: {
      fixed: {
        enabled: false
      },
      x: {
        show: false
      },
      y: {
        title: {
          formatter: (seriesName) => seriesName
        }
      },
      marker: {
        show: false
      }
    }
  };

  new ApexCharts(sparkline5, sparklineCompositeOptions).render();
});

// Sparkline 6 - Tristate
document.addEventListener('DOMContentLoaded', () => {
  const sparkline6 = document.getElementById('sparkline6');
  if (!sparkline6) return;

  const sparklineTristateOptions = {
    series: [{
      data: [1, 2, -1, -2, 1, -1, 2, -2, 1, 2, 1]
    }],
    chart: {
      type: 'bar',
      width: 100,
      height: 35,
      sparkline: {
        enabled: true
      },
      toolbar: {
        show: false
      }
    },
    colors: ['#00a896', '#02c39a', '#e56b6f'],
    plotOptions: {
      bar: {
        columnWidth: '60%',
        borderRadius: 2,
        colors: {
          ranges: [
            {
              from: -10,
              to: 0,
              color: '#EA5455'
            },
            {
              from: 1,
              to: 10,
              color: '#28c76f'
            }
          ]
        }
      }
    },
    tooltip: {
      fixed: {
        enabled: false
      },
      x: {
        show: false
      },
      y: {
        title: {
          formatter: () => ''
        }
      },
      marker: {
        show: false
      }
    }
  };

  new ApexCharts(sparkline6, sparklineTristateOptions).render();
});

// Sparkline 7 - Pie
document.addEventListener('DOMContentLoaded', () => {
  const sparkline7 = document.getElementById('sparkline7');
  if (!sparkline7) return;

  const sparklinePieOptions = {
    series: [44, 55, 13, 33, 22],
    chart: {
      type: 'pie',
      width: 100,
      height: 35,
      sparkline: {
        enabled: true
      }
    },
    colors: ['#05668d', '#028090', '#00a896', '#02c39a', '#e56b6f'],
    stroke: {
      width: 0
    },
    tooltip: {
      fixed: {
        enabled: false
      },
      y: {
        title: {
          formatter: () => ''
        }
      },
      marker: {
        show: false
      }
    }
  };

  new ApexCharts(sparkline7, sparklinePieOptions).render();
});

// Sparkline 8 - Donut
document.addEventListener('DOMContentLoaded', () => {
  const sparkline8 = document.getElementById('sparkline8');
  if (!sparkline8) return;

  const sparklineDonutOptions = {
    series: [44, 55, 13, 33],
    chart: {
      type: 'donut',
      width: 100,
      height: 35,
      sparkline: {
        enabled: true
      }
    },
    colors: ['#05668d', '#028090', '#00a896', '#02c39a', '#e56b6f'],
    stroke: {
      width: 1
    },
    tooltip: {
      fixed: {
        enabled: false
      },
      y: {
        title: {
          formatter: () => ''
        }
      },
      marker: {
        show: false
      }
    }
  };

  new ApexCharts(sparkline8, sparklineDonutOptions).render();
});

// Sparkline 9 - Donut
document.addEventListener('DOMContentLoaded', () => {
  const sparkline9 = document.getElementById('sparkline9');
  if (!sparkline9) return;

  const sparklineDonutOptions = {
    series: [22, 67, 33, 40],
    chart: {
      type: 'donut',
      width: 100,
      height: 35,
      sparkline: {
        enabled: true
      }
    },
    colors: ['#05668d', '#028090', '#00a896', '#02c39a', '#e56b6f'],
    stroke: {
      width: 1
    },
    tooltip: {
      fixed: {
        enabled: false
      },
      y: {
        title: {
          formatter: () => ''
        }
      },
      marker: {
        show: false
      }
    }
  };

  new ApexCharts(sparkline9, sparklineDonutOptions).render();
});

// Sparkline 10 - Donut
document.addEventListener('DOMContentLoaded', () => {
  const sparkline10 = document.getElementById('sparkline10');
  if (!sparkline10) return;

  const sparklineDonutOptions = {
    series: [10, 20, 30, 40],
    chart: {
      type: 'donut',
      width: 100,
      height: 35,
      sparkline: {
        enabled: true
      }
    },
    colors: ['#05668d', '#028090', '#00a896', '#02c39a', '#e56b6f'],
    stroke: {
      width: 1
    },
    tooltip: {
      fixed: {
        enabled: false
      },
      y: {
        title: {
          formatter: () => ''
        }
      },
      marker: {
        show: false
      }
    }
  };

  new ApexCharts(sparkline10, sparklineDonutOptions).render();
});

// Sparkline 11 - Traffic Sources
document.addEventListener('DOMContentLoaded', () => {
  const sparkline11 = document.getElementById('sparkline11');
  if (!sparkline11) return;

  const trafficSourcesOptions = {
    series: [{
      data: [30, 45, 22, 67, 40, 28, 52, 63, 42, 38, 55]
    }],
    chart: {
      type: 'line',
      width: 160,
      height: 35,
      sparkline: {
        enabled: true
      },
      toolbar: {
        show: false
      }
    },
    colors: ['#e56b6f'],
    stroke: {
      curve: 'smooth',
      width: 3,
    },
    tooltip: {
      fixed: {
        enabled: false
      },
      x: {
        show: false
      },
      y: {
        title: {
          formatter: () => 'Traffic'
        }
      },
      marker: {
        show: false
      }
    }
  };

  new ApexCharts(sparkline11, trafficSourcesOptions).render();
});

// Sparkline 12 - Conversion Rates
document.addEventListener('DOMContentLoaded', () => {
  const sparkline12 = document.getElementById('sparkline12');
  if (!sparkline12) return;

  const conversionRatesOptions = {
    series: [{
      data: [25, 35, 45, 30, 55, 40, 60, 45, 50, 42, 57]
    }],
    chart: {
      type: 'area',
      width: 160,
      height: 35,
      sparkline: {
        enabled: true
      },
      toolbar: {
        show: false
      }
    },
    colors: ['#00a896', '#02c39a', '#e56b6f'],
    stroke: {
      curve: 'smooth',
      width: 2,
    },
    fill: {
      opacity: 0.3,
    },
    tooltip: {
      fixed: {
        enabled: false
      },
      x: {
        show: false
      },
      y: {
        title: {
          formatter: () => 'Conversion'
        }
      },
      marker: {
        show: false
      }
    }
  };

  new ApexCharts(sparkline12, conversionRatesOptions).render();
});