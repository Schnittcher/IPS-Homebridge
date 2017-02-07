<?
class IPS_HomebridgeLightbulb extends IPSModule {
  public function Create() {
      //Never delete this line!
      parent::Create();
      $this->RegisterPropertyInteger("Anzahl",1);
      $this->ConnectParent("{86C2DE8C-FB21-44B3-937A-9B09BB66FB76}");
      for($count = 1; $count -1 < 99; $count++) {
        $DeviceName = "DeviceName{$count}";
        $LightbulbID = "LightbulbID{$count}";
        $VariableState = "VariableState{$count}";
        $VariableBrightness = "VariableBrightness{$count}";
        $VariableBrightnessOptional = "VariableBrightnessOptional{$count}";
        $this->RegisterPropertyString($DeviceName, "");
        $this->RegisterPropertyInteger($LightbulbID, 0);
        $this->RegisterPropertyInteger($VariableState, 0);
        $this->RegisterPropertyBoolean($VariableBrightnessOptional, false);
        $this->RegisterPropertyInteger($VariableBrightness, 0);
      }
  }

  public function ApplyChanges() {
      //Never delete this line!
      parent::ApplyChanges();
      $anzahl = $this->ReadPropertyInteger("Anzahl");
      $this->HasActiveParent();
      for($count = 1; $count-1 < $anzahl; $count++) {
        $DeviceNameID = "DeviceName{$count}";
        $VariableState = "VariableState{$count}";
        $VariableBrightness = "VariableBrightness{$count}";
        $VariableBrightnessOptional = "VariableBrightnessOptional{$count}";
        if (is_int($this->GetBuffer($DeviceNameID." State ".$VariableState))) {
          $this->UnregisterMessage(intval($this->GetBuffer($DeviceNameID." State ".$VariableState)), 10603);
        }
        if (is_int($this->GetBuffer($DeviceNameID." State ".$VariableState))) {
          $this->UnregisterMessage(intval($this->GetBuffer($DeviceNameID." Brightness ".$VariableBrightness)), 10603);
        }
        if ($this->ReadPropertyString($DeviceNameID) != "") {
          $BrightnessBoolean = $this->ReadPropertyBoolean($VariableBrightnessOptional);
          $this->RegisterMessage($this->ReadPropertyInteger($VariableState), 10603);
          $this->RegisterMessage($this->ReadPropertyInteger($VariableBrightness), 10603);
          $this->SetBuffer($DeviceNameID." State ".$VariableState,$this->ReadPropertyInteger($VariableState));
          $this->SetBuffer($DeviceNameID." Brightness ".$VariableBrightness,$this->ReadPropertyInteger($VariableBrightness));
          $this->addAccessory($this->ReadPropertyString($DeviceNameID),$BrightnessBoolean);
        } else {
          return;
        }
      }

    }

  public function Destroy() {
  }

