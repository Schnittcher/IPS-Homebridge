<?
require_once(__DIR__ . "/../HomeKitService.php");

class IPS_HomebridgeSpeaker extends HomeKitService {
  public function Create() {
      //Never delete this line!
      parent::Create();
      $this->ConnectParent("{86C2DE8C-FB21-44B3-937A-9B09BB66FB76}");
      //Anzahl die in der Konfirgurationsform angezeigt wird - Hier Standard auf 1
      $this->RegisterPropertyInteger("Anzahl",1);
      //99 Geräte können pro Konfirgurationsform angelegt werden
      for($count = 1; $count -1 < 99; $count++) {
        $DeviceName = "DeviceName{$count}";
        $SpeakerDeviceID = "SpeakerDeviceID{$count}";
        $VariableMute = "VariableMute{$count}";
        $VariableMuteTrue = "VariableMuteTrue{$count}";
        $VariableMuteFalse = "VariableMuteFalse{$count}";
        $VariableVolume = "VariableVolume{$count}";
        $VariableVolumeMax = "VariableVolumeMax{$count}";
        $this->RegisterPropertyString($DeviceName, "");
        $this->RegisterPropertyInteger($SpeakerDeviceID, 0);
        $this->RegisterPropertyInteger($VariableMute, 0);
        $this->RegisterPropertyInteger($VariableMuteTrue, 0);
        $this->RegisterPropertyInteger($VariableMuteFalse, 0);
        $this->RegisterPropertyInteger($VariableVolume, 0);
        $this->RegisterPropertyInteger($VariableVolumeMax, 0);
      }
  }
  public function ApplyChanges() {
      //Never delete this line!
      parent::ApplyChanges();
      //Setze Filter für ReceiveData
      $this->SetReceiveDataFilter(".*Speaker.*");
      $anzahl = $this->ReadPropertyInteger("Anzahl");
      for($count = 1; $count-1 < $anzahl; $count++) {
        $Devices[$count]["DeviceName"] = $this->ReadPropertyString("DeviceName{$count}");
        $Devices[$count]["VariableVolume"] = $this->ReadPropertyInteger("VariableVolume{$count}");
        $Devices[$count]["VariableMute"] = $this->ReadPropertyInteger("VariableMute{$count}");
        $Devices[$count]["VariableMuteTrue"] = $this->ReadPropertyInteger("VariableMuteTrue{$count}");
        $Devices[$count]["VariableMuteFalse"] =  $this->ReadPropertyInteger("VariableMuteFalse{$count}");
        $Devices[$count]["VariableVolumeMax"] =  $this->ReadPropertyInteger("VariableVolumeMax{$count}");


        $DeviceNameCount = "DeviceName{$count}";
        $VariableMuteCount = "VariableMute{$count}";
        $VariableVolumeCount = "VariableVolume{$count}";
        $BufferNameMute = $Devices[$count]["DeviceName"]." Mute";
        $BufferNameVolume = $Devices[$count]["DeviceName"]." Volume";


        //Alte Registrierung auf Variablen Veränderung aufheben
        $UnregisterBufferIDs = [];
        array_push($UnregisterBufferIDs,$this->GetBuffer($BufferNameMute));
        array_push($UnregisterBufferIDs,$this->GetBuffer($BufferNameVolume));
        $this->UnregisterMessages($UnregisterBufferIDs, 10603);

        if ($this->ReadPropertyString($DeviceNameCount) != "") {
          //Regestriere Humidity Variable auf Veränderungen
          $RegisterBufferIDs = [];
          array_push($RegisterBufferIDs,$Devices[$count]["VariableMute"]);
          array_push($RegisterBufferIDs,$Devices[$count]["VariableVolume"]);
          $this->RegisterMessages($RegisterBufferIDs, 10603);
          //Buffer mit der aktuellen Variablen ID befüllen
          $this->SetBuffer($BufferNameMute,$Devices[$count]["VariableMute"]);
          $this->SetBuffer($BufferNameVolume,$Devices[$count]["VariableVolume"]);

          //Accessory anlegen
          $this->addAccessory($this->ReadPropertyString($DeviceNameCount));
        } else {
          return;
        }
      }
      $DevicesConfig = serialize($Devices);
      $this->SetBuffer("Speaker Config",$DevicesConfig);
    }

