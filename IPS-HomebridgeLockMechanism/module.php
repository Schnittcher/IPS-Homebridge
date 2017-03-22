<?
require_once(__DIR__ . "/../HomeKitService.php");

class IPS_HomebridgeLockMechanism extends HomeKitService {

  public function Create() {
      //Never delete this line!
      parent::Create();
      $this->ConnectParent("{86C2DE8C-FB21-44B3-937A-9B09BB66FB76}");
      //Anzahl die in der Konfirgurationsform angezeigt wird - Hier Standard auf 1
      $this->RegisterPropertyInteger("Anzahl",1);
      //99 Geräte können pro Konfirgurationsform angelegt werden
      for($count = 1; $count -1 < 99; $count++) {
        $DeviceName = "DeviceName{$count}";
        $LockMechanismID = "SwitchID{$count}";
        $LockCurrentState = "LockCurrentState{$count}";
        $LockTargetState = "LockTargetState{$count}";
        $this->RegisterPropertyString($DeviceName, "");
        $this->RegisterPropertyInteger($LockMechanismID, 0);
        $this->RegisterPropertyInteger($LockCurrentState, 0);
        $this->RegisterPropertyInteger($LockTargetState, 0);
      }
  }
  public function ApplyChanges() {
      //Never delete this line!
      parent::ApplyChanges();
      $this->ConnectParent("{86C2DE8C-FB21-44B3-937A-9B09BB66FB76}");
      //Setze Filter für ReceiveData
      $this->SetReceiveDataFilter(".*LockMechanism.*");
      $anzahl = $this->ReadPropertyInteger("Anzahl");

      for($count = 1; $count-1 < $anzahl; $count++) {
        $Devices[$count]["DeviceName"] = $this->ReadPropertyString("DeviceName{$count}");
        $Devices[$count]["LockCurrentState"] = $this->ReadPropertyInteger("LockCurrentState{$count}");
        $Devices[$count]["LockTargetState"] = $this->ReadPropertyBoolean("LockTargetState{$count}");

        $BufferNameLockCurrentState = $Devices[$count]["DeviceName"]." LockCurrentState";
        $BufferNameLockTargetState = $Devices[$count]["DeviceName"]." LockTargetState";

        //Alte Registrierungen auf Variablen Veränderung aufheben
        $UnregisterBufferIDs = [];
        array_push($UnregisterBufferIDs,$this->GetBuffer($BufferNameLockCurrentState));
        array_push($UnregisterBufferIDs,$this->GetBuffer($BufferNameLockTargetState));
        $this->UnregisterMessages($UnregisterBufferIDs, 10603);

        if ($Devices[$count]["DeviceName"] != "") {
          //Regestriere State Variable auf Veränderungen
          $RegisterBufferIDs = [];
          array_push($RegisterBufferIDs,$Devices[$count]["LockCurrentState"]);
          array_push($RegisterBufferIDs,$Devices[$count]["LockTargetState"]);
          $this->RegisterMessages($RegisterBufferIDs, 10603);

          //Buffer mit den aktuellen Variablen IDs befüllen
          $this->SetBuffer($BufferNameLockCurrentState,$Devices[$count]["LockCurrentState"]);
          $this->SetBuffer($BufferNameLockTargetState,$Devices[$count]["LockTargetState"]);

          $this->addAccessory($Devices[$count]["DeviceName"]);
        }
        else {
          return;
        }
      }
      $DevicesConfig = serialize($Devices);
      $this->SetBuffer("LockMechanism Config",$DevicesConfig);
    }

  public function Destroy() {
  }

