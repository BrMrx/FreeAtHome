<?php

require_once __DIR__ . '/../libs/FunctionID.php';
require_once __DIR__ . '/../libs/PairingID.php';

class FreeAtHomeBridge extends IPSModule
{
    const mBridgeDataId   = '{BC9334EC-8C5C-61C2-C5DD-96FE9368F38D}';  // DatenId der Bridge
    const mDeviceModuleId = '{BDE4603B-E68A-D3AF-2510-9462C7374097}';  // Device Modul Id
    const mChildId        = '{7E471B91-3407-F7EE-347B-64B459E33D76}';  // Child Id

    // GUID des IPS-internen Client-Socket-Moduls
    const mClientSocketGuid = '{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}';

    // Buffer-Schlüssel
    private const WS_BUF_RX            = 'WsRxBuffer';       // noch nicht geparste Bytes
    private const WS_BUF_HANDSHAKE_OK  = 'WsHandshakeDone';  // '1' nach erfolgreichem Upgrade
    private const WS_BUF_WS_KEY        = 'WsKey';            // Sec-WebSocket-Key für Validierung

    // ====================================================================
    //  Lebenszyklusmethoden
    // ====================================================================

    public function Create()
    {
        // Never delete this line!
        parent::Create();

        // Verbindungs-Properties
        $this->RegisterPropertyString('Host', '');
        $this->RegisterPropertyString('Username', '');
        $this->RegisterPropertyString('Password', '');
        $this->RegisterPropertyString('SysAPName', '');
        $this->RegisterPropertyString('SysAPFirmware', '');
        $this->RegisterPropertyString('SysAP_GUID', '');
        $this->RegisterPropertyInteger('UpdateInterval', 10);
        $this->RegisterPropertyBoolean('UseTLS', false);

        // WebSocket-Properties
        $this->RegisterPropertyBoolean('UseWebSocket', false);
        $this->RegisterPropertyInteger('WebSocketPort', 0);    // 0 = automatisch (80/443)
        $this->RegisterPropertyInteger('WsKeepaliveSec', 30);
        $this->RegisterPropertyInteger('WsStaleTimeoutSec', 120);

        // Attribute (persistente Felder, die der SysAP zurückliefert)
        $this->RegisterAttributeString('SysAPName', '');
        $this->RegisterAttributeString('SysAPFirmware', '');
        $this->RegisterAttributeString('SysAP_GUID', '');

        // Timer
        $this->RegisterTimer('FAHBR_UpdateState',   0, 'FAHBR_UpdateState($_IPS[\'TARGET\']);');
        $this->RegisterTimer('FAHBR_WsKeepalive',   0, 'FAHBR_WsTimerKeepalive($_IPS[\'TARGET\']);');
        $this->RegisterTimer('FAHBR_WsStaleCheck',  0, 'FAHBR_WsTimerStaleCheck($_IPS[\'TARGET\']);');

        // Buffer-Initialisierung
        $this->SetBuffer(self::WS_BUF_RX, '');
        $this->SetBuffer(self::WS_BUF_HANDSHAKE_OK, '0');
        $this->SetBuffer(self::WS_BUF_WS_KEY, '');

        // Client-Socket als Eltern-Instanz anlegen (wird von IPS verwaltet)
        $this->RequireParent(self::mClientSocketGuid);
    }

    public function Destroy()
    {
        parent::Destroy();
    }

    public function ApplyChanges()
    {
        // Never delete this line!
        parent::ApplyChanges();

        // Timer und WS-State zurücksetzen
        $this->SetTimerInterval('FAHBR_WsKeepalive', 0);
        $this->SetTimerInterval('FAHBR_WsStaleCheck', 0);
        $this->wsResetState();

        // Nachrichten vom Client-Socket registrieren
        $this->RegisterMessage(0, IPS_KERNELMESSAGE);

        if (!$this->BridgeConnected())
        {
            $this->SetStatus(200);
            $this->LogMessage('Error: Login incomplete, please fill in correct information for SysAP.', KL_ERROR);
            $this->SetTimerInterval('FAHBR_UpdateState', 0);
            $this->wsConfigureClientSocket(false);
            return;
        }

        // Polling-Timer: bei WebSocket als reiner Sync-Fallback (alle 5 min)
        $lPollSec = $this->ReadPropertyInteger('UpdateInterval');
        if ($this->ReadPropertyBoolean('UseWebSocket'))
        {
            $lPollSec = max($lPollSec, 300);
        }
        $this->SetTimerInterval('FAHBR_UpdateState', $lPollSec * 1000);

        // Client-Socket konfigurieren und ggf. verbinden
        if ($this->ReadPropertyBoolean('UseWebSocket'))
        {
            $this->wsConfigureClientSocket(true);
        }
        else
        {
            $this->wsConfigureClientSocket(false);
        }
    }

