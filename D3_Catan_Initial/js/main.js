var width = 960,
    height = 500;

svg = d3.select("body")
      .append("svg")
      .attr("width", width)
      .attr("height", height);

//hexagon parameters
var h = (Math.sqrt(3) / 2),
    radius = 40,
    center_x = width / 2,
    center_y = height / 2;

var drawHexagon = d3.svg.line()
        .x(function(d) { return d.x; })
        .y(function(d) { return d.y; });


var resource_color_map = {"Brick": "#a54b4b", 
                         "Lumber": "#2F723E",
                         "Ore": "#7E7E7E",
                         "Grain": "#F6BD43",
                         "Wool": "#57B741",
                         "Nothing": "#CAC186"};

//(xn, yn, zn) is the coordinate of every tile
function tile_paint(xn, yn, zn, value, resource) {

    var xp = center_x + (xn - yn) * radius,
        yp = center_y + 1.5 * radius / h * zn,

    textData = [{"x": xp, "y": yp}];

    hexagonData = [
        { "x": radius + xp, "y": radius / 2+yp}, 
        { "x": xp, "y": radius / h+yp},
        { "x": -radius + xp, "y": radius / 2+yp},
        { "x": -radius + xp, "y": -radius / 2+yp},
        { "x": xp, "y": -radius / h+yp},
        { "x": radius + xp, "y": -radius / 2+yp},
        { "x": radius + xp, "y": radius / 2+yp}
    ];

    //pack shape and text together
    var g = svg.append("g");

    //Hexagon shape
    hexagon = g.append("path")
                    .attr("d", drawHexagon(hexagonData))
                    .attr("fill", resource_color_map[resource])
                    .attr("stroke", "grey")
                    .attr("stroke-width", "2");

    //the coordinate value
    text = g.selectAll("text")
                .data(textData)
                .enter()
                .append("text");

    textLabels = text
                .attr("x", function(d) {return d.x; })
                .attr("y", function(d) {return d.y; })
                .text( function (d) { return value; })
                .attr("font-family", "sans-serif")
                .attr("text-anchor", "middle")
                .attr("font-size", "17px")
                .attr("fill", "black");
}

//load MapData JSON file & paint each tile
d3.json("js/MapData.json", function(error, mapData) {
    tiles = mapData.tiles;
    for(var i=0; i<tiles.length; i++) {
        tile_paint(tiles[i].x, tiles[i].y, tiles[i].z, tiles[i].coordinates, tiles[i].resourceType);
    }
});

