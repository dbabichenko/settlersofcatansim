<?php
$players = array();
$terrain = array();
$settlement = array();
$road = array();
$resCard = array();
$devCard = array();
$numOfPlayers;
$banditLocation;
$hasLongestRoad;
$hasBiggestArmy;

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
        global $numOfPlayers;
        $numOfPlayers = $numPlayers;

        $string = file_get_contents("MapData.json");
        $map = json_decode($string, true);

        for ($i = 0; $i < 72; $i++) {
            global $road;
            $road[$i] = new road($map, $i);
        }

        for ($i = 0; $i < 37; $i++) {
            global $terrain;
            $terrain[$i] = new terrain($map, $i);
        }

        for ($i = 0; $i < 54; $i++) {
            global $settlement;
            $settlement[$i] = new Settlement($map, $i);
        }

        for ($i = 0; $i < $numPlayers; $i++) {
            $this->color[$i] = $i;

            global $players;
            $players[$i] = new Player($i);
        }
    }

    function rollingDice($player)
    {
        outputToConsole("function rolling dice is called by " . $player->color);
        $diceA = mt_rand(0, 6);
        $diceB = mt_rand(0, 6);
        $sumOfDices = $diceA + $diceB;

        outputToConsole("" . $sumOfDices . "is rolled.");
        if ($sumOfDices == 7) {
            global $numPlayers, $resCard;
            for ($j = 0; $j < $numPlayers; $j++) {
                if ($player[$j]->$resCard > 7) {
                    $returnAmount = floor($resCard / 2);
                    outputToConsole("" . $player->color . " needs to discard" . $returnAmount . "cards");
                    //how to choose which card to discard ?
                }
            }

            $player->moveBandit($destination);
            // !IMPORTANT!: destination is not defined here
        } else {
            return produceResource($sumOfDices);
        }
    }

    function produceResource($sumOfDices)
    {

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
        $this->color = $color;
    }

    function tradeWithBank($tradeInAmount, $tradeInType, $getType, &$bankResCard, $portType)
    {
        if ($portType == "none") $ratio = 4;
        else if ($portType == "general") $ratio = 3;
        else if ($portType == $getType) $ratio = 2;
        else return false;

        if ($tradeInType == $getType) return false;

        $getAmount = floor($tradeInAmount / $ratio);

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

        // remove resource from bank and add to player
        foreach ($removeList as &$index) {
            $next = count($this->resCard);
            $this->resCard[$next] = &$bankResCard[$index];
            // array_push($this->resCard, $resCard[$index]);

            unset($bankResCard[$index]);
        }
        $bankResCard = array_values($bankResCard);

        // remove resource from player and add to bank
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

        return true;
    }

    function tradeWithPlayer($tradeInAmount, $tradeInType, $getType, $askRatio, &$other)
    {
        // assume player has accepted the trade
        // no decision logic here

        if ($tradeInType == $getType) return false;

        $getAmount = floor($tradeInAmount / $askRatio);

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

        return true;
    }

    function moveBandit($destination)
    {
        // !ask for input here!
        $banditLocation = $destination;
        return steal($targetPlayer, $banditLocation);
    }

    /**
     * @para $targetPlayer is an instance of Player class
     * @para $destination is a index of settlement array
     **/
    function steal(&$targetPlayer, $destination)
    {
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
    public $portType;

    /*
     *  @para $map is the data read from the JSON file
     *  @para $i is index of the terrain array in the JSON file
     */
    function __construct($map, $i)
    {
        global $settlement;
        $terr = $map['tiles'][$i];
        $sett = $map['settlements'];

        // parse the map
        // IDE might say undefined constant, it is because they are from JSON file
        $this->id = $terr[tile_id];
        $this->resourceType = $terr[resourceType];
        $this->diceValue = $terr[coordinates];
        $this->hasBandit = $terr[hasRobber];

        if ($terr[portType] == "none") {
            $this->portType = null;
        } else {
            $this->portType = $terr[portType];
        }

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

    function __construct($map, $i)
    {
        global $terrain, $road;
        $sett = $map['settlements'][$i];
        $hex = $map['tiles'];
        $rds = $map['roads'];

        $this->id = $sett[settle_id];
        $this->control = null;
        $this->index = $i;

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

        return true;
    }

    function upgradeToCity(&$player)
    {
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
        return true;
    }
}

class Road
{
    public $control; //Player.color if active, otherwise null
    public $settlement = array();

    function __construct($map, $i)
    {
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
    }


    function build(&$player)
    {
        //Pushing the new road element into player's road array

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
        return true;
    }
}

class ResCard
{
    public $type;

    function __construct($type)
    {
        $this->type = $type;
    }
}

class DevelopmentCard
{
    public $index;

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

        $this->control = $player->color;

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
        if ($player->control != $player->color) return false;
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
