<?php

declare(strict_types=1);
require_once __DIR__ . '/../libs/ColorHelper.php';
require_once __DIR__ . '/../libs/FunctionID.php';
require_once __DIR__ . '/../libs/PairingID.php';

class FreeAtHomeDevice extends IPSModule
{
    use ColorHelper;
    const mBridgeDataId     = '{BC9334EC-8C5C-61C2-C5DD-96FE9368F38D}';      // DatenId der Bridge
    const mDeviceModuleId   = '{BDE4603B-E68A-D3AF-2510-9462C7374097}';      // Device Modul Id 
    const mParentId         = '{9AFFB383-D756-8422-BCA0-EFD3BB1E3E29}';      // Parent Id (Bridge)

    public function Create()
    {
        //Never delete this line!
        parent::Create();
        $this->ConnectParent(self::mParentId);
        $this->RegisterPropertyString('FAHDeviceID', '');
        $this->RegisterPropertyString('DeviceType', '');
        $this->RegisterPropertyString('Channel', '');
        $this->RegisterPropertyString('Inputs', '');
        $this->RegisterPropertyString('Outputs', '');
 
        $this->RegisterAttributeString('DeviceType', '');
    }

    public function HasActionInput( string $a_Action )
    {
        // Variablen für alle Outputs (des Devises) anlegen
        $lInputs = json_decode( $this->ReadPropertyString('Inputs') );

        $lActionPairingId = PID::GetID($a_Action);

        foreach( $lInputs as $lOdp => $lPairingId  )
        {
            if( $lActionPairingId == $lPairingId )
            {
                return true;
            }
        }
        return false;
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();
        if ($this->ReadPropertyString('DeviceType') == '') {
            return;
        }

        // Variablen für alle Outputs (des Devises) anlegen
        $lOutputs = json_decode( $this->ReadPropertyString('Outputs') );
 
        foreach( $lOutputs as $lOdp => $lPairingId  )
        {
            $lPIDName = PID::GetName($lPairingId);
            IPS_LogMessage( $this->InstanceID, __FUNCTION__.' '.$lOdp.":".$lPairingId.' - '.$lPIDName );
            $this->MaintainVariable(
                $lPIDName, 
                $this->Translate(PID::GetInfo($lPairingId)), 
                PID::GetType($lPairingId), PID::GetProfile($lPairingId), 
                0, true );          
            // hat die Pairing ID ein Action Item
            $Action = PID::GetAction($lPairingId);
            // Prüfe ob das Action Item in den Inputs enthalten ist
            $this->MaintainAction( $lPIDName, $this->HasActionInput($Action) );    
        }



        if(0)
        {
            //Sensors
            $sensor = $this->ReadPropertyString('DeviceType') == 'sensors';
            $this->MaintainVariable('HUE_Battery', $this->Translate('Battery'), 1, '~Battery.100', 0, $sensor == true);

            //Presence
            $Presence = false;
    
            $this->MaintainVariable('HUE_Presence', $this->Translate('Presence'), 0, '~Presence', 0, $Presence == true && $sensor == true);
            //$this->MaintainVariable('HUE_Presence', $this->Translate('Presence'), 0, '~Presence', 0, ($this->ReadPropertyString('SensorType') == 'ZLLPresence' || $this->ReadPropertyString('SensorType') == 'CLIPPresence') && $sensor == 'sensors');
            //$this->MaintainVariable('HUE_PresenceState', $this->Translate('Sensor State'), 0, '~Switch', 0, $this->ReadPropertyString('SensorType') == 'ZLLPresence' && $this->ReadPropertyString('DeviceType') == 'sensors');
            $this->MaintainVariable('HUE_PresenceState', $this->Translate('Sensor State'), 0, '~Switch', 0, $Presence == true && $sensor == true);

            if ($Presence == true && $sensor == true) {
                $this->EnableAction('HUE_PresenceState');
            }

            $this->MaintainVariable('HUE_CLIPGenericState', $this->Translate('State'), 0, '~Switch', 0, $this->ReadPropertyString('SensorType') == 'CLIPGenericStatus' && $sensor == true);
            if ($this->ReadPropertyString('SensorType') == 'CLIPGenericStatus' && $this->ReadPropertyString('DeviceType') == 'sensors') {
                $this->EnableAction('HUE_CLIPGenericState');
            }

            $this->MaintainVariable('HUE_Lightlevel', $this->Translate('Lightlevel'), 1, '~Illumination', 0, $this->ReadPropertyString('SensorType') == 'ZLLLightLevel' && $sensor == true);
            $this->MaintainVariable('HUE_Dark', $this->Translate('Dark'), 0, '', 0, $this->ReadPropertyString('SensorType') == 'ZLLLightLevel' && $sensor == true);
            $this->MaintainVariable('HUE_Daylight', $this->Translate('Daylight'), 0, '', 0, ($this->ReadPropertyString('SensorType') == 'ZLLLightLevel') || ($this->ReadPropertyString('SensorType') == 'Daylight') && $sensor == true);

            $this->MaintainVariable('HUE_Temperature', $this->Translate('Temperature'), 2, '~Temperature', 0, $this->ReadPropertyString('SensorType') == 'ZLLTemperature' && $sensor == true);

            $this->MaintainVariable('HUE_Buttonevent', $this->Translate('Buttonevent'), 1, '', 0, ($this->ReadPropertyString('SensorType') == 'ZGPSwitch' || $this->ReadPropertyString('SensorType') == 'ZLLSwitch') && $sensor == true);

            //Lights and Groups
            $this->MaintainVariable('HUE_ColorMode', $this->Translate('Color Mode'), 1, 'HUE.ColorMode', 0, ($this->ReadPropertyString('DeviceType') == 'lights' || $this->ReadPropertyString('DeviceType') == 'groups') && $this->ReadPropertyBoolean('ColorModeActive') == true);

            $this->MaintainVariable('HUE_State', $this->Translate('State'), 0, '~Switch', 0, $this->ReadPropertyString('DeviceType') == 'lights' || $this->ReadPropertyString('DeviceType') == 'groups' || $this->ReadPropertyString('DeviceType') == 'plugs');

            $this->MaintainVariable('HUE_Brightness', $this->Translate('Brightness'), 1, 'HUE.Intensity', 0, $this->ReadPropertyString('DeviceType') == 'lights' || $this->ReadPropertyString('DeviceType') == 'groups');

            $this->MaintainVariable('HUE_Color', $this->Translate('Color'), 1, 'HexColor', 0, ($this->ReadPropertyString('DeviceType') == 'lights' || $this->ReadPropertyString('DeviceType') == 'groups') && $this->ReadPropertyBoolean('ColorActive') == true);

            $this->MaintainVariable('HUE_Saturation', $this->Translate('Saturation'), 1, 'HUE.Intensity', 0, ($this->ReadPropertyString('DeviceType') == 'lights' || $this->ReadPropertyString('DeviceType') == 'groups') && $this->ReadPropertyBoolean('SaturationActive') == true);

            $this->MaintainVariable('HUE_ColorTemperature', $this->Translate('Color Temperature'), 1, 'HUE.ColorTemperature', 0, $this->ReadPropertyString('DeviceType') == 'lights' || $this->ReadPropertyString('DeviceType') == 'groups');
            $this->MaintainVariable('HUE_ColorTemperatureKelvin', $this->Translate('Color Temperature Kelvin'), 1, 'HUE.ColorTemperatureKelvin', 0, ($this->ReadPropertyString('DeviceType') == 'lights' || $this->ReadPropertyString('DeviceType') == 'groups') && $this->ReadPropertyBoolean('KelvinActive') == true);

            //Groups
            $ParentID = IPS_GetInstance($this->InstanceID)['ConnectionID'];
            $this->MaintainVariable('HUE_GroupScenes', $this->Translate('Scenes'), 1, 'HUE.GroupScene' . $ParentID . '_' . $this->ReadPropertyString('FAHDeviceID'), 0, $this->ReadPropertyString('DeviceType') == 'groups');

            if ($this->ReadPropertyString('DeviceType') == 'lights' || $this->ReadPropertyString('DeviceType') == 'groups') {
                if ($this->ReadPropertyBoolean('ColorModeActive')) {
                    $this->EnableAction('HUE_ColorMode');
                }
                $this->EnableAction('HUE_State');
                $this->EnableAction('HUE_Brightness');
                if ($this->ReadPropertyBoolean('ColorActive')) {
                    $this->EnableAction('HUE_Color');
                }
                if ($this->ReadPropertyBoolean('SaturationActive')) {
                    $this->EnableAction('HUE_Saturation');
                }
                $this->EnableAction('HUE_ColorTemperature');
                $this->EnableAction('HUE_ColorTemperatureKelvin');

                if (@$this->GetIDForIdent('HUE_ColorMode') != false) {
                    $ColorMode = GetValue(IPS_GetObjectIDByIdent('HUE_ColorMode', $this->InstanceID));
                    $this->hideVariables($ColorMode);
                }
            }

            if ($this->ReadPropertyString('DeviceType') == 'groups') {
                SetValue($this->GetIDForIdent('HUE_GroupScenes'), -1);
                $this->EnableAction('HUE_GroupScenes');
            }

            //Reachable for Lights and Sensors
            if ($this->ReadPropertyString('DeviceType') == 'lights' || $this->ReadPropertyString('DeviceType') == 'plugs' || ($this->ReadPropertyString('DeviceType') == 'sensors' && $this->ReadPropertyString('SensorType') != 'ZGPSwitch' && $this->ReadPropertyString('SensorType') != 'Daylight')) {
                $CreateVariableReachable = true;
            } else {
                $CreateVariableReachable = false;
            }
            $this->MaintainVariable('HUE_Reachable', $this->Translate('Reachable'), 0, 'HUE.Reachable', 0, $CreateVariableReachable);
        }
    }