    // ====================================================================
    //  IPS-Kernel-Nachrichten (Client-Socket Status)
    // ====================================================================

    public function MessageSink($a_TimeStamp, $a_SenderID, $a_Message, $a_Data)
    {
        switch ($a_Message)
        {
            case IPS_KERNELMESSAGE:
                if ($a_Data[0] === KR_READY)
                {
                    // Kernel ist hochgefahren → WS-Verbindung aufbauen
                    if ($this->ReadPropertyBoolean('UseWebSocket'))
                    {
                        $this->wsConfigureClientSocket(true);
                    }
                }
                break;

            case IM_CHANGESTATUS:
                // Client-Socket hat seinen Status geändert
                if ($a_SenderID === $this->wsGetClientSocketId())
                {
                    if ($a_Data[0] === 102) // IS_ACTIVE
                    {
                        // Verbindung steht → WebSocket-Handshake senden
                        $this->SendDebug('WS', 'Client Socket connected → sending handshake', 0);
                        $this->wsSendHandshake();
                    }
                    else
                    {
                        $this->SendDebug('WS', 'Client Socket disconnected (status ' . $a_Data[0] . ')', 0);
                        $this->wsResetState();
                    }
                }
                break;
        }
    }

    // ====================================================================
    //  Datenempfang vom Client-Socket (wird von IPS aufgerufen)
    // ====================================================================

    public function ReceiveData($a_JSONString)
    {
        $lIncoming = json_decode($a_JSONString);
        if (!isset($lIncoming->Buffer))
        {
            return;
        }

        // IPS überträgt Binärdaten als Latin-1-kodierten String im JSON
        $lChunk = utf8_decode($lIncoming->Buffer);

        if ($this->GetBuffer(self::WS_BUF_HANDSHAKE_OK) !== '1')
        {
            // Noch kein Handshake abgeschlossen → HTTP-Response auswerten
            $this->wsProcessHandshakeResponse($lChunk);
            return;
        }

        // Normaler WebSocket-Betrieb: Daten in Puffer anhängen und Frames extrahieren
        $lBuf = $this->GetBuffer(self::WS_BUF_RX) . $lChunk;
        $this->wsProcessFrames($lBuf);
    }

    // ====================================================================
    //  Öffentliche Funktionen (Buttons in form.json / direkte Aufrufe)
    // ====================================================================

    public function CheckConnection()
    {
        if (!$this->BridgeConnected())
        {
            $this->SetStatus(200);
            $this->LogMessage('Error: Login incomplete, please fill in correct information for SysAP.', KL_ERROR);
            $this->SetTimerInterval('FAHBR_UpdateState', 0);
            return;
        }
        $lPollSec = $this->ReadPropertyInteger('UpdateInterval');
        if ($this->ReadPropertyBoolean('UseWebSocket'))
        {
            $lPollSec = max($lPollSec, 300);
        }
        $this->SetTimerInterval('FAHBR_UpdateState', $lPollSec * 1000);
    }

    public function WsConnect(): void
    {
        $this->wsConfigureClientSocket(true);
    }

    public function WsDisconnect(): void
    {
        $this->wsConfigureClientSocket(false);
    }

    public function WsSendPing(): bool
    {
        if ($this->GetBuffer(self::WS_BUF_HANDSHAKE_OK) !== '1')
        {
            return false;
        }
        return $this->wsSendFrame(0x9, '');
    }

    // ====================================================================
    //  Timer-Einsprungpunkte
    // ====================================================================

    public function WsTimerKeepalive(): void
    {
        if ($this->GetBuffer(self::WS_BUF_HANDSHAKE_OK) !== '1')
        {
            return;
        }
        if (!$this->wsSendFrame(0x9, ''))
        {
            $this->SendDebug('WS', 'Keepalive ping failed', 0);
        }
    }

    public function WsTimerStaleCheck(): void
    {
        // Der Client-Socket reconnectet selbstständig bei Verbindungsverlust.
        // Dieser Timer prüft nur ob wir im Handshake-Zustand hängen.
        if ($this->GetBuffer(self::WS_BUF_HANDSHAKE_OK) !== '1')
        {
            $lSockId = $this->wsGetClientSocketId();
            if ($lSockId !== 0 && IPS_GetInstance($lSockId)['InstanceStatus'] === 102)
            {
                // Socket ist verbunden aber kein Handshake → erneut versuchen
                $this->SendDebug('WS', 'Stale handshake detected → retrying', 0);
                $this->wsSendHandshake();
            }
        }
    }

