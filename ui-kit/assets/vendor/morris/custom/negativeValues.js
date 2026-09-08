// Morris Negative values
var neg_data = [
  { period: "2017-02-12", a: 100 },
  { period: "2017-01-03", a: 75 },
  { period: "2016-08-08", a: 50 },
  { period: "2016-05-10", a: 25 },
  { period: "2016-03-14", a: 0 },
  { period: "2016-01-10", a: -25 },
  { period: "2005-12-10", a: -50 },
  { period: "2005-10-07", a: -75 },
  { period: "2005-09-25", a: -100 },
];
Morris.Line({
  element: "negativeValues",
  data: neg_data,
  xkey: "period",
  ykeys: ["a"],
  labels: ["Series A"],
  units: "%",
  resize: true,
  hideHover: "auto",
  gridLineColor: "#dfd6ff",
  pointFillColors: ["#ffffff"],
  pointStrokeColors: [
    "#5387e4", "#27ba89", "#ff7d5c", "#fbcc41", "#64c1fe", "#8a94a4", "#9c6ade"
  ],
  lineColors: [
    "#5387e4", "#27ba89", "#ff7d5c", "#fbcc41", "#64c1fe", "#8a94a4", "#9c6ade"
  ],
});
