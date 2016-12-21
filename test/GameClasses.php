<?php
$players = array();
$terrain = array();
$settlement = array();
$road = array();
$resCard = array();
$devCard = array();
$numOfPlayers = null;
$banditLocation = 9;
$hasLongestRoad = null;
$hasBiggestArmy = null;

if($_SERVER['REQUEST_METHOD']=="GET") {
    $class = $_GET['class'];
    $function = $_GET['call'];
    $type = $_GET['type'];
    if(method_exists($class, $function)) {
        $g = new Game(3);
        if($type=="para")
            $value = $_GET['value'];
        else if($type=="player")
            $value = array(&$players[0]);
        else if($type=="double") {
            $value = [];
            $value[0] = &$players[1];
            $value[1] = $_GET['value'];
        }

        if($class=="Game"){
            call_user_func(array($g, $function), $value);
        }else if($class=="Player"){
            if($function=="tradeWithBank")
                call_user_func_array(array($players[0], $function), $value);
            else if($function=="tradeWithPlayer"){
                $next = count($value);
                $value[$next] = &$players[1];
                call_user_func_array(array($players[0], $function), $value);
            } else if($function == "purchaseDevCard"){
                call_user_func(array($players[0], $function), $value);
            } else
                call_user_func(array($players[0], $function), $value);
        }else if($class=="Road"){
            if($function=="build"){
                call_user_func(array($road[0], $function), $players[0]);
            }
        }
        else if ($class=="Settlement"){
          if($function=="build"){
            call_user_func(array($settlement[0],$function), $players[0]);
          }
        }
        else if ($class=="Settlement"){
          if($function=="upgradeToCity"){
            call_user_func(array($settlement[0],$function), $players[0]);
          }
        }else
            call_user_func_array(array(__NAMESPACE__ .$class, $function), $value);
    } else {
        echo 'Function Not Exists!!';
    }
}

class Game
{
    public $color = array();
    public $currentPlayer;

    function __construct($numPlayers)
    {
        echo ("Constructor of Game class is called with " . $numPlayers . " players. \n");
        global $numOfPlayers;
        $numOfPlayers = $numPlayers;

        $string = file_get_contents("MapData.json");
        $map = json_decode($string, true);

        for ($i = 0; $i < 72; $i++) {
            echo ("Create road array.\n");
            global $road;
            $road[$i] = new road($map, $i);
        }

        for ($i = 0; $i < 37; $i++) {
            echo ("Create terrain array\n");
            global $terrain;
            $terrain[$i] = new terrain($map, $i);
        }

        for ($i = 0; $i < 54; $i++) {
            echo ("Create settlement array\n");
            global $settlement;
            $settlement[$i] = new Settlement($map, $i);
        }

        for ($i = 0; $i < $numPlayers; $i++) {
            echo ("Create player array.\n");
            $this->color[$i] = $i;

            global $players;
            $players[$i] = new Player($i);
        }

        // 2 roadBuilding 2 yearOfPlenty 2 monopoly
        // 14 knight 5 roadBuilding
        $devCardIndex = -1;
        $cardNum = 0;
        $devCardType = array("roadBuilding", "yearOfPlenty", "monopoly", "knight", "victoryPoints");
        for($i = 0; $i<5; $i++){
            echo ("Create resources cards\n");
            global $devCard;
            if($i<3) $cardNum = 2;
            else if($i==3) $cardNum = 14;
            else if($i==4) $cardNum = 5;
            for($j = 0; $j<$cardNum; $j++){
                $devCardIndex++;
                $devCard[$devCardIndex] = new DevelopmentCard($devCardType[$i]);
            }
        }
        shuffle($devCard);

        $this->initialize();
    }

