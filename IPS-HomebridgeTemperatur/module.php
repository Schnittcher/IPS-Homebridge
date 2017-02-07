<?
class IPS_HomebridgeTemperatur extends IPSModule {
  public function Create() {
      //Never delete this line!
      parent::Create();
      $this->ConnectParent("{86C2DE8C-FB21-44B3-937A-9B09BB66FB76}");
        $this->RegisterPropertyInteger("Anzahl",1);

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
      $anzahl = $this->ReadPropertyInteger("Anzahl");
      for($count = 1; $count-1 < $anzahl; $count++) {
        $DeviceNameID = "DeviceName{$count}";
        $VariableTemperatur = "VariableTemperatur{$count}";
        if (is_int($this->GetBuffer($DeviceNameID." Temperatur ".$VariableTemperatur))) {
        $this->UnregisterMessage(intval($this->GetBuffer($DeviceNameID." Temperatur ".$VariableTemperatur)), 10603);
        }
        if ($this->ReadPropertyString($DeviceNameID) != "") {
          $this->addAccessory($this->ReadPropertyString($DeviceNameID));
          $this->RegisterMessage($this->ReadPropertyInteger($VariableTemperatur), 10603);
          $this->SetBuffer($DeviceNameID." Temperatur ".$VariableTemperatur,$this->ReadPropertyInteger($VariableTemperatur));
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
      $VariableTemperatur = $this->ReadPropertyInteger("VariableTemperatur{$count}");
      if ($VariableTemperatur == $SenderID) {
        $DeviceName = $this->ReadPropertyString("DeviceName{$count}");
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
    $data = json_decode($JSONString);
    // Buffer decodieren und in eine Variable schreiben
    $Buffer = utf8_decode($data->Buffer);
    // Und Diese dann wieder dekodieren
    $HomebridgeData = json_decode($Buffer);
      if ($HomebridgeData->Action == "get" && $HomebridgeData->Service == "TemperatureSensor") {
        $this->getVar($HomebridgeData->Device, $HomebridgeData->Characteristic);
      }
  }

  public function getVar($DeviceName, $Characteristic) {
    for($count = 1; $count -1 < $this->ReadPropertyInteger("Anzahl"); $count++) {
      //Hochzählen der Konfirgurationsform Variablen
      $TemperaturDeviceID = "TemperaturDeviceID{$count}";
      $VariableTemperatur = "VariableTemperatur{$count}";
      //Prüfen ob der übergebene Name aus dem Hook zu einem Namen aus der Konfirgurationsform passt
      if ($DeviceName == $this->ReadPropertyString("DeviceName{$count}")) {
        //IPS Variable abfragen
        $result = GetValue($this->ReadPropertyInteger($VariableTemperatur));
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
    $JSON['DataID'] = "{018EF6B5-AB94-40C6-AA53-46943E824ACF}";
    $JSON['Buffer'] = utf8_encode('{"topic": "add", "name": "'.$DeviceName.'", "service": "TemperatureSensor"}');
    $Data = json_encode($JSON);
    @$this->SendDataToParent($Data);
  }
}
?>