    public function Destroy() {

    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data) {
      $Devices = unserialize($this->getBuffer("Speaker Config"));
      if ($Data[1] == true) {
        $anzahl = $this->ReadPropertyInteger("Anzahl");
        for($count = 1; $count-1 < $anzahl; $count++) {
          $Device = $Devices[$count];

          $DeviceName = $Device["DeviceName"];
          //Prüfen ob die SenderID gleich der Humidity Variable ist, dann den aktuellen Wert an die Bridge senden
          if ($Device["VariableVolume"] == $SenderID) {
            $Characteristic = "Volume";
            $data = $Data[0];
            $result = number_format($data, 2, '.', '');
            $this->sendJSONToParent("setValue", $Characteristic, $DeviceName, $result);
          }
          if ($Device["VariableVolume"] == $SenderID) {
            $Characteristic = "Mute";
            $data = $Data[0];
            if ($data > 0) {
              $data = $Device["VariableMuteTrue"];
            }
            switch ($data) {
              case $Device["VariableMuteTrue"]:
                $result = 'true';
                break;
              case $Device["VariableMuteFalse"]:
                $result = 'false';
                break;
            }
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
        $form .= '{ "type": "SelectInstance", "name": "SpeakerDeviceID'.$count.'", "caption": "Gerät" },';
        $form .= '{ "type": "SelectVariable", "name": "VariableMute'.$count.'", "caption": "Mute" },';
        $form .= '{ "type": "ValidationTextBox", "name": "VariableMuteTrue'.$count.'", "caption": "Value True (Muted)" },';
        $form .= '{ "type": "ValidationTextBox", "name": "VariableMuteFalse'.$count.'", "caption": "Value False (not muted)" },';
        $form .= '{ "type": "SelectVariable", "name": "VariableVolume'.$count.'", "caption": "Volume" },';
        $form .= '{ "type": "NumberSpinner", "name": "VariableVolumeMax'.$count.'", "caption": "Max. Value", "digits": 2},';
        $form .= '{ "type": "Button", "label": "Löschen", "onClick": "echo HBSpeaker_removeAccessory('.$this->InstanceID.','.$count.');" },';
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
      $Devices = unserialize($this->getBuffer("Speaker Config"));
      $anzahl = $this->ReadPropertyInteger("Anzahl");
      for($count = 1; $count -1 < $anzahl; $count++) {
        $Device = $Devices[$count];
        //Prüfen ob der übergebene Name aus dem Socket zu einem Namen aus der Konfirgurationsform passt
        $name = $Device["DeviceName"];
        if ($DeviceName == $name) {
          //IPS Variable abfragen
          switch ($Characteristic) {
            case 'Mute':
              //Mute abfragen
              $result = floatval(GetValue($Device["VariableMute"]));
              if ($result > 0) {
                $result = $Device["VariableMuteTrue"];
              }
              //IPS Variable für die Bridge umwandeln
              switch ($result) {
                case $Device["VariableMuteTrue"]:
                  $result = 'true';
                  break;
                case $Device["VariableMuteFalse"]:
                  $result = 'false';
                  break;
              }
              break;
            case 'Volume':
              //Lightbulb Brightness abfragen
              $result = GetValue($Device["VariableVolume"]);
              $result = ($result / $Device["VariableVolumeMax"]) * 100;
              $result = intval($result);
              break;
          }
          //Status an die Bridge senden
          $this->sendJSONToParent("callback", $Characteristic, $DeviceName, $result);
          return;
        }
      }
    }

    public function setVar($DeviceName, $value, $Characteristic) {
      $Devices = unserialize($this->getBuffer("Speaker Config"));
      $anzahl = $this->ReadPropertyInteger("Anzahl");
      for($count = 1; $count -1 < $anzahl; $count++) {
        $Device = $Devices[$count];
        //Prüfen ob der übergebene Name aus dem Hook zu einem Namen aus der Konfirgurationsform passt
        $name = $Device["DeviceName"];
        if ($DeviceName == $name) {
          switch ($Characteristic) {
            case 'Mute':
              //Mute abfragen
              $result = floatval(GetValue($Device["VariableMute"]));
              //IPS Variable für die Bridge umwandeln
        if ($result > 0) {
          $result = $Device["VariableMuteTrue"];
        }
        if ($value == 1) {
          $value = $Device["VariableMuteTrue"];
        }
              switch ($result) {
                case $Device["VariableMuteTrue"]:
                  $result = 'true';
                  break;
                case $Device["VariableMuteFalse"]:
                  $result = 'false';
                  break;
              }
              if ($result == 'true' && $value == $Device["VariableMuteFalse"]) {
                $variable = IPS_GetVariable($Device["VariableMute"]);
                $variableObject = IPS_GetObject($Device["VariableMute"]);
                $value = $Device["VariableMuteFalse"];
                //den übgergebenen Wert in den VariablenTyp für das IPS-Gerät umwandeln
                $result = $this->ConvertVariable($variable, $value);
                //Geräte Variable setzen
                $this->SetValueToIPS($variable,$variableObject,$result);
              }
              if ($result == 'false' && $value == $Device["VariableMuteTrue"]) {
                $variable = IPS_GetVariable($Device["VariableMute"]);
                $variableObject = IPS_GetObject($Device["VariableMute"]);
                $value = $Device["VariableMuteTrue"];
                //den übgergebenen Wert in den VariablenTyp für das IPS-Gerät umwandeln
                $result = $this->ConvertVariable($variable, $value);
                //Geräte Variable setzen
                $this->SetValueToIPS($variable,$variableObject,$result);
              }
              break;
            case 'Volume':
              //Umrechnung
              $value = ($value / 100) * $Device["VariableVolumeMax"];
              $variable = IPS_GetVariable($Device["VariableVolume"]);
              $variableObject = IPS_GetObject($Device["VariableVolume"]);
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
      $payload["service"] = "Speaker";
      $payload["Volume"] = "default";

      $array["topic"] ="add";
      $array["payload"] = $payload;
      $data = json_encode($array);
      $SendData = json_encode(Array("DataID" => "{018EF6B5-AB94-40C6-AA53-46943E824ACF}", "Buffer" => $data));
      @$this->SendDataToParent($SendData);
    }
  }

  ?>
