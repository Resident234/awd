var options = {
  series: [
    {
      name: "Funnel Series",
      data: [1380, 1100, 990, 880, 740, 548, 330, 200],
    },
  ],
  chart: {
    type: 'bar',
    height: 350,
    dropShadow: {
      enabled: true,
      color: '#000',
      opacity: 0.1,
      blur: 4
    },
  },
  colors: ['#b5e48c', '#99d98c', '#76c893', '#52b69a', '#34a0a4', '#168aad', '#1a759f', '#1e6091'],
  plotOptions: {
    bar: {
      borderRadius: 0,
      horizontal: true,
      barHeight: '80%',
      isFunnel: true,
      distributed: true,
    },
  },
  dataLabels: {
    enabled: true,
    formatter: function (val, opt) {
      return opt.w.globals.labels[opt.dataPointIndex] + ':  ' + val
    },
    style: {
      fontSize: '12px',
      fontWeight: 600,
      colors: ["#fff"]
    },
    dropShadow: {
      enabled: true,
      opacity: 0.8,
    },
  },
  title: {
    text: 'Recruitment Funnel',
    align: 'middle',
  },
  xaxis: {
    categories: [
      'Sourced',
      'Screened',
      'Assessed',
      'HR Interview',
      'Technical',
      'Verify',
      'Offered',
      'Hired',
    ],
  },
  legend: {
    show: false,
  },
  theme: {
    mode: 'light',
    monochrome: {
      enabled: false
    },
  }
};

var chart = new ApexCharts(document.querySelector("#funnelChart"), options);
chart.render();



var pyramidOptions = {
  series: [
    {
      name: "Food Group",
      data: [200, 330, 548, 740, 880, 990, 1100, 1380],
    },
  ],
  chart: {
    type: 'bar',
    height: 350,
    fontFamily: 'Nunito, sans-serif',
    dropShadow: {
      enabled: true,
      opacity: 0.1,
      blur: 4,
      color: '#000'
    },
  },
  plotOptions: {
    bar: {
      borderRadius: 0,
      horizontal: true,
      distributed: true,
      barHeight: '80%',
      isFunnel: true,
    },
  },
  colors: [
    '#b5e48c', '#99d98c', '#76c893', '#52b69a', '#34a0a4', '#168aad', '#1a759f', '#1e6091'
  ],
  dataLabels: {
    enabled: true,
    formatter: function (val, opt) {
      return opt.w.globals.labels[opt.dataPointIndex] + ': ' + val;
    },
    style: {
      fontSize: '12px',
      fontWeight: 600,
      colors: ["#fff"]
    },
    dropShadow: {
      enabled: true,
      opacity: 0.8,
    },
  },
  title: {
    text: 'Food Pyramid Chart',
    align: 'center',
    style: {
      fontSize: '18px',
      fontWeight: 'bold'
    }
  },
  xaxis: {
    categories: ['Sweets', 'Processed Foods', 'Healthy Fats', 'Meat', 'Beans & Legumes', 'Dairy', 'Fruits & Vegetables', 'Grains'],
  },
  legend: {
    show: false,
  },
  theme: {
    mode: 'light',
  }
};

var pyramidChart = new ApexCharts(document.querySelector("#pyramidChart"), pyramidOptions);
pyramidChart.render();