    function initialize(){
        global $players, $terrain, $resCard, $settlement;
        $resCard[0] = new ResourceCard("Wool");
        for($i = 0; $i<3; $i++){
            $players[$i] = new Player($i);
            for($j = 0; $j<3; $j++){
                $players[$i]->resCard[$j] = new ResourceCard("Wool");
            }
            for($j = 3; $j<6; $j++){
                $players[$i]->resCard[$j] = new ResourceCard("Lumber");
            }
            for($j = 6; $j<10; $j++){
                $players[$i]->resCard[$j] = new ResourceCard("Brick");
            }
            $players[$i]->resCard[10] = new ResourceCard("Ore");
            $players[$i]->resCard[11] = new ResourceCard("Grain");
        }
        $this->currentPlayer = &$players[0];

        foreach($terrain as &$terr){
            if($terr->id == "10"){
                $players[1]->settlements[0] = &$terr->settlement[0];
                $terr->settlement[0]->control = 1;
                break;
            }
        }

        foreach($settlement as &$sett){
            if($sett->id == "105"){
                $players[0]->settlements[0] = $sett;
                $sett->control = $players[0]->color;
            }
        }



    }

    function rollingDice()
    {
        // activate all development card when each round begins
        global $players;
        foreach($players as &$player){
            foreach($player->devCard as &$card){
                $card->isActivated = true;
            }
        }
        echo ("function rolling dice is called\n");
        $diceA = mt_rand(0, 6);
        $diceB = mt_rand(0, 6);
        $sumOfDices = $diceA + $diceB;

        echo ("" . $sumOfDices . " is rolled.\n");
        return $sumOfDices;
    }

    function produceResource($sumOfDices)
    {
        echo ("Produce resource function is called with dice value of " . $sumOfDices . "\n");
        global $players, $terrain;
        if ($sumOfDices == 7) {
            echo ("7 is rolled.\n");
            foreach($players as &$player){
                $totalResCard = count($player->resCard);
                if ($totalResCard > 7) {
                    $returnAmount = floor($totalResCard / 2);
                    echo ("" . $player->color . " needs to discard " . $returnAmount . " cards\n");

                    // $i = -1;
                    // discard function
                    // player input the type of card to discard
                    /*foreach($player->resCard as &$card){
                        $i++;
                        echo ("Do you want to discard a " . $card->type . "card? \n");
                        $discard = $_GET['value'];
                        if($discard=="yes")
                            unset($player->resCard[$i]);
                    }*/

                    echo "Enter one number for each type of resources card to discard (Brick, Grain, Lumber, Ore, Wool)\n";
                    $discard = $_GET['discard'];
                    $discardType = ["Brick", "Grain", "Lumber", "Ore", "Wool"];
                    global $resCard;

                    for($i=0; $i<5; $i++) {
                        $j = -1;
                        $count = 0;
                        foreach ($player->resCard as &$card) {
                            $j++;
                            if ($card->type == $discardType[$i]) {
                                $next=count($resCard);
                                $resCard[$next] = $player->resCard[$j];

                                unset($player->resCard[$j]);
                                $count++;
                            }

                            if ($count == $discard[$i])
                                break;
                        }
                        if($count!=$discard[$i])
                            echo "Not enough cards of " . $discardType[$i] . " to discard.";

                        echo "Player " . $player->color . " discards " . $discard[$i] . " " . $discardType[$i] . " cards.";
                        $player->resCard = array_values($player->resCard);
                        $result = print_r($player->resCard, true);
                        echo $result;
                    }
                }
            }

            echo ("Please put in the id of the terrain where you want to move the bandit to.\n");
            $destination = $_GET['destination']; // get input from HTML form
            $this->currentPlayer->moveBandit($destination);

            echo ("Please put in the color number of the player who you want to steal from.\n");
            $targetColor = $_GET['color'];
            $targetPlayer = null;
            foreach($players as &$player){
                if($player->color == $targetColor)
                    $targetPlayer = &$player;
            }
            $this->currentPlayer->steal($targetPlayer, $destination);

        } else {
            foreach ($terrain as &$hex) {
                if (($hex->diceValue == $sumOfDices) && (!$hex->hasBandit)) {
                    echo "Terrain " . $hex->id . " has dice value of " . $hex->diceValue . "\n";
                    // iterate through the settlements arount the hex
                    foreach ($hex->settlement as &$sett) {
                        // find the player who control the current settlement
                        foreach ($players as &$player) {
                            if ($player->color == $sett->control) {
                                // create a new resource card, add to current player's resCard array
                                echo ("Player " . $player->color . " gets " . $hex->resourceType . " resource \n");

                                $next = count($player->resCard);
                                $player->resCard[$next] = new ResourceCard($hex->resourceType);

                                $result = print_r($player->resCard, true);
                                echo $result . "\n";
                            }
                        }
                    }
                }
            }
        }
    }
}

