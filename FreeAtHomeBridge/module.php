<?php

declare(strict_types=1);
require_once __DIR__ . '/../libs/FunctionID.php';
require_once __DIR__ . '/../libs/PairingID.php';

class FreeAtHomeBridge extends IPSModule
{
    const mBridgeDataId     = '{BC9334EC-8C5C-61C2-C5DD-96FE9368F38D}';      // DatenId der Bridge
    const mDeviceModuleId   = '{BDE4603B-E68A-D3AF-2510-9462C7374097}';      // Device Modul Id 
    const mChildId          = '{7E471B91-3407-F7EE-347B-64B459E33D76}';      // Child Id 

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
  
        $this->RegisterAttributeString('SysAPName', '');
        $this->RegisterAttributeString('SysAPFirmware', '');
        $this->RegisterAttributeString('SysAP_GUID', '');
      
        $this->RegisterTimer('FAHBR_UpdateState', 0, 'FAHBR_UpdateState($_IPS[\'TARGET\']);');
     }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

        if (!$this->BridgeConnected()) {
            $this->SetStatus(200);

            $this->LogMessage('Error: Loggin incomplete, please fill in correct informations for SysAP.', KL_ERROR);
            $this->SetTimerInterval('FAHBR_UpdateState', 0);
            return;
        }
        $this->SetTimerInterval('FAHBR_UpdateState', $this->ReadPropertyInteger('UpdateInterval') * 1000);
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
            if( isset($lDevices->{$lRequestArray[0]}))
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
                    if( isset($lChannelValue->functionID )  )
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

        $url = "http://{$host}/fhapi/v1/api/rest/{$endpoint}";
        $this->SendDebug(__FUNCTION__ . ' URL', $url, 0);

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);


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
}