    public function GetConfigurationForm()
    {
        $jsonForm = json_decode(file_get_contents(__DIR__ . '/form.json'), true);

        return json_encode($jsonForm);
    }

    public function ReceiveData($JSONString)
    {
        $lDeviceID = $this->ReadPropertyString('FAHDeviceID');
        $lData = json_decode($JSONString );
        $lDataObj = json_decode($lData->Buffer );

        // Daten für dieses Device dabei
        if(!isset( $lDataObj->{$lDeviceID} ))
        {
            return;
        }

        // Daten empfangen
        $this->SendDebug(__FUNCTION__, json_encode($lDataObj->{$lDeviceID}), 0);

        $lChannel = $this->ReadPropertyString('Channel');
        $lOutputs = json_decode( $this->ReadPropertyString('Outputs') );

        foreach( $lOutputs as $lDatapoint => $lPairingID  )
        {
            if( isset( $lDataObj->{$lDeviceID}->{$lChannel} ))
            {
                $lChannelData = $lDataObj->{$lDeviceID}->{$lChannel};

                foreach( $lChannelData as $lDP => $lValue )
                {
                    if( $lDP == $lDatapoint )
                    {
                        $lValueId = PID::GetName( $lPairingID );

                        $this->SendDebug(__FUNCTION__ , $lValueId.' => '.$lValue, 0);
                        $this->SetValue($lValueId, $lValue);
                    }
                }
            }
        }                  
    }

