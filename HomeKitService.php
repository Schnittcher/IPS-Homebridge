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

  protected function hslToRgb( $h, $s, $l ){
      $r;
      $g;
      $b;
  	$c = ( 1 - abs( 2 * $l - 1 ) ) * $s;
  	$x = $c * ( 1 - abs( fmod( ( $h / 60 ), 2 ) - 1 ) );
  	$m = $l - ( $c / 2 );
  	if ( $h < 60 ) {
  		$r = $c;
  		$g = $x;
  		$b = 0;
  	} else if ( $h < 120 ) {
  		$r = $x;
  		$g = $c;
  		$b = 0;
  	} else if ( $h < 180 ) {
  		$r = 0;
  		$g = $c;
  		$b = $x;
  	} else if ( $h < 240 ) {
  		$r = 0;
  		$g = $x;
  		$b = $c;
  	} else if ( $h < 300 ) {
  		$r = $x;
  		$g = 0;
  		$b = $c;
  	} else {
  		$r = $c;
  		$g = 0;
  		$b = $x;
  	}
  	$r = ( $r + $m ) * 255;
  	$g = ( $g + $m ) * 255;
  	$b = ( $b + $m  ) * 255;
      return array( floor( $r ), floor( $g ), floor( $b ) );
  }

  protected function rgb2hex($rgb) {
     $hex = str_pad(dechex($rgb[0]), 2, "0", STR_PAD_LEFT);
     $hex .= str_pad(dechex($rgb[1]), 2, "0", STR_PAD_LEFT);
     $hex .= str_pad(dechex($rgb[2]), 2, "0", STR_PAD_LEFT);
  
     return $hex; // returns the hex value including the number sign (#)
}
?>
