<?php
    class Game {
        public $color = array();
        public $numPlayers;
        public $players = array();
        public $terrain = array();
        public $settlement = array();
        public $road =  = array();
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
            
            for ($i = 0; $i<54; $i++){
                $this->road[$i] = new road($map['roads'][$i]);
            }
            
            for ($i = 0; $i<19; $i++){
                $this->terrain[$i] = new terrain($map['tiles'][$i]);
            }
            
            for ($i = 0; $i<54; $i++){
                $this->settlement[$i] = new Settlement($map, $i);
            }
            
            for ($i = 0; $i<$numPlayers; $i++){
                $this->color[$i] = $i;
                
                $this->players[$i] = new Player($i);
            }
        }
        
    }

    class Player {
        public $color;
        public $victoryPoints;
        public $settlements = array();
        public $resCard = array();
        public $devCard = array();
        public $longestPath;
        public $numKnights;
        
        function __construct($color){
            $this->color = $color;
            
        }
        
        function steal($targetPlayer){
            
        }
    }

    class Terrain{
        public $id;
        public $resourceType;
        public $settlement = array();
        public $diceValue;
        public $hasBandit;
        public $portType; //JSON file put harbor in settlements
        
        function __construct($terr){
            $this->id = $terr[tile_id];
            $this->resourceType = $terr[resourceType];
            $this->diceValue = $terr[coordinates];
            $this->hasBandit = $terr[hasRobber];
            
            //$this->portType
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
                
            $this->id = sett[settle_id];
            $this->control = null;
            
            $sequence = array(0,3,7,12,16);
            foreach($sett[tiles] as &$value){
                $hexIndex = $sequence[floor($value/10)-1]+($value%10)-1;
                // convert tile_id to index in array
                
                array_push($this->terrain, );
            }
            
            foreach($sett[roads] as &$value){
                array_push($this->road, $value);
            }
            
            $this->isCity = null;
        }
    }

    class Road{
        public $control; //Player.color if active, otherwise null
        public $settlement = array();
        
        function __construct($rd){
            this->control = null;
            array_push(this->$settlement, $rd[source], $rd[target]);
        }
    }

?>