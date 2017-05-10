<?

require_once(__DIR__ . "/../HomeKitService.php");
class IPS_HomebridgeRGB extends HomeKitService {
  public function Create() {
      //Never delete this line!
      parent::Create();
      //Anzahl die in der Konfirgurationsform angezeigt wird - Hier Standard auf 1
      $this->RegisterPropertyInteger("Anzahl",1);
      $this->ConnectParent("{86C2DE8C-FB21-44B3-937A-9B09BB66FB76}");
      //99 Geräte können pro Konfirgurationsform angelegt werden
      for($count = 1; $count -1 < 99; $count++) {
        $DeviceName = "DeviceName{$count}";
        $RGBID = "RGBID{$count}";
        $VariableState = "VariableState{$count}";
        $VariableStateTrue = "VariableStateTrue{$count}";
        $VariableStateFalse = "VariableStateFalse{$count}";
        $VariableRGB = "VariableRGB{$count}";
        //$VariableBrightness = "VariableBrightness{$count}";
        //$VariableBrightnessOptional = "VariableBrightnessOptional{$count}";
        //$VariableBrightnessMax = "VariableBrightnessMax{$count}";
        $this->RegisterPropertyString($DeviceName, "");
        $this->RegisterPropertyInteger($RGBID, 0);
        $this->RegisterPropertyInteger($VariableStateTrue, 1);
        $this->RegisterPropertyInteger($VariableStateFalse, 0);
        $this->RegisterPropertyInteger($VariableState, 0);
        $this->RegisterPropertyInteger($VariableRGB, 0);
        //$this->RegisterPropertyBoolean($VariableBrightnessOptional, false);
        //$this->RegisterPropertyInteger($VariableBrightnessMax, 100);
        //$this->RegisterPropertyInteger($VariableBrightness, 0);
      }
  }
  public function ApplyChanges() {
      //Never delete this line!
      parent::ApplyChanges();
      //Setze Filter für ReceiveData
      $this->SetReceiveDataFilter(".*RGB.*");
      $anzahl = $this->ReadPropertyInteger("Anzahl");
      $Devices = [];
      for($count = 1; $count-1 < $anzahl; $count++) {
        $Devices[$count]["DeviceName"] = $this->ReadPropertyString("DeviceName{$count}");
        $Devices[$count]["VariableState"] = $this->ReadPropertyInteger("VariableState{$count}");
        $Devices[$count]["VariableStateTrue"] = $this->ReadPropertyInteger("VariableStateTrue{$count}");
        $Devices[$count]["VariableStateFalse"] =  $this->ReadPropertyInteger("VariableStateFalse{$count}");
        $Devices[$count]["VariableRGB"] =  $this->ReadPropertyInteger("VariableRGB{$count}");
        //$Devices[$count]["VariableBrightness"] = $this->ReadPropertyInteger("VariableBrightness{$count}");
        //$Devices[$count]["VariableBrightnessMax"] = $this->ReadPropertyInteger("VariableBrightnessMax{$count}");
        //$Devices[$count]["VariableBrightnessOptional"] = $this->ReadPropertyBoolean("VariableBrightnessOptional{$count}");
        $BufferNameState = $Devices[$count]["DeviceName"]." State";
        $BufferNameRGB = $Devices[$count]["DeviceName"]." RGB";
        //Alte Registrierungen auf Variablen Veränderung aufheben
        $UnregisterBufferIDs = [];
        array_push($UnregisterBufferIDs,$this->GetBuffer($BufferNameState));
        array_push($UnregisterBufferIDs,$this->GetBuffer($BufferNameRGB));
        $this->UnregisterMessages($UnregisterBufferIDs, 10603);
        if ($Devices[$count]["DeviceName"] != "") {
          //Regestriere State Variable auf Veränderungen
          $RegisterBufferIDs = [];
          array_push($RegisterBufferIDs,$Devices[$count]["VariableState"]);
          array_push($RegisterBufferIDs,$Devices[$count]["VariableRGB"]);
          $this->RegisterMessages($RegisterBufferIDs, 10603);
          //Buffer mit den aktuellen Variablen IDs befüllen für State und Brightness
          $this->SetBuffer($BufferNameState,$Devices[$count]["VariableState"]);
          $this->SetBuffer($BufferNameRGB,$Devices[$count]["VariableRGB"]);
          $this->addAccessory($Devices[$count]["DeviceName"]);
        } else {
          return;
        }
      }
      $DevicesConfig = serialize($Devices);
      $this->SetBuffer("RGB Config",$DevicesConfig);
    }
  public function Destroy() {
  }
  public function MessageSink($TimeStamp, $SenderID, $Message, $Data) {
    $Devices = unserialize($this->getBuffer("RGB Config"));
    if ($Data[1] == true) {
      $anzahl = $this->ReadPropertyInteger("Anzahl");
      for($count = 1; $count-1 < $anzahl; $count++) {
        $Device = $Devices[$count];
        $DeviceName = $Device["DeviceName"];

        //Prüfen ob die SenderID gleich der State oder Brightness Variable ist, dann den aktuellen Wert an die Bridge senden
        if ($SenderID == $Device["VariableState"]) {
          $Characteristic = "On";
          $data = $Data[0];
          if ($data > 0) {
            $data = $Device["VariableStateTrue"];
          }
          switch ($data) {
            case $Device["VariableStateTrue"]:
              $result = 'true';
              break;
            case $Device["VariableStateFalse"]:
              $result = 'false';
              break;
          }
          $this->sendJSONToParent("setValue", $Characteristic, $DeviceName, $result);
        }
        if ($SenderID == $Device["VariableRGB"]) {
          $data = $Data[0];

            $RGB = $this->hex2rgb(dechex($data));
            $HLS = $this->rgbToHsl($RGB[0],$RGB[1],$RGB[2]);
            if ($HLS[0] < 0 AND $HLS[1] < 0 and $HLS[2] < 0) {
              $this->SendDebug("MessageSink aus", $HLS,0);
              $this->sendJSONToParent("setValue", "Hue", $DeviceName, 0);
              $this->sendJSONToParent("setValue", "Saturation", $DeviceName, 0);
              $this->sendJSONToParent("setValue", "Brightness", $DeviceName, 0);
              $this->sendJSONToParent("setValue", "On", $DeviceName, 'false');
            }
            else {
              $this->SendDebug("MessageSink an", $HLS,0);
              $this->sendJSONToParent("setValue", "Hue", $DeviceName, number_format($HLS[0], 2, '.', ''));
              $this->sendJSONToParent("setValue", "Saturation", $DeviceName, number_format($HLS[1], 2, '.', ''));
              $this->sendJSONToParent("setValue", "Brightness", $DeviceName, number_format($HLS[2], 2, '.', ''));
              $this->sendJSONToParent("setValue", "On", $DeviceName, 'true');
            }
        }
      }
    }
  }

