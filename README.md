# IPS-Homebridge
Mit dem Modul ist es möglich über die Homebridge IP-Symcon mit dem Apple HomeKit zu verknüpfen und somit IP-Symcon über Siri zu steuern.

Beispiel: "Siri, schalte das Deckenlicht im Wohnzimmer ein!"

## Inhaltverzeichnis
1. [Voraussetzungen](#1-voraussetzungen)
2. [IP-Symcon Voraussetzungen](#2-ip-symcon-voraussetzungen)
3. [Installation](#3-installation)
4. [Konfiguration der Homebridge](#4-konfiguration-der-homebridge)
5. [Einrichtung des eigentlichen IPS Homebridge Moduls](#5-einrichtung-des-eigentlichen-ips-homebridge-moduls)
6. [WebFront](#6-webfront)
7. [PHP-Befehlsreferenz](#7-php-befehlsreferenz)

## 1. Voraussetzungen

Dieses Modul basiert auf der [Homebridge von nfarina](https://github.com/nfarina/homebridge) und dem [Homebridge Plugin von cflurin](https://github.com/cflurin/homebridge-websocket).
Damit das Homebridge Modul von cflurin mit meinem PHP Modul für IP-Symcon arbeiten kann, musste ich ein paar kleine Änderungen vornehmen.
Deshalb gibt es einen Fork für dieses Plugin und die Änderungen können an dieser Stelle eingesehen werden: [Commits homebridge-websocket GitHub](https://github.com/Schnittcher/homebridge-websocket/commits/master)

## 2. IP-Symcon Voraussetzungen

* Mindestens die IPS Version 4.1
* [Websocket Modul von Nall Chan](https://github.com/Nall-chan/IPSWebSockets)

## 3. Installation

### 3.1 Installation für IP-Symcon

Websocket Modul von Nall Chan:
```
https://github.com/Nall-chan/IPSWebSockets.git
```
IPS-Homebridge Modul:
```
https://github.com/Schnittcher/IPS-Homebridge.git
```

### 3.2 Installation der Homebridge inkl. Plugin

```
sudo npm install -g homebridge
```

Plugin Installation für die Homebridge:
```
sudo npm install -g https://github.com/Schnittcher/homebridge-websocket.git
```

## 4. Konfiguration der Homebridge

Folgende Zeilen in der config.json einfügen unter platform hinzufügen:Folgende Zeilen in der config.json einfügen unter platform hinzufügen:

```{
  "platform" : "websocket",
  "name" : "websocket",
  "port": 4050
}
```
## 5. Einrichtung des eigentlichen IPS Homebridge Moduls

Anlegen einer neuen Instanz zum Beispiel IPS_HomebridgeLightbulb.
Es wird ein IPs_HomebridgeSplitter und ein WebsocketClient mitangelegt, die Konfigruation bitte den Screenshots entnehmen:

![Instanzen](https://www.symcon.de/forum/attachment.php?attachmentid=37694&d=1486493188)
![Homebridge Splitter](https://www.symcon.de/forum/attachment.php?attachmentid=37695&d=1486493197)
![Websocket Client](https://www.symcon.de/forum/attachment.php?attachmentid=37696&d=1486493206)

Die Einrichtung der verschiedenen Homebridge Modulen wird in der nachstehenden Dokumentation erklärt.
