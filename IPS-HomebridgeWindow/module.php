<?
require_once(__DIR__ . "/../HomeKitService.php");

class IPS_HomebridgeWindow extends HomeKitService {
  public function Create() {
      //Never delete this line!
      parent::Create();
      $this->ConnectParent("{86C2DE8C-FB21-44B3-937A-9B09BB66FB76}");
      //Anzahl die in der Konfirgurationsform angezeigt wird - Hier Standard auf 1
      $this->RegisterPropertyInteger("Anzahl",1);
      //99 Geräte können pro Konfirgurationsform angelegt werden
      for($count = 1; $count -1 < 99; $count++) {
        $DeviceName = "DeviceName{$count}";
        $CurrentPosition = "CurrentPosition{$count}";
        $TargetPosition = "TargetPosition{$count}";
        $PositionState = "PositionState{$count}";

        $this->RegisterPropertyString($DeviceName, "");
        $this->RegisterPropertyInteger($WindowID, 0);
        $this->RegisterPropertyInteger($CurrentPosition, 0);
        $this->RegisterPropertyInteger($TargetPosition, 0);
        $this->RegisterPropertyInteger($PositionState, 0);
      }
  }
  public function ApplyChanges() {
      //Never delete this line!
      parent::ApplyChanges();
      $this->ConnectParent("{86C2DE8C-FB21-44B3-937A-9B09BB66FB76}");
      $anzahl = $this->ReadPropertyInteger("Anzahl");

      for($count = 1; $count-1 < $anzahl; $count++) {
        $Devices[$count]["DeviceName"] = $this->ReadPropertyString("DeviceName{$count}");
        $Devices[$count]["PositionState"] = $this->ReadPropertyString("PositionState{$count}");
        $Devices[$count]["TargetPosition"] = $this->ReadPropertyString("TargetPosition{$count}");
        $Devices[$count]["CurrentPosition"] = $this->ReadPropertyString("CurrentPosition{$count}");

        $BufferNameState = $Devices[$count]["DeviceName"]. " PositionState";
        $BufferNameTarget = $Devices[$count]["DeviceName"]. " TargetPosition";
        $BufferNameCurrent = $Devices[$count]["DeviceName"]. " CurrentPosition";

        //Alte Registrierungen auf Variablen Veränderung aufheben
        $UnregisterBufferIDs = [];
        array_push($UnregisterBufferIDs,$this->GetBuffer($BufferNameState));
        array_push($UnregisterBufferIDs,$this->GetBuffer($BufferNameTarget));
        array_push($UnregisterBufferIDs,$this->GetBuffer($BufferNameCurrent));
        $this->UnregisterMessages($UnregisterBufferIDs, 10603);

        if ($Devices[$count]["DeviceName"] != "") {
          //Regestriere State Variable auf Veränderungen
          $RegisterBufferIDs = [];
          array_push($RegisterBufferIDs,$Devices[$count]["PositionState"]);
          array_push($RegisterBufferIDs,$Devices[$count]["TargetPosition"]);
          array_push($RegisterBufferIDs,$Devices[$count]["CurrentPosition"]);
          $this->RegisterMessages($RegisterBufferIDs, 10603);

          //Buffer mit den aktuellen Variablen IDs befüllen für State und Brightness
          $this->SetBuffer($BufferNameState,$Devices[$count]["PositionState"]);
          $this->SetBuffer($BufferNameTarget,$Devices[$count]["TargetPosition"]);
          $this->SetBuffer($BufferNameCurrent,$Devices[$count]["CurrentPosition"]);

          $this->addAccessory($Devices[$count]["DeviceName"]);
        } else {
          return;
        }
      }
      $DevicesConfig = serialize($Devices);
      $this->SetBuffer("Window Config",$DevicesConfig);
    }

  public function Destroy() {
  }

