var options = {
  chart: {
    type: 'donut',
    height: 182
  },
  labels: ['Web', 'App', 'Others'],
  series: [10250, 9580, 4750],
  colors: ['#5387e4', '#ff7d5c', '#27ba89'],
  dataLabels: {
    enabled: false
  },
  legend: {
    show: false
  },
  stroke: {
    width: 0
  },
  tooltip: {
    y: {
      formatter: function (val) {
        return "$" + val;
      }
    }
  }
};

var chart = new ApexCharts(document.querySelector("#earnings-chart"), options);
chart.render();