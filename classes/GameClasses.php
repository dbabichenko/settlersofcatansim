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
            
        }
        
        function produceResource($sumOfDices){
            
        }
        
    }

    class Player {
        public $color;
        public $victoryPoints;
        public $settlements = array(); // stores indexes of settlement array
        public $resCard = array();
        public $devCard = array();
        public $longestPath;
        public $numKnights;
        
        function __construct($color){
            $this->color = $color;
        }
        
        function tradeWithBank($tradeInAmount, $tradeInType, $getType){
            
        }
        
        function tradeWithPlayer($tradeInAmount, $tradeInType, $getType, $askRatio){
            
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
            $index = mt_rand(0, length-1);
            
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
        public $control; //Player.color if active, otherwise null
        public $terrain = array();
        public $road = array();
        public $isCity;
        
        function __construct($map, $i){
            $sett = $map['settlements'][$i];
            $hex = $map['tiles'];
            $rds = $map['roads'];
                
            $this->id = sett[settle_id];
            $this->control = null;
            
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
            
            $this->isCity = null;
        }
        
        function build($intersectionID, $control){
            
        }
        
        function upgradeToCity($settlementID){
            
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
        
        function build($start, $end, $control){
            
        }
    }

?>