# Free@Home Modul für IP-Symcon

**Autor**: Bruno Marx  
**Version**: 1.0


## Beschreibung

Dieses Modul ermöglicht die Integration von Busch-Jaeger free@home in IP-Symcon. Es bietet eine automatische Gerätekonfiguration, Szenensteuerung und Unterstützung für Philips Hue-Komponenten über den SysAP.


## Funktionen

- Gruppierter Konfigurator für:
  - free@home Geräte
  - free@home Rauchmelder
  - Hue-Komponenten 
- Automatische Variablenanlage mit passenden Profilen
- Echtzeit-Aktualisierung über WebSocket
- Synchronisation mit SysAP 
  - verwaiste Instanzen werden markiert
  - Umbenennungen werden nachgezogen

## Installation

1. Repository in IP-Symcon hinzufügen
2. SysAP mit Discovery Konfigurator suchen und Bridge & Konfigurator erstellen
2. Bridge Parameter ausfüllen
3. Im Konfigurator Instanzen gruppiert anlegen

## Voraussetzungen

- IP-Symcon ab Version 6.0
- Lokaler Zugriff auf den free@home SysAP
- Benutzername und Passwort für SysAP

## Verzeichnisstruktur

- `FreeAtHomeDiscovery/` – SysAP Discovery Konfigurator sucht im gleichen Netzt nach SysAP
- `FreeAtHomeConfigurator/` – Geräte Konfigurator
- `FreeAtHomeDevice/` – Gerät mit REST/WebSocket
- `FreeAtHomeBridge/` – Zentrale Kommunikationsinstanz

## Lizenz

Copyright © 2026 Bruno Marx
