<?php

declare(strict_types=1);
require_once __DIR__ . '/../libs/FunctionID.php';
require_once __DIR__ . '/../libs/PairingID.php';

class FreeAtHomeBridge extends IPSModule
{
    const mBridgeDataId     = '{BC9334EC-8C5C-61C2-C5DD-96FE9368F38D}';      // DatenId der Bridge
    const mDeviceModuleId   = '{BDE4603B-E68A-D3AF-2510-9462C7374097}';      // Device Modul Id 
    const mChildId          = '{7E471B91-3407-F7EE-347B-64B459E33D76}';      // Child Id 

    // -------------------------------------------------------------------
    //  WebSocket-Konstanten
    // -------------------------------------------------------------------
    private const WS_BUF_RX        = 'WsRxBuffer';       // noch nicht geparste Bytes
    private const WS_BUF_SOCKET_ID = 'WsSocketId';       // ID auf statische Socket-Map
    private const WS_BUF_BACKOFF   = 'WsBackoffSec';     // aktueller Reconnect-Backoff
    private const WS_BUF_LAST_RX   = 'WsLastRxTs';       // Zeitstempel letzter empfangener Frame

    // Socket-Ressource lässt sich nicht serialisieren → statische Map,
    // stabil innerhalb einer Symcon-Prozess-Laufzeit.
    private static $s_wsSockets = [];

    public function Create()
    {
        //Never delete this line!
        parent::Create();
        $this->RegisterPropertyString('Host', '');
        $this->RegisterPropertyString('Username', '');
        $this->RegisterPropertyString('Password', '');
        $this->RegisterPropertyString('SysAPName', '');
        $this->RegisterPropertyString('SysAPFirmware', '');
        $this->RegisterPropertyString('SysAP_GUID', '');
        $this->RegisterPropertyInteger('UpdateInterval', 10);
  
        // NEU: WebSocket-Properties
        $this->RegisterPropertyBoolean('UseWebSocket', false);
        $this->RegisterPropertyBoolean('UseTLS', false);
        $this->RegisterPropertyInteger('WebSocketPort', 0);           // 0 = automatisch (80/443)
        $this->RegisterPropertyInteger('WsReceiveIntervalMs', 300);
        $this->RegisterPropertyInteger('WsKeepaliveSec', 30);
        $this->RegisterPropertyInteger('WsStaleTimeoutSec', 120);     // ohne RX → Reconnect

        $this->RegisterAttributeString('SysAPName', '');
        $this->RegisterAttributeString('SysAPFirmware', '');
        $this->RegisterAttributeString('SysAP_GUID', '');
      
        $this->RegisterTimer('FAHBR_UpdateState', 0, 'FAHBR_UpdateState($_IPS[\'TARGET\']);');

        // NEU: WebSocket-Timer
        $this->RegisterTimer('FAHBR_WsConnect',   0, 'FAHBR_WsTimerConnect($_IPS[\'TARGET\']);');
        $this->RegisterTimer('FAHBR_WsReceive',   0, 'FAHBR_WsTimerReceive($_IPS[\'TARGET\']);');
        $this->RegisterTimer('FAHBR_WsKeepalive', 0, 'FAHBR_WsTimerKeepalive($_IPS[\'TARGET\']);');

        $this->SetBuffer(self::WS_BUF_RX, '');
        $this->SetBuffer(self::WS_BUF_SOCKET_ID, '');
        $this->SetBuffer(self::WS_BUF_BACKOFF, '1');
        $this->SetBuffer(self::WS_BUF_LAST_RX, '0');
    }

    public function Destroy()
    {
        $this->wsCloseSocket();
        parent::Destroy();
     }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

        // WebSocket-Timer und Socket erst mal zurücksetzen;
        // werden unten ggf. neu gestartet.
        $this->SetTimerInterval('FAHBR_WsConnect', 0);
        $this->SetTimerInterval('FAHBR_WsReceive', 0);
        $this->SetTimerInterval('FAHBR_WsKeepalive', 0);
        $this->wsCloseSocket();

        if (!$this->BridgeConnected()) {
            $this->SetStatus(200);

            $this->LogMessage('Error: Loggin incomplete, please fill in correct informations for SysAP.', KL_ERROR);
            $this->SetTimerInterval('FAHBR_UpdateState', 0);
            return;
        }