  public function MessageSink($TimeStamp, $SenderID, $Message, $Data) {
    $Devices = unserialize($this->getBuffer("Window Config"));
    if ($Data[1] == true) {
    $anzahl = $this->ReadPropertyInteger("Anzahl");

    for($count = 1; $count-1 < $anzahl; $count++) {
      $Device = $Devices[$count];
      $DeviceName = $Device["DeviceName"];

      $data = $Data[0];
      //Prüfen ob die SenderID gleich der State Variable ist, dann den aktuellen Wert an die Bridge senden
      switch ($SenderID) {
       case $Device["PositionState"]:
        $Characteristic = "PositionState";
        $result = intval($data);
        $this->sendJSONToParent("setValue", $Characteristic, $DeviceName, $result);
        break;
      case $Device["CurrentPosition"]:
          $result = intval($data);
          $Characteristic ="CurrentPosition";
          $this->sendJSONToParent("setValue", $Characteristic, $DeviceName, $result);
        break;
      case $Device["TargetPosition"]:
          $result = intval($data);
          $Characteristic ="TargetPosition";
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
      $form .= '{ "type": "SelectInstance", "name": "WindowID'.$count.'", "caption": "Gerät" },';
      $form .= '{ "type": "SelectVariable", "name": "PositionState'.$count.'", "caption": "Status " },';
      $form .= '{ "type": "SelectVariable", "name": "TargetPosition'.$count.'", "caption": "Target " },';
      $form .= '{ "type": "SelectVariable", "name": "CurrentPosition'.$count.'", "caption": "Current" },';
      $form .= '{ "type": "Button", "label": "Löschen", "onClick": "echo HBWindow_removeAccessory('.$this->InstanceID.','.$count.');" },';

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
    $Devices = unserialize($this->getBuffer("Window Config"));
    $anzahl = $this->ReadPropertyInteger("Anzahl");
    for($count = 1; $count -1 < $anzahl; $count++) {
      $Device = $Devices[$count];
      //Prüfen ob der übergebene Name aus dem Hook zu einem Namen aus der Konfirgurationsform passt
      $name = $Device["DeviceName"];
      if ($DeviceName == $name) {
        //IPS Variable abfragen
        switch ($Characteristic) {
          case 'CurrentPosition':
            $VariableID = $Device["CurrentPosition"];
            $result = GetValue($VariableID);
            //$result = ($result) ? 'true' : 'false';
            break;
          case 'TargetPosition':
            $VariableID = $Device["TargetPosition"];
            $result = GetValue($VariableID);
            break;
          case 'PositionState':
            $VariableID = $Device["PositionState"];
            $result = GetValue($VariableID);
            break;
        }
        $this->sendJSONToParent("callback", $Characteristic, $DeviceName, $result);
        return;
      }
    }
  }

  public function setVar($DeviceName, $value, $Characteristic) {
    $Devices = unserialize($this->getBuffer("Switch Config"));
    $anzahl = $this->ReadPropertyInteger("Anzahl");
    for($count = 1; $count -1 < $anzahl; $count++) {
      $Device = $Devices[$count];

      //Prüfen ob der übergebene Name aus dem Hook zu einem Namen aus der Konfirgurationsform passt
      $name = $Device["DeviceName"];
      if ($DeviceName == $name) {
        switch ($Characteristic) {
          case 'CurrentPosition':
            $VariableID = $Device["CurrentPosition"];
            $variable = IPS_GetVariable($VariableID);
            $variableObject = IPS_GetObject($VariableID);
            $this->SetValueToIPS($variable,$variableObject,$result);
            break;
          case 'TargetPosition':
            //Lightbulb Brightness abfragen
            $VariableID = $Device["TargetPosition"];
            $variable = IPS_GetVariable($VariableID);
            $variableObject = IPS_GetObject($VariableID);
            //den übgergebenen Wert in den VariablenTyp für das IPS-Gerät umwandeln
            $result = $this->ConvertVariable($variable, $value);
            //Geräte Variable setzen
            $this->SetValueToIPS($variable,$variableObject,$result);
            break;
          case 'PositionState':
            //Lightbulb Brightness abfragen
            $VariableID = $Device["PositionState"];
            $variable = IPS_GetVariable($VariableID);
            $variableObject = IPS_GetObject($VariableID);
            //den übgergebenen Wert in den VariablenTyp für das IPS-Gerät umwandeln
            $result = $this->ConvertVariable($variable, $value);
            //Geräte Variable setzen
            $this->SetValueToIPS($variable,$variableObject,$result);
            break;
        }
      }
    }
  }

  private function addAccessory($DeviceName) {
    //Payload bauen
    $payload["name"] = $DeviceName;
    $payload["service"] = "Window";

    $array["topic"] ="add";
    $array["payload"] = $payload;
    $data = json_encode($array);
    $SendData = json_encode(Array("DataID" => "{018EF6B5-AB94-40C6-AA53-46943E824ACF}", "Buffer" => $data));
    @$this->SendDataToParent($SendData);
  }
}
?>
