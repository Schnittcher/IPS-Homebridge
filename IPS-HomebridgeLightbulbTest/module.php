<?
require_once(__DIR__ . "/../HomeKitService.php");

class IPS_HomebridgeLightbulbTest extends HomeKitService {

  public function Create() {
      //Never delete this line!
      parent::Create();
      //Anzahl die in der Konfirgurationsform angezeigt wird - Hier Standard auf 1
      $this->RegisterPropertyInteger("Anzahl",1);
      $this->ConnectParent("{86C2DE8C-FB21-44B3-937A-9B09BB66FB76}");
      //99 Geräte können pro Konfirgurationsform angelegt werden
      for($count = 1; $count -1 < 99; $count++) {
        $DeviceName = "DeviceName{$count}";
        $LightbulbID = "LightbulbID{$count}";
        $VariableState = "VariableState{$count}";
        $VariableStateTrue = "VariableStateTrue{$count}";
        $VariableStateFalse = "VariableStateFalse{$count}";
        $VariableBrightness = "VariableBrightness{$count}";
        $VariableBrightnessOptional = "VariableBrightnessOptional{$count}";
        $VariableBrightnessMax = "VariableBrightnessMax{$count}";
        $this->RegisterPropertyString($DeviceName, "");
        $this->RegisterPropertyInteger($LightbulbID, 0);
        $this->RegisterPropertyInteger($VariableStateTrue, 1);
        $this->RegisterPropertyInteger($VariableStateFalse, 0);
        $this->RegisterPropertyInteger($VariableState, 0);
        $this->RegisterPropertyBoolean($VariableBrightnessOptional, false);
        $this->RegisterPropertyInteger($VariableBrightnessMax, 100);
        $this->RegisterPropertyInteger($VariableBrightness, 0);
      }
  }

  public function ApplyChanges() {
      //Never delete this line!
      parent::ApplyChanges();
      //Setze Filter für ReceiveData
      $this->SetReceiveDataFilter(".*Lightbulb.*");
      $anzahl = $this->ReadPropertyInteger("Anzahl");
      $Devices = [];
      for($count = 1; $count-1 < $anzahl; $count++) {
        $Devices[$count]["DeviceName"] = $this->ReadPropertyString("DeviceName{$count}");
        $Devices[$count]["VariableState"] = $this->ReadPropertyInteger("VariableState{$count}");
        $Devices[$count]["VariableStateTrue"] = $this->ReadPropertyInteger("VariableStateTrue{$count}");
        $Devices[$count]["VariableStateFalse"] =  $this->ReadPropertyInteger("VariableStateTrue{$count}");

        $Devices[$count]["VariableBrightness"] = $this->ReadPropertyInteger("VariableBrightness{$count}");
        $Devices[$count]["VariableBrightnessMax"] = $this->ReadPropertyInteger("VariableBrightnessMax{$count}");
        $Devices[$count]["VariableBrightnessOptional"] = $this->ReadPropertyBoolean("VariableBrightnessOptional{$count}");

        $BufferNameState = $Devices[$count]["DeviceName"]." State";
        $BufferNameBrightness = $Devices[$count]["DeviceName"]." Brightness";

        //Alte Registrierungen auf Variablen Veränderung aufheben
        $UnregisterBufferIDs = [];
        array_push($UnregisterBufferIDs,$this->GetBuffer($BufferNameState))
        array_push($UnregisterBufferIDs,$this->GetBuffer($BufferNameBrightness))
        $this->UnregisterMessages($UnregisterBufferIDs, 10603);

        if ($Devices[$count]["DeviceName"] != "") {
          //Regestriere State Variable auf Veränderungen
          $RegisterBufferIDs = [];
          array_push($RegisterBufferIDs,$this->$Devices[$count]["VariableState"]);
          array_push($RegisterBufferIDs,$this->$Devices[$count]["VariableBrightness"]);
          $this->RegisterMessages($RegisterBufferIDs, 10603);

          //Buffer mit den aktuellen Variablen IDs befüllen für State und Brightness
          $this->SetBuffer($BufferNameState,$Devices[$count]["VariableState"]);
          $this->SetBuffer($BufferNameBrightness,$Devices[$count]["VariableBrightness"]);

          $this->addAccessory($Devices[$count]["DeviceName"],$Devices[$count]["VariableBrightnessOptional"]);
        } else {
          return;
        }
      }
      $DevicesConfig = serialize($Devices);
      $this->SetBuffer("Lightbulb Config",$DevicesConfig);
    }

