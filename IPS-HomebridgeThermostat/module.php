<?
class IPS_HomebridgeThermostat extends IPSModule {
  public function Create() {
      //Never delete this line!
      parent::Create();
      $this->ConnectParent("{86C2DE8C-FB21-44B3-937A-9B09BB66FB76}");
      //Anzahl die in der Konfirgurationsform angezeigt wird - Hier Standard auf 1
      $this->RegisterPropertyInteger("Anzahl",1);
      //99 Geräte können pro Konfirgurationsform angelegt werden
      for($count = 1; $count -1 < 99; $count++) {
        $DeviceName = "DeviceName{$count}";
        $ThermostatID = "ThermostatID{$count}";
        $CurrentHeatingCoolingState = "CurrentHeatingCoolingState{$count}";
        $TargetHeatingCoolingState = "TargetHeatingCoolingState{$count}";
        $CurrentTemperature = "CurrentTemperature{$count}";
        $TargetTemperature = "TargetTemperature{$count}";

        $CurrentHeatingCoolingOff = "CurrentHeatingCoolingOff{$count}";
        $CurrentHeatingCoolingHeating ="CurrentHeatingCoolingHeating{$count}";
        $CurrentHeatingCoolingCooling ="CurrentHeatingCoolingCooling{$count}";

        $TargetHeatingCoolingOff = "TargetHeatingCoolingOff{$count}";
        $TargetHeatingCoolingHeating = "TargetHeatingCoolingHeating{$count}";
        $TargetHeatingCoolingCooling = "TargetHeatingCoolingCooling{$count}";
        $TargetHeatingCoolingAuto = "TargetHeatingCoolingAuto{$count}";

        $this->RegisterPropertyString($DeviceName, "");
        $this->RegisterPropertyInteger($ThermostatID, 0);
        //CurrentHeatingCoolingState
        $this->RegisterPropertyInteger($CurrentHeatingCoolingState, 0);
        $this->RegisterPropertyInteger($CurrentHeatingCoolingOff, "");
        $this->RegisterPropertyInteger($CurrentHeatingCoolingHeating, "");
        $this->RegisterPropertyInteger($CurrentHeatingCoolingCooling, "");
        //TargetHeatingCoolingState
        $this->RegisterPropertyInteger($TargetHeatingCoolingState, 0);
        $this->RegisterPropertyInteger($TargetHeatingCoolingOff, "");
        $this->RegisterPropertyInteger($TargetHeatingCoolingHeating, "");
        $this->RegisterPropertyInteger($TargetHeatingCoolingCooling, "");
        $this->RegisterPropertyInteger($TargetHeatingCoolingAuto, "");

        $this->RegisterPropertyInteger($CurrentTemperature, 0);
        $this->RegisterPropertyInteger($TargetTemperature, 0);

        $this->SetBuffer($DeviceName." CurrentHeatingCoolingState ".$CurrentHeatingCoolingState,"");
        $this->SetBuffer($DeviceName." TargetHeatingCoolingState ".$TargetHeatingCoolingState,"");
        $this->SetBuffer($DeviceName." CurrentTemperature ".$CurrentTemperature,"");
        $this->SetBuffer($DeviceName." TargetTemperature ".$TargetTemperature,"");
      }
  }
  public function ApplyChanges() {
      //Never delete this line!
      parent::ApplyChanges();
      //Setze Filter für ReceiveData
      $this->SetReceiveDataFilter(".*Thermostat.*");
      $anzahl = $this->ReadPropertyInteger("Anzahl");

      for($count = 1; $count-1 < $anzahl; $count++) {
        $Devices[$count]["DeviceName"] = $this->ReadPropertyString("DeviceName{$count}");
        $Devices[$count]["CurrentHeatingCoolingState"] = $this->ReadPropertyInteger("DeviceName{$count}");
        $Devices[$count]["TargetHeatingCoolingState"] = $this->ReadPropertyInteger("DeviceName{$count}");
        $Devices[$count]["CurrentTemperature"] = $this->ReadPropertyInteger("DeviceName{$count}");
        $Devices[$count]["TargetTemperature"] = $this->ReadPropertyInteger("DeviceName{$count}");

        $Devices[$count]["CurrentHeatingCoolingOff"] = $this->ReadPropertyInteger("CurrentHeatingCoolingOff{$count}");
        $Devices[$count]["CurrentHeatingCoolingHeating"] = $this->ReadPropertyInteger("CurrentHeatingCoolingHeating{$count}");
        $Devices[$count]["CurrentHeatingCoolingCooling"] = $this->ReadPropertyInteger("CurrentHeatingCoolingCooling{$count}");
        $Devices[$count]["TargetHeatingCoolingOff"] = $this->ReadPropertyInteger("TargetHeatingCoolingOff{$count}");
        $Devices[$count]["TargetHeatingCoolingHeating"] = $this->ReadPropertyInteger("TargetHeatingCoolingHeating{$count}");
        $Devices[$count]["TargetHeatingCoolingCooling"] = $this->ReadPropertyInteger("TargetHeatingCoolingCooling{$count}");
        $Devices[$count]["TargetHeatingCoolingAuto"] = $this->ReadPropertyInteger("TargetHeatingCoolingAuto{$count}");

        //Buffernamen
        $BufferNameCurrentHeatingCoolingState = $Devices[$count]["DeviceName"]." CurrentHeatingCoolingState";
        $BufferNameTargetHeatingCoolingState = $Devices[$count]["DeviceName"]." TargetHeatingCoolingState";
        $BufferNameCurrentTemperature = $Devices[$count]["DeviceName"]." CurrentTemperature";
        $BufferNameTargetTemperature = $Devices[$count]["DeviceName"]." TargetTemperature";

        //Alte Registrierungen auf Variablen Veränderung aufheben
        $UnregisterBufferIDs = [];
        array_push($UnregisterBufferIDs,$this->GetBuffer($BufferNameCurrentHeatingCoolingState));
        array_push($UnregisterBufferIDs,$this->GetBuffer($BufferNameTargetHeatingCoolingState));
        array_push($UnregisterBufferIDs,$this->GetBuffer($BufferNameCurrentTemperature));
        array_push($UnregisterBufferIDs,$this->GetBuffer($BufferNameTargetTemperature));
        $this->UnregisterMessages($UnregisterBufferIDs, 10603);

        if ($Devices[$count]["DeviceName"] != "") {
          //Regestriere State Variable auf Veränderungen
          $RegisterBufferIDs = [];
          array_push($RegisterBufferIDs,$Devices[$count]["CurrentHeatingCoolingState"]);
          array_push($RegisterBufferIDs,$Devices[$count]["TargetHeatingCoolingState"]);
          array_push($RegisterBufferIDs,$Devices[$count]["CurrentTemperature"]);
          array_push($RegisterBufferIDs,$Devices[$count]["TargetTemperature"]);
          $this->RegisterMessages($RegisterBufferIDs, 10603);

          //Buffer mit den aktuellen Variablen IDs befüllen
          $this->SetBuffer($BufferNameState,$Devices[$count]["CurrentHeatingCoolingState"]);
          $this->SetBuffer($BufferNameBrightness,$Devices[$count]["TargetHeatingCoolingState"]);
          $this->SetBuffer($BufferNameState,$Devices[$count]["CurrentTemperature"]);
          $this->SetBuffer($BufferNameBrightness,$Devices[$count]["TargetTemperature"]);

          //Accessory anlegen
          $this->addAccessory($Devices[$count]["DeviceName"]);
        }
        else {
          return;
        }
      }
      $DevicesConfig = serialize($Devices);
      $this->SetBuffer("Thermostat Config",$DevicesConfig);
    }
  public function Destroy() {
  }

