<?
class IPS_HomebridgeThermostat extends IPSModule {
  public function Create() {
      //Never delete this line!
      parent::Create();
      $this->ConnectParent("{86C2DE8C-FB21-44B3-937A-9B09BB66FB76}");
        $this->RegisterPropertyInteger("Anzahl",1);

      for($count = 1; $count -1 < 99; $count++) {
        $ThermostatID = "ThermostatID{$count}";
        $CurrentHeatingCoolingState = "CurrentHeatingCoolingState{$count}";
        $TargetHeatingCoolingState = "TargetHeatingCoolingState{$count}";
        $CurrentTemperature = "CurrentTemperature{$count}";
        $TargetTemperature = "TargetTemperature{$count}";
        $this->RegisterPropertyInteger($ThermostatID, 0);
        $this->RegisterPropertyInteger($CurrentHeatingCoolingState, 0);
        $this->RegisterPropertyInteger($TargetHeatingCoolingState, 0);
        $this->RegisterPropertyInteger($CurrentTemperature, 0);
        $this->RegisterPropertyInteger($TargetTemperature, 0);
      }
  }
  public function ApplyChanges() {
      //Never delete this line!
      parent::ApplyChanges();
    }

  public function Destroy() {
  }

  public function GetConfigurationForm() {
    $anzahl = $this->ReadPropertyInteger("Anzahl");
    $form = '{"elements":
              [
                { "type": "NumberSpinner", "name": "Anzahl", "caption": "Anzahl" },';
    // Zählen wieviele Felder in der Form angelegt werden müssen
    for($count = 1; $count-1 < $anzahl; $count++) {
      $form .= '{ "type": "SelectInstance", "name": "ThermostatID'.$count.'", "caption": "Gerät" },';
      $form .= '{ "type": "SelectVariable", "name": "CurrentHeatingCoolingState'.$count.'", "caption": "CurrentHeatingCoolingState" },';
      $form .= '{ "type": "SelectVariable", "name": "TargetHeatingCoolingState'.$count.'", "caption": "TargetHeatingCoolingState" },';
      $form .= '{ "type": "SelectVariable", "name": "CurrentTemperature'.$count.'", "caption": "CurrentTemperature" },';
      $form .= '{ "type": "SelectVariable", "name": "TargetTemperature'.$count.'", "caption": "TargetTemperature" },';
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
      if ($data->Action == "get" && $data->Service == "Thermostat") {
        $this->getVar($data->Device, $data->Variable);
      }
      if ($data->Action == "set" && $data->Service == "Thermostat") {
        $this->setVar($data->Device, $data->Value, $data->Variable);
      }
  }

  public function getVar($name, $variable) {
    for($count = 1; $count -1 < $this->ReadPropertyInteger("Anzahl"); $count++) {
      //Hochzählen der Konfirgurationsform Variablen
      $ThermostatID = "ThermostatID{$count}";
      $CurrentHeatingCoolingState = "CurrentHeatingCoolingState{$count}";
      $TargetHeatingCoolingState = "TargetHeatingCoolingState{$count}";
      $CurrentTemperature = "CurrentTemperature{$count}";
      $TargetTemperature = "TargetTemperature{$count}";
      //Prüfen ob der übergebene Name aus dem Hook zu einem Namen aus der Konfirgurationsform passt
      if ($name == IPS_GetObject($this->ReadPropertyInteger("ThermostatID{$count}"))["ObjectName"]) {
        //IPS Variable abfragen
        switch ($variable) {
          case 'CurrentHeatingCoolingState':
            //CurrentHeatingCoolingState abfragen
            $result = intval(GetValue($this->ReadPropertyInteger($CurrentHeatingCoolingState)));
            IPS_LogMessage("State Lightbulb Var",$result);
            break;
          case 'TargetHeatingCoolingState':
            //TargetHeatingCoolingState abfragen
            $result = GetValue($this->ReadPropertyInteger($TargetHeatingCoolingState));
            break;
          case 'CurrentTemperature':
            //CurrentTemperature abfragen
            $result = GetValue($this->ReadPropertyInteger($CurrentTemperature));
            break;
          case 'TargetTemperature':
            //TargetTemperature abfragen
            $result = GetValue($this->ReadPropertyInteger($TargetTemperature));
            break;
      }
        $this->SendDataToParent(json_encode(Array("DataID" => "{78487FC0-53EC-4C53-A472-D64772FB341D}", "Typ" => $variable, "Device" => $name, "Result" => $result)));
        return;
      }
    }
  }

  public function setVar($name, $value, $variable) {
    for($count = 1; $count -1 < $this->ReadPropertyInteger("Anzahl"); $count++) {
      //Hochzählen der Konfirgurationsform Variablen
      $ThermostatID = "ThermostatID{$count}";
      $TargetHeatingCoolingState = "TargetHeatingCoolingState{$count}";
      $TargetTemperature = "TargetTemperature{$count}";
      //Prüfen ob der übergebene Name aus dem Hook zu einem Namen aus der Konfirgurationsform passt
      if ($name == IPS_GetObject($this->ReadPropertyInteger("ThermostatID{$count}"))["ObjectName"]) {
        switch ($variable) {
          case 'TargetHeatingCoolingState':
            //TargetHeatingCoolingState abfragen
            $variable = IPS_GetVariable($this->ReadPropertyInteger("TargetHeatingCoolingState{$count}"));
            $variableObject = IPS_GetObject($this->ReadPropertyInteger("TargetHeatingCoolingState{$count}"));
            break;
          case 'TargetTemperature':
            //TargetTemperature abfragen
            $variable = IPS_GetVariable($this->ReadPropertyInteger("TargetTemperature{$count}"));
            $variableObject = IPS_GetObject($this->ReadPropertyInteger("TargetTemperature{$count}"));
            break;
      }
        //den übgergebenen Wert in den VariablenTyp für das IPS-Gerät umwandeln
        $result = $this->ConvertVariable($variable, $value);
        //Geräte Variable setzen
        IPS_RequestAction($variableObject["ParentID"], $variableObject['ObjectIdent'], $result);
      }
    }
  }

  public function ConvertVariable($variable, $value) {
      switch ($variable["VariableType"]) {
        case 0: // boolean
          return boolval($value);
        case 1: // integer
          return intval($value);
        case 2: // float
          return floatval($value);
        case 3: // string
          return strval($value);
    }
  }
}
?>
