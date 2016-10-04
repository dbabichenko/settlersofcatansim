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
function tile_paint(xn, yn, zn, value, resource, robber) {

    var xp = center_x + (xn - yn) * radius,
        yp = center_y + 1.5 * radius / h * zn,

    textData = [{"x": xp, "y": yp+5}];

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
                    .attr("fill", resource_color_map[resource]);
    
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
            .attr("font-size", "20px")
            .attr("fill", function(d) {if(robber) return "black"; else return "white"});
        
}

//center node for every line. 
//e.g. for the first line 104 is the center node
var line_centerNode = {1:4, 2:5, 3:6, 4:6, 5:5, 6:4};
var nodes = [],
    links = {source:[], target:[]};

//add (x, y) coordinate to each settlement
function generate_nodes(settleId, isActive, isCity, color) {
    var i = (settleId/100) | 0,
        j = settleId%100;
    
    //calculate x
    var xn = radius * (j - line_centerNode[i]) + center_x;
    
    //calculate y
    var a = 1.5 * (i - 3.5) + 0.25;
    if(i>3.5&&j%2!=0 || i<3.5&&j%2==0) {
        a = 1.5 * (i - 3.5) - 0.25;
    }
    
    var yn = center_y + a * radius/h;

    var n_object = {"id": settleId, "x": xn, "y": yn, "isActive": isActive, "isCity": isCity, "color": color};
    
    nodes.push(n_object);
}

//load MapData JSON file & paint each tile
d3.json("js/MapData.json", function(error, mapData) {
//    tiles = mapData.tiles;
//    for(var i=0; i<tiles.length; i++) {
//        tile_paint(tiles[i].x, tiles[i].y, tiles[i].z, tiles[i].coordinates, tiles[i].resourceType, tiles[i].hasRobber);
//    }
    
    settlements = mapData.settlements;
    for(var j=0; j<settlements.length; j++) {
        generate_nodes(settlements[j].settle_id, settlements[j].isActive, settlements[j].isCity, settlements[j].color);
    }
    
    //transfer id to index
    mapData.roads.forEach(function(e) {
        var sourceN = nodes.filter(function(n) {return n.id === e.source; })[0],
            targetN = nodes.filter(function(n) {return n.id === e.target; })[0];
        
        links.source.push(sourceN);
        links.target.push(targetN);

    });
    
    var force = d3.layout.force()
        .size([width, height])
        .nodes(nodes)
        .links(links)
        .linkDistance(radius/h);

    //node
    var node = svg.selectAll('.node')
        .data(nodes)
        .enter().append('circle')
        .attr('class', 'node')
        .attr('r', function(d) {if(d.isCity) return 6; else return 3;})
        .attr('cx', function(d) { return d.x; })
        .attr('cy', function(d) { return d.y; })
        .style('stroke', "grey")
        .style('fill', function(d) {
           if(d.isActive) {
               return d.color;
           } else {
               return "none";
           }
        });
    
    //link: 72 roads
    var link = svg.selectAll('.link')
        .data(links)
        .enter().append('line')
        .attr('class', 'link')
        .attr('x1', function(d,i) { return nodes[d.source[i].index].x; })
        .attr('y1', function(d,i) { return nodes[d.source[i].index].y; })
        .attr('x2', function(d,i) { return nodes[d.target[i].index].x; })
        .attr('y2', function(d,i) { return nodes[d.target[i].index].y; })
        .style('stroke', "black")
        .style('stroke-width', 3);
    
    force.start();
    
    
});
