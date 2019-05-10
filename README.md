# SicWebMH
Conexión con el SIC de MH
Clase hecha en PHP.

Permite consultar la cédula del emisor en el ministerio de hacienda.

$consulta = new SicWebMH();

//$tipo  debe ser uno de Fisico,Juridico,DIMEX;
$tipo = "Fisico";

//Cédula de 9 o mas;
$cedula = "999999999";

$resulta = $consulta->BuscaCedula($cedula,$tipo);
echo $resulta["CEDULA"] . '<br>';
echo $resulta['NOMBRECOMPLETO'];


