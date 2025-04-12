<?php

class FreeAtHomeConfigurator extends IPSModule {

    private $devices = [];

    public function Create() {
        parent::Create();
        $this->RegisterPropertyString("Devices", "[]");
        $this->RegisterAttributeString("Devices", "[]");

        $this->RegisterPropertyInteger("DeviceCategory", 0);
        $this->RegisterPropertyInteger("SceneCategory", 0);
        $this->RegisterPropertyInteger("HueCategory", 0);
    }

    public function ApplyChanges() {
        parent::ApplyChanges();
        $this->devices = json_decode($this->ReadAttributeString("Devices"), true);
    }

    public function SetDevices(string $json) {
        $this->devices = json_decode($json, true);
        $this->WriteAttributeString("Devices", $json);
        $this->ReloadForm();
    }

    public function GetConfigurationForm() {
        $form = [
            "elements" => [
                ["type" => "SelectLocation", "name" => "DeviceCategory", "caption" => "Kategorie für Geräte"],
                ["type" => "SelectLocation", "name" => "SceneCategory", "caption" => "Kategorie für Szenen"],
                ["type" => "SelectLocation", "name" => "HueCategory", "caption" => "Kategorie für Hue-Komponenten"]
            ],
            "actions" => [],
        ];

        $availableDevices = $this->devices;
        $existingInstances = IPS_GetInstanceList();

        $deviceList = [];
        $sceneList = [];
        $hueList = [];

        $trackedIds = [];

        foreach ($existingInstances as $instID) {
            $inst = IPS_GetInstance($instID);
            $obj = IPS_GetObject($instID);
            if (!isset($inst['ModuleInfo']['ModuleID'])) continue;

            $mid = $inst['ModuleInfo']['ModuleID'];
            if (!in_array($mid, [
                "{C3D7FEE8-9C3F-4B88-9150-000000000003}",
                "{D4D8FEE8-9C3F-4B88-9150-000000000004}",
                "{E5D9FEE8-9C3F-4B88-9150-000000000005}"
            ])) continue;

            $deviceID = @IPS_GetProperty($instID, "DeviceID");
            if (!$deviceID) continue;

            $name = $obj['ObjectName'];
            $entry = [
                "id" => $deviceID,
                "name" => $name,
                "deviceId" => $deviceID,
                "instanceID" => $instID,
                "status" => "OK"
            ];
            $trackedIds[$deviceID] = true;

            if ($mid === "{D4D8FEE8-9C3F-4B88-9150-000000000004}") {
                $sceneList[] = $entry;
            } elseif ($mid === "{E5D9FEE8-9C3F-4B88-9150-000000000005}") {
                $hueList[] = $entry;
            } else {
                $deviceList[] = $entry;
            }
        }

        foreach ($availableDevices as $device) {
            $id = $device["id"];
            $name = $device["name"];

            if (isset($trackedIds[$id])) continue;

            $type = "device";
            if (stripos($id, "scene") !== false || stripos($name, "scene") !== false) {
                $type = "scene";
            } elseif (stripos($name, "hue") !== false || stripos($name, "philips") !== false) {
                $type = "hue";
            }

            $entry = [
                "id" => $id,
                "name" => $name,
                "deviceId" => $id,
                "instanceID" => 0,
                "status" => "Bereit zur Anlage"
            ];

            switch ($type) {
                case "scene":
                    $entry["create"] = [
                        "moduleID" => "{D4D8FEE8-9C3F-4B88-9150-000000000004}",
                        "configuration" => ["DeviceID" => $id],
                        "location" => "SceneCategory"
                    ];
                    $sceneList[] = $entry;
                    break;
                case "hue":
                    $entry["create"] = [
                        "moduleID" => "{E5D9FEE8-9C3F-4B88-9150-000000000005}",
                        "configuration" => ["DeviceID" => $id],
                        "location" => "HueCategory"
                    ];
                    $hueList[] = $entry;
                    break;
                default:
                    $entry["create"] = [
                        "moduleID" => "{C3D7FEE8-9C3F-4B88-9150-000000000003}",
                        "configuration" => ["DeviceID" => $id],
                        "location" => "DeviceCategory"
                    ];
                    $deviceList[] = $entry;
                    break;
            }
        }

        // Add "verwaiste" mark if instance exists but not in discovery
        foreach ($deviceList as &$entry) {
            if (!in_array($entry['deviceId'], array_column($this->devices, 'id'))) {
                $entry['status'] = "Nicht mehr im SysAP gefunden";
            }
        }
        foreach ($sceneList as &$entry) {
            if (!in_array($entry['deviceId'], array_column($this->devices, 'id'))) {
                $entry['status'] = "Nicht mehr im SysAP gefunden";
            }
        }
        foreach ($hueList as &$entry) {
            if (!in_array($entry['deviceId'], array_column($this->devices, 'id'))) {
                $entry['status'] = "Nicht mehr im SysAP gefunden";
            }
        }

        // Build panels
        foreach ([
            ["caption" => "Szenen", "values" => $sceneList],
            ["caption" => "Hue-Geräte", "values" => $hueList],
            ["caption" => "Standardgeräte", "values" => $deviceList]
        ] as $group) {
            if (!empty($group["values"])) {
                $form["actions"][] = [
                    "type" => "ExpansionPanel",
                    "caption" => $group["caption"],
                    "items" => [[
                        "type" => "List",
                        "columns" => [
                            ["caption" => "Name", "name" => "name", "width" => "300px"],
                            ["caption" => "Geräte-ID", "name" => "deviceId", "width" => "auto"],
                            ["caption" => "Instanz", "name" => "instanceID", "width" => "100px", "add" => ""],
                            ["caption" => "Status", "name" => "status", "width" => "200px"]
                        ],
                        "values" => $group["values"]
                    ]]
                ];
            }
        }

        return json_encode($form);
    }
}