  public function MessageSink($TimeStamp, $SenderID, $Message, $Data) {
    $Devices = unserialize($this->getBuffer("LockMechanism Config"));
    if ($Data[1] == true) {
      $anzahl = $this->ReadPropertyInteger("Anzahl");
      for($count = 1; $count-1 < $anzahl; $count++) {
        $Device = $Devices[$count];
        $DeviceName = $Device["DeviceName"];

        //Prüfen ob die SenderID gleich der State Variable ist, dann den aktuellen Wert an die Bridge senden

        switch ($SenderID) {
          case $Device["LockCurrentState"]:
            $Characteristic = "LockCurrentState";
            $data = $Data[0];
            $result = ($data) ? 'true' : 'false';
            $this->SendDebug("MessageSink Result", $result,0);
            $this->sendJSONToParent("setValue", $Characteristic, $DeviceName, $result);
            break;
          case $Device["LockTargetState"]:
            $Characteristic = "LockTargetState";
            $data = $Data[0];
            $result = ($data) ? 'true' : 'false';
            $this->SendDebug("MessageSink Result", $result,0);
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
      $form .= '{ "type": "SelectInstance", "name": "SwitchID'.$count.'", "caption": "Gerät" },';
      $form .= '{ "type": "SelectVariable", "name": "LockCurrentState'.$count.'", "caption": "LockCurrentState" },';
      $form .= '{ "type": "SelectVariable", "name": "LockTargetState'.$count.'", "caption": "LockTargetState" },';
      $form .= '{ "type": "Button", "label": "Löschen", "onClick": "echo HBLockMechanism_removeAccessory('.$this->InstanceID.','.$count.');" },';
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
    $Devices = unserialize($this->getBuffer("LockMechanism Config"));
    $anzahl = $this->ReadPropertyInteger("Anzahl");
    for($count = 1; $count -1 < $anzahl; $count++) {
      $Device = $Devices[$count];
      //Prüfen ob der übergebene Name aus dem Hook zu einem Namen aus der Konfirgurationsform passt
      $name = $Device["DeviceName"];
      if ($DeviceName == $name) {
        switch ($Characteristic) {
          case 'LockCurrentState':
            //IPS Variable abfragen
            $LockCurrentStateID = $Device["LockCurrentState"];
            $result = GetValue($LockCurrentStateID);
            $result = ($result) ? 'true' : 'false';
            $this->sendJSONToParent("callback", $Characteristic, $DeviceName, $result);
            break;
          case 'LockTargetState':
            //IPS Variable abfragen
            $LockTargetStateID = $Device["LockTargetState"];
            $result = GetValue($LockTargetStateID);
            $result = ($result) ? 'true' : 'false';
            $this->sendJSONToParent("callback", $Characteristic, $DeviceName, $result);
            break;
        }
      }
    }
  }

  public function setVar($DeviceName, $state, $Characteristic) {
    $Devices = unserialize($this->getBuffer("LockMechanism Config"));
    $anzahl = $this->ReadPropertyInteger("Anzahl");
    for($count = 1; $count -1 < $anzahl; $count++) {
      $Device = $Devices[$count];

      //Prüfen ob der übergebene Name aus dem Hook zu einem Namen aus der Konfirgurationsform passt
      $name = $Device["DeviceName"];
      if ($DeviceName == $name) {
        switch ($Characteristic) {
          case 'LockCurrentState':
            $LockCurrentStateID = $Device["LockCurrentState"];
            $variable = IPS_GetVariable($LockCurrentStateID);
            $variableObject = IPS_GetObject($LockCurrentStateID);
            //den übgergebenen Wert in den VariablenTyp für das IPS-Gerät umwandeln
            $result = $this->ConvertVariable($variable, $state);
            //Geräte Variable setzen
            $this->SetValueToIPS($variable,$variableObject,$result);
            break;
          case 'LockTargetState':
            $LockTargetStateID = $Device["LockTargetState"];
            $variable = IPS_GetVariable($LockTargetStateID);
            $variableObject = IPS_GetObject($LockTargetStateID);
            //den übgergebenen Wert in den VariablenTyp für das IPS-Gerät umwandeln
            $result = $this->ConvertVariable($variable, $state);
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
    $payload["service"] = "LockMechanism";

    $array["topic"] ="add";
    $array["payload"] = $payload;
    $data = json_encode($array);
    $SendData = json_encode(Array("DataID" => "{018EF6B5-AB94-40C6-AA53-46943E824ACF}", "Buffer" => $data));
    @$this->SendDataToParent($SendData);
  }
}
?>
