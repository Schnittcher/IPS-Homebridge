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
        //Hochzäglen
        $DeviceNameCount = "DeviceName{$count}";
        $ThermostatIDCount = "ThermostatID{$count}";
        $CurrentHeatingCoolingStateCount = "CurrentHeatingCoolingState{$count}";
        $TargetHeatingCoolingStateCount = "TargetHeatingCoolingState{$count}";
        $CurrentTemperatureCount = "CurrentTemperature{$count}";
        $TargetTemperatureCount = "TargetTemperature{$count}";
        //Buffernamen
        $BufferNameCurrentHeatingCoolingState = $DeviceNameCount." ".$CurrentHeatingCoolingStateCount;
        $BufferNameTargetHeatingCoolingState = $DeviceNameCount." ".$TargetHeatingCoolingStateCount;
        $BufferNameCurrentTemperature = $DeviceNameCount." ".$CurrentTemperatureCount;
        $BufferNameTargetTemperature = $DeviceNameCount." ".$TargetTemperatureCount;
        //Buffer auslesen
        $VariableCurrentHeatingCoolingStateBuffer = $this->GetBuffer($BufferNameCurrentHeatingCoolingState);
        $VariableTargetHeatingCoolingStateBuffer = $this->GetBuffer($BufferNameTargetHeatingCoolingState);
        $VariableCurrentTemperatureBuffer = $this->GetBuffer($BufferNameCurrentTemperature);
        $VariableNameTargetTemperatureBuffer = $this->GetBuffer($BufferNameTargetTemperature);

        if (is_int($VariableCurrentHeatingCoolingStateBuffer)) {
        $this->UnregisterMessage(intval($VariableCurrentHeatingCoolingStateBuffer), 10603);
        }
        if (is_int($VariableTargetHeatingCoolingStateBuffer)) {
        $this->UnregisterMessage(intval($VariableTargetHeatingCoolingStateBuffer), 10603);
        }
        if (is_int($VariableCurrentTemperatureBuffer)) {
        $this->UnregisterMessage(intval($VariableCurrentTemperatureBuffer), 10603);
        }
        if (is_int($VariableNameTargetTemperatureBuffer)) {
        $this->UnregisterMessage(intval($VariableNameTargetTemperatureBuffer), 10603);
        }

        $DeviceName = $this->ReadPropertyString($DeviceNameCount);
        if ($DeviceName != "") {
          //Accessory anlegen
          $this->addAccessory($DeviceName);

          $VariableCurrentHeatingCoolingStateID = $this->ReadPropertyInteger($CurrentHeatingCoolingStateCount);
          $VariableTargetHeatingCoolingStateID = $this->ReadPropertyInteger($TargetHeatingCoolingStateCount);
          $VariableCurrentTemperatureID = $this->ReadPropertyInteger($CurrentTemperatureCount);
          $VariableNameTargetTemperatureID = $this->ReadPropertyInteger($TargetTemperatureCount);
          //Regestriere Variablen auf Veränderungen
          $this->RegisterMessage($VariableCurrentHeatingCoolingStateID, 10603);
          $this->RegisterMessage($VariableTargetHeatingCoolingStateID, 10603);
          $this->RegisterMessage($VariableCurrentTemperatureID, 10603);
          $this->RegisterMessage($VariableNameTargetTemperatureID, 10603);
          //Buffer mit der aktuellen Variablen ID befüllen
          $this->SetBuffer($BufferNameCurrentHeatingCoolingState,$VariableCurrentHeatingCoolingStateID);
          $this->SetBuffer($BufferNameTargetHeatingCoolingState,$VariableTargetHeatingCoolingStateID);
          $this->SetBuffer($BufferNameCurrentTemperature,$VariableCurrentTemperatureID);
          $this->SetBuffer($BufferNameTargetTemperature,$VariableNameTargetTemperatureID);
        }
        else {
          return;
        }
      }
    }
  public function Destroy() {
  }

  public function MessageSink($TimeStamp, $SenderID, $Message, $Data) {
    if ($Data[1] == true) {
      $anzahl = $this->ReadPropertyInteger("Anzahl");

      for($count = 1; $count-1 < $anzahl; $count++) {
        $DeviceNameCount = "DeviceName{$count}";
        $CurrentHeatingCoolingStateCount = "CurrentHeatingCoolingState{$count}";
        $TargetHeatingCoolingStateCount = "TargetHeatingCoolingState{$count}";
        $CurrentTemperatureCount = "CurrentTemperature{$count}";
        $TargetTemperatureCount = "TargetTemperature{$count}";

        $VariableCurrentHeatingCoolingStateID = $this->ReadPropertyInteger($CurrentHeatingCoolingStateCount);
        $VariableTargetHeatingCoolingStateID = $this->ReadPropertyInteger($TargetHeatingCoolingStateCount);
        $VariableCurrentTemperatureID = $this->ReadPropertyInteger($CurrentTemperatureCount);
        $VariableTargetTemperatureID = $this->ReadPropertyInteger($TargetTemperatureCount);


        $CurrentHeatingCoolingOffCount = "CurrentHeatingCoolingOff{$count}";
        $CurrentHeatingCoolingHeatingCount ="CurrentHeatingCoolingHeating{$count}";
        $CurrentHeatingCoolingCoolingCount ="CurrentHeatingCoolingCooling{$count}";

        $TargetHeatingCoolingOffCount = "TargetHeatingCoolingOff{$count}";
        $TargetHeatingCoolingHeatingCount = "TargetHeatingCoolingHeating{$count}";
        $TargetHeatingCoolingCoolingCount = "TargetHeatingCoolingCooling{$count}";
        $TargetHeatingCoolingAutoCount = "TargetHeatingCoolingAuto{$count}";

        $DeviceName = $this->ReadPropertyString($DeviceNameCount);
        $data = $Data[0];
        //Prüfen ob die SenderID gleich der Temperatur Variable ist, dann den aktuellen Wert an die Bridge senden
        switch ($SenderID) {
          case $VariableCurrentHeatingCoolingStateID:
            $CurrentHeatingCoolingOff = $this->ReadPropertyInteger($CurrentHeatingCoolingOffCount);
            $CurrentHeatingCoolingHeating = $this->ReadPropertyInteger($CurrentHeatingCoolingHeatingCount);
            $CurrentHeatingCoolingCooling = $this->ReadPropertyInteger($CurrentHeatingCoolingCoolingCount);
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
          case $VariableTargetHeatingCoolingStateID:
            $Characteristic = "TargetHeatingCoolingState";
            $result = $data;

            $VariableTargetHeatingCoolingStateID = $this->ReadPropertyInteger($TargetHeatingCoolingStateCount);
            $TargetHeatingCoolingOff = $this->ReadPropertyInteger($TargetHeatingCoolingOffCount);
            $TargetHeatingCoolingHeating = $this->ReadPropertyInteger($TargetHeatingCoolingHeatingCount);
            $TargetHeatingCoolingCooling = $this->ReadPropertyInteger($TargetHeatingCoolingCoolingCount);
            $TargetHeatingCoolingAuto = $this->ReadPropertyInteger($TargetHeatingCoolingAutoCount);
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
          case $VariableCurrentTemperatureID:
            $Characteristic = "CurrentTemperature";
            $result = number_format($data, 2, '.', '');
            break;
          case $VariableTargetTemperatureID:
            $Characteristic = "TargetTemperature";
            $result = number_format($data, 2, '.', '');
            break;
        }
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


  public function ReceiveData($JSONString) {
    $this->SendDebug('ReceiveData',$JSONString, 0);
    $data = json_decode($JSONString);
    // Buffer decodieren und in eine Variable schreiben
    $Buffer = utf8_decode($data->Buffer);
    // Und Diese dann wieder dekodieren
    $HomebridgeData = json_decode($Buffer);
    //Prüfen ob die ankommenden Daten für den Switch sind wenn ja, Status abfragen oder setzen
    if ($HomebridgeData->Action == "get" && $HomebridgeData->Service == "Thermostat") {
      $this->getVar($HomebridgeData->Device, $HomebridgeData->Characteristic);
    }
    if ($HomebridgeData->Action == "set" && $HomebridgeData->Service == "Thermostat") {
      $this->setVar($HomebridgeData->Device, $HomebridgeData->Value, $HomebridgeData->Characteristic);
    }
  }


  public function getVar($DeviceName, $Characteristic) {
    $anzahl = $this->ReadPropertyInteger("Anzahl");

    for($count = 1; $count -1 < $anzahl; $count++) {

      //Hochzählen der Konfirgurationsform Variablen
      $DeviceNameCount = "DeviceName{$count}";
      $ThermostatID = "ThermostatID{$count}";

      $CurrentHeatingCoolingStateCount = "CurrentHeatingCoolingState{$count}";
      $TargetHeatingCoolingStateCount = "TargetHeatingCoolingState{$count}";
      $CurrentTemperatureCount = "CurrentTemperature{$count}";
      $TargetTemperatureCount = "TargetTemperature{$count}";

      $CurrentHeatingCoolingOffCount = "CurrentHeatingCoolingOff{$count}";
      $CurrentHeatingCoolingHeatingCount ="CurrentHeatingCoolingHeating{$count}";
      $CurrentHeatingCoolingCoolingCount ="CurrentHeatingCoolingCooling{$count}";

      $TargetHeatingCoolingOffCount = "TargetHeatingCoolingOff{$count}";
      $TargetHeatingCoolingHeatingCount = "TargetHeatingCoolingHeating{$count}";
      $TargetHeatingCoolingCoolingCount = "TargetHeatingCoolingCooling{$count}";
      $TargetHeatingCoolingAutoCount = "TargetHeatingCoolingAuto{$count}";

      $name = $this->ReadPropertyString($DeviceNameCount);
      //Prüfen ob der übergebene Name zu einem Namen aus der Konfirgurationsform passt wenn ja Wert an die Bridge senden
      if ($DeviceName == $name) {

        switch ($Characteristic) {
          case 'CurrentHeatingCoolingState':
            $VariableCurrentHeatingCoolingStateID = $this->ReadPropertyInteger($CurrentHeatingCoolingStateCount);
            $CurrentHeatingCoolingOff = $this->ReadPropertyInteger($CurrentHeatingCoolingOffCount);
            $CurrentHeatingCoolingHeating = $this->ReadPropertyInteger($CurrentHeatingCoolingHeatingCount);
            $CurrentHeatingCoolingCooling = $this->ReadPropertyInteger($CurrentHeatingCoolingCoolingCount);

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
            $VariableTargetHeatingCoolingStateID = $this->ReadPropertyInteger($TargetHeatingCoolingStateCount);
            $TargetHeatingCoolingOff = $this->ReadPropertyInteger($TargetHeatingCoolingOffCount);
            $TargetHeatingCoolingHeating = $this->ReadPropertyInteger($TargetHeatingCoolingHeatingCount);
            $TargetHeatingCoolingCooling = $this->ReadPropertyInteger($TargetHeatingCoolingCoolingCount);
            $TargetHeatingCoolingAuto = $this->ReadPropertyInteger($TargetHeatingCoolingAutoCount);

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
            $VariableCurrentTemperatureID = $this->ReadPropertyInteger($CurrentTemperatureCount);
            $result = GetValue($VariableCurrentTemperatureID);
            $result = number_format($result, 2, '.', '');
            break;
          case 'TargetTemperature':
            $VariableTargetTemperatureID = $this->ReadPropertyInteger($TargetTemperatureCount);
            $result = GetValue($VariableTargetTemperatureID);
            $result = number_format($result, 2, '.', '');
            break;
        }
        $JSON['DataID'] = "{018EF6B5-AB94-40C6-AA53-46943E824ACF}";
        $JSON['Buffer'] = utf8_encode('{"topic": "callback", "Characteristic": "'.$Characteristic.'", "Device": "'.$DeviceName.'", "value": "'.$result.'"}');
        $Data = json_encode($JSON);
        $this->SendDataToParent($Data);
        return;
      }
    }
  }

  public function setVar($DeviceName, $value, $Characteristic) {
    for($count = 1; $count -1 < $this->ReadPropertyInteger("Anzahl"); $count++) {
      $DeviceNameCount = "DeviceName{$count}";
      $ThermostatID = "ThermostatID{$count}";

      $CurrentHeatingCoolingStateCount = "CurrentHeatingCoolingState{$count}";
      $TargetHeatingCoolingStateCount = "TargetHeatingCoolingState{$count}";
      $CurrentTemperatureCount = "CurrentTemperature{$count}";
      $TargetTemperatureCount = "TargetTemperature{$count}";

      $CurrentHeatingCoolingOffCount = "CurrentHeatingCoolingOff{$count}";
      $CurrentHeatingCoolingHeatingCount ="CurrentHeatingCoolingHeating{$count}";
      $CurrentHeatingCoolingCoolingCount ="CurrentHeatingCoolingCooling{$count}";

      $TargetHeatingCoolingOffCount = "TargetHeatingCoolingOff{$count}";
      $TargetHeatingCoolingHeatingCount = "TargetHeatingCoolingHeating{$count}";
      $TargetHeatingCoolingCoolingCount = "TargetHeatingCoolingCooling{$count}";
      $TargetHeatingCoolingAutoCount = "TargetHeatingCoolingAuto{$count}";

      //Prüfen ob der übergebene Name zu einem Namen aus der Konfirgurationsform passt
      $name = $this->ReadPropertyString($DeviceNameCount);


    }

  }

  private function addAccessory($DeviceName) {
    //$array['topic'] = "add";
    //$array['Buffer'] = utf8_encode('"name": "'.$DeviceName.'", "service": "TemperatureSensor","CurrentTemperature": {"minValue": -100, "maxValue": 100, "minStep": 0.1}}');

//    $CurrentTemperature["minValue"] = -100;
//    $CurrentTemperature["maxValue"] = 100;
//    $CurrentTemperature["minStep"] = 0.1;
    //Payload bauen
    $payload["name"] = $DeviceName;
    $payload["service"] = "Thermostat";
    $payload["CurrentTemperature"] = "deafult";

    $array["topic"] ="add";
    $array["payload"] = $payload;
    $data = json_encode($array);
    $SendData = json_encode(Array("DataID" => "{018EF6B5-AB94-40C6-AA53-46943E824ACF}", "Buffer" => $data));
    @$this->SendDataToParent($SendData);
  }

  public function removeAccessory($DeviceCount) {
    //Payload bauen
    $DeviceName = $this->ReadPropertyString("DeviceName{$DeviceCount}");
    $payload["name"] = $DeviceName;

    $array["topic"] ="remove";
    $array["payload"] = $payload;
    $data = json_encode($array);
    $SendData = json_encode(Array("DataID" => "{018EF6B5-AB94-40C6-AA53-46943E824ACF}", "Buffer" => $data));
    $this->SendDebug('Remove',$SendData,0);
    $this->SendDataToParent($SendData);
    return "Gelöscht!";
  }
}
?>
