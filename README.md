# Free@Home Modul für IP-Symcon

**Autor**: Bruno Marx  
**Version**: 0.1

## ACHTUNG 

** Der Treiber ist bereits lauffähig, befindet sich aber noch in der Entwicklung!!**

es werden bisher nur wireless Aktoren und Dimmer Aktoren unterstützt

## Beschreibung

Dieses Modul ermöglicht die Integration von Busch-Jaeger free@home in IP-Symcon. Es bietet eine automatische Gerätekonfiguration, Szenensteuerung und Unterstützung für Philips Hue-Komponenten über den SysAP.


## Funktionen

- Gruppierter Konfigurator für:
  - free@home Geräte
  - Szenen  ( in Planung)
  - Hue-Komponenten (In Planung)
- Automatische Variablenanlage mit passenden Profilen
- Echtzeit-Aktualisierung über WebSocket (in Planung - derzeit zyklisch)
- Synchronisation mit SysAP 
  - verwaiste Instanzen werden markiert
  - Umbenennungen werden nachgezogen

## Installation

1. Repository in IP-Symcon hinzufügen
2. Konfigurator-Instanz erstellen und Bridge Parameter ausfüllen
3. Im Konfigurator Instanzen gruppiert anlegen

## Voraussetzungen

- IP-Symcon ab Version 6.0
- Lokaler Zugriff auf den free@home SysAP
- Benutzername und Passwort für SysAP

## Verzeichnisstruktur

- `FreeAtHomeConfigurator/` – Gruppierter Konfigurator
- `FreeAtHomeDevice/` – Gerät mit REST/WebSocket
- `FreeAtHomeBridge/` – Zentrale Kommunikationsinstanz

## Lizenz

Copyright © 2025 Bruno Marx
