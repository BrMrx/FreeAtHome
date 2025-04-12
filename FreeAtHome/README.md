# Free@Home Modul für IP-Symcon

**Autor**: Bruno Marx  
**Version**: 1.0

## Beschreibung

Dieses Modul ermöglicht die Integration von Busch-Jaeger free@home in IP-Symcon. Es bietet eine Discovery-Funktion, automatische Gerätekonfiguration, Szenensteuerung und Unterstützung für Philips Hue-Komponenten über den SysAP.

Dieses Modul wurde mit Unterstützung von **ChatGPT** entwickelt.

## Funktionen

- Geräte-Discovery per REST API
- Gruppierter Konfigurator für:
  - free@home Geräte
  - Szenen
  - Hue-Komponenten
- Automatische Variablenanlage mit passenden Profilen
- Echtzeit-Aktualisierung über WebSocket
- Synchronisation mit SysAP (verwaiste Instanzen werden markiert)
- Unterstützung mehrerer Instanztypen
- Unterstützung von Szenentriggern

## Installation

1. Repository in IP-Symcon hinzufügen
2. Discovery-Instanz erstellen
3. Geräte über Discovery suchen
4. Im Konfigurator Instanzen gruppiert anlegen

## Voraussetzungen

- IP-Symcon ab Version 6.0
- Lokaler Zugriff auf den free@home SysAP
- Benutzername und Passwort für SysAP

## Verzeichnisstruktur

- `FreeAtHomeDiscovery/` – REST-basierte Gerätesuche
- `FreeAtHomeConfigurator/` – Gruppierter Konfigurator
- `FreeAtHomeDevice/` – Standardgerät mit REST/WebSocket
- `FreeAtHomeSceneDevice/` – Szenensteuerung
- `FreeAtHomeHueDevice/` – Hue-Lichtsteuerung

## Lizenz

Copyright © 2025 Bruno Marx