class Player
{
    public $color;
    public $victoryPoints;
    public $settlements = array(); // stores indexes of settlement array
    public $roads = array();
    public $resCard = array();
    public $devCard = array();
    public $longestPath;
    public $numKnights;

    function __construct($color){
        echo ("Create player with color of " . $color . "\n");
        $this->color = $color;
        $this->victoryPoints = 0;
    }

    function tradeWithBank($tradeInAmount, $tradeInType, $getType, $portType)
    {
        global $resCard;
        echo ("Trade with bank function is called.\n");
        echo ("Player " . $this->color . " trade in " . $tradeInAmount . " " . $tradeInType . ". \n");

        if ($portType == "none") $ratio = 4;
        else if ($portType == "general") $ratio = 3;
        else if ($portType == $getType) $ratio = 2;
        else return false;

        if ($tradeInType == $getType) return false;

        $getAmount = floor($tradeInAmount / $ratio);
        echo ("Get " . $getAmount . " " . $getType . "\n");

        $count = 0;
        $i = -1;
        $enoughRes = false;
        $removeList = array();

        foreach ($resCard as &$card) {
            $i++;
            if ($card->type == $getType) {
                array_push($removeList, $i);
                $count++;
                if ($count == $getAmount) {
                    $enoughRes = true;
                    break;
                }
            }
        }

        if (!$enoughRes){
            echo "Bank does not have enough resources\n";
            return false;
        }

        // remove trade out resource from bank and add to player
        foreach ($removeList as &$index) {
            $next = count($this->resCard);
            $this->resCard[$next] = &$resCard[$index];
            // array_push($this->resCard, $resCard[$index]);

            unset($resCard[$index]);
        }
        $resCard = array_values($resCard);

        // remove trade in resource from player and add to bank
        $i = -1;
        $count = 0;
        foreach ($this->resCard as &$card) {
            $i++;
            if ($card->type == $tradeInType) {
                $next = count($resCard);
                $resCard[$next] = &$card;
                // array_push($resCard, $card);

                unset($this->resCard[$i]);
                $count++;
                if ($count == $tradeInAmount) break;
            }
        }
        $this->resCard = array_values($this->resCard);

        $result = print_r($resCard, true);
        echo "Bank resources left: \n" . $result;

        $result = print_r($this->resCard, true);
        echo "Player " . $this->color . " now has : \n" . $result;

        echo ("Trade successfully. \n");
        return true;
    }

    function tradeWithPlayer($tradeInAmount, $tradeInType, $getType, $askRatio, &$other)
    {
        echo ("Trade with player is called. \n");
        echo ("Player " . $this->color . " trade in " . $tradeInAmount . " " . $tradeInType . " with player " . $other->color . " \n");
        // assume player has accepted the trade
        // no decision logic here

        if ($tradeInType == $getType) return false;

        $getAmount = floor($tradeInAmount / $askRatio);
        echo ("Get " . $getAmount . " " . $getType . "\n");

        $count = 0;
        $i = -1;
        $enoughRes = false;
        $removeList = array();

        foreach ($other->resCard as &$card) {
            $i++;
            if ($card->type == $getType) {
                array_push($removeList, $i);
                $count++;
                if ($count == $getAmount) {
                    $enoughRes = true;
                    break;
                }
            }
        }

        if (!$enoughRes) {
            echo "Player " . $other->color . "does not enough resources";
            return false;
        };

        // remove resource from the other player and add to this player
        foreach ($removeList as &$index) {
            $next = count($this->resCard);
            $this->resCard[$next] = &$other->resCard[$index];

            // array_push($this->resCard, $other->resCard[$index]);
            unset($other->resCard[$index]);
        }
        $other->resCard = array_values($other->resCard);

        // remove resource from this player and add to the other player
        $i = -1;
        $count = 0;
        foreach ($this->resCard as &$card) {
            $i++;
            if ($card->type == $tradeInType) {
                $next = count($other->resCard);
                $other->resCard[$next] = $card;

                // array_push($other->resCard, $card);
                unset($this->resCard[$i]);
                $count++;
                if ($count == $tradeInAmount) break;
            }
        }
        $this->resCard = array_values($this->resCard);

        $result = print_r($this->resCard, true);
        echo "Player " . $this->color . " now has : \n" . $result . "\n";

        $result = print_r($other->resCard, true);
        echo "Player " . $other->color . " now has : \n" . $result . "\n";

        echo ("Trade successfully. \n");
        return true;
    }