    public function Request(array $Value)
    {
        if ($this->ReadPropertyString('DeviceType') == 'groups') {
            $command = 'action';
        } else {
            $command = 'state';
        }
        return $this->sendData($command, $Value);
    }


    public function SwitchMode(bool $Value)
    {
        if ($this->ReadPropertyString('DeviceType') == 'groups') {
            $command = 'action';
        } else {
            $command = 'state';
        }

        $params = ['on' => $Value];
        return $this->sendData($command, $params);
    }

    public function DimSet(int $Value)
    {
        if ($this->ReadPropertyString('DeviceType') == 'groups') {
            $command = 'action';
        } else {
            $command = 'state';
        }

        if ($Value <= 0) {
            $params = ['on' => false];
        } else {
            $params = ['bri' => $Value, 'on' => true];
        }
        return $this->sendData($command, $params);
    }

    public function ColorSet($Value)
    {
        if ($this->ReadPropertyString('DeviceType') == 'groups') {
            $command = 'action';
        } else {
            $command = 'state';
        }

        //If $Value Hex Color convert to Decimal
        if (preg_match('/^#[a-f0-9]{6}$/i', strval($Value))) {
            $Value = ltrim($Value, '#');
            $Value = hexdec($Value);
        }

        $this->SendDebug(__FUNCTION__, $Value, 0);

        $rgb = $this->decToRGB($Value);

        $ConvertedXY = $this->convertRGBToXY($rgb['r'], $rgb['g'], $rgb['b']);
        $xy[0] = $ConvertedXY['x'];
        $xy[1] = $ConvertedXY['y'];

        $params = ['bri' => $ConvertedXY['bri'], 'xy' => $xy, 'on' => true];

        return $this->sendData($command, $params);
    }

