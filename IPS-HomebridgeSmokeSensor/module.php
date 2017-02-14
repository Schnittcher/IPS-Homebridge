<?
class IPS_HomebridgeSmokeSensor extends IPSModule {
  public function Create() {
    //Never delete this line!
    parent::Create();
    $this->ConnectParent("{86C2DE8C-FB21-44B3-937A-9B09BB66FB76}");
    //Anzahl die in der Konfirgurationsform angezeigt wird - Hier Standard auf 1
    $this->RegisterPropertyInteger("Anzahl",1);
    //99 Geräte können pro Konfirgurationsform angelegt werden
    for($count = 1; $count -1 < 99; $count++) {
      $DeviceName = "DeviceName{$count}";
      $SmokeID = "SmokeID{$count}";
      $SmokeDetected = "SmokeDetected{$count}";
      $StatusTampered = "StatusTampered{$count}";
      $StatusLowBattery = "StatusLowBattery{$count}";

      $SmokeDummyOptional = "SmokeDummyOptional{$count}";
      $this->RegisterPropertyString($DeviceName, "");
      $this->RegisterPropertyInteger($SmokeID, 0);
      $this->RegisterPropertyInteger($SmokeDetected, 0);
      $this->RegisterPropertyInteger($StatusTampered, 0);
      $this->RegisterPropertyInteger($StatusLowBattery, 0);
      $this->RegisterPropertyBoolean($SmokeDummyOptional, false);
    }
  }

