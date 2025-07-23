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
   **FAHDEV_SetBrightness($InstanceID, int $Value)**\
   Mit dieser Funktion ist es möglich den aktuellen Dimmwert des Aktors zu setzen. Der Wertebereich liegt zwischen 0 und 100
   ```php
   FAHDEV_SetBrightness(12345,50); // Gibt true == Erfolg, false == Fehler, Funktion nicht verfügbar
   ```
  **FAHDEV_SetPosition($InstanceID, int $Value)**\
   Mit dieser Funktion ist es möglich die aktue Position des Rolladen Aktors zu setzen. Der Wertebereich liegt zwischen 0 und 100
   ```php
   FAHDEV_SetPosition(12345,50); // Gibt true == Erfolg, false == Fehler, Funktion nicht verfügbar
   ```

   