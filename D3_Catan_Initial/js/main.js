var width = 960,
    height = 600;

//hexagon parameters
var h = (Math.sqrt(3) / 2),
    radius = 40,
    center_x = width / 2,
    center_y = height / 2;

var rec_width = 12,
    rec_length = 12,
    node_radius = 5.5;


//DOM init
svg = d3.select("body")
      .append("svg")
      .attr("width", width)
      .attr("height", height);

//data for generating nodes
var line_centerNode = {1:4, 2:5, 3:6, 4:6, 5:5, 6:4};
var nodes = [],
    ports = [],
    points = []; //points: port triangle points

//data for generating hexagons-tiles
var drawHexagon = d3.line()
        .x(function(d) { return d.x; })
        .y(function(d) { return d.y; });


var resource_color_map = {"Brick": "#a54b4b", 
                         "Lumber": "#2F723E",
                         "Ore": "#7E7E7E",
                         "Grain": "#F6BD43",
                         "Wool": "#57B741",
                         "Nothing": "#CAC186",
                         "Ocean": "#5c98ff",
                         "General": "#c77978"};


//load MapData JSON file & paint each tile
d3.json("js/MapData.json", function(error, mapData) {

    if(error) throw error;
    
    // To make Tile part in the back, nodes & links part in the front, paint tiles firstly
    // ================ Tiles ================
    tiles = mapData.tiles;
    for(var i=0; i<tiles.length; i++) {
        tile_paint(tiles[i].x, tiles[i].y, tiles[i].z, tiles[i].diceValue, tiles[i].resourceType, tiles[i].hasRobber);
    }
 
    //get nodes data
    settlements = mapData.settlements;
    for(var j=0; j<settlements.length; j++) {
        generate_nodes(settlements[j].id, settlements[j].isCity, settlements[j].color, settlements[j].portType);
    }
    
    links = mapData.roads;

    // ================ Port ==================
    points.push({"x1": nodes[0].x, "y1": nodes[0].y, "x2": nodes[1].x, "y2": nodes[1].y, "x3": center_x-3*radius, "y3": center_y-4.5*radius/h, "type": nodes[0].portType});
    points.push({"x1": nodes[3].x, "y1": nodes[3].y, "x2": nodes[4].x, "y2": nodes[4].y, "x3": center_x+radius, "y3": center_y-4.5*radius/h, "type": nodes[3].portType});
    points.push({"x1": nodes[14].x, "y1": nodes[14].y, "x2": nodes[15].x, "y2": nodes[15].y, "x3": center_x+4*radius, "y3": center_y-3*radius/h, "type": nodes[14].portType});
    points.push({"x1": nodes[7].x, "y1": nodes[7].y, "x2": nodes[17].x, "y2": nodes[17].y, "x3": center_x-5*radius, "y3": center_y-1.5*radius/h, "type": nodes[7].portType});
    points.push({"x1": nodes[26].x, "y1": nodes[26].y, "x2": nodes[37].x, "y2": nodes[37].y, "x3": center_x+6*radius, "y3": center_y, "type": nodes[26].portType});
    points.push({"x1": nodes[28].x, "y1": nodes[28].y, "x2": nodes[38].x, "y2": nodes[38].y, "x3": center_x-5*radius, "y3": center_y+1.5*radius/h, "type": nodes[28].portType});
    points.push({"x1": nodes[45].x, "y1": nodes[45].y, "x2": nodes[46].x, "y2": nodes[46].y, "x3": center_x+4*radius, "y3": center_y+3*radius/h, "type": nodes[45].portType});
    points.push({"x1": nodes[47].x, "y1": nodes[47].y, "x2": nodes[48].x, "y2": nodes[48].y, "x3": center_x-3*radius, "y3": center_y+4.5*radius/h, "type": nodes[47].portType});
    points.push({"x1": nodes[50].x, "y1": nodes[50].y, "x2": nodes[51].x, "y2": nodes[51].y, "x3": center_x+radius, "y3": center_y+4.5*radius/h, "type": nodes[50].portType});
    
    var g_ports = svg.append('g').attr('id', 'ports');
    for(var k=0; k<points.length; k++) {
        var each_port_g = g_ports.append('g');
        each_port_g.append("path")
                .attr("d", function(d) { return 'M ' + points[k].x1 +' '+ points[k].y1 + ' L ' + points[k].x2 + ' ' + points[k].y2 + ' L ' + points[k].x3 + ' ' + points[k].y3 + ' L ' + points[k].x1 +' '+ points[k].y1;
    })
                .attr("fill", resource_color_map[points[k].type]);
        
        var portTextData = [{"x": points[k].x3, "y": points[k].y3+5}];
        
        each_port_g.selectAll("text")
                    .data(portTextData)
                    .enter()
                    .append("text")
                    .attr("x", function(d) {return d.x; })
                    .attr("y", function(d) {return d.y; })
                    .text(points[k].type)
                    .attr("font-family", "sans-serif")
                    .attr("text-anchor", "middle")
                    .attr("font-size", "12px")
                    .attr("fill", "white");
    }
    
    // ================ Nodes & Links ================
    //g element including links and nodes
    var g = svg.append('g')
            .attr('class', 'link-node');

    var g_links = g.append('g').attr('id', 'links'),
        g_nodes = g.append('g').attr('id', 'nodes');
    
    //force simulation
    var simulation = d3.forceSimulation()
        .force("link", d3.forceLink().id(function(d) { return d.id; }))
        .force("x", d3.forceX(center_x))
        .force("y", d3.forceY(center_y));

    var link = g_links.selectAll(".link"),
        node = g_nodes.selectAll(".node");
    
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
            .style('stroke', function(d) { if(d.color == "none") return "lightyellow"; else return d.color; })
            .style('stroke-width', 2);
    
    node = node
            .data(nodes)
            .enter().append("svg:g")
            .attr("class", function(d) {
                if(d.isCity)
                    return "city";
                else
                    return "settlement"
            });
    
    //append rectangle for city
    d3.selectAll(".city").append("rect")
        .attr("width", rec_width)
        .attr("height", rec_length)
        .attr("x", function(d) { return d.x-rec_width/2; })
        .attr("y", function(d) { return d.y-rec_length/2; })
        .style('fill', function(d) { return d.color; });
    
    //append circle for general settlement
    d3.selectAll(".settlement").append("circle")
        .attr('r', node_radius)
        .attr("cx", function(d) { return d.x; })
        .attr("cy", function(d) { return d.y; })
        .style('fill', function(d) { return d.color; });
    
});

//node data construction
//add (x, y) coordinate to each node according to settlement id
function generate_nodes(settleId, isCity, color, portType) {
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

    var n_object = {"id": settleId, "x": xn, "y": yn, "isCity": isCity, "color": color, "portType": portType};
    
    nodes.push(n_object);
}

//tile-drawing function
//(xn, yn, zn) is the coordinate of every tile
function tile_paint(xn, yn, zn, value, resource, robber) {

    var xp = center_x + (xn - yn) * radius,
        yp = center_y + 1.5 * radius / h * zn,

    textData = [{"x": xp, "y": yp+5}];

    hexagonData = [
        { "x": radius+xp, "y": radius/2+yp}, 
        { "x": xp, "y": radius/h+yp-1},
        { "x": -radius+xp, "y": radius/2+yp},
        { "x": -radius+xp, "y": -radius/2+yp},
        { "x": xp, "y": -radius/h+yp+1},
        { "x": radius+xp, "y": -radius/2+yp},
        { "x": radius+xp, "y": radius/2+yp}
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