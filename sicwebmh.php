<?php
/**
 * Funciones
 * Creado por Mario Fernandez J
 * Fecha de Creacion 08/05/2019 12:55:27 PM
 *
 */

 class SicWebMH
{
     
    private $passphrase  = "34e976ff";    
    private $_xml;

    /*
     * Consulta la cedula en MH
     * $tipo Fisico or Juridico or DIMEX
     */
    public function BuscaCedula($cedula,$tipo="Fisico")
    {
        try
        {
            $servicio ="https://sicexterno.hacienda.go.cr/WS_SICWEB_EXTERNO/Service1.asmx?wsdl"; //url del servicio
            $parametros = array(array(
                'origen' => $tipo,
                'cedula' => (strlen($cedula) > 10)? $cedula: $this->TrataCedula($cedula),
                'ape1' => null,
                'ape2' => null,
                'nomb1' => null,
                'nomb2' => null,
                'razon' => null,
                'Concatenado' => null,
                'Usuario' => base64_decode($this->Password())));
            $client = new SoapClient($servicio,array('trace' => true));
            
            $return = $client->__soapCall('ObtenerDatos',$parametros);
            $this->_xml = new SimpleXMLElement($client->__getLastResponse());
            $result = array(
                'CEDULA'    =>$this->CargarNodo('CEDULA'),
                'APELLIDO1' =>$this->CargarNodo('APELLIDO1'),
                'APELLIDO2' =>$this->CargarNodo('APELLIDO2'),
                'NOMBRE1'   =>$this->CargarNodo('NOMBRE1'),
                'NOMBRE2'   =>$this->CargarNodo('NOMBRE2'),
                'ADM'       =>$this->CargarNodo('ADM'),
                'ORI'       =>$this->CargarNodo('ORI')
            );
            $result['NOMBRECOMPLETO'] = $result['APELLIDO1']. ' ' . $result['APELLIDO2'] . ' ' . $result['NOMBRE1'] . ' ' . $result['NOMBRE2'] ;
            return $result;
        }
        catch(Exception $e)
        {
            return $e;
        }
    }

    protected function CargarNodo($Clave)
    {       
        $tmp = "";
        $Nodo = $this->_xml->xpath("//".$Clave);
        if ($Nodo == true)
        {
            $tmp = $Nodo[0];
        }
        return $tmp;
    }
    /*
     * Le agrega los 0 que corresponda a la cedula
     */
    protected function TrataCedula($valor)
    {
        $Temp = ""; $nCeros = "";$resulta = "";
        If (strlen($valor) <= 10)
        {
            $nCeros = str_pad($nCeros,10 - strlen($valor), "0");
            $Temp =$this->calculaDigitoCedula($nCeros . $valor);
            if (strlen($Temp) > 0)
            {
                $resulta = $nCeros . $valor . $Temp;
            }
        }
        return $resulta;
    }
    /*
     * Calcula el digito verificador de la cedula
     */
    protected function calculaDigitoCedula($Numero="")
    {
        $str = "";
        $Digito = "00"; $Conta = 0; $Indice = 0; $strTemp = ""; $I = 0; $Ksiduo = 0;

        if ((empty($Numero) == false And strlen($Numero) == 10 )? is_numeric($Numero):false)
        {
            $Indice = 11;
            $strTemp = $Numero;
            $I = 1;

            while($I <= 10)
            {
                $Conta = $Conta + (int)($this->left($strTemp, 1)) * $Indice;
                $Indice = $Indice - 1;
                $strTemp = $this->right($Numero, strlen($Numero) - $I);
                $I = $I + 1;
            }

            $Ksiduo = $Conta - intval($Conta / 37) * 37;
            $Indice = 37 - $Ksiduo;
            if ($Indice >= 10)
            {
                $Digito = $Indice;
            }
            else
            {
                $Digito = "0" . $Indice ;
            }
            $str = $Digito;
        }
        else
        {
            $str = "";
        }

        return $str;
    }
    /*
     * Funciones utilitarias
     */
    private function left($str, $length)
    {
         return substr($str, 0, $length);
    }
    private function right($str, $length)
    {
         return substr($str, -$length);
    }

    /*
     * Genera el password de hacienda
     */
    protected function Password()
    {
        date_default_timezone_set('America/Costa_Rica');
        $Str =  date("Ymd");
        $Str .= "_DECLARA7_";
        $Str .= $this->CrearPassword(10);
        $Str = $this->EncriptoDescriptoTexto($Str, $this->passphrase);
        return $Str;
    }

    /*
     * Encipta el password con la clave
     */
    protected function EncriptoDescriptoTexto($strValor = "", $strClave = "")
    {
        try
        {
            $key = $this->CreateKey($strClave);
            $iv = $this->CreateIV($strClave);
            
            $ciphertext_raw = openssl_encrypt($strValor, "AES-256-CBC", $key, OPENSSL_RAW_DATA, $iv);
            return  base64_encode( $ciphertext_raw );

//            Esto es para usar la funcion mcrypt_encrypt que esta en desuso en php 7.2
//
//            $block = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
//            $padding = $block - (strlen($strValor) % $block);
//            $strValor .= str_repeat(chr($padding), $padding);
//            $ciphertext =  mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $strValor, MCRYPT_MODE_CBC, $iv);
//            $crypttext64 = base64_encode($ciphertext);
//            return $crypttext64;
        }
        catch(Exception $e)
        {
            echo $e;
        }
    }
    /*
     * Crea un password aleatorio
     */
    protected function CrearPassword($longitud)
    {
        $str  = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
        $stringBuilder = "";
        $random = 0;
        @rand();
        while(true)
        {
            $num = $longitud;
            $longitud = $num - 1;

            If (0 >= $num)
            {
                break;
            }

            $i = rand(0,strlen($str)-1);
            $stringBuilder .= $str[$i];
        }
        return $stringBuilder;
    }
    /*
     * Crea IV para encriptar
     */
    protected function CreateIV($strPassword="")
    {
        $numArray  = unpack('C*', pack('H*', hash("sha512",$strPassword)));
        $numArray1 = array();

        for ($i = 33; $i <= 48;$i++)
        {
            $numArray1[] = $numArray[$i];
        }
        return $this->byteArray2String($numArray1);
    }
    /*
     * Crea Key para encriptar
     */
    protected function CreateKey($strPassword="")
    {
        $numArray  = unpack('C*', pack('H*', hash("sha512",$strPassword)));
        $numArray1 = array();

        for ($i = 1; $i <= 32;$i++)
        {
            $numArray1[] = $numArray[$i];
        }
        return $this->byteArray2String($numArray1);
    }
    /*
     * Convierte un arrary de bytes a cadena
     */
    protected function byteArray2String($byteArray)
    {
        $chars = array_map("chr", $byteArray);
        return join($chars);
    }
    
}

?>
