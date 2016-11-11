<?php
/**
 * Created by PhpStorm.
 * User: zigchg
 * Date: 11/11/16
 * Time: 12:11 PM
 */

if($_SERVER['REQUEST_METHOD']=="GET") {
    $function = $_GET['call'];
    if(function_exists($function)) {
        call_user_func($function);
    } else {
        echo 'Function Not Exists!!';
    }
}


function rollingDice()
{

    $diceA = mt_rand(0, 6);
    $diceB = mt_rand(0, 6);
    $sumOfDices = $diceA + $diceB;


    echo $sumOfDices;
}

?>