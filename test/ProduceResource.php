<?php
/**
 * Created by PhpStorm.
 * User: zigchg
 * Date: 11/11/16
 * Time: 1:07 PM
 */

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
                // discard function
                // player input the type of card to discard
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

?>
