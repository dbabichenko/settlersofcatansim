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

function outputToConsole($data)
{
    if (is_array($data))
        $output = "<script>console.log( 'Debug Objects: " . implode(',', $data) . "' );</script>";
    else
        $output = "<script>console.log( 'Debug Objects: " . $data . "' );</script>";

    echo $output;
}

class Game
{
    public $color = array();
    public $currentPlayer;

    function __construct($numPlayers)
    {
        outputToConsole("Constructor of Game class is called with " . $numPlayers . " players.");
        global $numOfPlayers;
        $numOfPlayers = $numPlayers;

        $string = file_get_contents("MapData.json");
        $map = json_decode($string, true);

        for ($i = 0; $i < 72; $i++) {
            outputToConsole("Create road array.");
            global $road;
            $road[$i] = new road($map, $i);
        }

        for ($i = 0; $i < 37; $i++) {
            outputToConsole("Create terrain array");
            global $terrain;
            $terrain[$i] = new terrain($map, $i);
        }

        for ($i = 0; $i < 54; $i++) {
            outputToConsole("Create settlement array");
            global $settlement;
            $settlement[$i] = new Settlement($map, $i);
        }

        for ($i = 0; $i < $numPlayers; $i++) {
            outputToConsole("Create player array.");
            $this->color[$i] = $i;

            global $players;
            $players[$i] = new Player($i);
        }

        global $players;
        $this->currentPlayer = &$players[0];
    }

    function rollingDice()
    {
        outputToConsole("function rolling dice is called");
        $diceA = mt_rand(0, 6);
        $diceB = mt_rand(0, 6);
        $sumOfDices = $diceA + $diceB;

        outputToConsole("" . $sumOfDices . "is rolled.");
        return $sumOfDices;
    }

