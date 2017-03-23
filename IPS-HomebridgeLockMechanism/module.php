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

        $LockCurrentStateTrue = "LockCurrentStateTrue{$count}";
        $LockCurrentStateFalse = "LockCurrentStateFalse{$count}";

        $LockTargetStateTrue = "LockTargetStateTrue{$count}";
        $LockTargetStateFalse = "LockTargetStateFalse{$count}";

        $this->RegisterPropertyInteger($LockCurrentStateTrue, 1);
        $this->RegisterPropertyInteger($LockCurrentStateFalse, 0);
        $this->RegisterPropertyInteger($LockTargetStateTrue, 1);
        $this->RegisterPropertyInteger($LockTargetStateFalse, 0);

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
        $Devices[$count]["LockTargetState"] = $this->ReadPropertyInteger("LockTargetState{$count}");
        $Devices[$count]["LockCurrentStateTrue"] = $this->ReadPropertyInteger("LockCurrentStateTrue{$count}");
        $Devices[$count]["LockCurrentStateFalse"] = $this->ReadPropertyInteger("LockCurrentStateFalse{$count}");
        $Devices[$count]["LockTargetStateTrue"] = $this->ReadPropertyInteger("LockTargetStateTrue{$count}");
        $Devices[$count]["LockTargetStateFalse"] = $this->ReadPropertyInteger("LockTargetStateFalse{$count}");

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
            switch ($data) {
              case 0:
                $result = $Device["LockCurrentStateTrue"];
                break;
              case 1:
                $result = $Device["LockCurrentStateFalse"];
                break;
            }
            $this->SendDebug("MessageSink Result", $result,0);
            $this->sendJSONToParent("setValue", $Characteristic, $DeviceName, $result);
            break;
          case $Device["LockTargetState"]:
            $Characteristic = "LockTargetState";
            switch ($data) {
              case 0:
                $result = $Device["LockTargetStateTrue"];
                break;
              case 1:
                $result = $Device["LockTargetStateFalse"];
                break;
            }
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
      $form .= '{ "type": "ValidationTextBox", "name": "LockCurrentStateTrue'.$count.'", "caption": "Value True (Auf)" },';
      $form .= '{ "type": "ValidationTextBox", "name": "LockCurrentStateFalse'.$count.'", "caption": "Value False (Zu)" },';
      $form .= '{ "type": "SelectVariable", "name": "LockTargetState'.$count.'", "caption": "LockTargetState" },';
      $form .= '{ "type": "ValidationTextBox", "name": "LockTargetStateTrue'.$count.'", "caption": "Value True (Auf)" },';
      $form .= '{ "type": "ValidationTextBox", "name": "LockTargetStateFalse'.$count.'", "caption": "Value False (Zu)" },';
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
            $result = floatval(GetValue($LockCurrentStateID));
            switch ($result) {
              case 0:
                $result = $Device["LockCurrentStateTrue"];
                break;
              case 1:
                $result = $Device["LockCurrentStateFalse"];
                break;
            }
            $this->sendJSONToParent("callback", $Characteristic, $DeviceName, $result);
            break;
          case 'LockTargetState':
            //IPS Variable abfragen
            $LockTargetStateID = $Device["LockTargetState"];
            $result = floatval(GetValue($LockTargetStateID));
            switch ($result) {
              case 0:
                $result = $Device["LockTargetStateTrue"];
                break;
              case 1:
                $result = $Device["LockTargetStateFalse"];
                break;
            }
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
            switch ($state) {
              case 0:
                $result = $Device["LockCurrentStateTrue"];
                break;
              case 1:
                $result = $Device["LockCurrentStateFalse"];
                break;
            }
            //den übgergebenen Wert in den VariablenTyp für das IPS-Gerät umwandeln
            $result = $this->ConvertVariable($variable, $state);
            //Geräte Variable setzen
            $this->SetValueToIPS($variable,$variableObject,$result);
            break;
          case 'LockTargetState':
            $LockTargetStateID = $Device["LockTargetState"];
            $variable = IPS_GetVariable($LockTargetStateID);
            $variableObject = IPS_GetObject($LockTargetStateID);
            switch ($state) {
              case 0:
                $result = $Device["LockTargetStateTrue"];
                break;
              case 1:
                $result = $Device["LockTargetStateFalse"];;
                break;
            }
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
