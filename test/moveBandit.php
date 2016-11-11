<?php
/**
 * Created by PhpStorm.
 * User: zigchg
 * Date: 11/11/16
 * Time: 1:12 PM
 */

$terrain = array();
$banditLocation = null;


$string = file_get_contents("MapData.json");
$map = json_decode($string, true);
for ($i = 0; $i < 37; $i++) {
    echo ("Create terrain array\n");
    global $terrain;
    $terrain[$i] = new terrain($map, $i);
}


if($_SERVER['REQUEST_METHOD']=="GET") {
    $function = $_GET['call'];
    if(function_exists($function)) {
        call_user_func($function(10));
    } else {
        echo 'Function Not Exists!!';
    }
}

function moveBandit($destination)
{
    echo ("Move bandit function is called with destination of " . $destination . "\n");
    global $banditLocation, $terrain;

    foreach($terrain as &$hex){
        if($hex->id == $destination){
            $hex->hasBandit = true;
        }else if($hex->id == $banditLocation){
            $hex->hasBandit = false;
        }
    }

    $banditLocation = $destination;

    echo ("Move bandit successfully.\n");
    return true;
}

class Terrain
{
    public $id;
    public $resourceType;
    public $settlement = array();
    public $diceValue;
    public $hasBandit;

    /*
     *  @para $map is the data read from the JSON file
     *  @para $i is index of the terrain array in the JSON file
     */
    function __construct($map, $i)
    {
        echo ("Constructor of terrain is called for terrain #" . $i . "\n");
        global $settlement;
        $terr = $map['tiles'][$i];
        $sett = $map['settlements'];

        // parse the map
        // IDE might say undefined constant, it is because they are from JSON file
        $this->id = $terr[tile_id];
        $this->resourceType = $terr[resourceType];
        $this->diceValue = $terr[diceValue];
        $this->hasBandit = $terr[hasRobber];

        for ($j = 0; $j < 54; $j++) {
            $hex = $sett[$j][tiles];
            foreach ($hex as &$value) {
                if ($value == $terr[tile_id]) {
                    $next = count($this->settlement);
                    $this->settlement[$next] = &$settlement[$j];
                    break;
                }
            }
        }
    }

}

?>