<?php

class FreeAtHomeDevice extends IPSModule {

    private $socket;

    public function Create() {
        parent::Create();
        $this->RegisterPropertyString("DeviceID", "");
        $this->RegisterPropertyString("Host", "");
        $this->RegisterPropertyString("Username", "");
        $this->RegisterPropertyString("Password", "");
        $this->RegisterAttributeString("Datapoints", "[]");
        $this->RegisterTimer("WebSocketPoll", 0, 'FAHDEV_ReceiveDataFromWebSocket($_IPS["TARGET"]);');
    }

    public function ApplyChanges() {
        parent::ApplyChanges();
        try {
            $this->SetStatus(102);
            $this->ConnectWebSocket();
            $this->SetTimerInterval("WebSocketPoll", 5000);
            $this->RequestDeviceStructure();
        } catch (Exception $e) {
            $this->SendDebug("Error", $e->getMessage(), 0);
            $this->SetStatus(201);
        }
    }

    public function RequestAction($Ident, $Value) {
        $datapoints = json_decode($this->ReadAttributeString("Datapoints"), true);
        if (isset($datapoints[$Ident])) {
            $this->SetValue($Ident, $Value);
            $this->SendCommandToDevice($datapoints[$Ident], $Value);
        }
    }

    private function SendCommandToDevice($dpInfo, $value) {
        $host = $this->ReadPropertyString("Host");
        $username = $this->ReadPropertyString("Username");
        $password = $this->ReadPropertyString("Password");
        $deviceID = $this->ReadPropertyString("DeviceID");
        $channel = $dpInfo["channel"];
        $datapoint = $dpInfo["id"];

        $url = "http://{$host}/fhapi/v1/api/rest/devices/{$deviceID}/channels/{$channel}/datapoints/{$datapoint}";
        $data = json_encode(["value" => $value]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $response = curl_exec($ch);
        curl_close($ch);

        $this->SendDebug("REST", "Response: " . $response, 0);
    }

    private function RequestDeviceStructure() {
        $host = $this->ReadPropertyString("Host");
        $username = $this->ReadPropertyString("Username");
        $password = $this->ReadPropertyString("Password");
        $deviceID = $this->ReadPropertyString("DeviceID");

        $url = "http://{$host}/fhapi/v1/api/rest/devices/{$deviceID}";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);

        $result = curl_exec($ch);
        curl_close($ch);

        $device = json_decode($result, true);
        if (!isset($device['channels'])) {
            $this->SendDebug("REST", "No channels found", 0);
            return;
        }

        IPS_SetName($this->InstanceID, $device['displayName'] ?? $deviceID);
        $this->SetIconForDeviceName(strtolower($device['displayName'] ?? ''));

        $dpMap = [];
        foreach ($device['channels'] as $channelId => $channel) {
            $channelName = $channel['displayName'] ?? $channelId;
            $channelIdent = preg_replace('/[^a-zA-Z0-9_]/', '_', $channelId);
            $pos = @IPS_GetObjectIDByIdent($channelIdent, $this->InstanceID);
            if (!$pos) {
                $pos = IPS_CreateCategory();
                IPS_SetIdent($pos, $channelIdent);
                IPS_SetParent($pos, $this->InstanceID);
            }
            IPS_SetName($pos, $channelName);

            foreach ($channel['datapoints'] ?? [] as $dpId => $dpInfo) {
                $ident = $channelId . "_" . $dpId;
                $type = $dpInfo['type'] ?? 'string';
                $value = $dpInfo['value'] ?? null;

                $name = $this->GetEnglishLabel($dpId);
                $profile = $this->GuessProfile($dpId, $type);
                $varType = $this->GetVariableTypeFromProfile($profile);

                if ($varType === 0) {
                    $this->RegisterVariableBoolean($ident, $name, $profile, 0);
                } elseif ($varType === 1) {
                    $this->RegisterVariableInteger($ident, $name, $profile, 0);
                } elseif ($varType === 2) {
                    $this->RegisterVariableFloat($ident, $name, $profile, 0);
                } else {
                    $this->RegisterVariableString($ident, $name, "", 0);
                }

                IPS_SetParent($this->GetIDForIdent($ident), $pos);

                if ($this->IsControllable($dpId)) {
                    $this->EnableAction($ident);
                }

                $dpMap[$ident] = [
                    "id" => $dpId,
                    "channel" => $channelId,
                    "type" => $type
                ];

                if ($value !== null) {
                    $this->SetValue($ident, $this->CastValue($value, $type));
                }
            }
        }

        $this->WriteAttributeString("Datapoints", json_encode($dpMap));
    }

