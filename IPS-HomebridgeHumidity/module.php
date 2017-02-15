<?
require_once(__DIR__ . "/../HomeKitService.php");

class IPS_HomebridgeHumidity extends HomeKitService {
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
      }
  }
  public function ApplyChanges() {
      //Never delete this line!
      parent::ApplyChanges();
      //Setze Filter für ReceiveData
      $this->SetReceiveDataFilter(".*HumiditySensor.*");
      $anzahl = $this->ReadPropertyInteger("Anzahl");
      for($count = 1; $count-1 < $anzahl; $count++) {
        $Devices[$count]["DeviceName"] = $this->ReadPropertyString("DeviceName{$count}");
        $Devices[$count]["VariableHumidity"] = $this->ReadPropertyInteger("VariableHumidity{$count}");


        $DeviceNameCount = "DeviceName{$count}";
        $VariableHumidityCount = "VariableHumidity{$count}";
        $BufferName = $Devices[$count]["DeviceName"]." Humidity";

        //Alte Registrierung auf Variablen Veränderung aufheben
        $UnregisterBufferIDs = [];
        array_push($UnregisterBufferIDs,$this->GetBuffer($BufferName));
        $this->UnregisterMessages($UnregisterBufferIDs, 10603);

        if ($this->ReadPropertyString($DeviceNameCount) != "") {
          //Regestriere Humidity Variable auf Veränderungen
          $RegisterBufferIDs = [];
          array_push($RegisterBufferIDs,$Devices[$count]["VariableHumidity"]);
          $this->RegisterMessages($RegisterBufferIDs, 10603);
          //Buffer mit der aktuellen Variablen ID befüllen
          $this->SetBuffer($BufferName,$Devices[$count]["VariableHumidity"]);

          //Accessory anlegen
          $this->addAccessory($this->ReadPropertyString($DeviceNameCount));
        } else {
          return;
        }
      }
      $DevicesConfig = serialize($Devices);
      $this->SetBuffer("Humidity Config",$DevicesConfig);
    }

  public function Destroy() {

  }

  public function MessageSink($TimeStamp, $SenderID, $Message, $Data) {
    $Devices = unserialize($this->getBuffer("Humidity Config"));
    if ($Data[1] == true) {
      $anzahl = $this->ReadPropertyInteger("Anzahl");
      for($count = 1; $count-1 < $anzahl; $count++) {
        $Device = $Devices[$count];

        $DeviceName = $Device["DeviceName"];
        //Prüfen ob die SenderID gleich der Humidity Variable ist, dann den aktuellen Wert an die Bridge senden
        if ($Device["VariableHumidity"] == $SenderID) {
          $Characteristic = "CurrentRelativeHumidity";
          $data = $Data[0];
          $result = number_format($data, 2, '.', '');
          $this->sendJSONToParent("setValue", $Characteristic, $DeviceName, $result);
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
      $form .= '{ "type": "SelectInstance", "name": "HumidityDeviceID'.$count.'", "caption": "Gerät" },';
      $form .= '{ "type": "SelectVariable", "name": "VariableHumidity'.$count.'", "caption": "Luftfeuchtigkeit" },';
      $form .= '{ "type": "Button", "label": "Löschen", "onClick": "echo HBHumidity_removeAccessory('.$this->InstanceID.','.$count.');" },';
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
    $Devices = unserialize($this->getBuffer("Humidity Config"));
    $anzahl = $this->ReadPropertyInteger("Anzahl");
    for($count = 1; $count -1 < $anzahl; $count++) {
      $Device = $Devices[$count];
      //Prüfen ob der übergebene Name zu einem Namen aus der Konfirgurationsform passt wenn ja Wert an die Bridge senden
      $name = $Device["DeviceName"];
      if ($DeviceName == $name) {
        //IPS Variable abfragen und zur Bridge schicken
        $result = GetValue($Device["VariableHumidity"]);
        $this->SendDebug("getVar Result", $result,0);
        $result = number_format($result, 2, '.', '');
        $this->sendJSONToParent("callback", $Characteristic, $DeviceName, $result);
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
