<?
class IPS_HomebridgeTemperatur extends IPSModule {
  public function Create() {
      //Never delete this line!
      parent::Create();
      $this->ConnectParent("{86C2DE8C-FB21-44B3-937A-9B09BB66FB76}");
      //Anzahl die in der Konfirgurationsform angezeigt wird - Hier Standard auf 1
      $this->RegisterPropertyInteger("Anzahl",1);
      //99 Geräte können pro Konfirgurationsform angelegt werden
      for($count = 1; $count -1 < 99; $count++) {
        $DeviceName = "DeviceName{$count}";
        $TemperaturDeviceID = "TemperaturDeviceID{$count}";
        $VariableTemperatur = "VariableTemperatur{$count}";
        $this->RegisterPropertyString($DeviceName, "");
        $this->RegisterPropertyInteger($TemperaturDeviceID, 0);
        $this->RegisterPropertyInteger($VariableTemperatur, 0);
        $this->SetBuffer($DeviceName." Temperatur ".$VariableTemperatur,"");
      }
  }
  public function ApplyChanges() {
      //Never delete this line!
      parent::ApplyChanges();
      //Setze Filter für ReceiveData
      $this->SetReceiveDataFilter(".*TemperatureSensor.*");
      $anzahl = $this->ReadPropertyInteger("Anzahl");

      for($count = 1; $count-1 < $anzahl; $count++) {

        $DeviceNameCount = "DeviceName{$count}";
        $VariableTemperaturCount = "VariableTemperatur{$count}";
        $BufferName = $DeviceNameCount." Temperatur ".$VariableTemperaturCount;
        $VariableTemperaturBuffer = $this->GetBuffer($BufferName);
        if (is_int($VariableTemperaturBuffer)) {
        $this->UnregisterMessage(intval($VariableTemperaturBuffer), 10603);
        }
        $DeviceName = $this->ReadPropertyString($DeviceNameCount);
        if ($DeviceName != "") {
          //Accessory anlegen
          $this->addAccessory($DeviceName);
          $VariableTemperaturID = $this->ReadPropertyInteger($VariableTemperaturCount);
          //Regestriere Temperatur Variable auf Veränderungen
          $this->RegisterMessage($VariableTemperaturID, 10603);
          //Buffer mit der aktuellen Variablen ID befüllen
          $this->SetBuffer($BufferName,$VariableTemperaturID);
        }
        else {
          return;
        }
      }
    }
  public function Destroy() {
  }

  public function MessageSink($TimeStamp, $SenderID, $Message, $Data) {
    $anzahl = $this->ReadPropertyInteger("Anzahl");

    for($count = 1; $count-1 < $anzahl; $count++) {
      $DeviceNameCount = "DeviceName{$count}";
      $VariableTemperaturCount = "VariableTemperatur{$count}";
      $VariableTemperatur = $this->ReadPropertyInteger($VariableTemperaturCount);

      //Prüfen ob die SenderID gleich der Temperatur Variable ist, dann den aktuellen Wert an die Bridge senden
      if ($VariableTemperatur == $SenderID) {
        $DeviceName = $this->ReadPropertyString($DeviceNameCount);
        $Characteristic = "CurrentTemperature";
        $data = $Data[0];
        $result = number_format($data, 2, '.', '');
        $JSON['DataID'] = "{018EF6B5-AB94-40C6-AA53-46943E824ACF}";
        $JSON['Buffer'] = utf8_encode('{"topic": "setValue", "Characteristic": "'.$Characteristic.'", "Device": "'.$DeviceName.'", "value": "'.$result.'"}');
        $Data = json_encode($JSON);
        $this->SendDataToParent($Data);
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
      $form .= '{ "type": "SelectInstance", "name": "TemperaturDeviceID'.$count.'", "caption": "Gerät" },';
      $form .= '{ "type": "SelectVariable", "name": "VariableTemperatur'.$count.'", "caption": "Temperatur" },';
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
    //Prüfen ob die ankommenden Daten für den TemperatureSensor sind wenn ja, Status abfragen
    if ($HomebridgeData->Action == "get" && $HomebridgeData->Service == "TemperatureSensor") {
      $this->getVar($HomebridgeData->Device, $HomebridgeData->Characteristic);
    }
  }

  public function getVar($DeviceName, $Characteristic) {
    $anzahl = $this->ReadPropertyInteger("Anzahl");

    for($count = 1; $count -1 < $anzahl; $count++) {

      //Hochzählen der Konfirgurationsform Variablen
      $DeviceNameCount = "DeviceName{$count}";
      $VariableTemperaturCount = "VariableTemperatur{$count}";
      $name = $this->ReadPropertyString($DeviceNameCount);
      //Prüfen ob der übergebene Name zu einem Namen aus der Konfirgurationsform passt wenn ja Wert an die Bridge senden
      if ($DeviceName == $name) {
        //IPS Variable abfragen
        $VariableTemperaturID = $this->ReadPropertyInteger($VariableTemperaturCount);
        $result = GetValue($VariableTemperaturID);
        $result = number_format($result, 2, '.', '');
        $JSON['DataID'] = "{018EF6B5-AB94-40C6-AA53-46943E824ACF}";
        $JSON['Buffer'] = utf8_encode('{"topic": "callback", "Characteristic": "'.$Characteristic.'", "Device": "'.$DeviceName.'", "value": "'.$result.'"}');
        $Data = json_encode($JSON);
        $this->SendDataToParent($Data);
        return;
      }
    }
  }
  private function addAccessory($DeviceName) {
    //$array['topic'] = "add";
    //$array['Buffer'] = utf8_encode('"name": "'.$DeviceName.'", "service": "TemperatureSensor","CurrentTemperature": {"minValue": -100, "maxValue": 100, "minStep": 0.1}}');

    $CurrentTemperature["minValue"] = -100;
    $CurrentTemperature["maxValue"] = 100;
    $CurrentTemperature["minStep"] = 0.1;
    //Payload bauen
    $payload["name"] = $DeviceName;
    $payload["service"] = "TemperatureSensor";
    $payload["CurrentTemperature"] = $CurrentTemperature;

    $array["topic"] ="add";
    $array["payload"] = $payload;
    $data = json_encode($array);
    $SendData = json_encode(Array("DataID" => "{018EF6B5-AB94-40C6-AA53-46943E824ACF}", "Buffer" => $data));
    @$this->SendDataToParent($SendData);

  }
}
?>
