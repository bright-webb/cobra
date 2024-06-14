<?php
    function printLn($string){
        echo $string.'<br>';
    }

    function env($string){
        return $_ENV[$string];
    }