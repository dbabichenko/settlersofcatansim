var width = 960,
    height = 600;

//hexagon parameters
var h = (Math.sqrt(3) / 2),
    radius = 40,
    center_x = width / 2,
    center_y = height / 2;


//DOM init
svg = d3.select("body")
      .append("svg")
      .attr("width", width)
      .attr("height", height);


var g = svg.append('g')
            .attr('class', 'link-node');

var g_links = g.append('g').attr('id', 'links'),
    g_nodes = g.append('g').attr('id', 'nodes');

var simulation = d3.forceSimulation()
    .force("link", d3.forceLink().id(function(d) { return d.id; }))
    .force("x", d3.forceX(center_x))
    .force("y", d3.forceY(center_y));

var link = g_links.selectAll(".link"),
    node = g_nodes.selectAll(".node");

var line_centerNode = {1:4, 2:5, 3:6, 4:6, 5:5, 6:4};
var nodes = [];

var drawHexagon = d3.line()
        .x(function(d) { return d.x; })
        .y(function(d) { return d.y; });


var resource_color_map = {"Brick": "#a54b4b", 
                         "Lumber": "#2F723E",
                         "Ore": "#7E7E7E",
                         "Grain": "#F6BD43",
                         "Wool": "#57B741",
                         "Nothing": "#CAC186",
                         "Ocean": "#5c98ff"};


//load MapData JSON file & paint each tile
d3.json("js/MapData.json", function(error, mapData) {

    if(error) throw error;
    
    // ================ Nodes & Links ================
    //get nodes data
    settlements = mapData.settlements;
    for(var j=0; j<settlements.length; j++) {
        generate_nodes(settlements[j].id, settlements[j].isCity, settlements[j].color);
    }
    
    links = mapData.roads;
    
    simulation.nodes(nodes);
    simulation.force("link").links(links);
    
    link = link
        .data(links)
        .enter().append("line")
            .attr("class", "link")
            .attr("x1", function(d) { return d.source.x; })
            .attr("y1", function(d) { return d.source.y; })
            .attr("x2", function(d) { return d.target.x; })
            .attr("y2", function(d) { return d.target.y; })
            .style('stroke', function(d) { return d.color; })
            .style('stroke-width', 3);
    
    node = node
        .data(nodes)
        .enter().append("circle")
            .attr("class", "node")
            .attr('r', function(d) {if(d.isCity) return 6; else return 3;})
            .attr("cx", function(d) { return d.x; })
            .attr("cy", function(d) { return d.y; })
            .style('stroke', "black")
            .style('fill', function(d) { return d.color; });
    
    // ================ Tiles ================
    tiles = mapData.tiles;
    for(var i=0; i<tiles.length; i++) {
        tile_paint(tiles[i].x, tiles[i].y, tiles[i].z, tiles[i].diceValue, tiles[i].resourceType, tiles[i].hasRobber);
    }
    
});

//add (x, y) coordinate to each settlement
function generate_nodes(settleId, isCity, color) {
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

    var n_object = {"id": settleId, "x": xn, "y": yn, "isCity": isCity, "color": color};
    
    nodes.push(n_object);
}

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
                    .attr("fill", resource_color_map[resource])
                    .attr("stroke", "grey")
                    .attr("stroke-width", 1);
    
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