    public function ColorSetOpt($Value, array $OptParams = null)
    {
        if ($this->ReadPropertyString('DeviceType') == 'groups') {
            $command = 'action';
        } else {
            $command = 'state';
        }

        //If $Value Hex Color convert to Decimal
        if (preg_match('/^#[a-f0-9]{6}$/i', strval($Value))) {
            $Value = ltrim($Value['hex'], '#');
            $Value = hexdec($Value);
        }

        $this->SendDebug(__FUNCTION__, $Value, 0);

        $rgb = $this->decToRGB($Value);

        $ConvertedXY = $this->convertRGBToXY($rgb['r'], $rgb['g'], $rgb['b']);
        $xy[0] = $ConvertedXY['x'];
        $xy[1] = $ConvertedXY['y'];

        $params = ['bri' => $ConvertedXY['bri'], 'xy' => $xy, 'on' => true];
        $params = array_merge($params, $OptParams);
        return $this->sendData($command, $params);
    }

    public function ColorSetHSB($HUE, $Saturation, $Brightness)
    {
        if ($this->ReadPropertyString('DeviceType') == 'groups') {
            $command = 'action';
        } else {
            $command = 'state';
        }

        $ConvertedHUE = $this->HUEConvertToHSB($HUE);
        $this->SendDebug('ColorSetHSB :: Values', 'HUE: ' . $HUE . 'HUE Converted: ' . $ConvertedHUE . ' Saturation: ' . $Saturation . ' Brightness: ' . $Brightness, 0);

        $params = ['sat' => $Saturation, 'bri' => $Brightness, 'hue'=> $ConvertedHUE, 'on' => true];
        return $this->sendData($command, $params);
    }

    public function SatSet(int $Value)
    {
        if ($this->ReadPropertyString('DeviceType') == 'groups') {
            $command = 'action';
        } else {
            $command = 'state';
        }

        $params = ['sat' => $Value];
        return $this->sendData($command, $params);
    }

    public function CTSet(int $Value)
    {
        if ($this->ReadPropertyString('DeviceType') == 'groups') {
            $command = 'action';
        } else {
            $command = 'state';
        }

        $params = ['ct' => $Value];
        return $this->sendData($command, $params);
    }

    public function SceneSet(string $Value)
    {
        $scenes = json_decode($this->ReadAttributeString('Scenes'), true);
        foreach ($scenes as $key => $scene) {
            if ($scene['name'] == $Value) {
                $this->SceneSetKey($scene['key']);
                return;
            }
        }
        $this->LogMessage('Scene Name (' . $Value . ') for Group ' . $this->ReadPropertyString('FAHDeviceID') . ' invalid', 10204);
    }

    public function SceneSetEx(string $Value, array $params)
    {
        $scenes = json_decode($this->ReadAttributeString('Scenes'), true);
        foreach ($scenes as $key => $scene) {
            if ($scene['name'] == $Value) {
                $this->SceneSetKeyEx($scene['key'], $params);
                return;
            }
        }
        $this->LogMessage('Scene Name (' . $Value . ') for Group ' . $this->ReadPropertyString('FAHDeviceID') . ' invalid', 10204);
    }

    public function AlertSet(string $Value)
    {
        if ($this->ReadPropertyString('DeviceType') == 'groups') {
            $command = 'action';
        } else {
            $command = 'state';
        }

        $params = ['alert' => $Value];
        return $this->sendData($command, $params);
    }

    public function EffectSet(string $Value)
    {
        if ($this->ReadPropertyString('DeviceType') == 'groups') {
            $command = 'action';
        } else {
            $command = 'state';
        }

        $params = ['effect' => $Value];
        return $this->sendData($command, $params);
    }

