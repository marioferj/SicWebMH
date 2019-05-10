<?php
    include "sicwebmh.php";
    $consulta = new SicWebMH();

    //$tipo  debe ser uno de Fisico,Juridico,DIMEX
    $tipo = "Fisico";
    //Cédula de 9 o mas
    $cedula = "999999999";
    $resulta = $consulta->BuscaCedula($cedula,$tipo);
    echo $resulta["CEDULA"] . '<br>';
    echo $resulta['NOMBRECOMPLETO'];
?>
