# free@home device
   Dieses Modul bildet die verschiedenen free@home Geräte in IP-Symcon ab.
     
   ## Inhaltverzeichnis
   1. [Funktionen](#1-funktionen)
   

  ## 1. Funktionen

   **FAHDEV_SetState($InstanceID, bool $Value)**\
   Mit dieser Funktion ist es möglich den aktuellen Zustand des Aktors zu setzen.
   ```php
   FAHDEV_SetState(12345,true); // Gibt true == Erfolg, false == Fehler, Funktion nicht verfügbar
   ```

   **FAHDEV_GetState($InstanceID)**\
   Mit dieser Funktion ist es möglich den aktuellen Zustand des Aktors zu ermitteln.
   ```php
   FAHDEV_GetState(12345); // Gibt den aktuellen Zustand des Aktors zurück
   ```

   **FAHDEV_SetBrightness($InstanceID, int $Value)**\
   Mit dieser Funktion ist es möglich den aktuellen Dimmwert des Aktors zu setzen. Der Wertebereich liegt zwischen 0 und 100
   ```php
   FAHDEV_SetBrightness(12345,50); // Gibt true == Erfolg, false == Fehler, Funktion nicht verfügbar
   ```

   **FAHDEV_SetPosition($InstanceID, int $Value)**\
   Mit dieser Funktion ist es möglich die aktuelle Position des Rolladen Aktors zu setzen. Der Wertebereich liegt zwischen 0 und 100
   ```php
   FAHDEV_SetPosition(12345,50); // Gibt true == Erfolg, false == Fehler, Funktion nicht verfügbar
   ```

   **FAHDEV_GetPosition($InstanceID)**\
   Mit dieser Funktion ist es möglich die aktuelle Position des Rolladen Aktors zu ermitteln. Der Wertebereich liegt zwischen 0 und 100
   ```php
   FAHDEV_GetPosition(12345); // Gibt gibt die aktuelle Rolladenposition zurück
   ```

   **FAHDEV_SetUp($InstanceID)**\
   Mit dieser Funktion ist es möglich das device nach oben zu fahren
   ```php
   FAHDEV_SetPUp(12345); // Gibt true == Erfolg, false == Fehler, Funktion nicht verfügbar
   ```

   **FAHDEV_SetDown($InstanceID)**\
   Mit dieser Funktion ist es möglich das device nach unten zu fahren
   ```php
   FAHDEV_SetDown(12345); // Gibt true == Erfolg, false == Fehler, Funktion nicht verfügbar
   ```

   **FAHDEV_SetStop($InstanceID)**\
   Mit dieser Funktion ist es möglich das device zu stoppen
   ```php
   FAHDEV_SetStop(12345); // Gibt true == Erfolg, false == Fehler, Funktion nicht verfügbar
   ```

   **FAHDEV_SetSensorLock($InstanceID, bool $Value)**\
   Mit dieser Funktion ist es möglich die Sensor Verriegelung zu setzen
   ```php
   FAHDEV_SetSensorLock(12345,true); // Gibt true == Erfolg, false == Fehler, Funktion nicht verfügbar
   ```

   **FAHDEV_GetSensorLock($InstanceID)**\
   Mit dieser Funktion ist es möglich den aktuellen Verriegelungsstatus des Sensors zu ermitteln
   ```php
   FAHDEV_GetSensorLock(12345); // Gibt gibt den aktuellen Verriegelungsstatus des Sensors zurück
   ```



   