    function moveBandit($destination)
    {
        echo ("Move bandit function is called with destination of " . $destination . "\n");
        global $banditLocation, $terrain;

        foreach($terrain as &$hex){
            if($hex->id == $destination){
                $hex->hasBandit = true;
                echo ("Bandit has been moved to terrain id# " . $hex->id . "\n");
            }else if($hex->id == $banditLocation){
                $hex->hasBandit = false;
            }
        }

        $banditLocation = $destination;

        echo ("Move bandit successfully.\n");

        return true;
    }

    /**
     * @para $targetPlayer is an instance of Player class
     * @para $destination is integer, settlement id
     **/
    function steal(&$targetPlayer, $destination)
    {
        echo ("Steal function is called. Steal from player  " . $targetPlayer->color . "\n");
        echo ("Destination terrain is " . $destination . "\n");
        $hasSettlement = false;
        foreach ($targetPlayer->settlements as &$sett) {
            foreach ($sett->terrain as &$terr) {
                if ($terr->id == $destination)
                    $hasSettlement = true;
            }
        }

        if (!$hasSettlement) {
            echo "Player does not have settlement around the destination\n";
            return false;
        }

        $length = count($targetPlayer->resCard);
        $index = mt_rand(0, $length - 1);

        echo "Player " . $targetPlayer->color . " lost a resources card of " . $targetPlayer->resCard[$index]->type . "\n";

        // add resource card to this player
        // array_push($this->resCard, $targetPlayer->resCard[$index]);
        $next = count($this->resCard);
        $this->resCard[$next] = &$targetPlayer->resCard[$index];

        $result = print_r($this->resCard, true);
        echo "Player " . $this->color . "'s resource card: \n";
        echo $result . "\n";

        // remove resource card from target player
        unset($targetPlayer->resCard[$index]);
        $targetPlayer->resCard = array_values($targetPlayer->resCard);

        $result = print_r($targetPlayer->resCard, true);
        echo "Player " . $targetPlayer->color . "'s resource card: \n";
        echo $result . "\n";

        echo ("Steal successfully \n");
        return true;
    }

    function purchaseDevCard()
    {
        global $devCard;

        echo ("Purchase dev card function is called by player ". $this->color . "\n");
        $i = -1;
        $resRemoveList = array();
        $requiredRes = array("Ore", "Wool", "Grain");
        foreach ($this->resCard as &$card) {
            $i++;
            if (in_array($card->type, $requiredRes)) {
                array_push($resRemoveList, $i);
                unset($requiredRes[array_search($card->type, $requiredRes)]);
            }
        }
        if (!empty($requiredRes)) {
            echo(print_r($requiredRes, true) . "\n");
            echo ("Player does not have enough resources \n");
            return false;
        }

        $next = count($this->devCard);
        $this->devCard[$next] = $devCard[0];
        echo ("Player gets ".$devCard[0]->type);

        // remove devCard from the bank
        unset($devCard[0]);
        $devCard = array_values($devCard);
        echo ("Dev card ".$this->devCard[$next]->type." has been removed from bank. \n");


        foreach ($resRemoveList as &$index) {
            unset($this->resCard[$index]);
        }
        $this->resCard = array_values($this->resCard);//reindexing the player's resCard
        echo ("Resources cards have been removed from player's cards. \n");

        $result = print_r($this->resCard, true);
        echo "Player " . $this->color . " now has resource card :\n" . $result;

        $result = print_r($this->devCard, true);
        echo "Player " . $this->color . " now has development card :\n" . $result;

        $result = print_r($devCard, true);
        echo "Bank now has development card :\n" . $result;

        echo ("Purchase resources card successfully. \n");
        return true;
    }
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
        if($terr[diceValue]!=null)
            $this->diceValue = $terr[diceValue];
        else
            $this->diceValue = -1;
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

