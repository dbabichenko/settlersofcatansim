var width = 960,
    height = 500;

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


var g = svg.append('g');

var simulation = d3.forceSimulation()
    .force("link", d3.forceLink().id(function(d) { return d.id; }))
    .force("x", d3.forceX(center_x))
    .force("y", d3.forceY(center_y));

var link = svg.selectAll(".link"),
    node = svg.selectAll(".node");

var line_centerNode = {1:4, 2:5, 3:6, 4:6, 5:5, 6:4};
var nodes = [];

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

//load MapData JSON file & paint each tile
d3.json("js/MapData.json", function(error, mapData) {

    if(error) throw error;
    
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
            .style('stroke', "black")
            .style('stroke-width', 1);
    
    node = node
        .data(nodes)
        .enter().append("circle")
            .attr("class", "node")
            .attr('r', function(d) {if(d.isCity) return 6; else return 3;})
            .attr("cx", function(d) { return d.x; })
            .attr("cy", function(d) { return d.y; })
            .style('stroke', "black")
            .style('fill', function(d) { return d.color; });
    
});
