<?
class IPS_HomebridgeHumidity extends IPSModule {
  public function Create() {
      //Never delete this line!
      parent::Create();
      $this->ConnectParent("{86C2DE8C-FB21-44B3-937A-9B09BB66FB76}");
      //Anzahl die in der Konfirgurationsform angezeigt wird - Hier Standard auf 1
      $this->RegisterPropertyInteger("Anzahl",1);
      //99 Geräte können pro Konfirgurationsform angelegt werden
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
      //Setze Filter für ReceiveData
      $this->SetReceiveDataFilter(".*HumiditySensor.*");
      $anzahl = $this->ReadPropertyInteger("Anzahl");
      for($count = 1; $count-1 < $anzahl; $count++) {

        $DeviceNameCount = "DeviceName{$count}";
        $VariableHumidityCount = "VariableHumidity{$count}";
        $BufferName = $DeviceNameCount." Humidity ".$VariableHumidityCount;
        //Variablen ID für Humidity aus dem Buffer lesen
        $VariableHumidityBuffer = $this->GetBuffer($BufferName);

        if (is_int($VariableHumidityBuffer)) {
          //Alte Registrierung auf Variablen Veränderung aufheben
          $this->UnregisterMessage(intval($this->GetBuffer($BufferName)), 10603);
        }

        if ($this->ReadPropertyString($DeviceNameCount) != "") {
          //Accessory anlegen
          $this->addAccessory($this->ReadPropertyString($DeviceNameCount));
          //Regestriere Humidity Variable auf Veränderungen
          $this->RegisterMessage($this->ReadPropertyInteger($VariableHumidityCount), 10603);
          //Buffer mit der aktuellen Variablen ID befüllen
          $this->SetBuffer($BufferName,$this->ReadPropertyInteger($VariableHumidityCount));
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

      $DeviceNameCount = "DeviceName{$count}";
      $VariableHumidityCount = "VariableHumidity{$count}";
      $VariableHumidity = $this->ReadPropertyInteger($VariableHumidityCount);
      //Prüfen ob die SenderID gleich der Humidity Variable ist, dann den aktuellen Wert an die Bridge senden
      if ($VariableHumidity == $SenderID) {
        $DeviceName = $this->ReadPropertyString($DeviceNameCount);
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
    //Prüfen ob die ankommenden Daten für den HumiditySensor sind wenn ja, Status abfragen
    if ($HomebridgeData->Action == "get" && $HomebridgeData->Service == "HumiditySensor") {
      $this->getVar($HomebridgeData->Device, $HomebridgeData->Characteristic);
    }
  }

  public function getVar($DeviceName, $Characteristic) {
    $anzahl = $this->ReadPropertyInteger("Anzahl");

    for($count = 1; $count -1 < $anzahl; $count++) {
      //Hochzählen der Konfirgurationsform Variablen
      $DeviceNameCount = "DeviceName{$count}";
      $VariableHumidityCount = "VariableHumidity{$count}";
      //Prüfen ob der übergebene Name zu einem Namen aus der Konfirgurationsform passt wenn ja Wert an die Bridge senden
      $name = $this->ReadPropertyString($DeviceNameCount);
      if ($DeviceName == $name) {
        //IPS Variable abfragen und zur Bridge schicken
        $VariableHumidityID = $this->ReadPropertyInteger($VariableHumidityCount);
        $result = GetValue($VariableHumidityID);
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
    //Payload bauen
    $payload["name"] = $DeviceName;
    $payload["service"] = "HumiditySensor";

    $array["topic"] ="add";
    $array["payload"] = $payload;
    $data = json_encode($array);
    $SendData = json_encode(Array("DataID" => "{018EF6B5-AB94-40C6-AA53-46943E824ACF}", "Buffer" => $data));
    @$this->SendDataToParent($SendData);
  }
}
?>