        echo "Dice value is " . $this->diceValue . "\n\n";
    }

}

class Settlement
{
    public $id;
    public $index;
    public $control; //Player.color if active, otherwise null
    public $terrain = array();
    public $road = array();
    public $isCity;
    public $portType;

    function __construct($map, $i)
    {
        echo ("Constructor of settlement is called for settlement #" . $i . "\n");
        global $terrain, $road;
        $sett = $map['settlements'][$i];
        $hex = $map['tiles'];
        $rds = $map['roads'];

        $this->id = $sett[id];
        $this->control = -1;
        $this->index = $i;

        if ($sett[portType] == "none") {
            $this->portType = null;
        } else {
            $this->portType = $sett[portType];
        }

        foreach ($sett[tiles] as &$value) {
            for ($j = 0; $j < 37; $j++) {
                if ($value == $hex[$j][tile_id]) {
                    $next = count($this->terrain);
                    $this->terrain[$next] = &$terrain[$j];
                    break;
                }
            }
        }

        foreach ($sett[roads] as &$value) {
            for ($j = 0; $j < 72; $j++) {
                if ($value == $rds[$j][road_id]) {
                    $next = count($this->road);
                    $this->road[$next] = &$road[$j];
                    break;
                }
            }
        }

        $this->isCity = false;
    }

    /*
     * @para $player is the player who is building the road
     * @para $roads is the roads array
     * @global $settlement is the settlement array
     */
    function build(&$player)
    {
        global $roads;
        echo ("Settlement build function is called by player " . $player->color);
        echo (" to build settlement #" . $this->id . "\n");
        global $settlement;
        if ($this->control != null) return false;

        $i = -1;
        $resRemoveList = array();
        $requiredRes = array("Brick", "Lumber", "Wool", "Grain");
        foreach ($player->resCard as &$card) {
            $i++;
            if (in_array($card->type, $requiredRes)) {
                array_push($resRemoveList, $i);
                unset($requiredRes[array_search($card->type, $requiredRes)]);
            }
        }

        if (!empty($requiredRes)) return false;

        $hasAdjacency = false;
        $hasRoad = false;
        foreach ($this->road as &$rdIndex) {
            foreach ($roads[$rdIndex]->settlement as &$setIndex) {
                if ($settlement[$setIndex]->control != null)
                    $hasAdjacency = true;
            }
            if ($roads[$rdIndex]->control == $player->control)
                $hasRoad = true;
        }

        if ((!$hasRoad) || ($hasAdjacency)) return false;

        $this->control = $player->color;

        // array_push($player->settlement, $this);
        $next = count($player->settlement);
        $player->settlement[$next] = &$this;

        foreach ($resRemoveList as &$index) {
            unset($player->resCard[$index]);
        }

        $player->resCard = array_values($player->resCard);
        $player->victoryPoints++;

        echo ("Settlement #" . $this->id . " is built.\n");
        return true;
    }

    function upgradeToCity(&$player)
    {
        echo ("Upgrade to city function is called by player " . $player->color);
        if ($this->control != $player->color) return false;

        $i = -1;
        $resRemoveList = array();
        $requiredRes = array("Ore", "Ore", "Ore", "Grain", "Grain");
        foreach ($player->resCard as &$card) {
            $i++;
            if (in_array($card->type, $requiredRes)) {
                array_push($resRemoveList, $i);
                unset($requiredRes[array_search($card->type, $requiredRes)]);
            }
        }

        if (!empty($requiredRes)) return false;

        foreach ($resRemoveList as &$index) {
            unset($player->resCard[$index]);
        }

        $player->resCard = array_values($player->resCard);

        $this->isCity = true;
        $player->victoryPoints++;

        echo ("Successfully upgrade to city. \n");
        return true;
    }
}

