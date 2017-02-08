<?
class IPS_HomebridgeSwitch extends IPSModule {
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
        $this->SetBuffer($DeviceName." Switch ".$VariableState,"");
      }
  }
  public function ApplyChanges() {
      //Never delete this line!
      parent::ApplyChanges();
      $this->ConnectParent("{86C2DE8C-FB21-44B3-937A-9B09BB66FB76}");
      $anzahl = $this->ReadPropertyInteger("Anzahl");

      for($count = 1; $count-1 < $anzahl; $count++) {
        $DeviceNameCount = "DeviceName{$count}";
        $VariableStateCount = "VariableState{$count}";
        $BufferName = $DeviceNameCount." State ".$VariableStateCount;

        $VariableStateBuffer = $this->GetBuffer($BufferName);

        if (is_int($VariableStateBuffer)) {
        $this->UnregisterMessage(intval($VariableStateBuffer), 10603);
        }
        $DeviceName = $this->ReadPropertyString($DeviceNameCount);
        if ($DeviceName != "") {
          $VariableStateID = $this->ReadPropertyInteger($VariableStateCount);
          $this->RegisterMessage($VariableStateID, 10603);
          $this->SetBuffer($BufferName,$VariableStateID);
          $this->addAccessory($DeviceName);
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
      $VariableStateCount = "VariableState{$count}";
      $VariableState = $this->ReadPropertyInteger($VariableStateCount);
      //Prüfen ob die SenderID gleich der State Variable ist, dann den aktuellen Wert an die Bridge senden
      if ($VariableState == $SenderID) {
        $DeviceName = $this->ReadPropertyString($DeviceNameCount);
        $Characteristic = "On";
        $data = $Data[0];
        $result = ($data) ? 'true' : 'false';
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
      $form .= '{ "type": "SelectInstance", "name": "SwitchID'.$count.'", "caption": "Gerät" },';
      $form .= '{ "type": "SelectVariable", "name": "VariableState'.$count.'", "caption": "Status (Characteristic .On)" },';
      $form .= '{ "type": "Label", "label": "Soll eine eigene Variable geschaltet werden?" },';
      $form .= '{ "type": "CheckBox", "name": "SwitchDummyOptional'.$count.'", "caption": "Ja" },';
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
    //Prüfen ob die ankommenden Daten für den Switch sind wenn ja, Status abfragen oder setzen
    if ($HomebridgeData->Action == "get" && $HomebridgeData->Service == "Switch") {
      $this->getState($HomebridgeData->Device, $HomebridgeData->Characteristic);
    }
    if ($HomebridgeData->Action == "set" && $HomebridgeData->Service == "Switch") {
      $this->setState($HomebridgeData->Device, $HomebridgeData->Value, $HomebridgeData->Characteristic);
    }
  }

  public function getState($DeviceName, $Characteristic) {
    $anzahl = $this->ReadPropertyInteger("Anzahl");

    for($count = 1; $count -1 < $anzahl; $count++) {

      //Hochzählen der Konfirgurationsform Variablen
      $DeviceNameCount = "DeviceName{$count}";
      $VariableStateCount = "VariableState{$count}";
      //Prüfen ob der übergebene Name aus dem Hook zu einem Namen aus der Konfirgurationsform passt
      $name = $this->ReadPropertyString($DeviceNameCount);
      if ($DeviceName == $name) {
        //IPS Variable abfragen
        $VariableStateID = $this->ReadPropertyInteger($VariableStateCount);
        $result = GetValue($VariableStateID);
        $result = ($result) ? 'true' : 'false';
        $JSON['DataID'] = "{018EF6B5-AB94-40C6-AA53-46943E824ACF}";
        $JSON['Buffer'] = utf8_encode('{"topic": "callback", "Characteristic": "'.$Characteristic.'", "Device": "'.$DeviceName.'", "value": "'.$result.'"}');
        $Data = json_encode($JSON);
        $this->SendDataToParent($Data);
        return;
      }
    }
  }

  public function setState($DeviceName, $state, $variable) {
    $anzahl = $this->ReadPropertyInteger("Anzahl");

    for($count = 1; $count -1 < $anzahl; $count++) {

      //Hochzählen der Konfirgurationsform Variablen
      $DeviceNameCount = "DeviceName{$count}";
      $VariableStateCount = "VariableState{$count}";
      $SwitchDummyOptional = "SwitchDummyOptional{$count}";
      //Prüfen ob der übergebene Name aus dem Hook zu einem Namen aus der Konfirgurationsform passt
      $name = $this->ReadPropertyString($DeviceNameCount)
      if ($DeviceName == $name) {
        $VariableStateID = $this->ReadPropertyInteger($VariableStateCount);
        $variable = IPS_GetVariable($VariableStateID);
        //den übgergebenen Wert in den VariablenTyp für das IPS-Gerät umwandeln
        $result = $this->ConvertVariable($variable, $state);
        $variableObject = IPS_GetObject($VariableStateID);
        //Geräte Variable setzen
        $SwitchDummyOptionalValue = $this->ReadPropertyBoolean($SwitchDummyOptional);
        if ($SwitchDummyOptionalValue == true) {
          $this->SendDebug('setState Dummy',$VariableStateID, 0);
          SetValue($VariableStateID, $result);
        } else {
          IPS_RequestAction($variableObject["ParentID"], $variableObject['ObjectIdent'], $result);
        }
      }
    }
  }

  private function addAccessory($DeviceName) {
    $JSON['DataID'] = "{018EF6B5-AB94-40C6-AA53-46943E824ACF}";
    $JSON['Buffer'] = utf8_encode('{"topic": "add", "name": "'.$DeviceName.'", "service": "Switch"}');
    $Data = json_encode($JSON);
    @$this->SendDataToParent($Data);
  }

  public function ConvertVariable($variable, $state) {
      switch ($variable["VariableType"]) {
        case 0: // boolean
          return boolval($state);
        case 1: // integer
          return intval($state);
        case 2: // float
          return floatval($state);
        case 3: // string
          return strval($state);
    }
  }
}
?>
