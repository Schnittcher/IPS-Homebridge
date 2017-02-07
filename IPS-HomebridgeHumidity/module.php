<?
class IPS_HomebridgeHumidity extends IPSModule {
  public function Create() {
      //Never delete this line!
      parent::Create();
      $this->ConnectParent("{86C2DE8C-FB21-44B3-937A-9B09BB66FB76}");
        $this->RegisterPropertyInteger("Anzahl",1);

      for($count = 1; $count -1 < 99; $count++) {
        $DeviceName = "DeviceName{$count}";
        $HumidityDeviceID = "HumidityDeviceID{$count}";
        $VariableHumidity = "VariableHumidity{$count}";
        $this->RegisterPropertyString($DeviceName, "");
        $this->RegisterPropertyInteger($HumidityDeviceID, 0);
        $this->RegisterPropertyInteger($VariableHumidity, 0);
        $this->GetBuffer($DeviceName." Humidity ".$VariableHumidity,"");
      }
  }
  public function ApplyChanges() {
      //Never delete this line!
      parent::ApplyChanges();
      $anzahl = $this->ReadPropertyInteger("Anzahl");
      for($count = 1; $count-1 < $anzahl; $count++) {
        $DeviceName = "DeviceName{$count}";
        $VariableHumidity = "VariableHumidity{$count}";
        if (is_int($this->GetBuffer($DeviceName." Humidity ".$VariableHumidity))) {
          IPS_LogMessage("integer",$this->GetBuffer($DeviceName." Humidity ".$VariableHumidity));
          $this->UnregisterMessage(intval($this->GetBuffer($DeviceName." Humidity ".$VariableHumidity)), 10603);
        }
        if ($this->ReadPropertyString($DeviceName) != "") {
          $this->addAccessory($this->ReadPropertyString($DeviceName));
          $this->RegisterMessage($this->ReadPropertyInteger($VariableHumidity), 10603);
          $this->SetBuffer($DeviceName." Humidity ".$VariableHumidity,$this->ReadPropertyInteger($VariableHumidity));
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
      $VariableHumidity = $this->ReadPropertyInteger("VariableHumidity{$count}");
      if ($VariableHumidity == $SenderID) {
        $DeviceName = $this->ReadPropertyString("DeviceName{$count}");
        $Characteristic = "CurrentRelativeHumidity";
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
      $form .= '{ "type": "SelectInstance", "name": "HumidityDeviceID'.$count.'", "caption": "Gerät" },';
      $form .= '{ "type": "SelectVariable", "name": "VariableHumidity'.$count.'", "caption": "Luftfeuchtigkeit" },';
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
    IPS_LogMessage("Luftfeuchte ReceiveData", $JSONString);
      if ($HomebridgeData->Action == "get" && $HomebridgeData->Service == "HumiditySensor") {
        $this->getVar($HomebridgeData->Device, $HomebridgeData->Characteristic);
      }
  }

  public function getVar($DeviceName, $Characteristic) {
    for($count = 1; $count -1 < $this->ReadPropertyInteger("Anzahl"); $count++) {
      //Hochzählen der Konfirgurationsform Variablen
      $HumidityDeviceID = "HumidityDeviceID{$count}";
      $VariableHumidity = "VariableHumidity{$count}";
          IPS_LogMessage("Luftfeuchte getVar", $DeviceName);
      //Prüfen ob der übergebene Name aus dem Hook zu einem Namen aus der Konfirgurationsform passt
      if ($DeviceName == $this->ReadPropertyString("DeviceName{$count}")) {
        //IPS Variable abfragen
        $result = GetValue($this->ReadPropertyInteger($VariableHumidity));
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
    $JSON['Buffer'] = utf8_encode('{"topic": "add", "name": "'.$DeviceName.'", "service": "HumiditySensor"}');
    $Data = json_encode($JSON);
    @$this->SendDataToParent($Data);
  }
}
?>
