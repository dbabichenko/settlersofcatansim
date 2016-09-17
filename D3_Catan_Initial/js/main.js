var width = 960,
    height = 700,
    radius = Math.min(width, height) / 2;

var svg = d3.select("body").append("svg")
    .attr("width", width)
    .attr("height", height);

var g = svg.append("g");

var circle = g.append("circle")
						.attr("cx", 250)
						.attr("cy", 250)
						.attr("r", 50)
						.attr("fill", "yellow");

function distribute_colores(n) {
  var colores = ["#dc3910", "#ff9900", "#2F723E", "#6CE15A", "#C3C3C3", "#CAC186"];
  return colores[n % colores.length];
}