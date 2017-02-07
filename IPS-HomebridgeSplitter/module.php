<?
class IPS_HomebridgeSplitter extends IPSModule {
  public function Create() {
      //Never delete this line!
      parent::Create();
      $this->ConnectParent("{3AB77A94-3467-4E66-8A73-840B4AD89582}");
      //Verbinde mit WebSocket Splitter
  }
  public function ApplyChanges() {
      //Never delete this line!
      parent::ApplyChanges();
  }
  public function Destroy() {
  }
  public function ReceiveData($JSONString) {
    $this->SendDebug("JSON", $JSONString,0);
    $data = json_decode($JSONString);
    // Buffer decodieren und in eine Variable schreiben
    $Buffer = utf8_decode($data->Buffer);
    // Und Diese dann wieder dekodieren
    $HomebridgeData = json_decode($Buffer);
    $this->SendDebug('ReceiveData JSON',$JSONString ,0);
    //JSON Daten
    if ($HomebridgeData->topic != "response") {
      $Service = $HomebridgeData->service;
      $DeviceName = $HomebridgeData->payload->name;
      $Characteristic = $HomebridgeData->payload->characteristic;
      switch ($HomebridgeData->topic) {
        case 'get':
          $this->SendDebug('ReceiveData get',"Service: ".$Service." DeviceName: ".$DeviceName." Characteristic: ".$Characteristic, 0);
          $this->getValue($Service, $DeviceName, $Characteristic);
          break;
        break;
        case 'set':
        //JSON Daten
        $value = $HomebridgeData->payload->value;
          $this->SendDebug('ReceiveData set',"Service: ".$Service." DeviceName: ".$DeviceName." Characteristic: ".$Characteristic." Value: ".$value, 0);
          $this->setValue($Service, $DeviceName, $Characteristic, $value);
        break;
      }
    }
  }
  public function ForwardData($JSONString) {
    $data = json_decode($JSONString);
    // Buffer decodieren und in eine Variable schreiben
    $Buffer = utf8_decode($data->Buffer);
    // Und Diese dann wieder dekodieren
    $HomebridgeData = json_decode($Buffer);
    $this->SendDebug('ForwardData JSON',$JSONString ,0);
    switch ($HomebridgeData->topic) {
      case 'setValue':
          $data =utf8_encode('{"topic": "setValue", "payload": {"name": "'.$HomebridgeData->Device.'", "characteristic": "'.$HomebridgeData->Characteristic.'", "value": '.$HomebridgeData->value.'}}');
          $this->SendDebug('setValue',$data ,0);
          $this->SendDataToParent(json_encode(Array("DataID" => "{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}", "Buffer" => $data)));
      break;
      case 'callback':
          $data =utf8_encode('{"topic": "callback", "payload": {"name": "'.$HomebridgeData->Device.'", "characteristic": "'.$HomebridgeData->Characteristic.'", "value": '.$HomebridgeData->value.'}}');
          IPS_LogMessage("Splitter ForwardData", $HomebridgeData->topic);
          $this->SendDebug('callback',$data ,0);
          $this->SendDataToParent(json_encode(Array("DataID" => "{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}", "Buffer" => $data)));
      break;
      case 'add':
          $array["topic"] ="add";
          $array["payload"] = $HomebridgeData;
          array(
            "name" => utf8_encode($HomebridgeData->name),
            "service" => utf8_encode($HomebridgeData->service)
          );
          $data = (json_encode($array));
          $SendData = json_encode(Array("DataID" => "{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}", "Buffer" => $data));
          $this->SendDebug('Add',$SendData,0);
          $this->SendDataToParent($SendData);
      break;
    }
  }
  protected function getValue($service, $DeviceName, $Characteristic) {
    //Servcie -> um herauszufinden welche Instanz dafür zuständig ist
    //Device -> der Name des Devices
    //Action -> damit die Child Instanz weiß, welche Funktion sie ausführen soll
    //$Characteristic -> damit die Child Instanz weiß, welche Variable abgefragt wird
    $JSON['DataID'] = "{018EF6B5-AB94-40C6-AA53-46943E824ACF}";
    $JSON['Buffer'] = utf8_encode('{"Service": "'.$service.'", "Device": "'.$DeviceName.'", "Action": "get", "Characteristic": "'.$Characteristic.'"}');
    $Data = json_encode($JSON);
    $this->SendDebug('getValue SendDataToChildren',$Data, 0);
    $this->SendDataToChildren($Data);
    //$this->SendDataToChildren(json_encode(Array("DataID" => "{018EF6B5-AB94-40C6-AA53-46943E824ACF}", "Service"=> $service, "Device" => $DeviceName, "Action" => "get", "Characteristic" => $Characteristic)));
  }
  protected function setValue($service, $DeviceName, $Characteristic, $value) {
    //Servcie -> um herauszufinden welche Instanz dafür zuständig ist
    //Device -> der Name des Devices
    //Action -> damit die Child Instanz weiß, welche Funktion sie ausführen soll
    //$Characteristic -> damit die Child Instanz weiß, welche Variable gesetzt werden soll
    //Value -> der Wert, der gesetzt werden soll
    $JSON['DataID'] = "{018EF6B5-AB94-40C6-AA53-46943E824ACF}";
    $JSON['Buffer'] = utf8_encode('{"Service": "'.$service.'", "Device": "'.$DeviceName.'", "Action": "set", "Characteristic": "'.$Characteristic.'", "Value": "'.$value.'"}');
    $Data = json_encode($JSON);
    $this->SendDebug('setValue SendDataToChildren',$Data,0);
    $this->SendDataToChildren($Data);
    }
}
?>