  public function GetConfigurationForm() {
    $anzahl = $this->ReadPropertyInteger("Anzahl");
    $form = '{"elements":
              [
                { "type": "NumberSpinner", "name": "Anzahl", "caption": "Anzahl" },';
    // Zählen wieviele Felder in der Form angelegt werden müssen
    for($count = 1; $count-1 < $anzahl; $count++) {
      $form .= '{ "type": "ValidationTextBox", "name": "DeviceName'.$count.'", "caption": "Gerätename für die Homebridge" },';
      $form .= '{ "type": "SelectInstance", "name": "RGBID'.$count.'", "caption": "Gerät" },';
      //$form .= '{ "type": "SelectVariable", "name": "VariableState'.$count.'", "caption": "Status" },';
      //$form .= '{ "type": "ValidationTextBox", "name": "VariableStateTrue'.$count.'", "caption": "Value True (On)" },';
      //$form .= '{ "type": "ValidationTextBox", "name": "VariableStateFalse'.$count.'", "caption": "Value False (Off)" },';
      $form .= '{ "type": "SelectVariable", "name": "VariableRGB'.$count.'", "caption": "RGB" },';
      $form .= '{ "type": "Button", "label": "Löschen", "onClick": "echo HBRGB_removeAccessory('.$this->InstanceID.','.$count.');" },';
      if ($count == $anzahl) {
        $form .= '{ "type": "Label", "label": "------------------" }';
      } else {
        $form .= '{ "type": "Label", "label": "------------------" },';
      }
    }
    $form .= ']}';
    return $form;
  }
  public function getVar($DeviceName, $Characteristic) {
    $Devices = unserialize($this->getBuffer("RGB Config"));
    $anzahl = $this->ReadPropertyInteger("Anzahl");
    for($count = 1; $count -1 < $anzahl; $count++) {
      $Device = $Devices[$count];
      //Prüfen ob der übergebene Name aus dem Socket zu einem Namen aus der Konfirgurationsform passt
      $name = $Device["DeviceName"];
      if ($DeviceName == $name) {
        //IPS Variable abfragen
        switch ($Characteristic) {
          case 'On':
            //RGB State abfragen
            $result = dechex(GetValue($Device["VariableRGB"]));
            if ($result != 0) {
              $result = 'true';
            }
            else {
              $result = 'false';
            }
            break;
          case 'Hue':
            //Lightbulb Hue abfragen
            $result = dechex(GetValue($Device["VariableRGB"]));
            $RGB = $this->hex2rgb(dechex($result));
            $HLS = $this->rgbToHsl($RGB[0],$RGB[1],$RGB[2]);
            $result = number_format($HLS[0], 2, '.', '');
            break;
        case 'Saturation':
          //Lightbulb Saturation abfragen
          $result = dechex(GetValue($Device["VariableRGB"]));
          $RGB = $this->hex2rgb(dechex($result));
          $HLS = $this->rgbToHsl($RGB[0],$RGB[1],$RGB[2]);
          $result = number_format($HLS[1], 2, '.', '');
          break;
        case 'Brightness':
          //Lightbulb Brightness abfragen
          $result = dechex(GetValue($Device["VariableRGB"]));
          $RGB = $this->hex2rgb(dechex($result));
          $HLS = $this->rgbToHsl($RGB[0],$RGB[1],$RGB[2]);
          $result = intval($HLS[2]);
          break;
    }
        //Status an die Bridge senden
        $this->sendJSONToParent("callback", $Characteristic, $DeviceName, $result);
        return;
      }
    }
  }

  public function setVar($DeviceName, $value, $Characteristic) {
    $Devices = unserialize($this->getBuffer("RGB Config"));
    $anzahl = $this->ReadPropertyInteger("Anzahl");
    for($count = 1; $count -1 < $anzahl; $count++) {
      $Device = $Devices[$count];
      //Prüfen ob der übergebene Name aus dem Hook zu einem Namen aus der Konfirgurationsform passt
      $name = $Device["DeviceName"];
      if ($DeviceName == $name) {
        switch ($Characteristic) {
          case 'On':
            $result = dechex(GetValue($Device["VariableRGB"]));
            if ($result != 0) {
              $result = '000000';
            }
            else {
              $result = 'FFFFFF';
            }
            $variable = IPS_GetVariable($Device["VariableRGB"]);
            $variableObject = IPS_GetObject($Device["VariableRGB"]);
            $result = $this->ConvertVariable($variable, $value);
            //Geräte Variable setzen
            $this->SetValueToIPS($variable,$variableObject,$result);
            break;
          case 'Brightness':
            break;
        }
      }
    }
  }
  private function addAccessory($DeviceName) {
    //Payload bauen
    $payload["name"] = $DeviceName;
    $payload["service"] = "Lightbulb";
    $payload["Brightness"] = "default";
    $payload["Hue"] = "default";
    $payload["Saturation"] = "default";

    $array["topic"] ="add";
    $array["payload"] = $payload;
    $data = json_encode($array);
    $SendData = json_encode(Array("DataID" => "{018EF6B5-AB94-40C6-AA53-46943E824ACF}", "Buffer" => $data));
    @$this->SendDataToParent($SendData);
  }
}
?>