    // ====================================================================
    //  ForwardData / UpdateState / getAllDevices (unverändert)
    // ====================================================================

    public function ForwardData($a_JSONString)
    {
        $this->SendDebug(__FUNCTION__, $a_JSONString, 0);
        $lData = json_decode($a_JSONString);
        switch ($lData->Buffer->Command)
        {
            case 'getAllDevices':
                $lResult = $this->getAllDevices();
                break;

            case 'setDatapoint':
                $lGUID      = $this->ReadPropertyString('SysAP_GUID');
                $lDeviceID  = $lData->Buffer->DeviceID;
                $lChannel   = $lData->Buffer->Channel;
                $lParams    = json_decode($lData->Buffer->Params);
                $lDatapoint = $lParams->datapoint;
                $lEndpoint  = 'datapoint/' . $lGUID . '/' . $lDeviceID . '.' . $lChannel . '.' . $lDatapoint;
                $lParamsArr = [$lParams->value];
                $this->SendDebug(__FUNCTION__ . ' - ' . $lData->Buffer->Command, $lEndpoint . ' => ' . json_encode($lParamsArr), 0);
                $lResult = $this->sendRequest($lEndpoint, $lParamsArr, 'PUT');
                break;

            case 'getDevice':
                $lGUID     = $this->ReadPropertyString('SysAP_GUID');
                $lDeviceID = $lData->Buffer->DeviceID;
                $lEndpoint = 'device/' . $lGUID . '/' . $lDeviceID;
                $this->SendDebug(__FUNCTION__ . ' - ' . $lData->Buffer->Command, $lEndpoint, 0);
                $lResult = $this->sendRequest($lEndpoint);
                if (isset($lResult->{$lGUID}->devices))
                {
                    $lResult = $lResult->{$lGUID}->devices;
                }
                break;

            default:
                $this->SendDebug(__FUNCTION__, 'Invalid Command: ' . $lData->Buffer->Command, 0);
                $lResult = null;
                break;
        }
        $this->SendDebug(__FUNCTION__, json_encode($lResult), 0);
        return json_encode($lResult);
    }

    public function UpdateState()
    {
        $this->SendDebug(__FUNCTION__, 'update SysAP States', 0);

        $lListRequest = $this->GetOutputDataPointsOfDevices();
        $lDataObj     = [];
        $lGUID        = $this->ReadPropertyString('SysAP_GUID');
        $lResult      = $this->sendRequest('configuration');
        $lDevices     = $lResult->{$lGUID}->devices;

        foreach ($lListRequest as $lRequest)
        {
            $lRequestArray = explode('.', $lRequest);
            if (isset($lDevices->{$lRequestArray[0]}) &&
                isset($lDevices->{$lRequestArray[0]}->channels) &&
                isset($lRequestArray[1]) &&
                isset($lDevices->{$lRequestArray[0]}->channels->{$lRequestArray[1]}) &&
                isset($lDevices->{$lRequestArray[0]}->channels->{$lRequestArray[1]}->outputs) &&
                isset($lRequestArray[2]) &&
                isset($lDevices->{$lRequestArray[0]}->channels->{$lRequestArray[1]}->outputs->{$lRequestArray[2]}) &&
                isset($lDevices->{$lRequestArray[0]}->channels->{$lRequestArray[1]}->outputs->{$lRequestArray[2]}->value))
            {
                $lValue       = $lDevices->{$lRequestArray[0]}->channels->{$lRequestArray[1]}->outputs->{$lRequestArray[2]}->value;
                $lUnresponsive = $lDevices->{$lRequestArray[0]}->unresponsive;
                $lDisplayName = $lDevices->{$lRequestArray[0]}->displayName;
                $lDataObj[$lRequestArray[0]][$lRequestArray[1]][$lRequestArray[2]] = $lValue;
                $lDataObj[$lRequestArray[0]]['unresponsive'] = $lUnresponsive;
                $lDataObj[$lRequestArray[0]]['displayName']  = $lDisplayName;
            }
            else
            {
                $lDataObj[$lRequestArray[0]]['unresponsive'] = true;
            }
        }

        $lData           = [];
        $lData['DataID'] = self::mChildId;
        $lData['Buffer'] = json_encode($lDataObj);
        $lData           = json_encode($lData);

        $this->SendDebug(__FUNCTION__, 'send: ' . $lData, 0);
        $lResultSend = $this->SendDataToChildren($lData);
        $this->SendDebug(__FUNCTION__, 'result: ' . json_encode($lResultSend), 0);
    }

