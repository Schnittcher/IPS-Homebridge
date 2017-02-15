
<?
require_once(__DIR__ . "/../HomeKitService.php");

class IPS_HomebridgeSmokeSensor extends HomeKitService {
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
    $this->SetReceiveDataFilter(".*SmokeSensor.*");
    $this->ConnectParent("{86C2DE8C-FB21-44B3-937A-9B09BB66FB76}");
    $anzahl = $this->ReadPropertyInteger("Anzahl");
    $Devices = [];
    for($count = 1; $count-1 < $anzahl; $count++) {
      $Devices[$count]["DeviceName"] = $this->ReadPropertyString("DeviceName{$count}");
      $Devices[$count]["VariableSmokeDetected"] = $this->ReadPropertyString("SmokeDetected{$count}");
      $Devices[$count]["VariableStatusTampered"] = $this->ReadPropertyString("StatusTampered{$count}");
      $Devices[$count]["VariableStatusLowBattery"] = $this->ReadPropertyString("StatusLowBattery{$count}");
      $Devices[$count]["SmokeDummyOptional"] = $this->ReadPropertyBoolean("SmokeDummyOptional{$count}");
      $DeviceNameCount = "DeviceName{$count}";
      $SmokeDetectedCount = "SmokeDetected{$count}";
      $StatusTamperedCount = "StatusTampered{$count}";
      $StatusLowBatteryCount = "StatusLowBattery{$count}";

      $BufferNameState = $Devices[$count]["DeviceName"]." SmokeDetected";
      $BufferNameTampered = $Devices[$count]["DeviceName"]." StatusTampered";
      $BufferNameLowBattery = $Devices[$count]["DeviceName"]." StatusLowBattery";

      //Alte Registrierungen auf Variablen Veränderung aufheben
      $UnregisterBufferIDs = [];
      array_push($UnregisterBufferIDs,$this->GetBuffer($BufferNameState));
      array_push($UnregisterBufferIDs,$this->GetBuffer($BufferNameTampered));
      array_push($UnregisterBufferIDs,$this->GetBuffer($BufferNameLowBattery));
      $this->UnregisterMessages($UnregisterBufferIDs, 10603);

      if ($Devices[$count]["DeviceName"] != "") {
        //Regestrieren der Variable auf Veränderungen
        $RegisterBufferIDs = [];
        array_push($RegisterBufferIDs,$Devices[$count]["VariableSmokeDetected"]);
        array_push($RegisterBufferIDs,$Devices[$count]["VariableStatusTampered"]);
        array_push($RegisterBufferIDs,$Devices[$count]["VariableStatusLowBattery"]);
        $this->RegisterMessages($RegisterBufferIDs, 10603);

        //Buffer mit den aktuellen Variablen IDs befüllen für State und Brightness
        $this->SetBuffer($BufferNameState,$Devices[$count]["VariableSmokeDetected"]);
        $this->SetBuffer($BufferNameTampered,$Devices[$count]["VariableStatusTampered"]);
        $this->SetBuffer($BufferNameLowBattery,$Devices[$count]["VariableStatusLowBattery"]);

        //Accessory hinzufügen
        $this->addAccessory($Devices[$count]["DeviceName"]);
      } else {
        return;
      }
    }
    $DevicesConfig = serialize($Devices);
    $this->SetBuffer("SmokeSensor Config",$DevicesConfig);
  }

  public function Destroy() {
  }

  public function MessageSink($TimeStamp, $SenderID, $Message, $Data) {
    $Devices = unserialize($this->getBuffer("SmokeSensor Config"));
    if ($Data[1] == true) {
      $anzahl = $this->ReadPropertyInteger("Anzahl");
      for($count = 1; $count-1 < $anzahl; $count++) {
        $Device = $Devices[$count];

        $data = $Data[0];
        //Prüfen ob die SenderID gleich der Variable ist, dann den aktuellen Wert an die Bridge senden
        switch ($SenderID) {
         case $Device["VariableSmokeDetected"]:
            $Characteristic = "SmokeDetected";
            $result = intval($data);
            $this->sendJSONToParent("setValue", $Characteristic, $DeviceName, $result);
          break;
          case $Device["VariableStatusLowBattery"]:
            $Characteristic ="StatusLowBattery";
            $result = intval($data);
            $this->sendJSONToParent("setValue", $Characteristic, $DeviceName, $result);
          break;
          case $Device["VariableStatusTampered"]:
            $Characteristic ="StatusTampered";
            $result = intval($data);
            $this->sendJSONToParent("setValue", $Characteristic, $DeviceName, $result);
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

  public function getVar($DeviceName, $Characteristic) {
    $Devices = unserialize($this->getBuffer("SmokeSensor Config"));
    $anzahl = $this->ReadPropertyInteger("Anzahl");
    for($count = 1; $count -1 < $anzahl; $count++) {
      $Device = $Devices[$count];

      //Hochzählen der Konfirgurationsform Variablen
      $DeviceNameCount = "DeviceName{$count}";
      $SmokeDetectedCount = "SmokeDetected{$count}";
      $StatusTamperedCount = "StatusTampered{$count}";
      $StatusLowBatteryCount = "StatusLowBattery{$count}";
      //Prüfen ob der übergebene Name zu einem Namen aus der Konfirgurationsform passt
      $name = $Device["DeviceName"];
      if ($DeviceName == $name) {
         //IPS Variable abfragen
         switch ($Characteristic) {
          case 'StatusLowBattery':
            //abfragen
            $result = GetValue($Device["VariableStatusLowBattery"]);
            $result = ($result) ? '1' : '0';
            break;
          case 'StatusTampered':
            // abfragen
            $result = GetValue($Device["VariableStatusTampered"]);
            $result = ($result) ? '1' : '0';
            break;
          case 'SmokeDetected':
            // abfragen
            $result = GetValue($Device["VariableSmokeDetected"]);
            $result = ($result) ? '1' : '0';
            break;

        }
        $this->sendJSONToParent("callback", $Characteristic, $DeviceName, $result);
        return;
      }
    }
  }

  public function setVar($DeviceName, $value, $Characteristic) {
    $Devices = unserialize($this->getBuffer("SmokeSensor Config"));
    $anzahl = $this->ReadPropertyInteger("Anzahl");

    for($count = 1; $count -1 < $anzahl; $count++) {
      $Device = $Devices[$count];

      //Prüfen ob der übergebene Name zu einem Namen aus der Konfirgurationsform passt
      $name = $Device["DeviceName"];
      $DummyOptionalValue = $Device["SmokeDummyOptional"];
      if ($DeviceName == $name) {
        switch ($Characteristic) {
          case 'StatusLowBattery':
            //Battery Status abfragen
            $VariableID = $Device["VariableStatusLowBattery"];
            $variable = IPS_GetVariable($VariableID);
            $variableObject = IPS_GetObject($VariableID);
            $result = $this->ConvertVariable($variable, $value);
            if ($DummyOptionalValue == true) {
              SetValue($VariableID, $result);
            } else {
              IPS_RequestAction($variableObject["ParentID"], $variableObject['ObjectIdent'], $result);
            }
            break;
          case 'StatusTampered':
            //Manipulations Status Abfragen
            $VariableID = $Device["VariableStatusTampered"];
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
            $VariableID = $Device["VariableSmokeDetected"];
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
}
?>