    public function GetState()
    {
        $params = [];
        if ($this->ReadPropertyString('DeviceType') == 'groups') {
            $command = 'getGroupState';
            $result = $this->sendData($command, $params)['action']['on'];
        } else {
            $command = 'getLightState';
            $result = $this->sendData($command, $params)['state']['on'];
        }
        return $result;
    }

    public function GetStateExt()
    {
        $params = [];
        if ($this->ReadPropertyString('DeviceType') == 'groups') {
            $command = 'getGroupState';
            $result = $this->sendData($command, $params)['action'];
        } else {
            $command = 'getLightState';
            $result = $this->sendData($command, $params)['state'];
        }
        return $result;
    }

    public function SensorStateSet(bool $Value)
    {
        $command = 'config';
        $params = ['on' => $Value];
        return $this->sendData($command, $params);
    }

    public function CLIPSensorStateSet(bool $Value)
    {
        $command = 'state';
        $params = ['status' => intval($Value)];
        return $this->sendData($command, $params);
    }

    public function RequestAction($Ident, $Value)
    {
     
        // Daten empfangen
        $this->SendDebug(__FUNCTION__, $Ident.' => '.$Value, 0);

        $lbPollData=false;
        $lSettings = PID::GetSettings($Ident);
        if( isset($lSettings['action']) )
        {
            $lActionPID = PID::GetID( $lSettings['action'] );
            // Variablen für alle Outputs (des Devises) anlegen
            $lInputs = json_decode( $this->ReadPropertyString('Inputs') );

            foreach( $lInputs as $lDatapoint => $lPairingId  )
            {
                if( $lActionPID == $lPairingId )
                {
                   $lDeviceID = $this->ReadPropertyString('FAHDeviceID');
                   $lChannel = $this->ReadPropertyString('Channel');

                   $lDataType = $lSettings['type'];

                   $SendValue = strval( $Value);
                   if( $lDataType == 0 )
                   {
                        if( $Value == true)
                        {
                            $SendValue = '1'; 
                        }
                        else
                        {
                            $SendValue = '0'; 
                        }
                   }     

                   IPS_LogMessage( $this->InstanceID, __FUNCTION__.'('.__LINE__.")" );
                   // PUT Datapoint Value
                   // {$lDeviceID}.{$lChannel}.{$lDatapoint}
                   $lSendData = [ 'datapoint' => $lDatapoint, 'value' => $SendValue ];
                   $lResult = $this->sendData('setDatapoint', json_encode($lSendData) );
                   $this->SendDebug(__FUNCTION__,json_encode($lResult) );
                   IPS_LogMessage( $this->InstanceID, __FUNCTION__.'('.__LINE__.")" );

                   $lbPollData = true;
                }
            }

            IPS_LogMessage( $this->InstanceID, __FUNCTION__.'('.__LINE__.")" );
            if( $lbPollData )
            {
                IPS_LogMessage( $this->InstanceID, __FUNCTION__.'('.__LINE__.")" );
                $this->SendDebug(__FUNCTION__,'update data' );

                $lResult = $this->sendData('getDevice' );
                $this->SendDebug(__FUNCTION__,json_encode($lResult) );
            }

        }



        return;


        switch ($Ident) {
            case 'HUE_State':
                $result = $this->SwitchMode($Value);
                if (array_key_exists('success', $result[0])) {
                    $this->SetValue($Ident, $Value);
                }
                break;
            case 'HUE_Brightness':
                $result = $this->DimSet($Value);

                if ($Value <= 0) {
                    $this->SwitchMode(false);
                    $this->SetValue('HUE_State', false);
                    return;
                }
                if (array_key_exists('success', $result[0])) {
                    $this->SetValue('HUE_State', true);
                }
                if (array_key_exists('success', $result[1])) {
                    $this->SetValue($Ident, $Value);
                }
                break;
            case 'HUE_Color':
                $result = $this->ColorSet($Value);
                if (array_key_exists('success', $result[0])) {
                    $this->SetValue('HUE_State', true);
                }

                if ($this->ReadPropertyString('DeviceType') == 'groups') {
                    //If DeviceType Group Key 1 is Brightness
                    if (array_key_exists('success', $result[1])) {
                        $this->SetValue('HUE_Brightness', $result[1]['success']['/groups/' . $this->ReadPropertyString('FAHDeviceID') . '/action/bri']);
                    }
                    //If DeviceType is Group Key 2 is Color
                    if (array_key_exists('success', $result[2])) {
                        $this->SetValue($Ident, $Value);
                    }
                } elseif (($this->ReadPropertyString('DeviceType') == 'lights') || ($this->ReadPropertyString('DeviceType') == 'plugs')) {
                    //If DeviceType is Lights Key 1 is Color
                    if (array_key_exists('success', $result[1])) {
                        $this->SetValue($Ident, $Value);
                    }
                    //If DeviceType is Lights Key 2 is Brightness
                    if (array_key_exists('success', $result[2])) {
                        $this->SetValue('HUE_Brightness', $result[2]['success']['/lights/' . $this->ReadPropertyString('FAHDeviceID') . '/state/bri']);
                    }
                }
                break;
            case 'HUE_Saturation':
                $result = $this->SatSet($Value);

                if (array_key_exists('success', $result[0])) {
                    $this->SetValue($Ident, $Value);
                }
                break;
            case 'HUE_ColorTemperature':
                $result = $this->CTSet($Value);
                if (array_key_exists('success', $result[0])) {
                    $this->SetValue($Ident, $Value);
                    if ($this->ReadPropertyBoolean('KelvinActive')) {
                        $this->SetValue('HUE_ColorTemperatureKelvin', intval(round(1000000 / $Value, 0)));
                    }
                }
                break;
            case 'HUE_ColorTemperatureKelvin':
                $result = $this->CTSet(intval(round(1000000 / $Value, 0)));
                if (array_key_exists('success', $result[0])) {
                    $this->SetValue($Ident, $Value);
                    $this->SetValue('HUE_ColorTemperature', intval(round(1000000 / $Value, 0)));
                }
                break;
            case 'HUE_ColorMode':
                $this->hideVariables($Value);
                switch ($Value) {
                    case 0: //Color
                        $result = $this->ColorSet($this->GetValue('HUE_Color'));
                        if (array_key_exists('success', $result[0])) {
                            $this->SetValue('HUE_State', true);
                        }
                        break;
                    case 1: //Color temperature
                        $result = $this->CTSet($this->GetValue('HUE_ColorTemperature'));
                        if (array_key_exists('success', $result[0])) {
                            $this->SetValue($Ident, $Value);
                        }
                }
                $this->SetValue($Ident, $Value);
                break;
            case 'HUE_GroupScenes':
                $scenes = json_decode($this->ReadAttributeString('Scenes'), true);
                $this->SendDebug(__FUNCTION__ . ' Scene Value', $scenes[$Value]['name'], 0);
                $this->SceneSetKey($scenes[$Value]['key']);
                break;
            case 'HUE_PresenceState':
                    $result = $this->SensorStateSet($Value);
                    if (array_key_exists('success', $result[0])) {
                        $this->SetValue($Ident, $Value);
                    }
                    break;
            case 'HUE_CLIPGenericState':
                $result = $this->CLIPSensorStateSet($Value);
                if (array_key_exists('success', $result[0])) {
                    $this->SetValue($Ident, $Value);
                }
                break;
            default:
                $this->SendDebug(__FUNCTION__, 'Invalid Ident', 0);
                break;
        }
    }