class Road
{
    public $control; //Player.color if active, otherwise null
    public $settlement = array();
    public $id;

    function __construct($map, $i)
    {
        echo ("Constructor of terrain is called for road #" . $i . "\n");
        global $settlement;
        $this->control = null;
        $rd = $map['roads'][$i];
        $sett = $map['settlements'];

        $source = -1;
        $target = -1;

        echo "This road should starts at " . $rd[source] . "\n";
        echo "This road should end at " . $rd[target] . "\n";
        for ($j = 0; $j < 54; $j++) {
            echo "Currently checking " . $sett[$j][id] . "\n";
            if ($rd[source] == $sett[$j][id]) {
                echo "Road source found at " . $j . "\n";
                $source = $j;
                break;
            }
        }

        for ($j = 0; $j < 54; $j++) {
            if ($rd[target] == $sett[$j][id]) {
                echo "Road target found at " . $j . "\n";
                $target = $j;
                break;
            }
        }
        if(($source==-1)&&($target==-1)){
            echo "Road building error with road " . $this->id . "\n";
        }

        $this->settlement[0] = &$settlement[$source];
        $this->settlement[1] = &$settlement[$target];
        $this->id = $rd[id];
    }


    function build(&$player)
    {
        //Pushing the new road element into player's road array

        echo ("Road build function is called by player " . $player->color);
        echo (" to build road #" . $this->id . "\n");
        global $settlement, $road;
        if ($this->control != null) return false;

        $i = -1;
        $resRemoveList = array();
        $requiredRes = array("Brick", "Lumber");
        foreach ($player->resCard as &$card) {
            $i++;
            if (in_array($card->type, $requiredRes)) {
                array_push($resRemoveList, $i);
                unset($requiredRes[array_search($card->type, $requiredRes)]);
            }
        }

        if (!empty($requiredRes)) return false;

        $hasRoad = false;
        $hasSettlement = false;


        foreach ($this->settlement as &$sett) {
            if ($sett->control == $player->color) $hasSettlement = true;

            foreach ($sett->road as &$rd) {
                if ($rd->control == $player->color) $hasRoad = true;
            }
        }

        if ((!$hasRoad) && (!$hasSettlement)) {
            echo "Cannot build the road of " . $this->id . " because the player " . $player->color . " do not have required settlement and roads connect to it.\n";
            return false;
        }

        foreach ($resRemoveList as &$index) {
            unset($player->resCard[$index]);
        }

        $player->resCard = array_values($player->resCard);

        $next = count($player->roads);
        $player->roads[$next] = &$this;

        $this->control = $player->color;
        echo ("Road #" . $this->id . " is built. \n");

        // !!! add longest road
        global $hasLongestRoad;
        if($hasLongestRoad==null){
            if(count($player->roads)>=3){
                $player->victoryPoints += 2;
                $hasLongestRoad = $player;
            }
        }
        else if(count($player->roads)>count($hasLongestRoad->roads)){
            $hasLongestRoad->victoryPoints -= 2;
            $player->victoryPoints += 2;
            $hasLongestRoad = &$player;
        }

        return true;
    }
}

class ResourceCard
{
    public $type;

    function __construct($type)
    {
        echo ("Constructor of ResCard class is called with resource type of " . $type . "\n");
        $this->type = $type;
    }
}

class DevelopmentCard
{
    public $type;
    public $isActivated;

    function DevelopmentCard($type)
    {
        $this->type = $type;
        $this->isActivated = false;
        if(($this->type=="yearOfPlenty")||($this->type=="victoryPoints"))
            $this->isActivated = true;
    }

