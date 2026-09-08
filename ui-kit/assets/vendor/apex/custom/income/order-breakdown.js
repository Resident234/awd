var breakdownOptions = {
  chart: {
    type: 'donut',
    height: 340,
    animations: {
      enabled: true,
      easing: 'easeinout',
      speed: 800,
      animateGradually: { enabled: true, delay: 150 }
    }
  },
  labels: ['Completed', 'Pending', 'Cancelled', 'Returned'],
  series: [10870, 890, 450, 220],
  colors: ['#5387e4', '#27ba89', '#f0c031', '#a855f7'],
  legend: {
    position: 'bottom',
    horizontalAlign: 'center',
    fontSize: '14px',
    labels: { colors: '#6b7280' },
    markers: { radius: 12, width: 12, height: 12 },
    itemMargin: { horizontal: 10 }
  },
  dataLabels: {
    enabled: true,
    dropShadow: { enabled: true, top: 1, left: 1, blur: 1, opacity: 0.3 },
    style: { fontSize: '13px', fontWeight: 600, colors: ['#FFFFFF'] },
    formatter: function (val, opts) {
      return val.toFixed(1) + "%";
    }
  },
  plotOptions: {
    pie: {
      donut: {
        size: '65%',
        labels: {
          show: true,
          name: {
            show: true,
            fontSize: '14px',
            color: '#6b7280',
            offsetY: -10
          },
          value: {
            show: true,
            fontSize: '18px',
            fontWeight: 600,
            color: '#000000',
            offsetY: 5,
            formatter: function (val) {
              return val.toLocaleString();
            }
          },
          total: {
            show: true,
            showAlways: true,
            label: 'Total Orders',
            fontSize: '16px',
            fontWeight: 600,
            color: '#000000',
            formatter: function (w) {
              return w.globals.seriesTotals.reduce((a, b) => a + b, 0).toLocaleString();
            }
          }
        }
      }
    }
  },
  stroke: {
    width: 2,
    colors: ['#fff']
  },
  tooltip: {
    theme: 'light',
    y: {
      formatter: function (val, { seriesIndex, w }) {
        const label = w.globals.labels[seriesIndex];
        return label + ": " + val.toLocaleString();
      }
    }
  }
};

var ordersBreakdownChart = new ApexCharts(
  document.querySelector("#ordersBreakdown"),
  breakdownOptions
);
ordersBreakdownChart.render();