<?
class IPS_HomebridgeLightbulb extends IPSModule {
  public function Create() {
      //Never delete this line!
      parent::Create();
      //Anzahl die in der Konfirgurationsform angezeigt wird - Hier Standard auf 1
      $this->RegisterPropertyInteger("Anzahl",1);
      $this->ConnectParent("{86C2DE8C-FB21-44B3-937A-9B09BB66FB76}");
      //99 Geräte können pro Konfirgurationsform angelegt werden
      for($count = 1; $count -1 < 99; $count++) {
        $DeviceName = "DeviceName{$count}";
        $LightbulbID = "LightbulbID{$count}";
        $VariableState = "VariableState{$count}";
        $VariableStateTrue = "VariableStateTrue{$count}";
        $VariableStateFalse = "VariableStateFalse{$count}";
        $VariableBrightness = "VariableBrightness{$count}";
        $VariableBrightnessOptional = "VariableBrightnessOptional{$count}";
        $this->RegisterPropertyString($DeviceName, "");
        $this->RegisterPropertyInteger($LightbulbID, 0);
        $this->RegisterPropertyInteger($VariableStateTrue, 1);
        $this->RegisterPropertyInteger($VariableStateFalse, 0);
        $this->RegisterPropertyInteger($VariableState, 0);
        $this->RegisterPropertyBoolean($VariableBrightnessOptional, false);
        $this->RegisterPropertyInteger($VariableBrightness, 0);
      }
  }

  public function ApplyChanges() {
      //Never delete this line!
      parent::ApplyChanges();
      //Setze Filter für ReceiveData
      $this->SetReceiveDataFilter(".*Lightbulb.*");
      $anzahl = $this->ReadPropertyInteger("Anzahl");
      for($count = 1; $count-1 < $anzahl; $count++) {

        $DeviceNameCount = "DeviceName{$count}";
        $VariableStateCount = "VariableState{$count}";
        $VariableBrightnessCount = "VariableBrightness{$count}";
        $VariableBrightnessOptionalCount = "VariableBrightnessOptional{$count}";

        $BufferNameState = $DeviceNameCount." State ".$VariableStateCount;
        $BufferNameBrightness = $DeviceNameCount." Brightness ".$VariableBrightnessCount;

        $VariableIDStateBuffer = $this->GetBuffer($BufferNameState);
        $VariableIDBrightnessBuffer = $this->GetBuffer($BufferNameBrightness);

        //Alte Registrierung auf Variablen Veränderung aufheben
        if (is_int($VariableIDStateBuffer)) {
          $this->UnregisterMessage(intval($VariableIDStateBuffer), 10603);
        }
        if (is_int($VariableIDBrightnessBuffer)) {
          $this->UnregisterMessage(intval($VariableIDBrightnessBuffer), 10603);
        }

        if ($this->ReadPropertyString($DeviceNameCount) != "") {
          $BrightnessBoolean = $this->ReadPropertyBoolean($VariableBrightnessOptionalCount);

          //Regestriere State Variable auf Veränderungen
          $NewVariableIDStateBuffer = $this->ReadPropertyInteger($VariableStateCount);
          $this->RegisterMessage($NewVariableIDStateBuffer, 10603);

          //Regestriere Brightness Variable auf Veränderungen
          $NewVariableIDBrightnessBuffer = $this->ReadPropertyInteger($VariableBrightnessCount);
          $this->RegisterMessage($NewVariableIDBrightnessBuffer, 10603);

          //Buffer mit den aktuellen Variablen IDs befüllen für State und Brightness
          $this->SetBuffer($BufferNameState,$this->ReadPropertyInteger($VariableStateCount));
          $this->SetBuffer($BufferNameBrightness,$this->ReadPropertyInteger($VariableBrightnessCount));

          $this->addAccessory($this->ReadPropertyString($DeviceNameCount),$BrightnessBoolean);
        } else {
          return;
        }
      }

    }

  public function Destroy() {
  }