    function playDevCard(&$player)
    {
        if($this->isActivated!=false) {
            if ($this->type == "knight") {
                $destination = $_GET['value'];
                $this->knight($player, $destination);
            } else if ($this->type == "roadBuilding") {
                $this->roadBuilding($player);
            } else if ($this->type == "yearOfPlenty") {
                $this->yearOfPlenty($player);
            } else if ($this->type == "monopoly") {
                $this->monopoly($player);
            } else if ($this->type == "victoryPoints") {
                $this->victoryPoints($player);
            }
        }else{
            echo "This card of " . $this->type . " can not be played in this turn.";
        }
    }

    function knight(&$player, $destination)
    {
        echo "A knight card is played by Player" . $player->color . "\n";
        global $hasBiggestArmy;
        $player->moveBandit($destination);

        $player->numKnights++;      //knights -> numKnights
        if($hasBiggestArmy==null){
            if($player->numKnights>=3){
                $player->victoryPoints += 2;
                $hasBiggestArmy = &$player;
                echo "Player " . $player->color . "has the largest army\n";
            }
            else echo "Currently no one has largest army\n";
        }else if ($player->numKnights > $hasBiggestArmy->numKnights) {
            $hasBiggestArmy->victoryPoints -= 2;
            $player->victoryPoints += 2;
            $hasBiggestArmy = &$player;
            echo "Player " . $player->color . "has the largest army\n";
        }else echo "Player" . $hasBiggestArmy->color .  "has the largest army\n";
        echo "End of play knight card";
    }

    function roadBuilding(&$player)
    {
        $i = 0;
        $rdNum = $_GET['value'];
        while($i<2){
            global $road;
            $size = count($road);
            for($j=0;$j<$size;$j++){
                if($road[$j]->id==$rdNum[$i]){
                    if($road[$j]->control==null){
                        if($road[$j]->build($player))
                            echo ("Successfully build rd# ".$rdNum[$i] . "\n");
                        $i++;
                        break;
                    }
                    else{
                        echo ("This road is occupied. \n");
                        break;
                    }
                }
            }
        }
        return true;
    }

    function yearOfPlenty(&$player)
    {
        echo "Year of plenty function is called\n";
        $i = 0;
        global $resCard;
        $type = $_GET['value'];

        while($i<2) {
            echo ("What type of resource card do you want? \n");
            $j = -1;
            foreach ($resCard as &$card) {
                $j++;
                if ($card->type == $type[$i]) {
                    $next = count($player->resCard);
                    $player->resCard[$next] = &$card;
                    unset($resCard[$j]);
                    $i++;
                    break;
                }
            }
            $resCard = array_values($resCard);
        }

        echo "Player " . $player->color . " resource card array : ";
        $result = print_r($player->resCard, true);
        echo $result . "\n";

    }

    function monopoly(&$player)
    {
        global $players;
        echo ("What type of resources do you want?\n");
        $askType = $_GET['value'];
        echo ("The player wants " . $askType . "\n");

        foreach($players as &$other) {
            if($other->color!=$player->color){
                $i = -1;
                foreach($other->resCard as &$card){
                    $i++;
                    if($card->type==$askType){
                        $next = count($player->resCard);
                        $player->resCard[$next] = &$card;

                        unset($other->resCard[$i]);
                    }
                }
                $other->resCard = array_values($other->resCard);

                echo "Player " . $other->color . " resource card array : ";
                $result = print_r($other->resCard, true);
                echo $result . "\n";
            }
        }

        echo "Player " . $player->color . " resource card array : ";
        $result = print_r($player->resCard, true);
        echo $result . "\n";
    }

    function victoryPoints(&$player)
    {
        $count = 0;
        foreach($player->devCard as &$card){
            if($this->type=="victoryPoints")
                $count++;
        }
        if($count+$player->victoryPoints==10){
            echo "The player " . $player->color . " played " . $player->victoryPoints . " victory points card.";
            echo "The player " . $player->color . " wins the game.";
        }else{
            echo "The player " . $player->color . " cannot play victory points card at this moment.";
        }

    }
}

?>