  public function MessageSink($TimeStamp, $SenderID, $Message, $Data) {
    $Devices = unserialize($this->getBuffer("Thermostat Config"));
    if ($Data[1] == true) {
      $anzahl = $this->ReadPropertyInteger("Anzahl");

      for($count = 1; $count-1 < $anzahl; $count++) {
        $Device = $Devices[$count];

        $DeviceName = $Device["DeviceName"];
        $data = $Data[0];
        //Prüfen ob die SenderID gleich der Temperatur Variable ist, dann den aktuellen Wert an die Bridge senden
        switch ($SenderID) {
          case $Device["CurrentHeatingCoolingState"]:
            $CurrentHeatingCoolingOff = $Device["CurrentHeatingCoolingOff"];
            $CurrentHeatingCoolingHeating = $Device["CurrentHeatingCoolingHeating"];
            $CurrentHeatingCoolingCooling = $Device["CurrentHeatingCoolingCooling"];
            $Characteristic = "CurrentHeatingCoolingState";
            $result = $data;
            switch ($result) {
              case $CurrentHeatingCoolingOff:
                $result = 0;
                break;
              case $CurrentHeatingCoolingHeating:
                $result = 1;
                break;
              case $CurrentHeatingCoolingCooling:
                $result = 2;
                break;
            }
            break;
          case $Device["TargetHeatingCoolingState"]:
            $Characteristic = "TargetHeatingCoolingState";
            $result = $data;

            $VariableTargetHeatingCoolingStateID = $Device["TargetHeatingCoolingState"];
            $TargetHeatingCoolingOff = $Device["TargetHeatingCoolingOff"];
            $TargetHeatingCoolingHeating = $Device["TargetHeatingCoolingHeating"];
            $TargetHeatingCoolingCooling = $Device["TargetHeatingCoolingCooling"];
            $TargetHeatingCoolingAuto = $Device["TargetHeatingCoolingAuto"];
            switch ($result) {
              case $TargetHeatingCoolingOff:
                $result = 0;
                break;
              case $TargetHeatingCoolingHeating:
                $result = 1;
                break;
              case $TargetHeatingCoolingCooling:
                $result = 2;
                break;
              case $TargetHeatingCoolingAuto:
                $result = 3;
                break;
            }
            break;
          case $Device["CurrentTemperature"]:
            $Characteristic = "CurrentTemperature";
            $result = number_format($data, 2, '.', '');
            break;
          case $Device["TargetTemperature"]:
            $Characteristic = "TargetTemperature";
            $result = number_format($data, 2, '.', '');
            break;
        }
        $this->sendJSONToParent("setValue", $Characteristic, $DeviceName, $result);
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
      $form .= '{ "type": "SelectInstance", "name": "ThermostatID'.$count.'", "caption": "Gerät" },';
      $form .= '{ "type": "SelectVariable", "name": "CurrentHeatingCoolingState'.$count.'", "caption": "CurrentHeatingCoolingState" },';

      $form .= '{ "type": "ValidationTextBox", "name": "CurrentHeatingCoolingOff'.$count.'", "caption": "Value Off" },';
      $form .= '{ "type": "ValidationTextBox", "name": "CurrentHeatingCoolingHeating'.$count.'", "caption": "Value Heating" },';
      $form .= '{ "type": "ValidationTextBox", "name": "CurrentHeatingCoolingCooling'.$count.'", "caption": "Value Cooling" },';

      $form .= '{ "type": "SelectVariable", "name": "TargetHeatingCoolingState'.$count.'", "caption": "TargetHeatingCoolingState" },';

      $form .= '{ "type": "ValidationTextBox", "name": "TargetHeatingCoolingOff'.$count.'", "caption": "Value Off" },';
      $form .= '{ "type": "ValidationTextBox", "name": "TargetHeatingCoolingHeating'.$count.'", "caption": "Value Heating" },';
      $form .= '{ "type": "ValidationTextBox", "name": "TargetHeatingCoolingCooling'.$count.'", "caption": "Value Cooling" },';
      $form .= '{ "type": "ValidationTextBox", "name": "TargetHeatingCoolingAuto'.$count.'", "caption": "Value Auto" },';

      $form .= '{ "type": "SelectVariable", "name": "CurrentTemperature'.$count.'", "caption": "CurrentTemperature" },';
      $form .= '{ "type": "SelectVariable", "name": "TargetTemperature'.$count.'", "caption": "TargetTemperature" },';
      $form .= '{ "type": "Button", "label": "Löschen", "onClick": "echo HBThermostat_removeAccessory('.$this->InstanceID.','.$count.');" },';
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
    $Devices = unserialize($this->getBuffer("Thermostat Config"));
    $anzahl = $this->ReadPropertyInteger("Anzahl");
    for($count = 1; $count -1 < $anzahl; $count++) {
      $Device = $Devices[$count];
      $name = $Device["DeviceName"];
      //Prüfen ob der übergebene Name zu einem Namen aus der Konfirgurationsform passt wenn ja Wert an die Bridge senden
      if ($DeviceName == $name) {
        switch ($Characteristic) {
          case 'CurrentHeatingCoolingState':
            $VariableCurrentHeatingCoolingStateID = $Device["CurrentHeatingCoolingState"];
            $CurrentHeatingCoolingOff = $Device["CurrentHeatingCoolingOff"];
            $CurrentHeatingCoolingHeating = $Device["CurrentHeatingCoolingHeating"];
            $CurrentHeatingCoolingCooling = $Device["CurrentHeatingCoolingCooling"];

            $result = intval(GetValue($VariableCurrentHeatingCoolingStateID));
            switch ($result) {
              case $CurrentHeatingCoolingOff:
                $result = 0;
                break;
              case $CurrentHeatingCoolingHeating:
                $result = 1;
                break;
              case $CurrentHeatingCoolingCooling:
                $result = 2;
                break;
            }
            break;
          case 'TargetHeatingCoolingState':
          $VariableTargetHeatingCoolingStateID = $Device["TargetHeatingCoolingState"];
          $TargetHeatingCoolingOff = $Device["TargetHeatingCoolingOff"];
          $TargetHeatingCoolingHeating = $Device["TargetHeatingCoolingHeating"];
          $TargetHeatingCoolingCooling = $Device["TargetHeatingCoolingCooling"];
          $TargetHeatingCoolingAuto = $Device["TargetHeatingCoolingAuto"];
            $result = intval(GetValue($VariableTargetHeatingCoolingStateID));
            switch ($result) {
              case $TargetHeatingCoolingOff:
                $result = 0;
                break;
              case $TargetHeatingCoolingHeating:
                $result = 1;
                break;
              case $TargetHeatingCoolingCooling:
                $result = 2;
                break;
              case $TargetHeatingCoolingAuto:
                $result = 3;
                break;
            }
            break;
          case 'CurrentTemperature':
            $VariableCurrentTemperatureID = $Device["CurrentTemperature"]);
            $result = GetValue($VariableCurrentTemperatureID);
            $result = number_format($result, 2, '.', '');
            break;
          case 'TargetTemperature':
            $VariableTargetTemperatureID = $Device["TargetTemperature"]);
            $result = GetValue($VariableTargetTemperatureID);
            $result = number_format($result, 2, '.', '');
            break;
          case 'TemperatureDisplayUnits':
            $result = 0;
            break;
          }
        $this->sendJSONToParent("callback", $Characteristic, $DeviceName, $result);
        return;
      }
    }
  }

