<?php

declare(strict_types=1);

class FreeAtHomeBridge extends IPSModule
{
    const mSysApId = '00000000-0000-0000-0000-000000000000';

    // Function ID's
    const FID_SWITCH_ACTUATOR                                   = 0x0007;   // A (binary) switch actuator  
    const FID_ROOM_TEMPERATURE_CONTROLLER_MASTER_WITHOUT_FAN    = 0x0023;   // A master room temperature controller that does not include a fan */
    const FID_DIMMING_ACTUATOR                                  = 0x0012;   // A dimming actuator  */
    const FID_RGB_W_ACTUATOR                                    = 0x002E;   // A dimming actuator that can also control the light hue */
    const FID_RGB_ACTUATOR                                      = 0x002F;   // A dimming actuator that can also control the light hue */
    const FID_DES_AUTOMATIC_DOOR_OPENER_ACTUATOR                = 0x0020;   // An automatic door opener */
    const FID_SMOKE_DETECTOR                                    = 0x007D;   // A smoke detector */
    const FID_MOVEMENT_DETECTOR                                 = 0x0011;   // A motion detector */
    const FID_DES_DOOR_OPENER_ACTUATOR                          = 0x001A;   // A door opener */
    const FID_SHUTTER_ACTUATOR                                  = 0x0009;   // A shutter actuator */
    const FID_BLIND_ACTUATOR                                    = 0x0061;   // A roller blind actuator */
    const FID_ATTIC_WINDOW_ACTUATOR                             = 0x0062;   // An attic window actuator  */
    const FID_AWNING_ACTUATOR                                   = 0x0063;   // An awning actuator */
    const FID_WINDOW_DOOR_SENSOR                                = 0x000F;   // A binary door or window sensor */
    const FID_WINDOW_DOOR_POSITION_SENSOR                       = 0x0064;   // A door or window sensor that also reports the door or window position */
    const FID_SWITCH_SENSOR                                     = 0x0000;   // A (binary) switch sensor */
    const FID_DIMMING_SENSOR                                    = 0x0001;   // A dimming sensor */
    const FID_LIGHT_GROUP                                       = 0x4000;   // A light group */
    const FID_SCENE                                             = 0x4800;   // A scene */
    const FID_SPECIAL_SCENE_PANIC                               = 0x4801;   // The special panic scene */
    const FID_SPECIAL_SCENE_ALL_OFF                             = 0x4802;   // The special all-off scene */
    const FID_SPECIAL_SCENE_ALL_BLINDS_UP                       = 0x4803;   // The special all blinds up scene */
    const FID_SPECIAL_SCENE_ALL_BLINDS_DOWN                     = 0x4804;   // The special all blinds down scene */
    const FID_SCENE_SENSOR                                      = 0x0006;   // A scene sensor */
    const FID_STAIRCASE_LIGHT_SENSOR                            = 0x0004;   // A staircase light sensor */
    const FID_TRIGGER                                           = 0x0045;   // A generic trigger */
    const FID_BRIGHTNESS_SENSOR                                 = 0x0041;   // A brightness sensor */
    const FID_TEMPERATURE_SENSOR                                = 0x0043;   // A temperature sensor */
    const FID_RADIATOR_ACTUATOR_MASTER                          = 0x003E;   // A master radiator actuator */
    const FID_DIMMING_SENSOR_ROCKER_TYPE0                       = 0x1010;   // A wireless rocker type dimming sensor */
    const FID_DIMMING_SENSOR_PUSHBUTTON_TYPE2                   = 0x101A;   // A wireless push button type dimming sensor */
    const FID_DIMMING_ACTUATOR_TYPE0                            = 0x1810;   // A wireless dimming actuator */