  public function MessageSink($TimeStamp, $SenderID, $Message, $Data) {
    $anzahl = $this->ReadPropertyInteger("Anzahl");
    for($count = 1; $count-1 < $anzahl; $count++) {
      $VariableState = $this->ReadPropertyInteger("VariableState{$count}");
      $VariableBrightness = $this->ReadPropertyInteger("VariableBrightness{$count}");
      $DeviceName = $this->ReadPropertyString("DeviceName{$count}");
      switch ($SenderID) {
        case $VariableState:
          $Characteristic = "On";
          $data = $Data[0];
          $result = ($data) ? 'true' : 'false';
          $JSON['DataID'] = "{018EF6B5-AB94-40C6-AA53-46943E824ACF}";
          $JSON['Buffer'] = utf8_encode('{"topic": "setValue", "Characteristic": "'.$Characteristic.'", "Device": "'.$DeviceName.'", "value": "'.$result.'"}');
          $Data = json_encode($JSON);
          if ($this->HasActiveParent() == true) {
            $this->SendDataToParent($Data);
          }
          else {
            IPS_LogMessage("Homebridge Lightbulb", "Parent nicht aktiv!");
          }
          break;
        case $VariableBrightness:
          $Characteristic = "Brightness";
          $result = $Data[0];
          $JSON['DataID'] = "{018EF6B5-AB94-40C6-AA53-46943E824ACF}";
          $JSON['Buffer'] = utf8_encode('{"topic": "setValue", "Characteristic": "'.$Characteristic.'", "Device": "'.$DeviceName.'", "value": "'.$result.'"}');
          $Data = json_encode($JSON);
          if ($this->HasActiveParent() == true) {
            $this->SendDataToParent($Data);
          }
          else {
            IPS_LogMessage("Homebridge Lightbulb", "Parent nicht aktiv!");
          }
          break;
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
      $form .= '{ "type": "SelectInstance", "name": "LightbulbID'.$count.'", "caption": "Gerät" },';
      $form .= '{ "type": "SelectVariable", "name": "VariableState'.$count.'", "caption": "Status" },';
      $form .= '{ "type": "CheckBox", "name": "VariableBrightnessOptional'.$count.'", "caption": "Dimmbar?" },';
      $form .= '{ "type": "SelectVariable", "name": "VariableBrightness'.$count.'", "caption": "Brightness" },';
      if ($count == $anzahl) {
        $form .= '{ "type": "Label", "label": "------------------" }';
      } else {
        $form .= '{ "type": "Label", "label": "------------------" },';
      }
    }
    $form .= ']}';
    return $form;
  }

  public function ReceiveData($JSONString) {
    $data = json_decode($JSONString);
    // Buffer decodieren und in eine Variable schreiben
    $Buffer = utf8_decode($data->Buffer);
    // Und Diese dann wieder dekodieren
    $HomebridgeData = json_decode($Buffer);
      if ($HomebridgeData->Action == "get" && $HomebridgeData->Service == "Lightbulb") {
        $this->getVar($HomebridgeData->Device, $HomebridgeData->Characteristic);
      }
      if ($HomebridgeData->Action == "set" && $HomebridgeData->Service == "Lightbulb") {
        $this->setVar($HomebridgeData->Device, $HomebridgeData->Value, $HomebridgeData->Characteristic);
      }
  }

  public function getVar($DeviceName, $Characteristic) {
    for($count = 1; $count -1 < $this->ReadPropertyInteger("Anzahl"); $count++) {
      //Hochzählen der Konfirgurationsform Variablen
      $LightbulbID = "LightbulbID{$count}";
      $VariableState = "VariableState{$count}";
      $VariableBrightness = "VariableBrightness{$count}";
      //Prüfen ob der übergebene Name aus dem Socket zu einem Namen aus der Konfirgurationsform passt
      if ($DeviceName == $this->ReadPropertyString("DeviceName{$count}")) {
        //IPS Variable abfragen
        switch ($Characteristic) {
          case 'On':
            //Lightbulb State abfragen
            $result = intval(GetValue($this->ReadPropertyInteger($VariableState)));
            $result = ($result) ? 'true' : 'false';
            break;
          case 'Brightness':
            //Lightbulb Brightness abfragen
            $result = GetValue($this->ReadPropertyInteger($VariableBrightness));
            break;
        }
        $JSON['DataID'] = "{018EF6B5-AB94-40C6-AA53-46943E824ACF}";
        $JSON['Buffer'] = utf8_encode('{"topic": "callback", "Characteristic": "'.$Characteristic.'", "Device": "'.$DeviceName.'", "value": "'.$result.'"}');
        $Data = json_encode($JSON);

        if ($this->HasActiveParent() == true) {
          IPS_LogMessage("GetVAr","drin");
          $this->SendDataToParent($Data);
        }
        else {
          IPS_LogMessage("Homebridge Lightbulb", "Parent nicht aktiv!");
        }
        return;
      }
    }
  }

  public function setVar($DeviceName, $value, $Characteristic) {
    for($count = 1; $count -1 < $this->ReadPropertyInteger("Anzahl"); $count++) {
      //Hochzählen der Konfirgurationsform Variablen
      $LightbulbID = "LightbulbID{$count}";
      $VariableState = "VariableState{$count}";
      $VariableBrightness = "VariableBrightness{$count}";
      //Prüfen ob der übergebene Name aus dem Hook zu einem Namen aus der Konfirgurationsform passt
      if ($DeviceName == $this->ReadPropertyString("DeviceName{$count}")) {
        switch ($Characteristic) {
          case 'On':
            //Lightbulb State abfragen
            $result = intval(GetValue($this->ReadPropertyInteger($VariableState)));
            $result = ($result) ? 'true' : 'false';
            if ($result == true && $value == 0) {
              $variable = IPS_GetVariable($this->ReadPropertyInteger("VariableState{$count}"));
              $variableObject = IPS_GetObject($this->ReadPropertyInteger("VariableState{$count}"));
              //den übgergebenen Wert in den VariablenTyp für das IPS-Gerät umwandeln
              $result = $this->ConvertVariable($variable, $value);
              //Geräte Variable setzen
              IPS_RequestAction($variableObject["ParentID"], $variableObject['ObjectIdent'], $result);
          }
            IPS_LogMessage("lightbulb", $result. " ".$value);
            if ($result == "false" && $value == 1) {
              $variable = IPS_GetVariable($this->ReadPropertyInteger("VariableState{$count}"));
              $variableObject = IPS_GetObject($this->ReadPropertyInteger("VariableState{$count}"));
              //den übgergebenen Wert in den VariablenTyp für das IPS-Gerät umwandeln
              $result = $this->ConvertVariable($variable, $value);
              //Geräte Variable setzen
              IPS_RequestAction($variableObject["ParentID"], $variableObject['ObjectIdent'], $result);
          }
            break;
          case 'Brightness':
            //Lightbulb Brightness abfragen
            $variable = IPS_GetVariable($this->ReadPropertyInteger("VariableBrightness{$count}"));
            $variableObject = IPS_GetObject($this->ReadPropertyInteger("VariableBrightness{$count}"));
            //den übgergebenen Wert in den VariablenTyp für das IPS-Gerät umwandeln
            $result = $this->ConvertVariable($variable, $value);
            //Geräte Variable setzen
            IPS_RequestAction($variableObject["ParentID"], $variableObject['ObjectIdent'], $result);
            break;
        }
      }
    }
  }

  private function addAccessory($DeviceName,$Brightness) {
    $JSON['DataID'] = "{018EF6B5-AB94-40C6-AA53-46943E824ACF}";
    if ($Brightness == true) {
      $JSON['Buffer'] = utf8_encode('{"topic": "add", "name": "'.$DeviceName.'", "service": "Lightbulb", "Brightness": "default"}');
    }
    else {
      $JSON['Buffer'] = utf8_encode('{"topic": "add", "name": "'.$DeviceName.'", "service": "Lightbulb"}');
    }
    $Data = json_encode($JSON);
    @$this->SendDataToParent($Data);
  }

  public function ConvertVariable($variable, $value) {
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
  private function HasActiveParent() {
    $instance = IPS_GetInstance($this->InstanceID);
    if ($instance['ConnectionID'] > 0) {
      $parent = IPS_GetInstance($instance['ConnectionID']);
      if ($parent['InstanceStatus'] == 102) {
        return true;
      }
      return false;
    }
  }
}
?>