  public function setVar($DeviceName, $value, $Characteristic) {
    $Devices = unserialize($this->getBuffer("Thermostat Config"));
    for($count = 1; $count -1 < $this->ReadPropertyInteger("Anzahl"); $count++) {
      $Device = $Devices[$count];
      //Prüfen ob der übergebene Name zu einem Namen aus der Konfirgurationsform passt
      $name = $Device["DeviceName"];
      if ($DeviceName == $name) {
        switch ($Characteristic) {
          case 'CurrentHeatingCoolingState':
            $VariableCurrentHeatingCoolingStateID = $Device["CurrentHeatingCoolingState"];
            $CurrentHeatingCoolingOff = $Device["CurrentHeatingCoolingOff"];
            $CurrentHeatingCoolingHeating = $Device["CurrentHeatingCoolingHeating"];
            $CurrentHeatingCoolingCooling = $Device["CurrentHeatingCoolingCooling"];

            $variable = IPS_GetVariable($VariableCurrentHeatingCoolingStateID);
            $variableObject = IPS_GetObject($VariableCurrentHeatingCoolingStateID);
            switch ($value) {
              case 0:
                $result = $CurrentHeatingCoolingOff;
                break;
              case 1:
                $result = $CurrentHeatingCoolingHeating;
                break;
              case 2:
                $result = $CurrentHeatingCoolingCooling;
                break;
            }
            //den übgergebenen Wert in den VariablenTyp für das IPS-Gerät umwandeln
            $result = $this->ConvertVariable($variable, $result);
            //Geräte Variable setzen
            IPS_RequestAction($variableObject["ParentID"], $variableObject['ObjectIdent'], $result);
            break;
          case 'TargetHeatingCoolingState':
            $VariableTargetHeatingCoolingStateID = $Device["TargetHeatingCoolingState"];
            $TargetHeatingCoolingOff = $Device["TargetHeatingCoolingOff"];
            $TargetHeatingCoolingHeating = $Device["TargetHeatingCoolingHeating"];
            $TargetHeatingCoolingCooling = $Device["TargetHeatingCoolingCooling"];
            $TargetHeatingCoolingAuto = $Device["TargetHeatingCoolingAuto"];

            $variable = IPS_GetVariable($VariableTargetHeatingCoolingStateID);
            $variableObject = IPS_GetObject($VariableTargetHeatingCoolingStateID);

            switch ($value) {
              case 0:
                $result = $TargetHeatingCoolingOff;
                break;
              case 1:
                $result = $TargetHeatingCoolingHeating;
                break;
              case 2:
                $result = $TargetHeatingCoolingCooling;
                IPS_LogMessage("cooling", $result);
                break;
              case 3:
                $result = $TargetHeatingCoolingAuto;
                break;
            }
            //den übgergebenen Wert in den VariablenTyp für das IPS-Gerät umwandeln
            $result = $this->ConvertVariable($variable, $result);
            IPS_RequestAction($variableObject["ParentID"], $variableObject['ObjectIdent'], $result);
            break;
          case 'CurrentTemperature':
            $VariableCurrentTemperatureID = $Device["CurrentTemperature"];
            $variable = IPS_GetVariable($VariableCurrentTemperatureID);
            $variableObject = IPS_GetObject($VariableCurrentTemperatureID);
            $result = $this->ConvertVariable($variable, $value);
            IPS_RequestAction($variableObject["ParentID"], $variableObject['ObjectIdent'], $result);
            break;
          case 'TargetTemperature':
            $VariableTargetTemperatureID = $Device["TargetTemperature"];
            $variable = IPS_GetVariable($VariableCurrentTemperatureID);
            $variableObject = IPS_GetObject($VariableCurrentTemperatureID);
            $result = $this->ConvertVariable($variable, $value);
            IPS_RequestAction($variableObject["ParentID"], $variableObject['ObjectIdent'], $result);
            break;
        }
      }
    }
  }

  private function addAccessory($DeviceName) {
    //Payload bauen
    $payload["name"] = $DeviceName;
    $payload["service"] = "Thermostat";

    $array["topic"] ="add";
    $array["payload"] = $payload;
    $data = json_encode($array);
    $SendData = json_encode(Array("DataID" => "{018EF6B5-AB94-40C6-AA53-46943E824ACF}", "Buffer" => $data));
    @$this->SendDataToParent($SendData);
  }
}
?>
