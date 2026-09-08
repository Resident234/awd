$(function () {
  var paymentOptions = {
    chart: {
      type: 'bar',
      height: 293,
      toolbar: { show: false },
      animations: {
        enabled: true,
        easing: 'easeinout',
        speed: 800
      }
    },
    plotOptions: {
      bar: {
        borderRadius: 8,
        columnWidth: '45%',
        distributed: true,
        dataLabels: {
          position: 'top'
        }
      }
    },
    series: [{
      name: 'Share',
      data: [60, 22, 12, 6]
    }],
    xaxis: {
      categories: ['UPI', 'Card', 'COD', 'Wallets'],
      axisBorder: { show: false },
      axisTicks: { show: false },
      labels: {
        style: {
          colors: ['#6c757d', '#6c757d', '#6c757d', '#6c757d'],
          fontSize: '13px',
          fontWeight: 500
        }
      }
    },
    yaxis: {
      labels: {
        style: { colors: '#9ca3af', fontSize: '12px' },
        formatter: function (val) {
          return val + "%";
        }
      }
    },
    colors: [
      '#5387e4', '#27ba89', '#f0c031', '#a855f7'
    ],
    dataLabels: {
      enabled: true,
      offsetY: 5,
      formatter: function (val) {
        return val + "%";
      },
      style: {
        fontSize: '13px',
        fontWeight: 600,
        colors: ['#FFF']
      }
    },
    tooltip: {
      theme: 'light',
      y: {
        formatter: function (val, { seriesIndex, dataPointIndex, w }) {
          const category = w.globals.labels[dataPointIndex];
          return category + ": " + val + "% Share";
        }
      }
    },
    grid: {
      borderColor: '#e5e7eb',
      strokeDashArray: 4,
      xaxis: { lines: { show: false } },
      yaxis: { lines: { show: true } }
    },
    legend: { show: false }
  };

  var paymentMethodsChart = new ApexCharts(
    document.querySelector("#paymentMethods"),
    paymentOptions
  );
  paymentMethodsChart.render();
});