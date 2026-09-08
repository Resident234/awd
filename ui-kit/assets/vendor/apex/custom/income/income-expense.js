$(function () {
  var incomeExpenseOptions = {
    chart: {
      height: 293,
      type: 'line',
      stacked: false,
      toolbar: { show: false },
      animations: {
        enabled: true,
        easing: 'easeinout',
        speed: 800,
        animateGradually: { enabled: true, delay: 150 }
      }
    },
    colors: ['#22c55e', '#3b82f6'], // Income - green, Expense - blue
    dataLabels: { enabled: false },
    stroke: {
      width: [3, 0],
      curve: 'smooth',
      lineCap: 'round'
    },
    series: [
      {
        name: 'Income',
        type: 'area',
        data: [45000, 52000, 58000, 61000, 68000, 72000, 80000]
      },
      {
        name: 'Expenses',
        type: 'column',
        data: [30000, 35000, 40000, 42000, 46000, 50000, 55000]
      }
    ],
    xaxis: {
      categories: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
      axisBorder: { show: false },
      axisTicks: { show: false },
      labels: {
        style: {
          colors: '#6b7280', // gray-500
          fontSize: '13px',
          fontWeight: 500
        }
      }
    },
    yaxis: {
      labels: {
        style: { colors: '#6b7280', fontSize: '12px' },
        formatter: function (val) {
          return "$" + val.toLocaleString();
        }
      }
    },
    legend: {
      position: 'bottom',
      horizontalAlign: 'center',
      fontSize: '13px',
      labels: { colors: '#374151' },
      markers: { radius: 12 },
      itemMargin: { horizontal: 8 }
    },
    plotOptions: {
      bar: {
        borderRadius: 8,
        columnWidth: '40%',
        endingShape: 'rounded'
      }
    },
    fill: {
      opacity: [0.35, 0.95],
      gradient: {
        shade: 'light',
        type: 'vertical',
        shadeIntensity: 0.4,
        opacityFrom: 0.5,
        opacityTo: 0.05,
        stops: [0, 100]
      }
    },
    grid: {
      borderColor: '#e5e7eb',
      strokeDashArray: 4,
      xaxis: { lines: { show: false } },
      yaxis: { lines: { show: true } }
    },
    tooltip: {
      theme: 'light',
      shared: true,
      intersect: false,
      style: { fontSize: '13px' },
      y: {
        formatter: function (val) {
          return "$" + val.toLocaleString();
        }
      }
    },
    markers: {
      size: 4,
      colors: ['#22c55e'],
      strokeColors: '#fff',
      strokeWidth: 2,
      hover: { size: 6 }
    }
  };

  var incomeExpenseChart = new ApexCharts(
    document.querySelector("#incomeExpenses"),
    incomeExpenseOptions
  );
  incomeExpenseChart.render();
});