    public function ReloadConfigurationFormDeviceType(string $DeviceType)
    {
        $this->WriteAttributeString('DeviceType', $DeviceType);
        if ($DeviceType == 'sensors') {
            $this->UpdateFormField('SensorType', 'visible', true);
        } else {
            $this->UpdateFormField('SensorType', 'visible', false);
        }
        if ($DeviceType == 'lights' || $DeviceType == 'groups') {
            $this->UpdateFormField('ColorModeActive', 'visible', true);
            $this->UpdateFormField('ColorActive', 'visible', true);
            $this->UpdateFormField('SaturationActive', 'visible', true);
            $this->UpdateFormField('KelvinActive', 'visible', true);
        } else {
            $this->UpdateFormField('ColorModeActive', 'visible', false);
            $this->UpdateFormField('ColorActive', 'visible', false);
            $this->UpdateFormField('SaturationActive', 'visible', false);
            $this->UpdateFormField('KelvinActive', 'visible', false);
        }
    }

    public function UpdateSceneProfile()
    {
        $Object = IPS_GetObject($this->InstanceID);
        if ($this->ReadPropertyString('DeviceType') == 'groups') {
            //TODO Map Profile to Attribute
            $scenes = $this->sendData('getScenesFromGroup', ['GroupID' => $this->ReadPropertyString('FAHDeviceID')]);
            $ParentID = IPS_GetInstance($this->InstanceID)['ConnectionID'];
            $ProfileName = 'HUE.GroupScene' . $ParentID . '_' . $this->ReadPropertyString('FAHDeviceID');
            if (!IPS_VariableProfileExists($ProfileName)) {
                IPS_CreateVariableProfile($ProfileName, 1);
            } else {
                if (!empty($scenes)) {
                    IPS_DeleteVariableProfile($ProfileName);
                    IPS_CreateVariableProfile($ProfileName, 1);
                }
            }

            $scenesAttribute = [];
            //$this->WriteAttributeString('Scenes',json_encode($scenes));
            $countScene = 0;
            foreach ($scenes as $key => $scene) {
                IPS_SetVariableProfileAssociation($ProfileName, $countScene, $scene['name'], '', 0x000000);
                $scenesAttribute[$countScene]['name'] = $scene['name'];
                $scenesAttribute[$countScene]['key'] = $key;
                $countScene++;
            }
            IPS_SetVariableProfileIcon($ProfileName, 'Database');
            if (!empty($scenesAttribute)) {
                $this->WriteAttributeString('Scenes', json_encode($scenesAttribute));
            }
            $this->MaintainVariable('HUE_GroupScenes', $this->Translate('Scenes'), 1, 'HUE.GroupScene' . $ParentID . '_' . $this->ReadPropertyString('FAHDeviceID'), 0, $this->ReadPropertyString('DeviceType') == 'groups');
        }
    }

