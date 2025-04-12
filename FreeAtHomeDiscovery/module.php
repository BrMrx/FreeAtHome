<?php

class FreeAtHomeDiscovery extends IPSModule {

    public function Create() {
        parent::Create();
        $this->RegisterPropertyString("Host", "");
        $this->RegisterPropertyString("Username", "");
        $this->RegisterPropertyString("Password", "");
    }

    public function ApplyChanges() {
        parent::ApplyChanges();
    }

    public function GetConfigurationForm() {
        return json_encode([
            "elements" => [
                ["type" => "ValidationTextBox", "name" => "Host", "caption" => "SysAP IP-Adresse"],
                ["type" => "ValidationTextBox", "name" => "Username", "caption" => "Benutzername"],
                ["type" => "PasswordTextBox", "name" => "Password", "caption" => "Passwort"]
            ],
            "actions" => [
                [
                    "type" => "Button",
                    "caption" => "Geräte suchen",
                    "onClick" => "FAHDISC_SearchDevices($id);"
                ]
            ]
        ]);
    }

    public function SearchDevices() {
        $host = $this->ReadPropertyString("Host");
        $username = $this->ReadPropertyString("Username");
        $password = $this->ReadPropertyString("Password");

        $url = "http://{$host}/fhapi/v1/api/rest/devices";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);

        $result = curl_exec($ch);
        curl_close($ch);

        $devices = json_decode($result, true);

        if (!$devices || !isset($devices['devices'])) {
            $this->SendDebug("REST", "Keine Geräte gefunden oder ungültiges Format", 0);
            return;
        }

        $deviceList = [];
        foreach ($devices['devices'] as $deviceId => $device) {
            $name = $device['displayName'] ?? $deviceId;
            $deviceList[] = [
                "id" => $deviceId,
                "name" => $name
            ];
        }

        // Suche Konfigurator-Instanz
        $instances = IPS_GetInstanceListByModuleID("{B2D6FEE8-9C3F-4B88-9150-000000000002}");
        foreach ($instances as $instID) {
            if (IPS_InstanceExists($instID)) {
                IPS_RequestAction($instID, "SetDevices", json_encode($deviceList));
            }
        }
    }
}