  public function Destroy() {
  }

  public function MessageSink($TimeStamp, $SenderID, $Message, $Data) {
    $Devices = unserialize($this->getBuffer("Lightbulb Config"));
    if ($Data[1] == true) {
      for($count = 1; $count-1 < $anzahl; $count++) {
        $Device = $Devices[$count];

        $DeviceName = $Device["DeviceName"];
        //Prüfen ob die SenderID gleich der State oder Brightness Variable ist, dann den aktuellen Wert an die Bridge senden
        switch ($SenderID) {
          case $Device["VariableState"]:
            $Characteristic = "On";
            $data = $Data[0];
            switch ($data) {
              case $Device["VariableStateTrue"]:
                $result = 'true';
                break;
              case $Device["VariableStateFalse"]:
                $result = 'false';
                break;
            }
            $JSON['DataID'] = "{018EF6B5-AB94-40C6-AA53-46943E824ACF}";
            $JSON['Buffer'] = utf8_encode('{"topic": "setValue", "Characteristic": "'.$Characteristic.'", "Device": "'.$DeviceName.'", "value": "'.$result.'"}');
            $Data = json_encode($JSON);
            $this->SendDataToParent($Data);
            break;
          case $Device["VariableBrightness"]:
            $Characteristic = "Brightness";
            $data = $Data[0];
            $VariableBrightnessMax = $Device["VariableBrightnessMax"];
            //Umrechnung
            $result = ($data / $VariableBrightnessMax) * 100;
            $JSON['DataID'] = "{018EF6B5-AB94-40C6-AA53-46943E824ACF}";
            $JSON['Buffer'] = utf8_encode('{"topic": "setValue", "Characteristic": "'.$Characteristic.'", "Device": "'.$DeviceName.'", "value": "'.$result.'"}');
            $Data = json_encode($JSON);
            $this->SendDataToParent($Data);
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
      $form .= '{ "type": "SelectInstance", "name": "LightbulbID'.$count.'", "caption": "Gerät" },';
      $form .= '{ "type": "SelectVariable", "name": "VariableState'.$count.'", "caption": "Status" },';

      $form .= '{ "type": "ValidationTextBox", "name": "VariableStateTrue'.$count.'", "caption": "Value True (On)" },';
      $form .= '{ "type": "ValidationTextBox", "name": "VariableStateFalse'.$count.'", "caption": "Value False (Off)" },';

      $form .= '{ "type": "CheckBox", "name": "VariableBrightnessOptional'.$count.'", "caption": "Dimmbar?" },';
      $form .= '{ "type": "SelectVariable", "name": "VariableBrightness'.$count.'", "caption": "Brightness" },';
      $form .= '{ "type": "NumberSpinner", "name": "VariableBrightnessMax'.$count.'", "caption": "Max. Value", "digits": 2},';
      $form .= '{ "type": "Button", "label": "Löschen", "onClick": "echo HBLightbulb_removeAccessory('.$this->InstanceID.','.$count.');" },';
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
    $this->SendDebug('ReceiveData',$JSONString, 0);
    $data = json_decode($JSONString);
    // Buffer decodieren und in eine Variable schreiben
    $Buffer = utf8_decode($data->Buffer);
    // Und Diese dann wieder dekodieren
    $HomebridgeData = json_decode($Buffer);

    //Prüfen ob die ankommenden Daten für den Lightbulb sind wenn ja, Status abfragen
    if ($HomebridgeData->Action == "get" && $HomebridgeData->Service == "Lightbulb") {
      $this->getVar($HomebridgeData->Device, $HomebridgeData->Characteristic);
    }
    //Prüfen ob die ankommenden Daten für den Lightbulb sind wenn ja, Status setzen
    if ($HomebridgeData->Action == "set" && $HomebridgeData->Service == "Lightbulb") {
      $this->setVar($HomebridgeData->Device, $HomebridgeData->Value, $HomebridgeData->Characteristic);
    }
  }

  public function getVar($DeviceName, $Characteristic) {
    $Devices = unserialize($this->getBuffer("Lightbulb Config"));
    for($count = 1; $count -1 < $this->ReadPropertyInteger("Anzahl"); $count++) {
      $Device = $Devices[$count];
      $this->SendDebug('ApplyChanges',$Device["DeviceName"], 0);
      //Prüfen ob der übergebene Name aus dem Socket zu einem Namen aus der Konfirgurationsform passt
      $name = $Device["DeviceName"];
      if ($DeviceName == $name) {
        //IPS Variable abfragen
        switch ($Characteristic) {
          case 'On':
            //Lightbulb State abfragen
            $result = intval(GetValue($Device["VariableState"]));
            switch ($result) {
              case $Device["VariableStateTrue"]:
                $result = 'true';
                break;
              case $Device["VariableStateFalse"]:
                $result = 'false';
                break;
            }
            break;
          case 'Brightness':
            //Lightbulb Brightness abfragen
            $result = GetValue($Device["VariableBrightness"]);
            $result = ($result / $Device["VariableBrightnessMax"]) * 100;
            break;
        }
        //Status an die Bridge senden
        $JSON['DataID'] = "{018EF6B5-AB94-40C6-AA53-46943E824ACF}";
        $JSON['Buffer'] = utf8_encode('{"topic": "callback", "Characteristic": "'.$Characteristic.'", "Device": "'.$DeviceName.'", "value": "'.$result.'"}');
        $Data = json_encode($JSON);
        $this->SendDataToParent($Data);
        return;
      }
    }
  }

  public function setVar($DeviceName, $value, $Characteristic) {
    $Devices = unserialize($this->getBuffer("Lightbulb Config"));
    for($count = 1; $count -1 < $this->ReadPropertyInteger("Anzahl"); $count++) {
      $Device = $Devices[$count];

      //Prüfen ob der übergebene Name aus dem Hook zu einem Namen aus der Konfirgurationsform passt
      $name = $Device["DeviceName"];
      if ($DeviceName == $name) {
        switch ($Characteristic) {
          case 'On':
            //Lightbulb State abfragen
            $result = intval(GetValue($Device["VariableState"]));

            //Result Wert in erwartete Device Variable ändern
            switch ($result) {
              case $Device["VariableStateTrue"]:
                $result = 'true';
                break;
              case $Device["VariableStateFalse"]:
                $result = 'false';
                break;
            }

            //Übergebnenen Wert in erwartete Device Variable ändern
            switch ($value) {
              case $Device["VariableStateTrue"]:
                $value = $Device["VariableStateTrue"];
                break;
              case $Device["VariableStateFalse"]:
                $value = $Device["VariableStateFalse"];
                break;
            }

            if ($result == 'true' && $value == $Device["VariableStateFalse"]) {
              $variable = IPS_GetVariable($Device["VariableState"]);
              $variableObject = IPS_GetObject($Device["VariableState"]);
              //den übgergebenen Wert in den VariablenTyp für das IPS-Gerät umwandeln
              $result = $this->ConvertVariable($variable, $value);
              //Geräte Variable setzen
              IPS_RequestAction($variableObject["ParentID"], $variableObject['ObjectIdent'], $result);
            }

            if ($result == 'false' && $value == $Device["VariableStateTrue"]) {
              $variable = IPS_GetVariable($Device["VariableState"]);
              $variableObject = IPS_GetObject($Device["VariableState"]);
              //den übgergebenen Wert in den VariablenTyp für das IPS-Gerät umwandeln
              $result = $this->ConvertVariable($variable, $value);
              //Geräte Variable setzen
              IPS_RequestAction($variableObject["ParentID"], $variableObject['ObjectIdent'], $result);
            }
            break;
          case 'Brightness':
            //Umrechnung
            $value = ($value / 100) * $Device["VariableBrightnessMax"];

            $variable = IPS_GetVariable($Device["VariableBrightness"]);
            $variableObject = IPS_GetObject($Device["VariableBrightness"]);

            //den übgergebenen Wert in den VariablenTyp für das IPS-Gerät umwandeln
            $result = $this->ConvertVariable($variable, $value);
            //Geräte Variable setzen
            IPS_RequestAction($variableObject["ParentID"], $variableObject['ObjectIdent'], $result);
            break;
        }
      }
    }
  }

  private function addAccessory($DeviceName,$Brightness) {
    //Payload bauen
    $payload["name"] = $DeviceName;
    $payload["service"] = "Lightbulb";

    if ($Brightness == true) {
      $payload["Brightness"] = "default";
    }

    $array["topic"] ="add";
    $array["payload"] = $payload;
    $data = json_encode($array);
    $SendData = json_encode(Array("DataID" => "{018EF6B5-AB94-40C6-AA53-46943E824ACF}", "Buffer" => $data));
    @$this->SendDataToParent($SendData);
  }
}
?>