    private function SceneSetKey(string $Value)
    {
        $params = ['scene' => $Value];
        return $this->sendData('action', $params);
    }

    private function SceneSetKeyEx(string $Value, $params)
    {
        $params['scene'] = $Value;
        return $this->sendData('action', $params);
    }

    private function hideVariables($Value)
    {
        switch ($Value) {
            case 0:
                IPS_SetHidden($this->GetIDForIdent('HUE_Saturation'), true);
                IPS_SetHidden($this->GetIDForIdent('HUE_ColorTemperature'), true);
                IPS_SetHidden($this->GetIDForIdent('HUE_ColorTemperatureKelvin'), true);

                IPS_SetHidden($this->GetIDForIdent('HUE_Color'), false);
                break;
            case 1:
                IPS_SetHidden($this->GetIDForIdent('HUE_Color'), true);

                IPS_SetHidden($this->GetIDForIdent('HUE_Saturation'), false);
                IPS_SetHidden($this->GetIDForIdent('HUE_ColorTemperature'), false);
                IPS_SetHidden($this->GetIDForIdent('HUE_ColorTemperatureKelvin'), false);
                break;
            default:
                $this->SendDebug(__FUNCTION__, 'Invalid Color Mode: ' . $Value, 0);
                break;
        }
    }

    private function sendData(string $command, $params = '')
    {
        $lDeviceID = $this->ReadPropertyString('FAHDeviceID');
        $lChannel = $this->ReadPropertyString('Channel');

        $Data['DataID'] = self::mBridgeDataId;
        $Buffer['Command'] = $command;
        $Buffer['DeviceID'] = $lDeviceID;
        $Buffer['Channel'] = $lChannel;
        $Buffer['Params'] = $params;
        $Data['Buffer'] = $Buffer;
        $Data = json_encode($Data);

        if (!$this->HasActiveParent()) {
            return [];
        }

        $this->SendDebug(__FUNCTION__, $Data, 0);
        $result = $this->SendDataToParent($Data);
        $this->SendDebug(__FUNCTION__, $result, 0);

        if (!$result) {
            return [];
        }
        $Data = json_decode($result, true);
        return $Data;
    }
}
