<?php
    class Game {
        public $color = array();
        public $numPlayers;
        public $players = array();
        public $terrain = array();
        public $settlement = array();
        public $road =  array();
        public $currentPlayer;
        public $banditLocation;
        public $resCard = array();
        public $devCard = array();
        public $hasLongestRoad;
        public $hasBiggestArmy;
        
        function __construct($numPlayers) {
            $this->numPlayers = $numPlayers;
            
            $string = file_get_contents("MapData.json");
            $map = json_decode($string, true);
            
            for ($i = 0; $i<72; $i++){
                $this->road[$i] = new road($map, $i);
            }
            
            for ($i = 0; $i<37; $i++){
                $this->terrain[$i] = new terrain($map, $i);
            }
            
            for ($i = 0; $i<54; $i++){
                $this->settlement[$i] = new Settlement($map, $i);
            }
            
            for ($i = 0; $i<$numPlayers; $i++){
                $this->color[$i] = $i;
                
                $this->players[$i] = new Player($i);
            }
        }
        
        function rollingDice(){
            $diceA = mt_rand(0, 6);
            $diceB = mt_rand(0, 6);
            return ($diceA+$diceB);
        }
        
        function produceResource($sumOfDices){
            
        }
        
    }

    class Player {
        public $color;
        public $victoryPoints;
        public $settlements = array(); // stores indexes of settlement array
        // Do we need stores road also?
        public $resCard = array();
        public $devCard = array();
        public $longestPath;
        public $numKnights;
        
        function __construct($color){
            $this->color = $color;
        }
        
        function tradeWithBank($tradeInAmount, $tradeInType, $getType, $resCard){
            $ratio = 4; // Fixed ratio!!ratio varies!!
            
            if($tradeInType==$getType) return false;
            
            $getAmount = floor($tradeInAmount / $ratio);
            
            $count = 0;
            $i = -1;
            $enoughRes = false;
            $removeList = array();
            
            foreach($resCard as &$card){
                $i++;
                if($card->type==$getType){
                    array_push($removeList, $i);
                    $count++;
                    if($count==$getAmount) {
                        $enoughRes = true;
                        break;
                    }
                }
            }
            
            if(!$enoughRes)
                return false;
            
            // remove resource from bank and add to player
            foreach($removeList as &$index){
                array_push($this->resCard, $resCard[$index]);
                unset($resCard[$index]);
            }
            $resCard = array_values($resCard);
            
            //remove resource from player and add to bank
            $i = -1;
            $count = 0;
            foreach($this->resCard as &$card){
                $i++;
                if($card->type = $tradeInType){
                    array_push($resCard, $card);
                    unset($this->resCard[$i]);
                    $count++;
                    if($count==$tradeInAmount) break;
                }
            }
            $this->resCard = array_values($this->resCard);
            
            return true;
        }
        
        function tradeWithPlayer($tradeInAmount, $tradeInType, $getType, $askRatio, $other){
            // assume player has accepted the trade
            // no decision logic here
            
            if($tradeInType==$getType) return false;
            
            $getAmount = floor($tradeInAmount / $askRatio);
            
            $count = 0;
            $i = -1;
            $enoughRes = false;
            $removeList = array();
            
            foreach($other->resCard as &$card){
                $i++;
                if($card->type==$getType){
                    array_push($removeList, $i);
                    $count++;
                    if($count==$getAmount) {
                        $enoughRes = true;
                        break;
                    }
                }
            }
            
            if(!$enoughRes) return false;
            
            // remove resource from the other player and add to this player
            foreach($removeList as &$index){
                array_push($this->resCard, $other->resCard[$index]);
                unset($other->resCard[$index]);
            }
            $other->resCard = array_values($other->resCard);
            
            //remove resource from this player and add to the other player
            $i = -1;
            $count = 0;
            foreach($this->resCard as &$card){
                $i++;
                if($card->type = $tradeInType){
                    array_push($other->resCard, $card);
                    unset($this->resCard[$i]);
                    $count++;
                    if($count==$tradeInAmount) break;
                }
            }
            $this->resCard = array_values($this->resCard);
            
            return true;   
        }
        
        
        /**
        * @para $targetPlayer is an instance of Player class
        * @para $destination is a index of settlement array
        **/
        function steal($targetPlayer, $destination){
            $hasSettlement = false;
            foreach($targetPlayer->settlements as &$sett){
                foreach($sett->terrain as &$value){
                    if($value==$destination)
                        $hasSettlement = true;
                }
            }
            
            if(!$hasSettlement)
                return false;
            
            $length = count($targetPlayer->resCard);
            $index = mt_rand(0, $length-1);
            
            // add resource card to this player
            array_push($this->resCard, $targetPlayer->resCard[$index]);
            // remove resource card from target player
            unset($targetPlayer->resCard[$index]);
            $targetPlayer->resCard = array_values($targetPlayer->resCard);
            
            return true;
        }
    }

    class Terrain{
        public $id;
        public $resourceType;
        public $settlement = array();
        public $diceValue;
        public $hasBandit;
        public $portType;
        
        function __construct($map, $i){
            $terr = $map['tiles'][$i];
            $sett = $map['settlements'];
            
            $this->id = $terr[tile_id];
            $this->resourceType = $terr[resourceType];
            $this->diceValue = $terr[coordinates];
            $this->hasBandit = $terr[hasRobber];
            
            if($terr[portType]=="none") {
                $this->portType = null;
            }
            else {
                $this->portType = $terr[portType];
            }
            
            for($j = 0; $j<54; $j++){
                $hex = $sett[$j][tiles];
                foreach($hex as &$value){
                    if($value==$terr[tile_id]){
                        array_push($this->settlement, $j);
                        break;
                    }
                }
            }
        }
        
    }

    class Settlement{
        public $id;
        public $index; // index of this object in the settlement array
        public $control; //Player.color if active, otherwise null
        public $terrain = array();
        public $road = array();
        public $isCity;
        
        function __construct($map, $i){
            $sett = $map['settlements'][$i];
            $hex = $map['tiles'];
            $rds = $map['roads'];
                
            $this->id = $sett[settle_id];
            $this->control = null;
            $this->index = $i;
            
            foreach($sett[tiles] as &$value){
                for($j = 0; $j<37; $j++){
                    if($value==$hex[$j][tile_id]){
                        array_push($this->terrain, $j);
                        break;
                    }
                }
            }
            
            foreach($sett[roads] as &$value){
                for($j = 0; $j<72; $j++){
                    if($value==$rds[$j][road_id]){
                        array_push($this->road, $j);
                        break;
                    }
                }
            }
            
            $this->isCity = false;
        }
        
        /*
        * @para $player is the player who is building the road
        * @para $settlement is the settlement array
        * @para $roads is the roads array
        */
        function build($player, $settlement, $roads){
            if($this->control!=null) return false;
            
            $i = -1;
            $resRemoveList = array();
            $requiredRes = array("Brick", "Lumber", "Wool", "Grain");
            foreach($player->resCard as &$card){
                $i++;
                if(in_array($card->type, $requiredRes)){
                    array_push($resRemoveList, $i);
                    unset($requiredRes[array_search($card->type, $requiredRes)]);
                }
            }
            
            if(!empty($requiredRes)) return false;
            
            $hasAdjacency = false;
            $hasRoad = false;
            foreach($this->road as &$rdIndex){
                foreach($roads[$rdIndex]->settlement as &$setIndex){
                    if($settlement[$setIndex]->control!=null)
                        $hasAdjacency = true;
                }
                if($roads[$rdIndex]->control==$player->control)
                    $hasRoad = true;
            }
            
            if((!$hasRoad)||($hasAdjacency)) return false;
            
            $this->control = $player->color;
            array_push($player->settlement, $this->index);
            
            foreach($resRemoveList as &$index){
                unset($player->resCard[$index]);
            }
            
            $player->resCard = array_values($player->resCard);
            
            return true;
        }
        
        function upgradeToCity($player){
            if($this->control==null) return false;
            
            $i = -1;
            $resRemoveList = array();
            $requiredRes = array("Ore", "Ore", "Ore", "Grain", "Grain");
            foreach($player->resCard as &$card){
                $i++;
                if(in_array($card->type, $requiredRes)){
                    array_push($resRemoveList, $i);
                    unset($requiredRes[array_search($card->type, $requiredRes)]);
                }
            }
            
            if(!empty($requiredRes)) return false;
            
            foreach($resRemoveList as &$index){
                unset($player->resCard[$index]);
            }
            
            $player->resCard = array_values($player->resCard);
            
            $this->isCity = true;
            return true;
        }
    }

    class Road{
        public $control; //Player.color if active, otherwise null
        public $settlement = array();
        
        function __construct($map, $i){
            $this->control = null;
            $rd = $map['roads'][$i];
            $sett = $map['settlements'];
            
            $source = -1;
            $target = -1;
            
            for($j = 0; $j<54; $j++){
                if($rd[source]==$sett[$j][settle_id]){
                    $source = $j;
                    break;
                }
            }
            
            for($j = 0; $j<54; $j++){
                if($rd[target]==$sett[$j][settle_id]){
                    $target = $j;
                    break;
                }
            }
            
            array_push($this->settlement, $source, $target);
        }
        
        function build($player, $settlement, $road){
            if($this->control!=null) return false;
            
            $i = -1;
            $resRemoveList = array();
            $requiredRes = array("Brick", "Lumber");
            foreach($player->resCard as &$card){
                $i++;
                if(in_array($card->type, $requiredRes)){
                    array_push($resRemoveList, $i);
                    unset($requiredRes[array_search($card->type, $requiredRes)]);
                }
            }
            
            if(!empty($requiredRes)) return false;
            
            $hasRoad = false;
            $hasSettlement = false;
            
            foreach($this->settlement as &$setIndex){
                if($settlement[$setIndex]->control==$player->color) $hasSettlement = true;
                foreach($settlement[$setIndex]->road as &$rdIndex){
                    if($road[$rdIndex]->control==$player->color) $hasRoad = true;
                }
            }
            
            if((!$hasRoad)&&(!$hasSettlement)) return false;
            
            foreach($resRemoveList as &$index){
                unset($player->resCard[$index]);
            }
            
            $player->resCard = array_values($player->resCard);
            
            $this->control = $player->color;
            return true;
        }
    }

    class ResCard{
        public $type;
        function __construct($type){
            $this->type = $type;
        }
    }

?>