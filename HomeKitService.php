<?
class HomeKitService extends IPSModule {

  public function ReceiveData($JSONString) {
    $this->SendDebug('ReceiveData',$JSONString, 0);
    $data = json_decode($JSONString);
    // Buffer decodieren und in eine Variable schreiben
    $Buffer = utf8_decode($data->Buffer);
    // Und Diese dann wieder dekodieren
    $HomebridgeData = json_decode($Buffer);

    //Prüfen ob die ankommenden Daten für den Lightbulb sind wenn ja, Status abfragen
    if ($HomebridgeData->Action == "get") {
      $this->getVar($HomebridgeData->Device, $HomebridgeData->Characteristic);
    }
    //Prüfen ob die ankommenden Daten für den Lightbulb sind wenn ja, Status setzen
    if ($HomebridgeData->Action == "set") {
      $this->setVar($HomebridgeData->Device, $HomebridgeData->Value, $HomebridgeData->Characteristic);
    }
  }

  protected function RegisterMessages($SenderIDs, $NachrichtenID) {
    foreach ($SenderIDs as $SenderID) {
      $this->RegisterMessage(intval($SenderID), $NachrichtenID);
    }
  }

  protected function UnregisterMessages($SenderIDs, $NachrichtenID) {
    foreach ($SenderIDs as $SenderID) {
      $this->UnregisterMessage(intval($SenderID), $NachrichtenID);
    }
  }

    public function removeAccessory($DeviceCount) {
    //Payload bauen
    $DeviceName = $this->ReadPropertyString("DeviceName{$DeviceCount}");
    $payload["name"] = $DeviceName;

    $array["topic"] ="remove";
    $array["payload"] = $payload;
    $data = json_encode($array);
    $SendData = json_encode(Array("DataID" => "{018EF6B5-AB94-40C6-AA53-46943E824ACF}", "Buffer" => $data));
    $this->SendDebug('Remove',$SendData,0);
    $this->SendDataToParent($SendData);
    return "Gelöscht!";
    }

    protected function ConvertVariable($variable, $value) {
      switch ($variable["VariableType"]) {
        case 0: // boolean
          return boolval($value);
        case 1: // integer
          return intval($value);
        case 2: // float
          return floatval($value);
        case 3: // string
          return strval($value);
      }
    }

  protected function sendJSONToParent($topic,$Characteristic,$DeviceName,$value) {
    $JSON['DataID'] = "{018EF6B5-AB94-40C6-AA53-46943E824ACF}";

    $array["topic"] = $topic;
    $array["Characteristic"] = $Characteristic;
    $array["Device"] = $DeviceName;
    $array["value"] = $value;
    $data = json_encode($array);
    $SendData = json_encode(Array("DataID" => "{018EF6B5-AB94-40C6-AA53-46943E824ACF}", "Buffer" => $data));
    $this->SendDataToParent($SendData);
  }

  protected function SetValueToIPS($variable,$variableObject,$result) {
    if ($variable["VariableAction"] > 0) {
      IPS_RequestAction($variable["VariableAction"], $variableObject['ObjectIdent'], $result);
    } else {
      SetValue($variable["VariableID"],$result);
    }
  }

  protected function hex2rgb($hex) {
     $hex = str_replace("#", "", $hex);

     if(strlen($hex) == 3) {
        $r = hexdec(substr($hex,0,1).substr($hex,0,1));
        $g = hexdec(substr($hex,1,1).substr($hex,1,1));
        $b = hexdec(substr($hex,2,1).substr($hex,2,1));
     } else {
        $r = hexdec(substr($hex,0,2));
        $g = hexdec(substr($hex,2,2));
        $b = hexdec(substr($hex,4,2));
     }
     $rgb = array($r, $g, $b);
     //return implode(",", $rgb); // returns the rgb values separated by commas
     return $rgb; // returns an array with the rgb values
  }

  protected function rgbToHsl( $r, $g, $b ) {
  	$oldR = $r;
  	$oldG = $g;
  	$oldB = $b;
  	$r /= 255;
  	$g /= 255;
  	$b /= 255;
      $max = max( $r, $g, $b );
  	$min = min( $r, $g, $b );
  	$h;
  	$s;
  	$l = ( $max + $min ) / 2;
  	$d = $max - $min;
      	if( $d == 0 ){
          	$h = $s = 0; // achromatic
      	} else {
          	$s = $d / ( 1 - abs( 2 * $l - 1 ) );
  		switch( $max ){
  	            case $r:
  	            	$h = 60 * fmod( ( ( $g - $b ) / $d ), 6 );
                          if ($b > $g) {
  	                    $h += 360;
  	                }
  	                break;
  	            case $g:
  	            	$h = 60 * ( ( $b - $r ) / $d + 2 );
  	            	break;
  	            case $b:
  	            	$h = 60 * ( ( $r - $g ) / $d + 4 );
  	            	break;
  	        }
  	}
    $s = $s * 100;
    $l = $l * 100;

  	return array( round( $h, 2 ), round( $s, 2 ), round( $l, 2 ) );
  }

  function HSLToRGB($h, $s, $l){

          $r = $l;
          $g = $l;
          $b = $l;
          $v = ($l <= 0.5) ? ($l * (1.0 + $s)) : ($l + $s - $l * $s);
          if ($v > 0){
                $m;
                $sv;
                $sextant;
                $fract;
                $vsf;
                $mid1;
                $mid2;

                $m = $l + $l - $v;
                $sv = ($v - $m ) / $v;
                $h *= 6.0;
                $sextant = floor($h);
                $fract = $h - $sextant;
                $vsf = $v * $sv * $fract;
                $mid1 = $m + $vsf;
                $mid2 = $v - $vsf;

                switch ($sextant)
                {
                      case 0:
                            $r = $v;
                            $g = $mid1;
                            $b = $m;
                            break;
                      case 1:
                            $r = $mid2;
                            $g = $v;
                            $b = $m;
                            break;
                      case 2:
                            $r = $m;
                            $g = $v;
                            $b = $mid1;
                            break;
                      case 3:
                            $r = $m;
                            $g = $mid2;
                            $b = $v;
                            break;
                      case 4:
                            $r = $mid1;
                            $g = $m;
                            $b = $v;
                            break;
                      case 5:
                            $r = $v;
                            $g = $m;
                            $b = $mid2;
                            break;
                }
          }
          return array('r' => $r * 255.0, 'g' => $g * 255.0, 'b' => $b * 255.0);
  }

  protected function rgb2hex($rgb) {
     $hex = str_pad(dechex($rgb[0]), 2, "0", STR_PAD_LEFT);
     $hex .= str_pad(dechex($rgb[1]), 2, "0", STR_PAD_LEFT);
     $hex .= str_pad(dechex($rgb[2]), 2, "0", STR_PAD_LEFT);

     return $hex; // returns the hex value including the number sign (#)
   }
}
?>
