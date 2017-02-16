<?
require_once(__DIR__ . "/../HomeKitService.php");

class IPS_HomebridgeSwitch extends HomeKitService {

  public function Create() {
      //Never delete this line!
      parent::Create();
      $this->ConnectParent("{86C2DE8C-FB21-44B3-937A-9B09BB66FB76}");
      //Anzahl die in der Konfirgurationsform angezeigt wird - Hier Standard auf 1
      $this->RegisterPropertyInteger("Anzahl",1);
      //99 Geräte können pro Konfirgurationsform angelegt werden
      for($count = 1; $count -1 < 99; $count++) {
        $DeviceName = "DeviceName{$count}";
        $SwitchID = "SwitchID{$count}";
        $VariableState = "VariableState{$count}";
        $SwitchDummyOptional = "SwitchDummyOptional{$count}";
        $this->RegisterPropertyString($DeviceName, "");
        $this->RegisterPropertyInteger($SwitchID, 0);
        $this->RegisterPropertyInteger($VariableState, 0);
        $this->RegisterPropertyBoolean($SwitchDummyOptional, false);
      }
  }
  public function ApplyChanges() {
      //Never delete this line!
      parent::ApplyChanges();
      $this->ConnectParent("{86C2DE8C-FB21-44B3-937A-9B09BB66FB76}");
      //Setze Filter für ReceiveData
      $this->SetReceiveDataFilter(".*Switch.*");
      $anzahl = $this->ReadPropertyInteger("Anzahl");

      for($count = 1; $count-1 < $anzahl; $count++) {
        $Devices[$count]["DeviceName"] = $this->ReadPropertyString("DeviceName{$count}");
        $Devices[$count]["VariableState"] = $this->ReadPropertyInteger("VariableState{$count}");
        $Devices[$count]["DummyOptional"] = $this->ReadPropertyBoolean("SwitchDummyOptional{$count}");

        $BufferName = $Devices[$count]["DeviceName"]." State";

        $VariableStateBuffer = $this->GetBuffer($BufferName);
        //Alte Registrierungen auf Variablen Veränderung aufheben
        $UnregisterBufferIDs = [];
        array_push($UnregisterBufferIDs,$this->GetBuffer($BufferName));
        $this->UnregisterMessages($UnregisterBufferIDs, 10603);

        if ($Devices[$count]["DeviceName"] != "") {
          //Regestriere State Variable auf Veränderungen
          $RegisterBufferIDs = [];
          array_push($RegisterBufferIDs,$Devices[$count]["VariableState"]);
          $this->RegisterMessages($RegisterBufferIDs, 10603);

          //Buffer mit den aktuellen Variablen IDs befüllen
          $this->SetBuffer($BufferName,$Devices[$count]["VariableState"]);

          $this->addAccessory($Devices[$count]["DeviceName"]);
        }
        else {
          return;
        }
      }
      $DevicesConfig = serialize($Devices);
      $this->SetBuffer("Switch Config",$DevicesConfig);
    }

  public function Destroy() {
  }

  public function MessageSink($TimeStamp, $SenderID, $Message, $Data) {
    $Devices = unserialize($this->getBuffer("Switch Config"));
    if ($Data[1] == true) {
      $anzahl = $this->ReadPropertyInteger("Anzahl");
      for($count = 1; $count-1 < $anzahl; $count++) {
        $Device = $Devices[$count];
        $DeviceName = $Device["DeviceName"];

        //Prüfen ob die SenderID gleich der State Variable ist, dann den aktuellen Wert an die Bridge senden
        if ($Device["VariableState"] == $SenderID) {
          $Characteristic = "On";
          $data = $Data[0];
          $result = ($data) ? 'true' : 'false';
          $this->SendDebug("MessageSink Result", $result);
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
      $form .= '{ "type": "SelectInstance", "name": "SwitchID'.$count.'", "caption": "Gerät" },';
      $form .= '{ "type": "SelectVariable", "name": "VariableState'.$count.'", "caption": "Status (Characteristic .On)" },';
      $form .= '{ "type": "Label", "label": "Soll eine eigene Variable geschaltet werden?" },';
      $form .= '{ "type": "CheckBox", "name": "SwitchDummyOptional'.$count.'", "caption": "Ja" },';
      $form .= '{ "type": "Button", "label": "Löschen", "onClick": "echo HBSwitch_removeAccessory('.$this->InstanceID.','.$count.');" },';
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
    $Devices = unserialize($this->getBuffer("Switch Config"));
    $anzahl = $this->ReadPropertyInteger("Anzahl");
    for($count = 1; $count -1 < $anzahl; $count++) {
      $Device = $Devices[$count];
      //Prüfen ob der übergebene Name aus dem Hook zu einem Namen aus der Konfirgurationsform passt
      $name = $Device["DeviceName"];
      if ($DeviceName == $name) {
        //IPS Variable abfragen
        $VariableStateID = $Device["VariableState"];
        $result = GetValue($VariableStateID);
        $result = ($result) ? 'true' : 'false';
        $this->sendJSONToParent("callback", $Characteristic, $DeviceName, $result);
        return;
      }
    }
  }

  public function setVar($DeviceName, $state, $variable) {
    $Devices = unserialize($this->getBuffer("Switch Config"));
    $anzahl = $this->ReadPropertyInteger("Anzahl");
    for($count = 1; $count -1 < $anzahl; $count++) {
      $Device = $Devices[$count];

      //Prüfen ob der übergebene Name aus dem Hook zu einem Namen aus der Konfirgurationsform passt
      $name = $Device["DeviceName"];
      if ($DeviceName == $name) {
        $VariableStateID = $Device["VariableState"];
        $variable = IPS_GetVariable($VariableStateID);
        //den übgergebenen Wert in den VariablenTyp für das IPS-Gerät umwandeln
        $result = $this->ConvertVariable($variable, $state);
        $variableObject = IPS_GetObject($VariableStateID);
        //Geräte Variable setzen
        if ($variable["VariableCustomAction"] > 0) {
          SetValue($VariableStateID,$result);
        } else {
          IPS_RequestAction($variableObject["ParentID"], $variableObject['ObjectIdent'], $result);
        }

      /**
        if ($Device["DummyOptional"] == true) {
          $this->SendDebug('setState Dummy',$VariableStateID, 0);
          SetValue($VariableStateID, $result);
        } else {
          IPS_RequestAction($variableObject["ParentID"], $variableObject['ObjectIdent'], $result);
        }
        **/
      }
    }
  }

  private function addAccessory($DeviceName) {
    //Payload bauen
    $payload["name"] = $DeviceName;
    $payload["service"] = "Switch";

    $array["topic"] ="add";
    $array["payload"] = $payload;
    $data = json_encode($array);
    $SendData = json_encode(Array("DataID" => "{018EF6B5-AB94-40C6-AA53-46943E824ACF}", "Buffer" => $data));
    @$this->SendDataToParent($SendData);
  }
}
?>
