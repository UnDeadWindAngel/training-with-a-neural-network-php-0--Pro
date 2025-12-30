<?php

$MyCite = "Волгоград";
$MyBD = 1993;
$CurrentYear = date("Y");

$MyAge = $CurrentYear - $MyBD;

if ($MyCite == "Москва") {
        echo "Да я живу в москве!";
    }
else{
    echo "Мой город $MyCite!";
}

echo "<br>";
echo "Мой возраст $MyAge";