  public function ApplyChanges() {
    //Never delete this line!
    parent::ApplyChanges();
    $this->ConnectParent("{86C2DE8C-FB21-44B3-937A-9B09BB66FB76}");
    $anzahl = $this->ReadPropertyInteger("Anzahl");
    for($count = 1; $count-1 < $anzahl; $count++) {
      $DeviceNameCount = "DeviceName{$count}";
      $SmokeDetectedCount = "SmokeDetected{$count}";
      $StatusTamperedCount = "StatusTampered{$count}";
      $StatusLowBatteryCount = "StatusLowBattery{$count}";

      $BufferNameState = $DeviceNameCount." ".$SmokeDetectedCount;
      $BufferNameTampered = $DeviceNameCount." ".$StatusTamperedCount;
      $BufferNameLowBattery = $DeviceNameCount." ".$StatusLowBatteryCount;

      $VariableIDStateBuffer = $this->GetBuffer($BufferNameState);
      $VariableIDTamperedBuffer = $this->GetBuffer($BufferNameTampered);
      $VariableIDLowBatterytBuffer = $this->GetBuffer($BufferNameLowBattery);

        //Alte Registrierung auf Variablen Veränderung aufheben
      if (is_int($VariableIDStateBuffer)) {
        $this->UnregisterMessage(intval($VariableIDStateBuffer), 10603);
      }
      if (is_int($VariableIDTamperedBuffer)) {
        $this->UnregisterMessage(intval($VariableIDTamperedBuffer), 10603);
      }
      if (is_int($VariableIDLowBatterytBuffer)) {
        $this->UnregisterMessage(intval($VariableIDLowBatterytBuffer), 10603);
      }
      if ($this->ReadPropertyString($DeviceNameCount) != "") {
        //Regestrieren der Variable auf Veränderungen
        $NewVariableID = $this->ReadPropertyInteger($SmokeDetectedCount);
        $this->RegisterMessage($NewVariableID, 10603);

        $NewVariableID = $this->ReadPropertyInteger($StatusTamperedCount);
        $this->RegisterMessage($NewVariableID, 10603);

        $NewVariableID = $this->ReadPropertyInteger($StatusLowBatteryCount);
        $this->RegisterMessage($NewVariableID, 10603);

        //Buffer mit den aktuellen Variablen IDs befüllen für State und Brightness
        $this->SetBuffer($BufferNameState,$this->ReadPropertyInteger($SmokeDetectedCount));
        $this->SetBuffer($BufferNameTampered,$this->ReadPropertyInteger($StatusTamperedCount));
        $this->SetBuffer($BufferNameLowBattery,$this->ReadPropertyInteger($StatusLowBatteryCount));

        //Accessory hinzufügen
        $this->addAccessory($this->ReadPropertyString($DeviceNameCount));
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
        $DeviceNameCount = "DeviceName{$count}";
        $DeviceName = $this->ReadPropertyString($DeviceNameCount);

        $SmokeDetectedCount = "SmokeDetected{$count}";
        $StatusTamperedCount= "StatusTampered{$count}";
        $StatusLowBatteryCount= "StatusLowBattery{$count}";

        $SmokeDetectedID = $this->ReadPropertyInteger($SmokeDetectedCount);
        $StatusTamperedID = $this->ReadPropertyInteger($StatusTamperedCount);
        $StatusLowBatteryID = $this->ReadPropertyInteger($StatusLowBatteryCount);
        $data = $Data[0];
        //Prüfen ob die SenderID gleich der Variable ist, dann den aktuellen Wert an die Bridge senden
        switch ($SenderID) {
         case $SmokeDetectedID:
            $Characteristic = "SmokeDetected";
            $result = intval($data);
            $JSON['DataID'] = "{018EF6B5-AB94-40C6-AA53-46943E824ACF}";
            $JSON['Buffer'] = utf8_encode('{"topic": "setValue", "Characteristic": "'.$Characteristic.'", "Device": "'.$DeviceName.'", "value": "'.$result.'"}');
            $Data = json_encode($JSON);
            $this->SendDataToParent($Data);
          break;
          case $StatusLowBatteryID:
            $result = intval($data);
            $Characteristic ="StatusLowBattery";
            $JSON['DataID'] = "{018EF6B5-AB94-40C6-AA53-46943E824ACF}";
            $JSON['Buffer'] = utf8_encode('{"topic": "setValue", "Characteristic": "'.$Characteristic.'", "Device": "'.$DeviceName.'", "value": "'.$result.'"}');
            $Data = json_encode($JSON);
            $this->SendDataToParent($Data);
          break;
          case $StatusTamperedID:
            $result = intval($data);
            $Characteristic ="StatusTampered";
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
      $form .= '{ "type": "SelectInstance", "name": "SmokeID'.$count.'", "caption": "Gerät" },';
      $form .= '{ "type": "SelectVariable", "name": "SmokeDetected'.$count.'", "caption": "SmokeDetected " },';
      $form .= '{ "type": "SelectVariable", "name": "StatusTampered'.$count.'", "caption": "StatusTampered " },';
      $form .= '{ "type": "SelectVariable", "name": "StatusLowBattery'.$count.'", "caption": "StatusLowBattery" },';
      $form .= '{ "type": "Label", "label": "Soll eine eigene Variable geschaltet werden?" },';
      $form .= '{ "type": "CheckBox", "name": "SmokeDummyOptional'.$count.'", "caption": "Ja" },';
      $form .= '{ "type": "Button", "label": "Löschen", "onClick": "echo TSHBsmo_removeAccessory('.$this->InstanceID.','.$count.');" },';
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
    //Prüfen ob die ankommenden Daten für den Smoke sind wenn ja, Status abfragen oder setzen
    if ($HomebridgeData->Action == "get" && $HomebridgeData->Service == "SmokeSensor") {
      $this->getState($HomebridgeData->Device, $HomebridgeData->Characteristic);
    }
    if ($HomebridgeData->Action == "set" && $HomebridgeData->Service == "SmokeSensor") {
      $this->setState($HomebridgeData->Device, $HomebridgeData->Value, $HomebridgeData->Characteristic);
    }
  }

  public function getState($DeviceName, $Characteristic) {
    $anzahl = $this->ReadPropertyInteger("Anzahl");
    for($count = 1; $count -1 < $anzahl; $count++) {

      //Hochzählen der Konfirgurationsform Variablen
      $DeviceNameCount = "DeviceName{$count}";
      $SmokeDetectedCount = "SmokeDetected{$count}";
      $StatusTamperedCount = "StatusTampered{$count}";
      $StatusLowBatteryCount = "StatusLowBattery{$count}";
      //Prüfen ob der übergebene Name zu einem Namen aus der Konfirgurationsform passt
      $name = $this->ReadPropertyString($DeviceNameCount);
      if ($DeviceName == $name) {
         //IPS Variable abfragen
         switch ($Characteristic) {
          case 'StatusLowBattery':
            //abfragen
            $VariableID = $this->ReadPropertyInteger($StatusLowBatteryCount);
            $result = GetValue($VariableID);
            $result = ($result) ? '1' : '0';
            break;
          case 'StatusTampered':
            // abfragen
            $VariableID = $this->ReadPropertyInteger($StatusTamperedCount);
            $result = GetValue($VariableID);
            $result = ($result) ? '1' : '0';
            break;
          case 'SmokeDetected':
            // abfragen
            $VariableID = $this->ReadPropertyInteger($SmokeDetectedCount);
            $result = GetValue($VariableID);
            $result = ($result) ? '1' : '0';
            break;

        }
        $JSON['DataID'] = "{018EF6B5-AB94-40C6-AA53-46943E824ACF}";
        $JSON['Buffer'] = utf8_encode('{"topic": "callback", "Characteristic": "'.$Characteristic.'", "Device": "'.$DeviceName.'", "value": "'.$result.'"}');
        $Data = json_encode($JSON);
        $this->SendDataToParent($Data);
        return;
      }
    }
  }

  public function setState($DeviceName, $value, $Characteristic) {
    $anzahl = $this->ReadPropertyInteger("Anzahl");

    for($count = 1; $count -1 < $anzahl; $count++) {

      //Hochzählen der Konfirgurationsform Variablen
      $DeviceNameCount = "DeviceName{$count}";
      $SmokeDetectedCount = "SmokeDetected{$count}";
      $StatusTamperedCount = "StatusTampered{$count}";
      $StatusLowBatteryCount = "StatusLowBattery{$count}";
      $DummyOptional = "SmokeDummyOptional{$count}";

      //Prüfen ob der übergebene Name zu einem Namen aus der Konfirgurationsform passt
      $name = $this->ReadPropertyString($DeviceNameCount);
      if ($DeviceName == $name) {
        $DummyOptionalValue = $this->ReadPropertyBoolean($DummyOptional);

        switch ($Characteristic) {
          case 'StatusLowBattery':
            //Battery Status abfragen
            $VariableID = $this->ReadPropertyInteger($StatusLowBatteryCount);
            $variable = IPS_GetVariable($VariableID);
            $variableObject = IPS_GetObject($VariableID);
            if ($DummyOptionalValue == true) {
              SetValue($VariableID, $result);
            } else {
              IPS_RequestAction($variableObject["ParentID"], $variableObject['ObjectIdent'], $result);
            }
            break;
          case 'StatusTampered':
            //Manipulations Status Abfragen
            $VariableID = $this->ReadPropertyInteger($StatusTamperedCount);
            $variable = IPS_GetVariable($VariableID);
            $variableObject = IPS_GetObject($VariableID);
            //den übgergebenen Wert in den VariablenTyp für das IPS-Gerät umwandeln
            $result = $this->ConvertVariable($variable, $value);
            //Geräte Variable setzen
            if ($DummyOptionalValue == true) {
              SetValue($VariableID, $result);
            } else {
              IPS_RequestAction($variableObject["ParentID"], $variableObject['ObjectIdent'], $result);
            }
            break;
          case 'SmokeDetected':
            //Raucherkennung abfragen
            $VariableID = $this->ReadPropertyInteger($SmokeDetectedCount);
            $variable = IPS_GetVariable($VariableID);
            $variableObject = IPS_GetObject($VariableID);
            //den übgergebenen Wert in den VariablenTyp für das IPS-Gerät umwandeln
            $result = $this->ConvertVariable($variable, $value);
            //Geräte Variable setzen
            if ($DummyOptionalValue == true) {
               SetValue($VariableID, $result);
            } else {
              IPS_RequestAction($variableObject["ParentID"], $variableObject['ObjectIdent'], $result);
            }
            break;
        }
      }
    }
  }

  private function addAccessory($DeviceName) {
    //Payload bauen
    $payload["name"] = $DeviceName;
    $payload["service"] = "SmokeSensor";
    $payload["StatusTampered"] ="default";
    $payload["StatusLowBattery"]="default";

    $array["topic"] ="add";
    $array["payload"] = $payload;

    $data = json_encode($array);
    $SendData = json_encode(Array("DataID" => "{018EF6B5-AB94-40C6-AA53-46943E824ACF}", "Buffer" => $data));
    @$this->SendDataToParent($SendData);
  }

  public function removeAccessory($DeviceCount) {
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

  public function ConvertVariable($variable, $state) {
      switch ($variable["VariableType"]) {
        case 0: // boolean
          return boolval($state);
        case 1: // integer
          return intval($state);
        case 2: // float
          return floatval($state);
        case 3: // string
          return strval($state);
    }
  }
}
?>