    private function SetIconForDeviceName($name) {
        if (str_contains($name, 'light')) {
            IPS_SetIcon($this->InstanceID, "Bulb");
        } elseif (str_contains($name, 'jalousie') || str_contains($name, 'shutter')) {
            IPS_SetIcon($this->InstanceID, "Shutter");
        } elseif (str_contains($name, 'scene')) {
            IPS_SetIcon($this->InstanceID, "Scene");
        } elseif (str_contains($name, 'sensor') || str_contains($name, 'temp')) {
            IPS_SetIcon($this->InstanceID, "Temperature");
        } else {
            IPS_SetIcon($this->InstanceID, "Gear");
        }
    }

    private function GetEnglishLabel($dpId) {
        $dpId = strtolower($dpId);
        if (str_contains($dpId, 'temperature')) return "Temperature";
        if (str_contains($dpId, 'humidity')) return "Humidity";
        if (str_contains($dpId, 'brightness')) return "Brightness";
        if (str_contains($dpId, 'position')) return "Position";
        if (str_contains($dpId, 'motion')) return "Motion Detected";
        if (str_contains($dpId, 'light')) return "Light";
        if (str_contains($dpId, 'scene')) return "Scene Trigger";
        return ucfirst($dpId);
    }

    private function GuessProfile($dpId, $type) {
        $dpId = strtolower($dpId);
        if (str_contains($dpId, 'temperature') || str_contains($dpId, 'temp')) return "~Temperature";
        if (str_contains($dpId, 'brightness') || str_contains($dpId, 'dimming')) return "~Intensity.100";
        if (str_contains($dpId, 'position')) return "~ShutterPosition";
        if (str_contains($dpId, 'scene')) return "~Switch";
        if ($type === 'boolean') return "~Switch";
        if ($type === 'integer') return "~Intensity.100";
        if ($type === 'float') return "~Temperature";
        return "";
    }

    private function GetVariableTypeFromProfile($profile) {
        if ($profile === "~Switch") return 0;
        if ($profile === "~Intensity.100" || $profile === "~ShutterPosition") return 1;
        if ($profile === "~Temperature") return 2;
        return 3;
    }

    private function CastValue($value, $type) {
        switch ($type) {
            case 'boolean': return $value === "true" || $value === true;
            case 'integer': return (int)$value;
            case 'float': return (float)$value;
            case 'string': return (string)$value;
            default: return (string)$value;
        }
    }

    private function IsControllable($dpId) {
        return str_starts_with($dpId, 'odp') || str_contains(strtolower($dpId), 'scene');
    }

    private function ConnectWebSocket() {
        $host = $this->ReadPropertyString("Host");
        $username = $this->ReadPropertyString("Username");
        $password = $this->ReadPropertyString("Password");

        $url = "ws://{$host}/fhapi/v1/api/ws";

        $headers = [
            "Authorization: Basic " . base64_encode("{$username}:{$password}")
        ];

        $context = stream_context_create([
            'http' => [
                'header' => implode("\r\n", $headers)
            ]
        ]);

        $this->SendDebug("WebSocket", "Connecting to {$url}", 0);
        $this->socket = @stream_socket_client($url, $errno, $errstr, 5, STREAM_CLIENT_CONNECT, $context);

        if (!$this->socket) {
            $this->SendDebug("WebSocket", "Connection failed: $errstr ($errno)", 0);
            return;
        }

        stream_set_blocking($this->socket, false);
        $this->SendDebug("WebSocket", "Connected", 0);
    }

    public function ReceiveDataFromWebSocket() {
        if ($this->socket) {
            $response = fread($this->socket, 2048);
            if ($response) {
                $this->SendDebug("WebSocket-Message", $response, 0);
                $json = json_decode($response, true);
                if (isset($json['devices'])) {
                    $deviceID = $this->ReadPropertyString("DeviceID");
                    if (isset($json['devices'][$deviceID])) {
                        $device = $json['devices'][$deviceID];
                        if (isset($device['displayName'])) {
                            IPS_SetName($this->InstanceID, $device['displayName']);
                        }

                        foreach ($device['channels'] ?? [] as $channelId => $channel) {
                            foreach ($channel['datapoints'] ?? [] as $dpId => $dpInfo) {
                                $ident = $channelId . "_" . $dpId;
                                $value = $dpInfo['value'] ?? null;
                                $type = $dpInfo['type'] ?? 'string';
                                if ($value !== null && @$this->GetIDForIdent($ident)) {
                                    $this->SetValue($ident, $this->CastValue($value, $type));
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
