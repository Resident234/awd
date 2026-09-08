var chartOptions = {
  chart: {
    type: 'area',
    height: 320,
    toolbar: { show: false },
    zoom: { enabled: false }
  },
  dataLabels: { enabled: false },
  stroke: { curve: 'smooth', width: 3 },
  series: [
    { name: 'Orders', data: [50, 60, 55, 70, 90, 85, 100] },
    { name: 'Revenue', data: [12000, 15000, 14000, 18000, 20000, 22000, 25000] }
  ],
  xaxis: {
    categories: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
    labels: {
      style: { colors: '#6c757d', fontSize: '12px' }
    },
    axisBorder: { show: false },
    axisTicks: { show: false }
  },
  yaxis: {
    labels: {
      style: { colors: '#6c757d', fontSize: '12px' }
    }
  },
  grid: {
    borderColor: '#e5e7eb',
    strokeDashArray: 4,
    xaxis: { lines: { show: false } },
    yaxis: { lines: { show: true } },
    padding: { left: 10, right: 10 }
  },
  colors: ['#5387e4', '#27ba89'],
  fill: {
    type: "gradient",
    gradient: {
      shadeIntensity: 1,
      opacityFrom: 0.5,
      opacityTo: 0.05,
      stops: [0, 90, 100]
    }
  },
  markers: {
    size: 5,
    strokeColors: "#fff",
    strokeWidth: 3,
    hover: { size: 7 }
  },
  tooltip: {
    theme: "light",
    y: {
      formatter: function (val, opts) {
        return opts.seriesIndex === 1 ? "$  " + val.toLocaleString() : val + " Orders";
      }
    }
  },
  legend: {
    position: 'top',
    horizontalAlign: 'right',
    fontSize: '13px',
    labels: { colors: '#495057' },
    markers: { width: 10, height: 10, radius: 12 }
  }
};

var orderRevenueChart = new ApexCharts(document.querySelector("#orderRevenueChart"), chartOptions);
orderRevenueChart.render();


// Toggle Data
const chartData = {
  week: {
    orders: [50, 60, 55, 70, 90, 85, 100],
    revenue: [12000, 15000, 14000, 18000, 20000, 22000, 25000],
    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
  },
  month: {
    orders: [450, 470, 490, 520, 540, 600, 640, 700, 750, 780, 800, 850],
    revenue: [120000, 135000, 140000, 150000, 160000, 175000, 190000, 210000, 220000, 230000, 240000, 260000],
    labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5', 'Week 6', 'Week 7', 'Week 8', 'Week 9', 'Week 10', 'Week 11', 'Week 12']
  },
  year: {
    orders: [3000, 3200, 3500, 4000, 4200, 4500, 4800, 5000, 5200, 5500, 5800, 6000],
    revenue: [1200000, 1300000, 1350000, 1500000, 1550000, 1600000, 1700000, 1800000, 1900000, 2000000, 2100000, 2200000],
    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
  }
};

document.querySelectorAll('[data-period]').forEach(btn => {
  btn.addEventListener('click', function () {
    document.querySelectorAll('[data-period]').forEach(b => b.classList.remove('active'));
    this.classList.add('active');
    let period = this.dataset.period;
    orderRevenueChart.updateOptions({
      series: [
        { name: 'Orders', data: chartData[period].orders },
        { name: 'Revenue', data: chartData[period].revenue }
      ],
      xaxis: { categories: chartData[period].labels }
    });
  });
});