        // Polling: bei aktivem WebSocket deutlich entschärft als Sync-Punkt,
        // sonst wie gehabt im konfigurierten Intervall.
        $lPollSec = $this->ReadPropertyInteger('UpdateInterval');
        if ($this->ReadPropertyBoolean('UseWebSocket')) {
            $lPollSec = max($lPollSec, 300); // min. 5 min als Sync-Fallback
        }
        $this->SetTimerInterval('FAHBR_UpdateState', $lPollSec * 1000);

        // WebSocket starten (falls aktiviert)
        if ($this->ReadPropertyBoolean('UseWebSocket')) {
            if (IPS_GetKernelRunlevel() === KR_READY) {
                $this->SetBuffer(self::WS_BUF_BACKOFF, '1');
                $this->SetTimerInterval('FAHBR_WsConnect', 1000);
            }
        }
    }

    public function CheckConnection()
    {
       if( !$this->BridgeConnected() )
        {
            $this->SetStatus(200);

            $this->LogMessage('Error: Loggin incomplete, please fill in correct informations for SysAP.', KL_ERROR);
            $this->SetTimerInterval('FAHBR_UpdateState', 0);
            return;          
        }
        $this->SetTimerInterval('FAHBR_UpdateState', $this->ReadPropertyInteger('UpdateInterval') * 1000);
    }

    public function ForwardData($JSONString)
    {
        $this->SendDebug(__FUNCTION__, $JSONString, 0);
        $data = json_decode($JSONString);
        switch ($data->Buffer->Command) {
            case 'getAllDevices':
                $result = $this->getAllDevices();
                break;
            case 'setDatapoint':
                $lGUID          = $this->ReadPropertyString("SysAP_GUID");
                $DeviceID       = $data->Buffer->DeviceID;
                $lChannel       = $data->Buffer->Channel;
                $lParameters    = json_decode($data->Buffer->Params);
                $lDatapoint     = $lParameters->datapoint;
                $lEndpoint = 'datapoint/'.$lGUID.'/'.$DeviceID.'.'.$lChannel.'.'.$lDatapoint;
                $lParams[] = $lParameters->value;
                $this->SendDebug(__FUNCTION__.' - '.$data->Buffer->Command, $lEndpoint.' => '.json_encode($lParams), 0);
                $result = $this->sendRequest( $lEndpoint, $lParams, 'PUT' );
                break;

            case 'getDevice':
                $lGUID          = $this->ReadPropertyString("SysAP_GUID");
                $DeviceID       = $data->Buffer->DeviceID;
                $lEndpoint = 'device/'.$lGUID.'/'.$DeviceID;
                $this->SendDebug(__FUNCTION__.' - '.$data->Buffer->Command, $lEndpoint, 0);
                $result = $this->sendRequest( $lEndpoint );
                // nur das geforderte device zurückliefern
                if( isset($result->{$lGUID}->devices) )
                {
                    $result = $result->{$lGUID}->devices;
                }
                break;
    
            default:
                $this->SendDebug(__FUNCTION__, 'Invalid Command: ' . $data->Buffer->Command, 0);
                break;
        }
        $this->SendDebug(__FUNCTION__, json_encode($result), 0);
        return json_encode($result);
    }

    protected function GetOutputDataPointsOfDevices()
    {    
        $lVectRet = array();

        $InstanceIDs = (object)IPS_GetInstanceListByModuleID(self::mDeviceModuleId); //FAHDevice
 
        foreach ($InstanceIDs as $lKey => $id) 
        {
            // Ist die Device Instanz mit dieser Bridge verbunden 
            if (IPS_GetInstance($id)['ConnectionID'] == $this->InstanceID ) 
            {
                $lData = IPS_GetProperty($id, 'FAHDeviceID').'.'.IPS_GetProperty($id, 'Channel').'.';
                $lOutputs = json_decode( IPS_GetProperty($id, 'Outputs') );

                foreach( $lOutputs as $lDatapoint => $lPairingID  )
                {
                     $lVectRet[] = $lData.$lDatapoint;
                }                  
             }
        }
 
        return $lVectRet;
    }


    public function UpdateState()
    {
        $this->SendDebug(__FUNCTION__ , 'update SysAP States', 0);

        $lListRequest = $this->GetOutputDataPointsOfDevices();
        
        $lDataObj = array();
        $lGUID = $this->ReadPropertyString("SysAP_GUID");

        // alle Daten Lesen
        $lResult = $this->sendRequest( 'configuration' );

        $lDevices = $lResult->{$lGUID}->devices;


        foreach( $lListRequest as $lRequest )
        {
            $lRequestArray = explode('.',$lRequest);
            if( isset($lDevices->{$lRequestArray[0]}) && 
                isset($lDevices->{$lRequestArray[0]}->channels) &&
                isset($lRequestArray[1]) &&
                isset($lDevices->{$lRequestArray[0]}->channels->{$lRequestArray[1]}) &&
                isset($lDevices->{$lRequestArray[0]}->channels->{$lRequestArray[1]}->outputs) &&
                isset($lRequestArray[2]) &&
                isset($lDevices->{$lRequestArray[0]}->channels->{$lRequestArray[1]}->outputs->{$lRequestArray[2]}) &&
                isset($lDevices->{$lRequestArray[0]}->channels->{$lRequestArray[1]}->outputs->{$lRequestArray[2]}->value) 
                )
            {
                $lValue = $lDevices->{$lRequestArray[0]}->channels->{$lRequestArray[1]}->outputs->{$lRequestArray[2]}->value;
                $lUnresponsive = $lDevices->{$lRequestArray[0]}->unresponsive;
                $ldisplayName = $lDevices->{$lRequestArray[0]}->displayName;
                $lDataObj[$lRequestArray[0]][$lRequestArray[1]][$lRequestArray[2]] = $lValue;
                $lDataObj[$lRequestArray[0]]['unresponsive'] = $lUnresponsive;
                $lDataObj[$lRequestArray[0]]['displayName'] = $ldisplayName;
            }  
            else
            {
                $lDataObj[$lRequestArray[0]]['unresponsive'] = true; 
            }      
        }
 
        $lData['DataID'] = self::mChildId;
        $lData['Buffer'] = json_encode($lDataObj);

        $lData = json_encode($lData);
        $this->SendDebug(__FUNCTION__ , 'send: '.$lData, 0);
   
        $lResultSend = $this->SendDataToChildren($lData);
        $this->SendDebug(__FUNCTION__ , 'result: '.json_encode($lResultSend), 0);
        
    }

    private function FilterSupportedDevices( $a_Devices )
    {
        $lRetValue = new stdClass();

        foreach($a_Devices as $lDeviceId => $DeviceValue)
        {        
            $lAddToList = false;
            if( isset($DeviceValue->channels ) && isset($DeviceValue->interface))
            {
                foreach($DeviceValue->channels as $lChannelNr => $lChannelValue)
                {
                    if( isset($lChannelValue->functionID ) && FID::IsSupportedID( $lChannelValue->functionID ) )
                    {
                        $SupportedPairingIDs = PID::FilterSupported($lChannelValue);
                        if( !empty($SupportedPairingIDs) )
                        {
                            $lAddToList = true;
                        }
                    }
                }

                if( $lAddToList )
                {
                    $lRetValue->$lDeviceId = $DeviceValue;
                }
            }
        }

        IPS_LogMessage( $this->InstanceID, __FUNCTION__.": ".json_encode($lRetValue) );

        return $lRetValue;
    }

    public function getAllDevices()
    {
        if ($this->ReadPropertyString('Host') == '') {
            $this->SendDebug(__FUNCTION__ ,'host missing', 0);
            return false;
        }
        if ($this->ReadPropertyString('Username') == '') {
            $this->SendDebug(__FUNCTION__ ,'username missing', 0);
            return false;
        }
        if ($this->ReadPropertyString('Password') == '') {
            $this->SendDebug(__FUNCTION__ ,'password missing', 0);
            return false;
        }
        if ($this->ReadPropertyString('SysAP_GUID') == '') {
            $this->SendDebug(__FUNCTION__ ,'SysAP GUID missing', 0);
            return false;
        }



        $lResult = $this->sendRequest( 'configuration' );
        return $this->FilterSupportedDevices( $lResult->{$this->ReadPropertyString("SysAP_GUID")}->devices );
    }

    
    private function sendRequest( string $endpoint, array $params = [], string $method = 'GET')
    {
        $this->SendDebug(__FUNCTION__ . ' endpoint', $endpoint, 0);
        if ($this->ReadPropertyString('Host') == '') {
            $this->SendDebug(__FUNCTION__ ,'host missing', 0);
            return false;
        }
        if ($this->ReadPropertyString('Username') == '') {
            $this->SendDebug(__FUNCTION__ ,'username missing', 0);
            return false;
        }
        if ($this->ReadPropertyString('Password') == '') {
            $this->SendDebug(__FUNCTION__ ,'password missing', 0);
            return false;
        }

         $ch = curl_init();

//        if ($User != '' && $endpoint != '') {
//            $this->SendDebug(__FUNCTION__ . ' URL', $this->ReadPropertyString('Host') . '/api/' . $User . '/' . $endpoint, 0);
//            curl_setopt($ch, CURLOPT_URL, $this->ReadPropertyString('Host') . '/api/' . $User . '/' . $endpoint);
//        } elseif ($endpoint != '') {
//            return [];
//        } else {
//            $this->SendDebug(__FUNCTION__ . ' URL', $this->ReadPropertyString('Host') . '/api/' . $endpoint, 0);
//            curl_setopt($ch, CURLOPT_URL, $this->ReadPropertyString('Host') . '/api/' . $endpoint);
//        }


        $host = $this->ReadPropertyString("Host");
        $username = $this->ReadPropertyString("Username");
        $password = $this->ReadPropertyString("Password");

        $scheme = $this->ReadPropertyBoolean('UseTLS') ? 'https' : 'http';
        $url = "{$scheme}://{$host}/fhapi/v1/api/rest/{$endpoint}";
        $this->SendDebug(__FUNCTION__ . ' URL', $url, 0);

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);

        if ($this->ReadPropertyBoolean('UseTLS')) {
            // SysAP nutzt self-signed Zertifikat
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        }


        if ($method == 'POST' || $method == 'PUT' || $method == 'DELETE') {
            if ($method == 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
            }
            if (in_array($method, ['PUT', 'DELETE'])) {
               curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params[0] );
        }

        $apiResult = curl_exec($ch);
        $this->SendDebug(__FUNCTION__ . ' Result', $apiResult, 0);
        $headerInfo = curl_getinfo($ch);
        if ($headerInfo['http_code'] == 200) {
            if ($apiResult != false) {
                $this->SetStatus(102);
                return json_decode($apiResult, false);
            } else {
                $this->LogMessage('Free At Home sendRequest Error' . curl_error($ch), 10205);
                $this->SetStatus(201);
                return new stdClass();
            }
        } else {
            $this->LogMessage('Free At Home sendRequest Error - Curl Error:' . curl_error($ch) . 'HTTP Code: ' . $headerInfo['http_code'], 10205);
            $this->SetStatus(202);
            return new stdClass();
        }
        curl_close($ch);
    }

  
    private function BridgeConnected()
    {
        $Answer = $this->sendRequest( 'sysap' );
  
        if( $Answer === false)
        {
            return false;
        }
        
        $this->SendDebug(__FUNCTION__ . ' Json:', json_encode($Answer ), 0);

        if( isset($Answer->sysapName) && isset($Answer->version) )
        {
            $this->SendDebug(__FUNCTION__ . ' Json:', $Answer->sysapName, 0);
            $this->SendDebug(__FUNCTION__ . ' Json:', $Answer->version, 0);

            $this->WriteAttributeString( 'SysAPName', $Answer->sysapName);
            $this->WriteAttributeString( 'SysAPFirmware',$Answer->version);

            if( $Answer->sysapName != $this->ReadPropertyString("SysAPName") || 
                $Answer->version   != $this->ReadPropertyString("SysAPFirmware") )
            {
                if( $Answer->sysapName != $this->ReadPropertyString("SysAPName") )
                {
                    $this->SendDebug(__FUNCTION__ . ' SysAP Name changed:', $this->ReadPropertyString("SysAPName").' -> '.$Answer->sysapName, 0);
                    IPS_SetProperty( $this->InstanceID,'SysAPName', $Answer->sysapName );
                }
                if( $Answer->version != $this->ReadPropertyString("SysAPFirmware") )
                {
                    $this->SendDebug(__FUNCTION__ . ' SysAP version changed:', $this->ReadPropertyString("SysAPFirmware").' -> '.$Answer->version, 0);
                    IPS_SetProperty( $this->InstanceID,'SysAPFirmware',$Answer->version );
                }
                IPS_ApplyChanges( $this->InstanceID);
            }

            // nun noch die GUID des SysAP bestimmen
            $Answer = $this->sendRequest( 'devicelist' );
            if( $Answer === false)
            {
                return false;
            }
            foreach( $Answer as $key => $lArrayDeviceID  )
            {
                $this->WriteAttributeString( 'SysAP_GUID',$key);
                if( $key != $this->ReadPropertyString("SysAP_GUID") )
                {
                    $this->SendDebug(__FUNCTION__ . ' SysAP_GUID changed:', $this->ReadPropertyString("SysAPName").' -> '.$key, 0);
                    IPS_SetProperty( $this->InstanceID,'SysAP_GUID', $key );
                    IPS_ApplyChanges( $this->InstanceID);
                }

                return true;
            }

        }

        return false;
    }

    // ====================================================================
    //  WebSocket-Erweiterung
    // ====================================================================

    // --- Öffentliche Actions für manuelle Steuerung / Debug ---------------
    public function WsConnect(): bool
    {
        return $this->wsDoConnect();
    }

    public function WsDisconnect(): void
    {
        $this->SetTimerInterval('FAHBR_WsConnect', 0);
        $this->SetTimerInterval('FAHBR_WsReceive', 0);
        $this->SetTimerInterval('FAHBR_WsKeepalive', 0);
        $this->wsCloseSocket();
    }

    public function WsSendPing(): bool
    {
        $lSocket = $this->wsGetSocket();
        if ($lSocket === null) {
            return false;
        }
        return $this->wsSendFrame($lSocket, 0x9, '');
    }

    // --- Timer-Einsprungpunkte --------------------------------------------
    public function WsTimerConnect(): void
    {
        $this->SetTimerInterval('FAHBR_WsConnect', 0);
        if (!$this->wsDoConnect()) {
            $this->wsScheduleReconnect();
        }
    }

    public function WsTimerReceive(): void
    {
        $this->wsReceiveLoop();
        $this->wsCheckStale();
    }

    public function WsTimerKeepalive(): void
    {
        $lSocket = $this->wsGetSocket();
        if ($lSocket === null) {
            return;
        }
        if (!$this->wsSendFrame($lSocket, 0x9, '')) {
            $this->SendDebug('WS', 'Keepalive send failed → reconnect', 0);
            $this->wsHandleDisconnect();
        }
    }

    // --- Verbindungsauf- und -abbau --------------------------------------
    private function wsDoConnect(): bool
    {
        if (!$this->ReadPropertyBoolean('UseWebSocket')) {
            return false;
        }

        $lHost   = $this->ReadPropertyString('Host');
        $lUser   = $this->ReadPropertyString('Username');
        $lPass   = $this->ReadPropertyString('Password');
        $lUseTls = $this->ReadPropertyBoolean('UseTLS');

        $lPort = $this->ReadPropertyInteger('WebSocketPort');
        if ($lPort <= 0) {
            $lPort = $lUseTls ? 443 : 80;
        }

        if ($lHost === '' || $lUser === '' || $lPass === '') {
            $this->SendDebug('WS', 'Missing credentials – cannot connect', 0);
            return false;
        }

        $lScheme = $lUseTls ? 'ssl' : 'tcp';
        $lRemote = sprintf('%s://%s:%d', $lScheme, $lHost, $lPort);

        $lCtx = stream_context_create([
            'ssl' => [
                // SysAP liefert self-signed Zertifikat
                'verify_peer'      => false,
                'verify_peer_name' => false,
            ],
        ]);

        $this->SendDebug('WS', "Connecting to {$lRemote}/fhapi/v1/api/ws", 0);

        $lErrno = 0;
        $lErrstr = '';
        $lSocket = @stream_socket_client(
            $lRemote,
            $lErrno,
            $lErrstr,
            5.0,
            STREAM_CLIENT_CONNECT,
            $lCtx
        );

        if ($lSocket === false) {
            $this->SendDebug('WS', "Connect failed: {$lErrstr} ({$lErrno})", 0);
            return false;
        }

        // Handshake blocking, danach non-blocking
        stream_set_timeout($lSocket, 5);

        $lKey = base64_encode(random_bytes(16));
        $lHostHeader = (($lUseTls && $lPort === 443) || (!$lUseTls && $lPort === 80))
            ? $lHost
            : "{$lHost}:{$lPort}";

        $lReq  = "GET /fhapi/v1/api/ws HTTP/1.1\r\n";
        $lReq .= "Host: {$lHostHeader}\r\n";
        $lReq .= "Upgrade: websocket\r\n";
        $lReq .= "Connection: Upgrade\r\n";
        $lReq .= "Sec-WebSocket-Key: {$lKey}\r\n";
        $lReq .= "Sec-WebSocket-Version: 13\r\n";
        $lReq .= 'Authorization: Basic ' . base64_encode("{$lUser}:{$lPass}") . "\r\n";
        $lReq .= "\r\n";

        if (fwrite($lSocket, $lReq) === false) {
            $this->SendDebug('WS', 'Handshake write failed', 0);
            fclose($lSocket);
            return false;
        }

        // Response-Header bis \r\n\r\n lesen
        $lHeader = '';
        $lRest = '';
        $lDeadline = microtime(true) + 5.0;
        while (microtime(true) < $lDeadline) {
            $lChunk = fread($lSocket, 4096);
            if ($lChunk === false || $lChunk === '') {
                if (feof($lSocket)) {
                    break;
                }
                usleep(20_000);
                continue;
            }
            $lHeader .= $lChunk;
            $lPos = strpos($lHeader, "\r\n\r\n");
            if ($lPos !== false) {
                $lRest = substr($lHeader, $lPos + 4);
                $lHeader = substr($lHeader, 0, $lPos);
                break;
            }
        }

        if (!preg_match('#^HTTP/1\.1\s+(\d+)#', $lHeader, $lM)) {
            $this->SendDebug('WS', 'No HTTP status in response', 0);
            fclose($lSocket);
            return false;
        }

        $lCode = (int) $lM[1];
        if ($lCode === 401) {
            $this->SendDebug('WS', 'Authentication failed (401)', 0);
            fclose($lSocket);
            return false;
        }
        if ($lCode !== 101) {
            $this->SendDebug('WS', "Unexpected HTTP status: {$lCode}", 0);
            fclose($lSocket);
            return false;
        }

        $lExpected = base64_encode(sha1($lKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
        if (!preg_match('#Sec-WebSocket-Accept:\s*(\S+)#i', $lHeader, $lMa)
            || trim($lMa[1]) !== $lExpected) {
            $this->SendDebug('WS', 'Invalid Sec-WebSocket-Accept', 0);
            fclose($lSocket);
            return false;
        }

        stream_set_blocking($lSocket, false);
        $this->wsStoreSocket($lSocket);
        $this->SetBuffer(self::WS_BUF_RX, $lRest);
        $this->SetBuffer(self::WS_BUF_BACKOFF, '1');
        $this->SetBuffer(self::WS_BUF_LAST_RX, (string) time());

        $this->SendDebug('WS', 'Connected & upgraded', 0);

        $lRxMs = max(50, $this->ReadPropertyInteger('WsReceiveIntervalMs'));
        $this->SetTimerInterval('FAHBR_WsReceive', $lRxMs);

        $lKaSec = max(5, $this->ReadPropertyInteger('WsKeepaliveSec'));
        $this->SetTimerInterval('FAHBR_WsKeepalive', $lKaSec * 1000);

        return true;
    }

    private function wsScheduleReconnect(): void
    {
        $lBackoff = (int) $this->GetBuffer(self::WS_BUF_BACKOFF);
        if ($lBackoff < 1) {
            $lBackoff = 1;
        }
        $lNext = min(60, $lBackoff * 2);
        $this->SetBuffer(self::WS_BUF_BACKOFF, (string) $lNext);
        $this->SendDebug('WS', "Reconnect in {$lBackoff}s", 0);
        $this->SetTimerInterval('FAHBR_WsConnect', $lBackoff * 1000);
    }

    private function wsHandleDisconnect(): void
    {
        $this->SetTimerInterval('FAHBR_WsReceive', 0);
        $this->SetTimerInterval('FAHBR_WsKeepalive', 0);
        $this->wsCloseSocket();
        if ($this->ReadPropertyBoolean('UseWebSocket')) {
            $this->wsScheduleReconnect();
        }
    }

    private function wsCheckStale(): void
    {
        $lTimeout = $this->ReadPropertyInteger('WsStaleTimeoutSec');
        if ($lTimeout <= 0) {
            return;
        }
        $lLast = (int) $this->GetBuffer(self::WS_BUF_LAST_RX);
        if ($lLast === 0) {
            return;
        }
        if ((time() - $lLast) > $lTimeout) {
            $this->SendDebug('WS', "No data for {$lTimeout}s → reconnect", 0);
            $this->wsHandleDisconnect();
        }
    }

    // --- Receive-Loop + Frame-Parser -------------------------------------
    private function wsReceiveLoop(): void
    {
        $lSocket = $this->wsGetSocket();
        if ($lSocket === null) {
            return;
        }

        $lBuf = $this->GetBuffer(self::WS_BUF_RX);
        $lReceivedAnything = false;

        while (true) {
            $lChunk = @fread($lSocket, 8192);
            if ($lChunk === false) {
                $this->SendDebug('WS', 'fread error → reconnect', 0);
                $this->wsHandleDisconnect();
                return;
            }
            if ($lChunk === '') {
                if (feof($lSocket)) {
                    $this->SendDebug('WS', 'Remote closed → reconnect', 0);
                    $this->wsHandleDisconnect();
                    return;
                }
                break;
            }
            $lBuf .= $lChunk;
            $lReceivedAnything = true;
            if (strlen($lChunk) < 8192) {
                break;
            }
        }

        if ($lReceivedAnything) {
            $this->SetBuffer(self::WS_BUF_LAST_RX, (string) time());
        }

        // Frames extrahieren, solange komplette im Puffer liegen
        while (true) {
            $lFrame = $this->wsDecodeFrame($lBuf);
            if ($lFrame === null) {
                break;
            }

            [$lOpcode, $lPayload, $lBuf] = $lFrame;

            switch ($lOpcode) {
                case 0x0: // continuation
                case 0x1: // text
                    $this->wsHandleTextPayload($lPayload);
                    break;
                case 0x2: // binary
                    $this->SendDebug('WS', 'Binary frame ignored', 0);
                    break;
                case 0x8: // close
                    $this->SendDebug('WS', 'Close frame received', 0);
                    $this->wsHandleDisconnect();
                    return;
                case 0x9: // ping
                    $this->wsSendFrame($lSocket, 0xA, $lPayload); // pong echo
                    break;
                case 0xA: // pong
                    break;
                default:
                    $this->SendDebug('WS', "Unknown opcode {$lOpcode}", 0);
            }
        }

        $this->SetBuffer(self::WS_BUF_RX, $lBuf);
    }

    /**
     * Extrahiert einen einzelnen Frame aus $a_Buf.
     * Server→Client ist unmaskiert (RFC 6455). Fragmentierung wird verworfen.
     *
     * @return array{0:int,1:string,2:string}|null [opcode, payload, restBuffer]
     */
    private function wsDecodeFrame(string $a_Buf): ?array
    {
        $lLen = strlen($a_Buf);
        if ($lLen < 2) {
            return null;
        }

        $lB0 = ord($a_Buf[0]);
        $lB1 = ord($a_Buf[1]);

        $lFin    = ($lB0 & 0x80) !== 0;
        $lOpcode = $lB0 & 0x0F;
        $lMasked = ($lB1 & 0x80) !== 0;
        $lPlen   = $lB1 & 0x7F;

        $lOffset = 2;

        if ($lPlen === 126) {
            if ($lLen < $lOffset + 2) {
                return null;
            }
            $lPlen = unpack('n', substr($a_Buf, $lOffset, 2))[1];
            $lOffset += 2;
        } elseif ($lPlen === 127) {
            if ($lLen < $lOffset + 8) {
                return null;
            }
            $lParts = unpack('N2', substr($a_Buf, $lOffset, 8));
            $lPlen = ($lParts[1] << 32) | $lParts[2];
            $lOffset += 8;
        }

        $lMaskKey = '';
        if ($lMasked) {
            if ($lLen < $lOffset + 4) {
                return null;
            }
            $lMaskKey = substr($a_Buf, $lOffset, 4);
            $lOffset += 4;
        }

        if ($lLen < $lOffset + $lPlen) {
            return null;
        }

        $lPayload = substr($a_Buf, $lOffset, $lPlen);
        $lOffset += $lPlen;

        if ($lMasked) {
            $lUnmasked = '';
            for ($i = 0; $i < $lPlen; $i++) {
                $lUnmasked .= $lPayload[$i] ^ $lMaskKey[$i % 4];
            }
            $lPayload = $lUnmasked;
        }

        if (!$lFin) {
            $this->SendDebug('WS', 'Fragmented frame dropped', 0);
        }

        $lRest = substr($a_Buf, $lOffset);
        return [$lOpcode, $lPayload, $lRest];
    }

    private function wsSendFrame($a_Socket, int $a_Opcode, string $a_Payload): bool
    {
        $lPlen = strlen($a_Payload);

        $lHeader = chr(0x80 | ($a_Opcode & 0x0F));

        // Client→Server MUSS maskieren
        if ($lPlen < 126) {
            $lHeader .= chr(0x80 | $lPlen);
        } elseif ($lPlen < 65536) {
            $lHeader .= chr(0x80 | 126) . pack('n', $lPlen);
        } else {
            $lHeader .= chr(0x80 | 127) . pack('J', $lPlen);
        }

        $lMask = random_bytes(4);
        $lHeader .= $lMask;

        $lMasked = '';
        for ($i = 0; $i < $lPlen; $i++) {
            $lMasked .= $a_Payload[$i] ^ $lMask[$i % 4];
        }

        $lData = $lHeader . $lMasked;
        $lWritten = @fwrite($a_Socket, $lData);
        return $lWritten === strlen($lData);
    }

    // --- Verarbeitung der WS-Payloads -----------------------------------
    //
    // Payload-Format des SysAP (vereinfacht):
    // {
    //   "<sysapGuid>": {
    //     "datapoints": {
    //       "ABB700xxxx/ch0001/odp0000": "1",
    //       ...
    //     },
    //     "devices": { ... },       // optional bei Topologie-Änderungen
    //     "devicesAdded": [ ... ],  // optional
    //     "devicesRemoved": [ ... ] // optional
    //   }
    // }
    //
    // Wir transformieren die "datapoints" ins gleiche Format, das
    // UpdateState() an die Child-Devices schickt, damit die bestehende
    // ReceiveData-Logik im FreeAtHomeDevice unverändert funktioniert.
    private function wsHandleTextPayload(string $a_Json): void
    {
        $this->SendDebug('WS/RX', $a_Json, 0);

        $lData = json_decode($a_Json, true);
        if (!is_array($lData)) {
            return;
        }

        $lGuid = $this->ReadPropertyString('SysAP_GUID');
        if ($lGuid === '' || !isset($lData[$lGuid])) {
            return;
        }

        $lSysap = $lData[$lGuid];

        if (!isset($lSysap['datapoints']) || !is_array($lSysap['datapoints']) || count($lSysap['datapoints']) === 0) {
            return;
        }

        // Datenpunkt-Keys können unterschiedliche Trenner haben, SysAP nutzt
        // meist "/" (device/channel/datapoint). Wir akzeptieren beides.
        $lDataObj = [];
        foreach ($lSysap['datapoints'] as $lKey => $lValue) {
            $lParts = preg_split('#[/.]#', (string) $lKey);
            if ($lParts === false || count($lParts) < 3) {
                continue;
            }
            [$lDeviceId, $lChannel, $lDatapoint] = $lParts;
            $lDataObj[$lDeviceId][$lChannel][$lDatapoint] = $lValue;
        }

        if (count($lDataObj) === 0) {
            return;
        }

        // Für WebSocket-Push gehen wir davon aus, dass die Devices
        // responsive sind (sonst bekämen wir kein Update). DisplayName
        // und unresponsive-Flag werden durch das periodische UpdateState()
        // als Sync-Punkt frisch gehalten.
        foreach ($lDataObj as $lDeviceId => &$lChannels) {
            if (!isset($lChannels['unresponsive'])) {
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

    // --- Socket-Verwaltung (Ressource in statischer Map) -----------------
    private function wsStoreSocket($a_Socket): void
    {
        $lId = uniqid('sock_', true);
        self::$s_wsSockets[$lId] = $a_Socket;
        $this->SetBuffer(self::WS_BUF_SOCKET_ID, $lId);
    }

    private function wsGetSocket()
    {
        $lId = $this->GetBuffer(self::WS_BUF_SOCKET_ID);
        if ($lId === '' || !isset(self::$s_wsSockets[$lId])) {
            return null;
        }
        $lSock = self::$s_wsSockets[$lId];
        if (!is_resource($lSock)) {
            unset(self::$s_wsSockets[$lId]);
            $this->SetBuffer(self::WS_BUF_SOCKET_ID, '');
            return null;
        }
        return $lSock;
    }

    private function wsCloseSocket(): void
    {
        $lId = $this->GetBuffer(self::WS_BUF_SOCKET_ID);
        if ($lId !== '' && isset(self::$s_wsSockets[$lId])) {
            $lSock = self::$s_wsSockets[$lId];
            if (is_resource($lSock)) {
                @$this->wsSendFrame($lSock, 0x8, '');
                @fclose($lSock);
            }
            unset(self::$s_wsSockets[$lId]);
        }
        $this->SetBuffer(self::WS_BUF_SOCKET_ID, '');
        $this->SetBuffer(self::WS_BUF_RX, '');
    }
}