    // ====================================================================
    //  Private Hilfsfunktionen
    // ====================================================================

    protected function GetOutputDataPointsOfDevices(): array
    {
        $lVectRet    = [];
        $lInstanceIDs = IPS_GetInstanceListByModuleID(self::mDeviceModuleId);

        foreach ($lInstanceIDs as $lId)
        {
            if (IPS_GetInstance($lId)['ConnectionID'] == $this->InstanceID)
            {
                $lData    = IPS_GetProperty($lId, 'FAHDeviceID') . '.' . IPS_GetProperty($lId, 'Channel') . '.';
                $lOutputs = json_decode(IPS_GetProperty($lId, 'Outputs'));

                foreach ($lOutputs as $lDatapoint => $lPairingID)
                {
                    $lVectRet[] = $lData . $lDatapoint;
                }
            }
        }

        return $lVectRet;
    }

    private function FilterSupportedDevices(object $a_Devices): object
    {
        $lRetValue = new stdClass();

        foreach ($a_Devices as $lDeviceId => $lDeviceValue)
        {
            $lAddToList = false;
            if (isset($lDeviceValue->channels) && isset($lDeviceValue->interface))
            {
                foreach ($lDeviceValue->channels as $lChannelNr => $lChannelValue)
                {
                    if (isset($lChannelValue->functionID) && FID::IsSupportedID($lChannelValue->functionID))
                    {
                        $lSupportedPairingIDs = PID::FilterSupported($lChannelValue);
                        if (!empty($lSupportedPairingIDs))
                        {
                            $lAddToList = true;
                        }
                    }
                }
                if ($lAddToList)
                {
                    $lRetValue->$lDeviceId = $lDeviceValue;
                }
            }
        }

        IPS_LogMessage($this->InstanceID, __FUNCTION__ . ': ' . json_encode($lRetValue));
        return $lRetValue;
    }

    public function getAllDevices()
    {
        if ($this->ReadPropertyString('Host') === '')
        {
            $this->SendDebug(__FUNCTION__, 'host missing', 0);
            return false;
        }
        if ($this->ReadPropertyString('Username') === '')
        {
            $this->SendDebug(__FUNCTION__, 'username missing', 0);
            return false;
        }
        if ($this->ReadPropertyString('Password') === '')
        {
            $this->SendDebug(__FUNCTION__, 'password missing', 0);
            return false;
        }
        if ($this->ReadPropertyString('SysAP_GUID') === '')
        {
            $this->SendDebug(__FUNCTION__, 'SysAP GUID missing', 0);
            return false;
        }

        $lResult = $this->sendRequest('configuration');
        return $this->FilterSupportedDevices($lResult->{$this->ReadPropertyString('SysAP_GUID')}->devices);
    }

