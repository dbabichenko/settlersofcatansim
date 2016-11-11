<?php
/**
 * Created by PhpStorm.
 * User: zigchg
 * Date: 11/11/16
 * Time: 1:12 PM
 */

$terrain = array();
$banditLocation = null;

if($_SERVER['REQUEST_METHOD']=="GET") {
    $function = $_GET['call'];
    if(function_exists($function)) {
        call_user_func($function);
    } else {
        echo 'Function Not Exists!!';
    }
}

function moveBandit($destination)
{
    echo ("Move bandit function is called with destination of " . $destination);
    global $banditLocation, $terrain;

    foreach($terrain as &$hex){
        if($hex->id == $destination){
            $hex->hasBandit = true;
        }else if($hex->id == $banditLocation){
            $hex->hasBandit = false;
        }
    }

    $banditLocation = $destination;

    echo ("Move bandit successfully.");
    return true;
}

?>