    const mSupportedFunctionIDs = array(
        self::FID_SWITCH_ACTUATOR,
        self::FID_DIMMING_ACTUATOR_TYPE0);


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
            case 'getLightState':
                $DeviceID = $data->Buffer->DeviceID;
                $result = $this->getLight($DeviceID);
                break;
            case 'getGroupState':
                $DeviceID = $data->Buffer->DeviceID;
                $result = $this->getGroupAttributes($DeviceID);
                break;
            case 'getAllGroups':
                $result = $this->getAllGroups();
                break;
            case 'getGroupAttributes':
                $params = (array) $data->Buffer->Params;
                $result = $this->getGroupAttributes($params['GroupID']);
                break;
            case 'setGroupAttributes':
                $params = (array) $data->Buffer->Params;
                $GroupID = $data->Buffer->GroupID;
                $result = $this->setGroupAttributes($GroupID, $params);
                break;
            case 'createGroup':
                $params = (array) $data->Buffer->Params;
                $result = $this->createGroup($params);
                break;
            case 'deleteGroup':
                $GroupID = $data->Buffer->GroupID;
                $result = $this->deleteGroup($GroupID);
                break;
            case 'getAllSensors':
                $result = $this->getAllSensors();
                break;
            case 'getScenesFromGroup':
                $params = (array) $data->Buffer->Params;
                $result = $this->getAlleScenesFromGroup($params['GroupID']);
                break;
            case 'state':
                $params = (array) $data->Buffer->Params;
                $result = $this->sendRequest( $data->Buffer->Endpoint . '/' . $data->Buffer->DeviceID . '/state', $params, 'PUT');
                break;
            case 'action':
                $params = (array) $data->Buffer->Params;
                $result = $this->sendRequest( $data->Buffer->Endpoint . '/' . $data->Buffer->DeviceID . '/action', $params, 'PUT');
                break;
            case 'config':
                $params = (array) $data->Buffer->Params;
                $result = $this->sendRequest( $data->Buffer->Endpoint . '/' . $data->Buffer->DeviceID . '/config', $params, 'PUT');
                break;
            case 'scanNewDevices':
                $result = $this->scanNewLights();
                break;
            case 'getNewLights':
                $result = $this->getNewLights();
                break;
            case 'getNewSensors':
                $result = $this->getNewSensors();
                break;
            case 'renameDevice':
                $params = (array) $data->Buffer->Params;
                switch ($data->Buffer->DeviceType) {
                    case 'lights':
                        $result = $this->renameLight($data->Buffer->DeviceID, $params);
                        break;
                    case 'sensors':
                        $result = $this->renameSensor($data->Buffer->DeviceID, $params);
                        break;
                    default:
                        $this->SendDebug(__FUNCTION__, 'renameDevice - Invalid DeviceType: ' . $data->Buffer->DeviceType, 0);
                        break;
                }
                break;
            case 'deleteDevice':
                switch ($data->Buffer->DeviceType) {
                    case 'lights':
                        $result = $this->deleteLight($data->Buffer->DeviceID);
                        break;
                    case 'sensors':
                        $result = $this->deleteSensor($data->Buffer->DeviceID);
                        break;
                    default:
                        $this->SendDebug(__FUNCTION__, 'renameDevice - Invalid DeviceType: ' . $data->Buffer->DeviceType, 0);
                        break;
                }
                break;
            default:
                $this->SendDebug(__FUNCTION__, 'Invalid Command: ' . $data->Buffer->Command, 0);
                break;
        }
        $this->SendDebug(__FUNCTION__, json_encode($result), 0);
        return json_encode($result);
    }

    public function UpdateState()
    {
        $this->SendDebug(__FUNCTION__ , 'update SysAP States', 0);

        return;

        $Data['DataID'] = '{7CF9826D-7E05-C7A2-1B73-32CC11F80D2E}';

        $Buffer['Lights'] = $this->getAllLights();
        $Buffer['Groups'] = $this->getAllGroups();
        $Buffer['Sensors'] = $this->getAllSensors();

        $Data['Buffer'] = json_encode($Buffer);

        $Data = json_encode($Data);
        $this->SendDataToChildren($Data);
    }

    private function FilterSupportedDevices( $a_Devices )
    {
        $lRetValue = new stdClass();

        foreach($a_Devices as $lDeviceId => $DeviceValue)
        {        
            $lAddToList = false;
            if( isset($DeviceValue->channels ) )
            {
                foreach($DeviceValue->channels as $lChannelNr => $lChannelValue)
                {
                    if( isset($lChannelValue->functionID )  )
                    {
                        $lFunctionId = hexdec( $lChannelValue->functionID );
                        if( in_array($lFunctionId, self::mSupportedFunctionIDs ) )
                        {
                            $lAddToList = true;
                            break;
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
        $lResult = $this->sendRequest( 'configuration' );
        return $this->FilterSupportedDevices( $lResult->{self::mSysApId}->devices );
    }

    //Functions for Lights

    public function getAllLights()
    {
        return $this->sendRequest( 'lights', [], 'GET');
    }

    //Functions for Scenes

    public function getAllScenes()
    {
        return $this->sendRequest( 'scenes', [], 'GET');
    }

    private function sendRequest( string $endpoint, array $params = [], string $method = 'GET')
    {
        if ($this->ReadPropertyString('Host') == '') {
            return false;
        }
        if ($this->ReadPropertyString('Username') == '') {
            return false;
        }
        if ($this->ReadPropertyString('Password') == '') {
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
//           if (in_array($method, ['PUT', 'DELETE'])) {
//               curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
//           }
//           curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
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

    private function getNewLights()
    {
        return $this->sendRequest( 'lights/new', [], 'GET');
    }

    private function scanNewLights()
    {
        $params['deviceid'] = [];
        return $this->sendRequest( 'lights', $params, 'POST');
    }

    private function getLight($id)
    {
        return $this->sendRequest( 'lights/' . $id, [], 'GET');
    }

    private function renameLight($id, $params)
    {
        return $this->sendRequest( 'lights/' . $id, $params, 'PUT');
    }

    private function setLightState($id, $state)
    {
        return $this->sendRequest( 'lights/' . $id, $state, 'PUT');
    }

    private function deleteLight($id)
    {
        return $this->sendRequest( 'lights/' . $id, [], 'DELETE');
    }

    //Functions for Sensors

    private function getAllSensors()
    {
        return $this->sendRequest( 'sensors', [], 'GET');
    }

    private function getNewSensors()
    {
        return $this->sendRequest( 'sensors/new', [], 'GET');
    }

    private function renameSensor($id, $params)
    {
        return $this->sendRequest( 'sensors/' . $id, $params, 'PUT');
    }

    private function deleteSensor($id)
    {
        return $this->sendRequest( 'sensors/' . $id, [], 'DELETE');
    }

    //Functions for Groups

    private function getAllGroups()
    {
        return $this->sendRequest( 'groups', [], 'GET');
    }

    private function getGroupAttributes($id)
    {
        return $this->sendRequest( 'groups/' . $id, [], 'GET');
    }

    private function setGroupAttributes($id, $params)
    {
        return $this->sendRequest( 'groups/' . $id, $params, 'PUT');
    }

    private function createGroup($params)
    {
        return $this->sendRequest( 'groups', $params, 'POST');
    }

    private function deleteGroup($id)
    {
        return $this->sendRequest( 'groups/' . $id, [], 'DELETE');
    }

    //Functions for Schedules

    private function getAllSchedules()
    {
        return $this->sendRequest( 'schedules', [], 'GET');
    }

    private function getAlleScenesFromGroup($GroupID)
    {
        $AllScenes = $this->getAllScenes();
        $GroupScenes = [];

        foreach ($AllScenes as $key => $scene) {
            if ($scene->type == 'GroupScene') {
                if ($scene->group == $GroupID) {
                    $GroupScenes[$key] = $scene;
                }
            }
        }
        return $GroupScenes;
    }

    //Functions for Rules

    private function getAllRules()
    {
        return $this->sendRequest( 'rules', $params, 'GET');
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