    function produceResource($sumOfDices)
    {
        outputToConsole("Produce resource function is called with dice value of " . $sumOfDices);
        global $players, $terrain;
        if ($sumOfDices == 7) {
            outputToConsole("7 is rolled.");
            foreach($players as &$player){
                $totalResCard = count($player->resCard);
                if ($totalResCard > 7) {
                    $returnAmount = floor($totalResCard / 2);
                    outputToConsole("" . $player->color . " needs to discard" . $returnAmount . "cards");

                    $i = -1;
                    foreach($player->resCard as &$card){
                        $i++;
                        outputToConsole("Do you want to discard a " . $card->type . "card?");
                        $discard = $_POST['value'];
                        if($discard=="yes")
                            unset($player->resCard[$i]);
                    }
                }
            }

            outputToConsole("Please put in the id of the terrain where you want to move the bandit to.");
            $destination = $_POST['value']; // get input from HTML form
            $this->currentPlayer->moveBandit($destination);

            outputToConsole("Please put in the color number of the player who you want to steal from.");
            $targetColor = $_POST['value'];
            $targetPlayer = null;
            foreach($players as &$player){
                if($player->color == $targetColor)
                    $targetPlayer = &$player;
            }
            $this->currentPlayer->steal($targetPlayer, $destination);

        } else {
            foreach ($terrain as &$hex) {
                if (($hex->diceValue == $sumOfDices) && ($hex->hasBandit)) {
                    // iterate through the settlements arount the hex
                    foreach ($hex->settlement as &$sett) {
                        // find the player who control the current settlement
                        foreach ($players as &$player) {
                            if ($player->color == $sett->control) {
                                // create a new resource card, add to current player's resCard array
                                outputToConsole("Player " . $player->color . " gets " . $hex->resourceType . " resource");

                                $newResCard = new ResCard($hex->resourceType);
                                $next = count($player->resCard);
                                $player->resCard[$next] = $newResCard;
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

    function __construct($color)
    {
        outputToConsole("Create player with color of " . $color);
        $this->color = $color;
    }

    function tradeWithBank($tradeInAmount, $tradeInType, $getType, &$bankResCard, $portType)
    {
        outputToConsole("Trade with bank function is called. Trade in " . $tradeInAmount . " " . $tradeInType);

        if ($portType == "none") $ratio = 4;
        else if ($portType == "general") $ratio = 3;
        else if ($portType == $getType) $ratio = 2;
        else return false;

        if ($tradeInType == $getType) return false;

        $getAmount = floor($tradeInAmount / $ratio);
        outputToConsole("Get " . $getAmount . $getType);

        $count = 0;
        $i = -1;
        $enoughRes = false;
        $removeList = array();

        foreach ($bankResCard as &$card) {
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

        if (!$enoughRes)
            return false;

        // remove trade out resource from bank and add to player
        foreach ($removeList as &$index) {
            $next = count($this->resCard);
            $this->resCard[$next] = &$bankResCard[$index];
            // array_push($this->resCard, $resCard[$index]);

            unset($bankResCard[$index]);
        }
        $bankResCard = array_values($bankResCard);

        // remove trade in resource from player and add to bank
        $i = -1;
        $count = 0;
        foreach ($this->resCard as &$card) {
            $i++;
            if ($card->type = $tradeInType) {
                $next = count($bankResCard);
                $bankResCard[$next] = &$card;
                // array_push($bankResCard, $card);

                unset($this->resCard[$i]);
                $count++;
                if ($count == $tradeInAmount) break;
            }
        }
        $this->resCard = array_values($this->resCard);

        outputToConsole("Trade successfully.");
        return true;
    }

    function tradeWithPlayer($tradeInAmount, $tradeInType, $getType, $askRatio, &$other)
    {
        outputToConsole("Trade with player is called. Trade in " . $tradeInAmount . " " . $tradeInType);
        // assume player has accepted the trade
        // no decision logic here

        if ($tradeInType == $getType) return false;

        $getAmount = floor($tradeInAmount / $askRatio);
        outputToConsole("Get " . $getAmount . $getType);

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

        if (!$enoughRes) return false;

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
            if ($card->type = $tradeInType) {
                $next = count($other->resCard);
                $other->resCard[$next] = $card;

                // array_push($other->resCard, $card);
                unset($this->resCard[$i]);
                $count++;
                if ($count == $tradeInAmount) break;
            }
        }
        $this->resCard = array_values($this->resCard);

        outputToConsole("Trade successfully.");
        return true;
    }

    function moveBandit($destination)
    {
        outputToConsole("Move bandit function is called with destination of " . $destination);
        global $banditLocation, $terrain;

        foreach($terrain as &$hex){
            if($hex->id == $destination){
                $hex->hasBandit = true;
            }else if($hex->id == $banditLocation){
                $hex->hasBandit = false;
            }
        }

        $banditLocation = $destination;

        outputToConsole("Move bandit successfully.");
        return true;
    }

    /**
     * @para $targetPlayer is an instance of Player class
     * @para $destination is an instance of Settlement class
     **/
    function steal(&$targetPlayer, $destination)
    {
        outputToConsole("Steal function is called. Steal from player  " . $targetPlayer);
        outputToConsole("Destination terrain is " . $destination->id);
        $hasSettlement = false;
        foreach ($targetPlayer->settlements as &$sett) {
            foreach ($sett->terrain as &$value) {
                if ($value == $destination)
                    $hasSettlement = true;
            }
        }

        if (!$hasSettlement)
            return false;

        $length = count($targetPlayer->resCard);
        $index = mt_rand(0, $length - 1);

        // add resource card to this player
        // array_push($this->resCard, $targetPlayer->resCard[$index]);
        $next = count($this->resCard);
        $this->resCard[$next] = &$targetPlayer->resCard[$index];

        // remove resource card from target player
        unset($targetPlayer->resCard[$index]);
        $targetPlayer->resCard = array_values($targetPlayer->resCard);

        outputToConsole("Steal successfully");
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
        outputToConsole("Constructor of terrain is called for terrain #" . $i);
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
        outputToConsole("Constructor of terrain is called for settlement #" . $i);
        global $terrain, $road;
        $sett = $map['settlements'][$i];
        $hex = $map['tiles'];
        $rds = $map['roads'];

        $this->id = $sett[id];
        $this->control = null;
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
    function build(&$player, $roads)
    {
        outputToConsole("Settlement build function is called by player " . $player->color);
        outputToConsole(" to build settlement #" . $this->id);
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

        outputToConsole("Settlement #" . $this->id . " is built.");
        return true;
    }

    function upgradeToCity(&$player)
    {
        outputToConsole("Upgrade to city function is called by player " . $player->color);
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

        outputToConsole("Successfully upgrade to city.");
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
        outputToConsole("Constructor of terrain is called for road #" . $i);
        global $settlement;
        $this->control = null;
        $rd = $map['roads'][$i];
        $sett = $map['settlements'];

        $source = -1;
        $target = -1;

        for ($j = 0; $j < 54; $j++) {
            if ($rd[source] == $sett[$j][settle_id]) {
                $source = $j;
                break;
            }
        }

        for ($j = 0; $j < 54; $j++) {
            if ($rd[target] == $sett[$j][settle_id]) {
                $target = $j;
                break;
            }
        }

        $this->settlement[0] = &$settlement[$source];
        $this->settlement[1] = &$settlement[$target];
        $this->id = $rd[id];
    }


    function build(&$player)
    {
        //Pushing the new road element into player's road array

        outputToConsole("Road build function is called by player " . $player->color);
        outputToConsole(" to build road #" . $this->id);
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

        foreach ($this->settlement as &$setIndex) {
            if ($settlement[$setIndex]->control == $player->color) $hasSettlement = true;
            foreach ($settlement[$setIndex]->road as &$rdIndex) {
                if ($road[$rdIndex]->control == $player->color) $hasRoad = true;
            }
        }

        if ((!$hasRoad) && (!$hasSettlement)) return false;

        foreach ($resRemoveList as &$index) {
            unset($player->resCard[$index]);
        }

        $player->resCard = array_values($player->resCard);

        $next = count($player->roads);
        $player->roads[$next] = &$this;

        $this->control = $player->color;
        outputToConsole("Road #" . $this->id . " is built.");
        return true;
    }
}

class ResCard
{
    public $type;

    function __construct($type)
    {
        outputToConsole("Constructor of ResCard class is called with resource type of " . $type);
        $this->type = $type;
    }
}

class DevelopmentCard
{
    // needs extra comments help me understand this class
    public $index;

    // this function needs to change
    function purchaseDevCard(&$player, &$bankResCard)
    {
        global $devCard;
        if ($player->control != null) return false;


        $i = -1;
        $resRemoveList = array();
        $requiredRes = array("Ore", "Wool", "Grain");
        foreach ($player->resCard as &$card) {
            $i++;
            if (in_array($card->type, $requiredRes)) {
                array_push($resRemoveList, $i);
                unset($requiredRes[array_search($card->type, $requiredRes)]);
            }
        }
        if (!empty($requiredRes)) return false;

        $this->control = $player->color; //???

        $next = count($player->devCard);
        $player->devCard[$next] = &$this;

        foreach ($resRemoveList as &$index) {
            unset($player->resCard[$index]);
        }

        $player->resCard = array_values($player->resCard);//reindexing the player's resCard

        return true;
    }

    function knight(&$player, $destination)
    {
        global $hasLongestRoad;
        $player->moveBandit($destination);
        $player->knights++;
        if ($player->numKnights > $hasLongestRoad->numKnights) {
            $hasLongestRoad = $player;
        }
    }

    function roadBuilding(&$player, $settlement, $firstRoad, $secondRoad)
    {
        if ($player->control != $player->color) return false; // ??? player does not have control attributes
        $firstRoad->build($player);
        $secondRoad->build($player);
        return true;
    }


    function yearOfPlenty(&$player, $firstType, $secondType, &$bankResCard)
    {
        $i = -1;
        $enoughRes = false;
        $removeList = array();

        foreach ($bankResCard as &$card) {
            $i++;
            if ($card->type == $firstType) {
                array_push($removeList, $i);
                $enoughRes = true;
                break;
            }
        }
        if (!$enoughRes)
            return false;

        $j = -1;
        foreach ($bankResCard as &$card) {
            $j++;
            if ($card->type == $secondType) {
                array_push($removeList, $j);
                $enoughRes = true;
                break;
            }
        }
        if (!$enoughRes)
            return false;

        foreach ($removeList as &$index) {
            $next = count($player->resCard);
            $player->resCard[$next] = &$bankResCard[$index];

        }
        $bankResCard = array_values($bankResCard);
        return true;
    }


    function Monopoly($currentPlayer, $type)
    {
        global $numOfPlayers;
        for ($j = 0; $j < $numOfPlayers; $j++) {
            global $players;
            if ($players[$j] != $currentPlayer) {
                $i = -1;
                $removeList = array();

                foreach ($players[$j]->resCard as &$card) {
                    $i++;
                    if ($card->type == $type) {
                        array_push($removeList, $i);
                    }
                }

                foreach ($removeList as &$index) {
                    $next = count($currentPlayer->resCard);
                    $currentPlayer->resCard[$next] = $players[$j]->resCard[$index];

                    unset($players[$j]->resCard[$index]);
                }
                $players[$j]->resCard = array_values($players[$j]->resCard);
            }
        }
    }

    function VictoryPoints(&$player)
    {
        if ($player->control != $player->color) return false;
        $player->victoryPoints++;
    }
}

?>
