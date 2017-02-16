<?
class HomeKitService extends IPSModule {

  public function ReceiveData($JSONString) {
    $this->SendDebug('ReceiveData',$JSONString, 0);
    $data = json_decode($JSONString);
    // Buffer decodieren und in eine Variable schreiben
    $Buffer = utf8_decode($data->Buffer);
    // Und Diese dann wieder dekodieren
    $HomebridgeData = json_decode($Buffer);

    //Prüfen ob die ankommenden Daten für den Lightbulb sind wenn ja, Status abfragen
    if ($HomebridgeData->Action == "get") {
      $this->getVar($HomebridgeData->Device, $HomebridgeData->Characteristic);
    }
    //Prüfen ob die ankommenden Daten für den Lightbulb sind wenn ja, Status setzen
    if ($HomebridgeData->Action == "set") {
      $this->setVar($HomebridgeData->Device, $HomebridgeData->Value, $HomebridgeData->Characteristic);
    }
  }

  protected function RegisterMessages($SenderIDs, $NachrichtenID) {
    foreach ($SenderIDs as $SenderID) {
      $this->RegisterMessage(intval($SenderID), $NachrichtenID);
    }
  }

  protected function UnregisterMessages($SenderIDs, $NachrichtenID) {
    foreach ($SenderIDs as $SenderID) {
      $this->UnregisterMessage(intval($SenderID), $NachrichtenID);
    }
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

    protected function ConvertVariable($variable, $value) {
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

  protected function sendJSONToParent($topic,$Characteristic,$DeviceName,$value) {
    $JSON['DataID'] = "{018EF6B5-AB94-40C6-AA53-46943E824ACF}";

    $array["topic"] = $topic;
    $array["Characteristic"] = $Characteristic;
    $array["Device"] = $DeviceName;
    $array["value"] = $value;
    $data = json_encode($array);
    $SendData = json_encode(Array("DataID" => "{018EF6B5-AB94-40C6-AA53-46943E824ACF}", "Buffer" => $data));
    $this->SendDataToParent($SendData);
  }

  protected function SetValueToIPS($variable,$variableObject,$result) {
    if ($variable["VariableAction"] > 0) {
      IPS_RequestAction($variable["VariableAction"], $variableObject['ObjectIdent'], $result);
    } else {
      SetValue($variable["VariableID"],$result);
    }
  }
}
?>