    private function sendRequest(string $a_Endpoint, array $a_Params = [], string $a_Method = 'GET')
    {
        $this->SendDebug(__FUNCTION__ . ' endpoint', $a_Endpoint, 0);

        if ($this->ReadPropertyString('Host') === '')
        {
            $this->SendDebug(__FUNCTION__, 'host missing', 0);
            return false;
        }
        if ($this->ReadPropertyString('Username') === '')
        {
            $this->SendDebug(__FUNCTION__, 'username missing', 0);
            return false;
        }
        if ($this->ReadPropertyString('Password') === '')
        {
            $this->SendDebug(__FUNCTION__, 'password missing', 0);
            return false;
        }

        $lHost     = $this->ReadPropertyString('Host');
        $lUsername = $this->ReadPropertyString('Username');
        $lPassword = $this->ReadPropertyString('Password');
        $lScheme   = $this->ReadPropertyBoolean('UseTLS') ? 'https' : 'http';
        $lUrl      = "{$lScheme}://{$lHost}/fhapi/v1/api/rest/{$a_Endpoint}";

        $this->SendDebug(__FUNCTION__ . ' URL', $lUrl, 0);

        $lCh = curl_init();
        curl_setopt($lCh, CURLOPT_URL, $lUrl);
        curl_setopt($lCh, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($lCh, CURLOPT_USERPWD, $lUsername . ':' . $lPassword);
        curl_setopt($lCh, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        if ($this->ReadPropertyBoolean('UseTLS'))
        {
            curl_setopt($lCh, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($lCh, CURLOPT_SSL_VERIFYHOST, 0);
        }

        if (in_array($a_Method, ['POST', 'PUT', 'DELETE'], true))
        {
            if ($a_Method === 'POST')
            {
                curl_setopt($lCh, CURLOPT_POST, true);
            }
            else
            {
                curl_setopt($lCh, CURLOPT_CUSTOMREQUEST, $a_Method);
            }
            curl_setopt($lCh, CURLOPT_POSTFIELDS, $a_Params[0]);
        }

        $lApiResult = curl_exec($lCh);
        $this->SendDebug(__FUNCTION__ . ' Result', $lApiResult, 0);
        $lHeaderInfo = curl_getinfo($lCh);

        if ($lHeaderInfo['http_code'] === 200)
        {
            if ($lApiResult !== false)
            {
                $this->SetStatus(102);
                curl_close($lCh);
                return json_decode($lApiResult, false);
            }
            else
            {
                $this->LogMessage('Free At Home sendRequest Error: ' . curl_error($lCh), 10205);
                $this->SetStatus(201);
                curl_close($lCh);
                return new stdClass();
            }
        }
        else
        {
            $this->LogMessage('Free At Home sendRequest Error – Curl: ' . curl_error($lCh) . ' HTTP: ' . $lHeaderInfo['http_code'], 10205);
            $this->SetStatus(202);
            curl_close($lCh);
            return new stdClass();
        }
    }

    private function BridgeConnected(): bool
    {
        $lAnswer = $this->sendRequest('sysap');

        if ($lAnswer === false)
        {
            return false;
        }

        $this->SendDebug(__FUNCTION__ . ' Json:', json_encode($lAnswer), 0);

        if (!isset($lAnswer->sysapName) || !isset($lAnswer->version))
        {
            return false;
        }

        $this->SendDebug(__FUNCTION__ . ' Json:', $lAnswer->sysapName, 0);
        $this->SendDebug(__FUNCTION__ . ' Json:', $lAnswer->version, 0);

        $this->WriteAttributeString('SysAPName', $lAnswer->sysapName);
        $this->WriteAttributeString('SysAPFirmware', $lAnswer->version);

        $lNameChanged    = $lAnswer->sysapName !== $this->ReadPropertyString('SysAPName');
        $lVersionChanged = $lAnswer->version    !== $this->ReadPropertyString('SysAPFirmware');

        if ($lNameChanged || $lVersionChanged)
        {
            if ($lNameChanged)
            {
                $this->SendDebug(__FUNCTION__ . ' SysAP Name changed:', $this->ReadPropertyString('SysAPName') . ' -> ' . $lAnswer->sysapName, 0);
                IPS_SetProperty($this->InstanceID, 'SysAPName', $lAnswer->sysapName);
            }
            if ($lVersionChanged)
            {
                $this->SendDebug(__FUNCTION__ . ' SysAP version changed:', $this->ReadPropertyString('SysAPFirmware') . ' -> ' . $lAnswer->version, 0);
                IPS_SetProperty($this->InstanceID, 'SysAPFirmware', $lAnswer->version);
            }
            IPS_ApplyChanges($this->InstanceID);
        }

        // GUID ermitteln
        $lAnswer = $this->sendRequest('devicelist');
        if ($lAnswer === false)
        {
            return false;
        }

        foreach ($lAnswer as $lKey => $lArrayDeviceID)
        {
            $this->WriteAttributeString('SysAP_GUID', $lKey);
            if ($lKey !== $this->ReadPropertyString('SysAP_GUID'))
            {
                $this->SendDebug(__FUNCTION__ . ' SysAP_GUID changed:', $this->ReadPropertyString('SysAP_GUID') . ' -> ' . $lKey, 0);
                IPS_SetProperty($this->InstanceID, 'SysAP_GUID', $lKey);
                IPS_ApplyChanges($this->InstanceID);
            }
            return true;
        }

        return false;
    }

    // ====================================================================
    //  WebSocket – Client-Socket-Verwaltung
    // ====================================================================

    /**
     * Liefert die InstanceID der untergeordneten Client-Socket-Instanz (0 = keine).
     */
    private function wsGetClientSocketId(): int
    {
        $lParentId = IPS_GetInstance($this->InstanceID)['ConnectionID'];
        if ($lParentId === 0)
        {
            return 0;
        }
        if (IPS_GetInstance($lParentId)['ModuleInfo']['ModuleGUID'] !== self::mClientSocketGuid)
        {
            return 0;
        }
        return $lParentId;
    }

    /**
     * Konfiguriert den Client-Socket und öffnet/schließt die Verbindung.
     */
    private function wsConfigureClientSocket(bool $a_Enable): void
    {
        $lSockId = $this->wsGetClientSocketId();
        if ($lSockId === 0)
        {
            $this->SendDebug('WS', 'No Client Socket instance found', 0);
            return;
        }

        if (!$a_Enable)
        {
            // Socket deaktivieren
            IPS_SetProperty($lSockId, 'Open', false);
            @IPS_ApplyChanges($lSockId);
            $this->wsResetState();
            $this->SetTimerInterval('FAHBR_WsKeepalive', 0);
            $this->SetTimerInterval('FAHBR_WsStaleCheck', 0);
            return;
        }

        $lHost   = $this->ReadPropertyString('Host');
        $lUseTls = $this->ReadPropertyBoolean('UseTLS');
        $lPort   = $this->ReadPropertyInteger('WebSocketPort');
        if ($lPort <= 0)
        {
            $lPort = $lUseTls ? 443 : 80;
        }

        // Client-Socket-Properties setzen
        IPS_SetProperty($lSockId, 'Host', $lHost);
        IPS_SetProperty($lSockId, 'Port', $lPort);
        IPS_SetProperty($lSockId, 'UseSSL', $lUseTls);
        IPS_SetProperty($lSockId, 'Open', true);

        // Statusänderungen des Sockets abonnieren
        $this->RegisterMessage($lSockId, IM_CHANGESTATUS);

        @IPS_ApplyChanges($lSockId);

        $this->SendDebug('WS', "Client Socket configured: {$lHost}:{$lPort} TLS=" . ($lUseTls ? '1' : '0'), 0);
    }

    /**
     * Setzt alle WS-internen Buffer zurück (kein Schließen des Sockets –
     * das übernimmt wsConfigureClientSocket).
     */
    private function wsResetState(): void
    {
        $this->SetBuffer(self::WS_BUF_RX, '');
        $this->SetBuffer(self::WS_BUF_HANDSHAKE_OK, '0');
        $this->SetBuffer(self::WS_BUF_WS_KEY, '');
    }

    // ====================================================================
    //  WebSocket – Handshake
    // ====================================================================

    private function wsSendHandshake(): void
    {
        $lHost   = $this->ReadPropertyString('Host');
        $lUser   = $this->ReadPropertyString('Username');
        $lPass   = $this->ReadPropertyString('Password');
        $lUseTls = $this->ReadPropertyBoolean('UseTLS');
        $lPort   = $this->ReadPropertyInteger('WebSocketPort');
        if ($lPort <= 0)
        {
            $lPort = $lUseTls ? 443 : 80;
        }

        $lHostHeader = (($lUseTls && $lPort === 443) || (!$lUseTls && $lPort === 80))
            ? $lHost
            : "{$lHost}:{$lPort}";

        $lKey = base64_encode(random_bytes(16));
        $this->SetBuffer(self::WS_BUF_WS_KEY, $lKey);

        $lReq  = "GET /fhapi/v1/api/ws HTTP/1.1\r\n";
        $lReq .= "Host: {$lHostHeader}\r\n";
        $lReq .= "Upgrade: websocket\r\n";
        $lReq .= "Connection: Upgrade\r\n";
        $lReq .= "Sec-WebSocket-Key: {$lKey}\r\n";
        $lReq .= "Sec-WebSocket-Version: 13\r\n";
        $lReq .= 'Authorization: Basic ' . base64_encode("{$lUser}:{$lPass}") . "\r\n";
        $lReq .= "\r\n";

        $this->SendDebug('WS', 'Sending HTTP upgrade request', 0);
        $this->wsSendRaw($lReq);

        // Stale-Check-Timer: falls Handshake nicht innerhalb 10s bestätigt
        $this->SetTimerInterval('FAHBR_WsStaleCheck', 10000);
    }

    /**
     * Verarbeitet die HTTP-Upgrade-Response des SysAP.
     * Alle eingehenden Bytes bis zum Handshake-Abschluss landen hier.
     */
    private function wsProcessHandshakeResponse(string $a_Chunk): void
    {
        $lBuf = $this->GetBuffer(self::WS_BUF_RX) . $a_Chunk;

        $lPos = strpos($lBuf, "\r\n\r\n");
        if ($lPos === false)
        {
            // Header noch nicht vollständig empfangen
            $this->SetBuffer(self::WS_BUF_RX, $lBuf);
            return;
        }

        $lHeader = substr($lBuf, 0, $lPos);
        $lRest   = substr($lBuf, $lPos + 4);

        if (!preg_match('#^HTTP/1\.1\s+(\d+)#', $lHeader, $lM))
        {
            $this->SendDebug('WS', 'No HTTP status in handshake response', 0);
            $this->wsResetState();
            return;
        }

        $lCode = (int) $lM[1];
        if ($lCode === 401)
        {
            $this->SendDebug('WS', 'Handshake: authentication failed (401)', 0);
            $this->wsResetState();
            return;
        }
        if ($lCode !== 101)
        {
            $this->SendDebug('WS', "Handshake: unexpected HTTP status {$lCode}", 0);
            $this->wsResetState();
            return;
        }

        // Sec-WebSocket-Accept validieren
        $lKey      = $this->GetBuffer(self::WS_BUF_WS_KEY);
        $lExpected = base64_encode(sha1($lKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
        if (!preg_match('#Sec-WebSocket-Accept:\s*(\S+)#i', $lHeader, $lMa) ||
            trim($lMa[1]) !== $lExpected)
        {
            $this->SendDebug('WS', 'Handshake: invalid Sec-WebSocket-Accept', 0);
            $this->wsResetState();
            return;
        }

        // Handshake erfolgreich
        $this->SetBuffer(self::WS_BUF_HANDSHAKE_OK, '1');
        $this->SetTimerInterval('FAHBR_WsStaleCheck', 0);

        $lKaSec = max(5, $this->ReadPropertyInteger('WsKeepaliveSec'));
        $this->SetTimerInterval('FAHBR_WsKeepalive', $lKaSec * 1000);

        $this->SendDebug('WS', 'Handshake complete – WebSocket active', 0);

        // Bytes die nach dem Header-Ende bereits mitgeliefert wurden sofort verarbeiten
        if ($lRest !== '')
        {
            $this->wsProcessFrames($lRest);
        }
        else
        {
            $this->SetBuffer(self::WS_BUF_RX, '');
        }
    }

    // ====================================================================
    //  WebSocket – Frame-Verarbeitung
    // ====================================================================

    private function wsProcessFrames(string $a_Buf): void
    {
        while (true)
        {
            $lFrame = $this->wsDecodeFrame($a_Buf);
            if ($lFrame === null)
            {
                break;
            }

            [$lOpcode, $lPayload, $a_Buf] = $lFrame;

            switch ($lOpcode)
            {
                case 0x0: // continuation (behandelt wie Text)
                case 0x1: // text
                    $this->wsHandleTextPayload($lPayload);
                    break;

                case 0x2: // binary
                    $this->SendDebug('WS', 'Binary frame ignored', 0);
                    break;

                case 0x8: // close
                    $this->SendDebug('WS', 'Close frame received', 0);
                    $this->wsSendFrame(0x8, '');
                    $this->wsResetState();
                    return;

                case 0x9: // ping → pong
                    $this->wsSendFrame(0xA, $lPayload);
                    break;

                case 0xA: // pong
                    break;

                default:
                    $this->SendDebug('WS', "Unknown opcode {$lOpcode}", 0);
                    break;
            }
        }

        $this->SetBuffer(self::WS_BUF_RX, $a_Buf);
    }

    /**
     * Extrahiert einen einzelnen Frame aus $a_Buf.
     * Server→Client ist unmaskiert (RFC 6455).
     *
     * @return array{0:int,1:string,2:string}|null [opcode, payload, restBuffer]
     */
    private function wsDecodeFrame(string $a_Buf): ?array
    {
        $lLen = strlen($a_Buf);
        if ($lLen < 2)
        {
            return null;
        }

        $lB0 = ord($a_Buf[0]);
        $lB1 = ord($a_Buf[1]);

        $lFin    = ($lB0 & 0x80) !== 0;
        $lOpcode = $lB0 & 0x0F;
        $lMasked = ($lB1 & 0x80) !== 0;
        $lPlen   = $lB1 & 0x7F;
        $lOffset = 2;

        if ($lPlen === 126)
        {
            if ($lLen < $lOffset + 2)
            {
                return null;
            }
            $lPlen = unpack('n', substr($a_Buf, $lOffset, 2))[1];
            $lOffset += 2;
        }
        elseif ($lPlen === 127)
        {
            if ($lLen < $lOffset + 8)
            {
                return null;
            }
            $lParts = unpack('N2', substr($a_Buf, $lOffset, 8));
            $lPlen  = ($lParts[1] << 32) | $lParts[2];
            $lOffset += 8;
        }

        $lMaskKey = '';
        if ($lMasked)
        {
            if ($lLen < $lOffset + 4)
            {
                return null;
            }
            $lMaskKey = substr($a_Buf, $lOffset, 4);
            $lOffset += 4;
        }

        if ($lLen < $lOffset + $lPlen)
        {
            return null;
        }

        $lPayload = substr($a_Buf, $lOffset, $lPlen);
        $lOffset += $lPlen;

        if ($lMasked)
        {
            $lUnmasked = '';
            for ($i = 0; $i < $lPlen; $i++)
            {
                $lUnmasked .= $lPayload[$i] ^ $lMaskKey[$i % 4];
            }
            $lPayload = $lUnmasked;
        }

        if (!$lFin)
        {
            $this->SendDebug('WS', 'Fragmented frame – FIN not set, treated as complete', 0);
        }

        $lRest = substr($a_Buf, $lOffset);
        return [$lOpcode, $lPayload, $lRest];
    }

    /**
     * Baut einen maskierten Client→Server-Frame und schickt ihn über den Client-Socket.
     */
    private function wsSendFrame(int $a_Opcode, string $a_Payload): bool
    {
        $lPlen   = strlen($a_Payload);
        $lHeader = chr(0x80 | ($a_Opcode & 0x0F));

        if ($lPlen < 126)
        {
            $lHeader .= chr(0x80 | $lPlen);
        }
        elseif ($lPlen < 65536)
        {
            $lHeader .= chr(0x80 | 126) . pack('n', $lPlen);
        }
        else
        {
            $lHeader .= chr(0x80 | 127) . pack('J', $lPlen);
        }

        $lMask   = random_bytes(4);
        $lHeader .= $lMask;

        $lMasked = '';
        for ($i = 0; $i < $lPlen; $i++)
        {
            $lMasked .= $a_Payload[$i] ^ $lMask[$i % 4];
        }

        return $this->wsSendRaw($lHeader . $lMasked);
    }

    /**
     * Sendet Rohdaten über den IPS-Client-Socket (SendDataToParent).
     */
    private function wsSendRaw(string $a_Data): bool
    {
        // IPS erwartet JSON mit DataID des Client-Sockets und
        // den Nutzdaten als utf8-kodiertem String im Feld "Buffer"
        $lJson = json_encode([
            'DataID' => '{C8792760-65CF-4C53-B5C7-A30FCC84FEBB}',  // Client-Socket Send-DataID
            'Buffer' => utf8_encode($a_Data),
        ]);

        $lResult = $this->SendDataToParent($lJson);
        return $lResult !== false;
    }

    // ====================================================================
    //  WebSocket – Payload-Verarbeitung
    // ====================================================================

    /**
     * Verarbeitet einen Text-Frame vom SysAP und leitet geänderte
     * Datenpunkte an die Child-Device-Instanzen weiter.
     *
     * Payload-Format des SysAP:
     * {
     *   "<sysapGuid>": {
     *     "datapoints": {
     *       "ABB700xxxx/ch0001/odp0000": "1",
     *       ...
     *     }
     *   }
     * }
     */
    private function wsHandleTextPayload(string $a_Json): void
    {
        $this->SendDebug('WS/RX', $a_Json, 0);

        $lData = json_decode($a_Json, true);
        if (!is_array($lData))
        {
            return;
        }

        $lGuid = $this->ReadPropertyString('SysAP_GUID');
        if ($lGuid === '' || !isset($lData[$lGuid]))
        {
            return;
        }

        $lSysap = $lData[$lGuid];

        if (!isset($lSysap['datapoints']) ||
            !is_array($lSysap['datapoints']) ||
            count($lSysap['datapoints']) === 0)
        {
            return;
        }

        // Datenpunkt-Keys: SysAP nutzt "/" als Trenner, wir akzeptieren auch "."
        $lDataObj = [];
        foreach ($lSysap['datapoints'] as $lKey => $lValue)
        {
            $lParts = preg_split('#[/.]#', (string) $lKey);
            if ($lParts === false || count($lParts) < 3)
            {
                continue;
            }
            [$lDeviceId, $lChannel, $lDatapoint] = $lParts;
            $lDataObj[$lDeviceId][$lChannel][$lDatapoint] = $lValue;
        }

        if (count($lDataObj) === 0)
        {
            return;
        }

        // unresponsive-Flag setzen (WS-Push → Device ist erreichbar)
        foreach ($lDataObj as $lDeviceId => &$lChannels)
        {
            if (!isset($lChannels['unresponsive']))
            {
                $lChannels['unresponsive'] = false;
            }
        }
        unset($lChannels);

        $lOut = [
            'DataID' => self::mChildId,
            'Buffer' => json_encode($lDataObj),
        ];

        $lJson = json_encode($lOut);
        $this->SendDebug('WS/FORWARD', $lJson, 0);
        $this->SendDataToChildren($lJson);
    }
}