  public function MessageSink($TimeStamp, $SenderID, $Message, $Data) {
    if ($Data[1] == true) {
      $anzahl = $this->ReadPropertyInteger("Anzahl");

      for($count = 1; $count-1 < $anzahl; $count++) {

        $VariableStateCount = $this->ReadPropertyInteger("VariableState{$count}");
        $VariableBrightnessCount = $this->ReadPropertyInteger("VariableBrightness{$count}");
        $DeviceName = $this->ReadPropertyString("DeviceName{$count}");

        //Prüfen ob die SenderID gleich der State oder Brightness Variable ist, dann den aktuellen Wert an die Bridge senden
        switch ($SenderID) {
          case $VariableStateCount:
            $Characteristic = "On";
            $data = $Data[0];
            $result = ($data) ? 'true' : 'false';
            $JSON['DataID'] = "{018EF6B5-AB94-40C6-AA53-46943E824ACF}";
            $JSON['Buffer'] = utf8_encode('{"topic": "setValue", "Characteristic": "'.$Characteristic.'", "Device": "'.$DeviceName.'", "value": "'.$result.'"}');
            $Data = json_encode($JSON);
            $this->SendDataToParent($Data);
            break;
          case $VariableBrightnessCount:
            $Characteristic = "Brightness";
            $result = $Data[0];
            $JSON['DataID'] = "{018EF6B5-AB94-40C6-AA53-46943E824ACF}";
            $JSON['Buffer'] = utf8_encode('{"topic": "setValue", "Characteristic": "'.$Characteristic.'", "Device": "'.$DeviceName.'", "value": "'.$result.'"}');
            $Data = json_encode($JSON);
            $this->SendDataToParent($Data);
            break;
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
      $form .= '{ "type": "SelectInstance", "name": "LightbulbID'.$count.'", "caption": "Gerät" },';
      $form .= '{ "type": "SelectVariable", "name": "VariableState'.$count.'", "caption": "Status" },';

      $form .= '{ "type": "ValidationTextBox", "name": "VariableStateTrue'.$count.'", "caption": "Value True (On)" },';
      $form .= '{ "type": "ValidationTextBox", "name": "VariableStateFalse'.$count.'", "caption": "Value False (Off)" },';

      $form .= '{ "type": "CheckBox", "name": "VariableBrightnessOptional'.$count.'", "caption": "Dimmbar?" },';
      $form .= '{ "type": "SelectVariable", "name": "VariableBrightness'.$count.'", "caption": "Brightness" },';
      $form .= '{ "type": "Button", "label": "Löschen", "onClick": "echo HBLightbulb_removeAccessory('.$this->InstanceID.','.$count.');" },';
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
    $this->SendDebug('ReceiveData',$JSONString, 0);
    $data = json_decode($JSONString);
    // Buffer decodieren und in eine Variable schreiben
    $Buffer = utf8_decode($data->Buffer);
    // Und Diese dann wieder dekodieren
    $HomebridgeData = json_decode($Buffer);

    //Prüfen ob die ankommenden Daten für den Lightbulb sind wenn ja, Status abfragen
    if ($HomebridgeData->Action == "get" && $HomebridgeData->Service == "Lightbulb") {
      $this->getVar($HomebridgeData->Device, $HomebridgeData->Characteristic);
    }
    //Prüfen ob die ankommenden Daten für den Lightbulb sind wenn ja, Status setzen
    if ($HomebridgeData->Action == "set" && $HomebridgeData->Service == "Lightbulb") {
      $this->setVar($HomebridgeData->Device, $HomebridgeData->Value, $HomebridgeData->Characteristic);
    }
  }

  public function getVar($DeviceName, $Characteristic) {
    for($count = 1; $count -1 < $this->ReadPropertyInteger("Anzahl"); $count++) {

      //Hochzählen der Konfirgurationsform Variablen
      $VariableStateCount = "VariableState{$count}";
      $VariableBrightnessCount = "VariableBrightness{$count}";

      $VariableStateTrueCount = "VariableStateTrue{$count}";
      $VariableStateFalseCount = "VariableStateFalse{$count}";

      //Prüfen ob der übergebene Name aus dem Socket zu einem Namen aus der Konfirgurationsform passt
      $name = $this->ReadPropertyString("DeviceName{$count}");
      if ($DeviceName == $name) {
        //IPS Variable abfragen
        switch ($Characteristic) {
          case 'On':
            //Lightbulb State abfragen
            $VariableStateTrue = $this->ReadPropertyInteger($VariableStateTrueCount);
            $VariableStateFalse = $this->ReadPropertyInteger($VariableStateFalseCount);
            $VariableStateID = $this->ReadPropertyInteger($VariableStateCount);
            $result = intval(GetValue($VariableStateID));
            switch ($result) {
              case $VariableStateTrue:
                $result = 'true';
                break;
              case $VariableStateFalse:
                $result = 'false';
                break;
            }
            //$result = ($result) ? 'true' : 'false';
            break;
          case 'Brightness':
            //Lightbulb Brightness abfragen
            $VariableBrightnessID = $this->ReadPropertyInteger($VariableBrightnessCount);
            $result = GetValue($VariableBrightnessID);
            break;
        }
        //Status an die Bridge senden
        $JSON['DataID'] = "{018EF6B5-AB94-40C6-AA53-46943E824ACF}";
        $JSON['Buffer'] = utf8_encode('{"topic": "callback", "Characteristic": "'.$Characteristic.'", "Device": "'.$DeviceName.'", "value": "'.$result.'"}');
        $Data = json_encode($JSON);
        $this->SendDataToParent($Data);
        return;
      }
    }
  }

  public function setVar($DeviceName, $value, $Characteristic) {
    for($count = 1; $count -1 < $this->ReadPropertyInteger("Anzahl"); $count++) {

      //Hochzählen der Konfirgurationsform Variablen
      $DeviceNameCount = "DeviceName{$count}";
      $VariableStateCount = "VariableState{$count}";
      $VariableBrightnessCount = "VariableBrightness{$count}";

      $VariableStateTrueCount = "VariableStateTrue{$count}";
      $VariableStateFalseCount = "VariableStateFalse{$count}";

      //Prüfen ob der übergebene Name aus dem Hook zu einem Namen aus der Konfirgurationsform passt
      $name = $this->ReadPropertyString($DeviceNameCount);
      if ($DeviceName == $name) {
        switch ($Characteristic) {
          case 'On':
            //Lightbulb State abfragen
            $VariableStateID = $this->ReadPropertyInteger($VariableStateCount);
            $VariableStateTrue = $this->ReadPropertyInteger($VariableStateTrueCount);
            $VariableStateFalse = $this->ReadPropertyInteger($VariableStateFalseCount);
            $result = intval(GetValue($VariableStateID));
            //$result = ($result) ? 'true' : 'false';

            //Result Wert in erwartete Device Variable ändern
            switch ($result) {
              case $VariableStateTrue:
                $result = 'true';
                break;
              case $VariableStateFalse:
                $result = 'false';
                break;
            }

            //Übergebnenen Wert in erwartete Device Variable ändern
            switch ($value) {
              case $VariableStateTrue:
                $value = $VariableStateTrue;
                break;
              case $VariableStateFalse:
                $value = $VariableStateFalse;
                break;
            }

            if ($result == 'true' && $value == $VariableStateFalse) {
              $variable = IPS_GetVariable($VariableStateID);
              $variableObject = IPS_GetObject($VariableStateID);
              //den übgergebenen Wert in den VariablenTyp für das IPS-Gerät umwandeln
              $result = $this->ConvertVariable($variable, $value);
              //Geräte Variable setzen
              IPS_RequestAction($variableObject["ParentID"], $variableObject['ObjectIdent'], $result);
            }

            if ($result == 'false' && $value == $VariableStateTrue) {
              $variable = IPS_GetVariable($VariableStateID);
              $variableObject = IPS_GetObject($VariableStateID);
              //den übgergebenen Wert in den VariablenTyp für das IPS-Gerät umwandeln
              $result = $this->ConvertVariable($variable, $value);
              //Geräte Variable setzen
              IPS_RequestAction($variableObject["ParentID"], $variableObject['ObjectIdent'], $result);
            }
            break;
          case 'Brightness':
            //Lightbulb Brightness abfragen
            $VariableBrightnessID = $this->ReadPropertyInteger($VariableBrightnessCount);
            $variable = IPS_GetVariable($VariableBrightnessID);
            $variableObject = IPS_GetObject($VariableBrightnessID);
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
    //Payload bauen
    $payload["name"] = $DeviceName;
    $payload["service"] = "Lightbulb";

    if ($Brightness == true) {
      $payload["Brightness"] = "default";
    }

    $array["topic"] ="add";
    $array["payload"] = $payload;
    $data = json_encode($array);
    $SendData = json_encode(Array("DataID" => "{018EF6B5-AB94-40C6-AA53-46943E824ACF}", "Buffer" => $data));
    @$this->SendDataToParent($SendData);
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
}
?>
