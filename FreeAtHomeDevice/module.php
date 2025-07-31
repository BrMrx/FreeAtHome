<?php

declare(strict_types=1);
require_once __DIR__ . '/../libs/FunctionID.php';
require_once __DIR__ . '/../libs/PairingID.php';

class FreeAtHomeDevice extends IPSModule
{
    const mBridgeDataId     = '{BC9334EC-8C5C-61C2-C5DD-96FE9368F38D}';      // DatenId der Bridge
    const mDeviceModuleId   = '{BDE4603B-E68A-D3AF-2510-9462C7374097}';      // Device Modul Id 
    const mParentId         = '{9AFFB383-D756-8422-BCA0-EFD3BB1E3E29}';      // Parent Id (Bridge)
    const mChildId          = '{7E471B91-3407-F7EE-347B-64B459E33D76}';      // Child Id 

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
        $this->RegisterPropertyInteger('Lin25', 25 );
        $this->RegisterPropertyInteger('Lin50', 50 );
        $this->RegisterPropertyInteger('Lin75', 75 );

 
        $this->RegisterAttributeString('DeviceType', '');

        // Wind Grenzwert Profil
        if (!IPS_VariableProfileExists('FAH.WindAlarm')) {
            IPS_CreateVariableProfile('FAH.WindAlarm', 0);
        }
		IPS_SetVariableProfileIcon('FAH.WindAlarm', 'wind' );
        IPS_SetVariableProfileAssociation('FAH.WindAlarm', false,   $this->translate('Below Limit'), 'wind', -1 );
        IPS_SetVariableProfileAssociation('FAH.WindAlarm', true,    $this->translate('Limit exceeded'), 'wind-warning', 0xE72D2E);
 
        // Frost Grenzwert Profil
        if (!IPS_VariableProfileExists('FAH.FrostAlarm')) {
            IPS_CreateVariableProfile('FAH.FrostAlarm', 0);
        }
		IPS_SetVariableProfileIcon('FAH.FrostAlarm', 'snowflake' );
        IPS_SetVariableProfileAssociation('FAH.FrostAlarm', false,   $this->translate('Above Limit'),   'snowflake', -1 );
        IPS_SetVariableProfileAssociation('FAH.FrostAlarm', true,    $this->translate('Limit undercut'), 'temperature-snow', 0xB5EBF5);
 
       // Helligkeit Grenzwert Profil
        if (!IPS_VariableProfileExists('FAH.IlluminationAlert')) {
            IPS_CreateVariableProfile('FAH.IlluminationAlert', 0);
        }
		IPS_SetVariableProfileIcon('FAH.IlluminationAlert', 'snowflake' );
        IPS_SetVariableProfileAssociation('FAH.IlluminationAlert', false,   $this->translate('Below Limit'),   'brightness-low', -1 );
        IPS_SetVariableProfileAssociation('FAH.IlluminationAlert', true,    $this->translate('Limit exceeded'), 'brightness', 0xFFD53D);
 
        if (!IPS_VariableProfileExists('FAH.MoveInfo')) {
            IPS_CreateVariableProfile('FAH.MoveInfo', 1);
        }

        IPS_SetVariableProfileAssociation('FAH.MoveInfo', 0,  $this->translate('Stopped up'), "arrow-up-to-line", 0x22BAD2);
        IPS_SetVariableProfileAssociation('FAH.MoveInfo', 1,  $this->translate('Stopped down'), "arrow-down-to-line", 0x22BAD2);
        IPS_SetVariableProfileAssociation('FAH.MoveInfo', 2,  $this->translate('Moving up'), "angles-up", 0x92B500);
        IPS_SetVariableProfileAssociation('FAH.MoveInfo', 3,  $this->translate('Moving down'), "angles-down", 0x92B500);
        IPS_SetVariableProfileValues('FAH.MoveInfo', 0, 3, 1);

        if (!IPS_VariableProfileExists('FAH.WindForce')) {
            IPS_CreateVariableProfile('FAH.WindForce', 1);
        }
		IPS_SetVariableProfileIcon('FAH.WindForce', 'wind' );
        IPS_SetVariableProfileText('FAH.WindForce', '', 'Bft');
        IPS_SetVariableProfileValues('FAH.WindForce', 0, 17, 1);

        IPS_SetVariableProfileAssociation('FAH.WindForce', 0,  $this->translate('0 Bft: Calm, less then 1 km/h'), "wind", 0x22BAD2);
        IPS_SetVariableProfileAssociation('FAH.WindForce', 1,  $this->translate('1 Bft: Light Air, 1-5 km/h'), "wind", 0x06C3BF);
        IPS_SetVariableProfileAssociation('FAH.WindForce', 2,  $this->translate('2 Bft: Light Breeze, 6-11 km/h'), "wind", 0x00BC8A);
        IPS_SetVariableProfileAssociation('FAH.WindForce', 3,  $this->translate('3 Bft: Gentle Breeze, 12-19 km/h'), "wind", 0x00B556);
        IPS_SetVariableProfileAssociation('FAH.WindForce', 4,  $this->translate('4 Bft: Moderate Breeze, 20-28 km/h'), "wind", 0x25B426);
        IPS_SetVariableProfileAssociation('FAH.WindForce', 5,  $this->translate('5 Bft: Fresh Breeze, 29-38 km/h'), "wind", 0x92B500);
        IPS_SetVariableProfileAssociation('FAH.WindForce', 6,  $this->translate('6 Bft: Strong Breeze, 39-49 km/h'), "wind-warning", 0xD6B301);
        IPS_SetVariableProfileAssociation('FAH.WindForce', 7,  $this->translate('7 Bft: Near Gale, 50-61 km/h'), "wind-warning", 0xDCA500);
        IPS_SetVariableProfileAssociation('FAH.WindForce', 8,  $this->translate('8 Bft: Gale, 62-74 km/h'), "wind-warning", 0xDF9500);
        IPS_SetVariableProfileAssociation('FAH.WindForce', 9,  $this->translate('9 Bft: Severe Gale, 75-88 km/h'), "wind-warning", 0xE28400);
        IPS_SetVariableProfileAssociation('FAH.WindForce', 10, $this->translate('10 Bft: Storm, 89-102 km/h'), "wind-warning", 0xE65305);
        IPS_SetVariableProfileAssociation('FAH.WindForce', 11, $this->translate('11 Bft: Violent Storm, 103-117 km/h'), "wind-warning", 0xE72D2E);
        IPS_SetVariableProfileAssociation('FAH.WindForce', 12, $this->translate('12 Bft: Hurricane, über 118–133 km/h'), "wind-warning", 0xE81854);
        IPS_SetVariableProfileAssociation('FAH.WindForce', 13, $this->translate('13 Bft: Hurricane, 134–149 km/h'), "wind-warning", 0xE81854);
        IPS_SetVariableProfileAssociation('FAH.WindForce', 14, $this->translate('14 Bft: Hurricane, 150–166 km/h'), "wind-warning", 0xE81854);
        IPS_SetVariableProfileAssociation('FAH.WindForce', 15, $this->translate('15 Bft: Hurricane, 167–183 km/h'), "wind-warning", 0xE81854);
        IPS_SetVariableProfileAssociation('FAH.WindForce', 16, $this->translate('16 Bft: Hurricane, 184–202 km/h'), "wind-warning", 0xE81854);
        IPS_SetVariableProfileAssociation('FAH.WindForce', 17, $this->translate('17 Bft: Hurricane, >= 203 km/h'), "wind-warning", 0xE81854);

    }

    public function UpdateVariables()
    {
        $lDeviceID  = $this->ReadPropertyString('FAHDeviceID');
        $lMyChannel = $this->ReadPropertyString('Channel');
        $lMyOutputs = $this->ReadPropertyString('Outputs');
        $lMyInputs  = $this->ReadPropertyString('Inputs');

        $this->SendDebug(__FUNCTION__,"update device $lDeviceID, channel $lMyChannel",0 );
        $lResult = $this->sendData('getDevice' );
        $this->SendDebug(__FUNCTION__,json_encode($lResult),0 );
        
        $lChannelResult = $lResult[$lDeviceID];

        $lListFunctionIds = FID::FilterSupportedChannels( (object)$lChannelResult['channels'] );

        foreach( $lListFunctionIds as $lChannel => $lChannelValue )
        {
            // suche meinen Kanal
            if( $lChannel == $lMyChannel )
            {
                $this->SendDebug(__FUNCTION__,"device $lDeviceID, channel $lMyChannel found",0 );
                $lChannelData = (object)$lChannelValue;

                $lInputs =  json_encode((object)PID::FilterSupportedType($lChannelData,'inputs'));
                $lOutputs = json_encode((object)PID::FilterSupportedType($lChannelData,'outputs'));

                $lDoApplyChanges=false;
                if( $lInputs != $lMyInputs )
                {
                    $this->SendDebug(__FUNCTION__,"inputs changed $lMyInputs -> $lInputs",0 );
                    // neue Kanaldatenübernehmen
                    IPS_SetProperty( $this->InstanceID , 'Inputs', $lInputs );
                    $lDoApplyChanges = true;
                }
                if( $lOutputs != $lMyOutputs )
                {
                    $this->SendDebug(__FUNCTION__,"outputs changed $lMyOutputs -> $lOutputs",0 );
                    // neue Kanaldatenübernehmen
                    IPS_SetProperty( $this->InstanceID , 'Outputs', $lOutputs );
                    $lDoApplyChanges = true;
                }

                if( $lDoApplyChanges )
                {
                    IPS_ApplyChanges( $this->InstanceID  );
                }
                return;
            }
        }
        $this->SendDebug(__FUNCTION__,"device $lDeviceID, channel $lMyChannel not found",0 );
   }

    protected function GetLinearisation() : array
    {
        $data = array();

        $lin25 = $this->ReadPropertyInteger("Lin25");
        $lin50 = $this->ReadPropertyInteger("Lin50");
        $lin75 = $this->ReadPropertyInteger("Lin75");
        // Sichheitscheck auf gültige Werte
        if ($lin25 > 0 && $lin50 > $lin25 && $lin75 > $lin50 && $lin75 < 100) {
            $data[25] = $lin25;
            $data[50] = $lin50;
            $data[75] = $lin75;
        }
        $data[100] = 100;
        return $data;
    }

    protected function Linearize(int $aValue, int $x0, int $y0, int $x1, int $y1) : int
    {
        $div = $x1 - $x0;
        return intval( floatval($y0) * ($x1 - $aValue) / $div + $y1 * ($aValue - $x0) / floatval($div) + 0.5);
    }

    protected function LinearizeToDevice(int $value) : int 
    {
        if ($value <= 0 || $value >= 100)
        {
            return $value;
        }  

        $data = $this->GetLinearisation();
        $x0 = 0;
        $y0 = 0;
        foreach ($data as $x1 => $y1) 
        {
            if ($value > $x1) 
            {
                $x0 = $x1;
                $y0 = $y1;
            } 
            else 
            {
                return $this->Linearize($value, $x0, $y0, $x1, $y1);
            }
        }

        return $value;
    }

    protected function LinearizeFromDevice(int $value) : int
    {
        if ($value <= 0 || $value >= 100)
        {
            return $value;
        }  

        $data = $this->GetLinearisation();
        $x0 = 0;
        $y0 = 0;
        foreach ($data as $y1 => $x1) 
        {
            if ($value > $x1) 
            {
                $x0 = $x1;
                $y0 = $y1;
            } 
            else 
            {
                return $this->Linearize($value, $x0, $y0, $x1, $y1);
            }
        }

        return $value;
    }

    protected function HasActionInput( string $a_Action ) : bool
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

        // Variablen für alle Outputs (des Devices) anlegen
        $lOutputs = json_decode( $this->ReadPropertyString('Outputs') );
 
        foreach( $lOutputs as $lOdp => $lPairingId  )
        {
            $lPIDName = PID::GetName($lPairingId);
            IPS_LogMessage( $this->InstanceID, __FUNCTION__.' '.$lOdp.":".$lPairingId.' - '.$lPIDName );
            $this->MaintainVariable(
                $lPIDName, 
                $this->translate(PID::GetInfo($lPairingId)), 
                PID::GetType($lPairingId), PID::GetProfile($lPairingId), 
                0, true );          
            // hat die Pairing ID ein Action Item
            $Action = PID::GetAction($lPairingId);
            // Prüfe ob das Action Item in den Inputs enthalten ist
            $this->MaintainAction( $lPIDName, $this->HasActionInput($Action) );    
        }
    }

    public function GetConfigurationForm()
    {
        $lJsonForm = json_decode(file_get_contents(__DIR__ . '/form.json'), true);


        $lOutputs = json_decode( $this->ReadPropertyString('Outputs') );

        // hat einer der Meldungen eine Linearisierung aktiv
        $lHasLinearisation = false;
        foreach( $lOutputs as $lDatapoint => $lPairingID  )
        {
            if( PID::HasLinearisation($lPairingID) )  
            {
                $lHasLinearisation = true;
            }          
        }


        // entferne die Linearisation wenn das Device diese nicht unterstützt
        if( !$lHasLinearisation )
        {
            // Liste von Namen, die aus 'elements' entfernt werden sollen 
            $lNamesToRemove = ['LinLabel', 'Lin25', 'Lin50', 'Lin75'];

            // Filtern des Arrays
            $lJsonForm['elements'] = array_filter($lJsonForm['elements'], function($lPos) use ($lNamesToRemove) {
                return !in_array($lPos['name'], $lNamesToRemove, true);
            });
        }

        return json_encode($lJsonForm);
    }


    private function GetOutputDataPointsOfDevices()
    {    
        $lVectRet = array();

        $lData = $this->ReadPropertyString('FAHDeviceID').'.'.$this->ReadPropertyString('Channel').'.';
        $lOutputs = json_decode( $this->ReadPropertyString('Outputs') );

        foreach( $lOutputs as $lDatapoint => $lPairingID  )
        {
            $lVectRet[] = $lData.$lDatapoint;
        }                  

        return $lVectRet;
    }


    private function  AssignData($lDevices)
    {
        $lDevices = json_decode(json_encode($lDevices));
        $this->SendDebug(__FUNCTION__, json_encode($lDevices), 0);
        $lListRequest = $this->GetOutputDataPointsOfDevices();
        $this->SendDebug(__FUNCTION__, json_encode($lListRequest), 0);
       
        $lDataObj = array();
  
        foreach( $lListRequest as $lRequest )
        {
            $lRequestArray = explode('.',$lRequest);

            $lValue = $lDevices->{$lRequestArray[0]}->channels->{$lRequestArray[1]}->outputs->{$lRequestArray[2]}->value;

            $lDataObj[$lRequestArray[0]][$lRequestArray[1]][$lRequestArray[2]] = $lValue;

        }
        $this->SendDebug(__FUNCTION__, json_encode($lDataObj), 0);
 
        do_ReseiveData($lDataObj);
     }

    private  function  do_ReseiveData(  $lDataObj  )
    {
        $lDeviceID = $this->ReadPropertyString('FAHDeviceID');
        $lDataObj = (object)$lDataObj;

        // Daten für dieses Device dabei
        if(!isset( $lDataObj->{$lDeviceID} ))
        {
            // Daten empfangen
            $this->SendDebug(__FUNCTION__, 'no device data found', 0);
            return;
        }

        // Daten empfangen
        $this->SendDebug(__FUNCTION__, json_encode($lDataObj->{$lDeviceID}), 0);

        $lChannel = $this->ReadPropertyString('Channel');
        $lOutputs = json_decode( $this->ReadPropertyString('Outputs') );

        if( isset( $lDataObj->{$lDeviceID}->unresponsive ) )
        {
            if( $lDataObj->{$lDeviceID}->unresponsive )
            {
                $this->SetStatus(200);
                IPS_LogMessage( $this->InstanceID, 'device '.$lDeviceID.' is unresponsiv' );
            }
            else
            {
                $this->SetStatus(102);
            }
        }


        $lOldName = explode( ' - ', IPS_GetName( $this->InstanceID) );



        if( isset( $lDataObj->{$lDeviceID}->displayName ) && isset( $lOldName[0] ) &&
                    $lDataObj->{$lDeviceID}->displayName != $lOldName[0] )
        {
            $lOldName[0] = $lDataObj->{$lDeviceID}->displayName;
            $lNewName = implode( ' - ', $lOldName );

            $this->SendDebug(__FUNCTION__, 'displayName: '.$lDataObj->{$lDeviceID}->displayName, 0);
            IPS_LogMessage( $this->InstanceID, 'device name changed "'.IPS_GetName( $this->InstanceID).'" => "'.$lNewName.'"' );
            IPS_SetName( $this->InstanceID, $lNewName );
        }

        if( isset( $lDataObj->{$lDeviceID}->{$lChannel} ))
        {
            $lChannelData = $lDataObj->{$lDeviceID}->{$lChannel};

            $lPairingIdsToSuppress = array();
            // Prüfe ob Daten für die Übernahme unterdrückt werden müssen
            foreach( $lOutputs as $lDatapoint => $lPairingID  )
            {
                foreach( $lChannelData as $lDP => $lValue )
                {
                    if( $lDP == $lDatapoint )
                    {
                        if( PID::GetName( $lPairingID ) == 'INFO_MOVE_UP_DOWN' )
                        {
                            $lInt = intval($lValue);
                            // gerade in Bewegung ?
                            if( $lInt >= 2 )
                            {
                                $this->SendDebug(__FUNCTION__, 'is moving do suppress CURRENT_ABSOLUTE_POSITION_BLINDS_PERCENTAGE ', 0);
                                // Informationen über die Position nicht übernehmen
                                $lPairingIdsToSuppress[] =  PID::GetID('CURRENT_ABSOLUTE_POSITION_BLINDS_PERCENTAGE');  
                            }
                        }
                    }
                }
            }

            foreach( $lOutputs as $lDatapoint => $lPairingID  )
            {
                if( in_array( $lPairingID, $lPairingIdsToSuppress ) )
                {
                    $this->SendDebug(__FUNCTION__, 'now suppressing '.PID::GetName( $lPairingID ), 0);
                }
                else
                {
                    foreach( $lChannelData as $lDP => $lValue )
                    {
                        if( $lDP == $lDatapoint )
                        {
                            $lValueId = PID::GetName( $lPairingID );
                            $lId = $this->GetIDForIdent($lValueId);
                            $lType = PID::GetType( $lPairingID );
                            switch($lType)
                            {
                            case 0: // bool
                                $lNewBool = boolval($lValue);
                            
                                if(GetValueBoolean($lId) != $lNewBool )
                                {
                                    $lConvertedBool = $lNewBool ? 'true' : 'false';
                                    $this->SendDebug(__FUNCTION__ , $lValueId.' => '.$lConvertedBool, 0);
                                    SetValueBoolean($lId,$lNewBool);
                                }
                                break;
                            case 1: // int
                                $lNewInt = intval($lValue);

                                if( PID::HasLinearisation($lPairingID) )
                                {
                                    $lNewInt = $this->LinearizeFromDevice( $lNewInt );
                                    $this->SendDebug(__FUNCTION__ , 'Linarize from Device '.intval($lValue).' => '.$lNewInt, 0);
                                }
                                
                                if(GetValueInteger($lId) != $lNewInt )
                                {
                                    $this->SendDebug(__FUNCTION__ , $lValueId.' => '.strval($lNewInt), 0);
                                    SetValueInteger($lId,$lNewInt);                           
                                }
                                break;
                            case 2: // float
                                $lNewFloat = floatval($lValue);
                                
                                if(GetValueFloat($lId) != $lNewFloat )
                                {
                                    $this->SendDebug(__FUNCTION__ , $lValueId.' => '.strval($lNewFloat), 0);
                                    SetValueFloat($lId,$lNewFloat);                           
                                }
                                break;
                            }
                        }
                    }
                }
            }
        }                  
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

        $this->do_ReseiveData( $lDataObj );
    }

    public function SetState( bool $Value )
    {
        $lOutputs = json_decode( $this->ReadPropertyString('Outputs') );

        foreach( $lOutputs as $lDatapoint => $lPairingID  )
        {
            $lSettings = PID::GetSettingsByID( $lPairingID );
            if( $lSettings['info'] == 'State' && $lSettings['action'] != '' && $lSettings['type'] == 0 )
            {
                $this->RequestAction( PID::GetName($lPairingID), $Value );
                return true;
            }
        }                  

        // Wert nicht gültig oder Funktion State nicht verfügbar
        IPS_LogMessage( $this->InstanceID, __FUNCTION__.'('.strval($Value).") not supported" );
        return false;
    }
   
    public function SetBrightness( int $Value )
    {
        // Wert im gültigen Bereich
        if( $Value <= 100 )
        {
            $lOutputs = json_decode( $this->ReadPropertyString('Outputs') );

            foreach( $lOutputs as $lDatapoint => $lPairingID  )
            {
                $lSettings = PID::GetSettingsByID( $lPairingID );
                if( $lSettings['info'] == 'Brightness' && $lSettings['action'] != '' && $lSettings['type'] == 1 )
                {
                    $this->RequestAction( PID::GetName($lPairingID), $Value );
                    return true;
                }
            }                  
        }

        // Wert nicht gültig oder Funktion Brighness nicht verfügbar
        IPS_LogMessage( $this->InstanceID, __FUNCTION__.'('.strval($Value).") not supported" );
        return false;
    }

    
    public function SetColour( int $Value )
    {
        $lOutputs = json_decode( $this->ReadPropertyString('Outputs') );

        foreach( $lOutputs as $lDatapoint => $lPairingID  )
        {
            $lSettings = PID::GetSettingsByID( $lPairingID );
            if( $lSettings['info'] == 'Colour' && $lSettings['action'] != '' && $lSettings['type'] == 1 )
            {
                $this->RequestAction( PID::GetName($lPairingID), $Value );
                return true;
            }
        }                  

        // Wert nicht gültig oder Funktion Colour nicht verfügbar
        IPS_LogMessage( $this->InstanceID, __FUNCTION__.'('.strval($Value).") not supported" );
        return false;
    }

    public function SetPosition( int $Value )
    {
        // beim negativem Wert nichts machen
        if( $Value < 0  )
        {
            // Wert nicht gültig oder Funktion Brighness nicht verfügbar
            IPS_LogMessage( $this->InstanceID, __FUNCTION__.'('.strval($Value).") not supported" );
            return false;
        }
       // beim Wert über 100 nichts machen
       if( $Value > 100  )
       {
            // Wert nicht gültig oder Funktion Brighness nicht verfügbar
            IPS_LogMessage( $this->InstanceID, __FUNCTION__.'('.strval($Value).") not supported" );
            return false;
       }

        $lOutputs = json_decode( $this->ReadPropertyString('Outputs') );

        foreach( $lOutputs as $lDatapoint => $lPairingID  )
        {
            $lSettings = PID::GetSettingsByID( $lPairingID );
            if( $lSettings['info'] == 'Position' && $lSettings['action'] != '' && $lSettings['type'] == 1 )
            {
                $this->RequestAction( PID::GetName($lPairingID), $Value );
                return true;
            }
        }                  

        // Wert nicht gültig oder Funktion Brighness nicht verfügbar
        IPS_LogMessage( $this->InstanceID, __FUNCTION__.'('.strval($Value).") not supported" );
        return false;
    }

    public function GetPosition() : int
    {
        $lOutputs = json_decode( $this->ReadPropertyString('Outputs') );

        foreach( $lOutputs as $lDatapoint => $lPairingID  )
        {
            $lSettings = PID::GetSettingsByID( $lPairingID );
            if( $lSettings['info'] == 'Position' && $lSettings['type'] == 1 )
            {
                $lId = $this->GetIDForIdent( PID::GetName($lPairingID) );
                return GetValueInteger($lId);
            }
        }
 
        // Attribut Position nicht gefunden
        IPS_LogMessage( $this->InstanceID, __FUNCTION__."() attribut Position not found" );
        return 0;
    }

  public function SetSensorLock( bool $Value )
  {
        $lOutputs = json_decode( $this->ReadPropertyString('Outputs') );

        foreach( $lOutputs as $lDatapoint => $lPairingID  )
        {
            $lSettings = PID::GetSettingsByID( $lPairingID );
            if( $lSettings['info'] == 'Sensor lock' && $lSettings['action'] != '' && $lSettings['type'] == 0 )
            {
                $this->RequestAction( PID::GetName($lPairingID), $Value );
                return true;
            }
        }                  

        // Wert nicht gültig oder Funktion Brighness nicht verfügbar
        IPS_LogMessage( $this->InstanceID, __FUNCTION__.'('.strval($Value).") attribut Sensor lock not found" );
        return false;
    }
  
    public function GetSensorLock() : bool
    {
        $lOutputs = json_decode( $this->ReadPropertyString('Outputs') );

        foreach( $lOutputs as $lDatapoint => $lPairingID  )
        {
            $lSettings = PID::GetSettingsByID( $lPairingID );
            if( $lSettings['info'] == 'Sensor lock' && $lSettings['type'] == 0 )
            {
                $lId = $this->GetIDForIdent( PID::GetName($lPairingID) );
                return GetValueBoolean($lId);
            }
        }
 
        // Attribut Position nicht gefunden
        IPS_LogMessage( $this->InstanceID, __FUNCTION__."() attribut Sensor lock not found" );
        return false;
    }


    private function do_GetValue( string $a_Ident )
    {
        $lPairingID = PID::GetID( $a_Ident );
        $lId        = $this->GetIDForIdent($a_Ident);
        $lType      = PID::GetType( $lPairingID );
        switch($lType)
        {
        case 0: // bool
             return GetValueBoolean($lId);
        case 1: // int
            return GetValueInteger($lId);
        case 2: // float
            return GetValueFloat($lId);
            }
        return false ;
    }

    private function do_SetValue( string $a_Ident, string $a_Value )
    {
        $lPairingID = PID::GetID( $a_Ident );
        $lId        = $this->GetIDForIdent($a_Ident);
        $lType      = PID::GetType( $lPairingID );
        switch($lType)
        {
        case 0: // bool
             $lNewBool = boolval($a_Value);
          
             if(GetValueBoolean($lId) != $lNewBool )
             {
                $lConvertedBool = $lNewBool ? 'true' : 'false';
                $this->SendDebug(__FUNCTION__ , $a_Ident.' => '.$lConvertedBool, 0);
                SetValueBoolean($lId,$lNewBool);
            }
            break;
        case 1: // int
            $lNewInt = intval($a_Value);
           
            if( PID::HasLinearisation($lPairingID) )
            {
                $lNewInt = $this->LinearizeFromDevice( $lNewInt );
                $this->SendDebug(__FUNCTION__ , 'Linearize from device '.intval($a_Value).' => '.$lNewInt, 0);
            }

			$lOldValue = GetValueInteger($lId);
            if($lOldValue != $lNewInt )
            {
                $this->SendDebug(__FUNCTION__ , "$a_Ident: $lOldValue => $lNewInt", 0);
                SetValueInteger($lId,$lNewInt);                           
            }
            break;
        }
    }

    public function RequestAction($Ident, $Value)
    {
        // Daten empfangen
        $this->SendDebug(__FUNCTION__, $Ident.' => '.$Value, 0);

        $lBeforeValue= $Value;
        if(  PID::HasLinearisation(PID::GetID($Ident)) )
        {
            $Value = $this->LinearizeToDevice( $Value );
            $this->SendDebug(__FUNCTION__, "LinearizeToDevice $lBeforeValue => $Value", 0);
        }

        $lOrigIdent = $Ident;
        $lOrigValue = $Value;
        $lDoSetOrigValue = false;        
        $lDoSetValue = true;

        switch($Ident)
        {
        // Helligkeit 0 in Aus umwandeln, ggf. erstmal einschalten 
        case 'INFO_ACTUAL_DIMMING_VALUE':
        	if($Value <= 0)
            {
                $Ident = 'INFO_ON_OFF';
                $Value = false;
                $lDoSetOrigValue = true;
            }
            else if( !$this->do_GetValue('INFO_ON_OFF') )
            {
                // Wert grösser 0 und war noch nicht an
                $Ident = 'INFO_ON_OFF';
                $Value = true;
                $lDoSetOrigValue = true;
            }
            break;
        // Farbe Schwarz in Aus umwandeln
        case 'INFO_RGB':
            if($Value <= 0)
            {
                $Ident = 'INFO_ON_OFF';
                $Value = false;
            }
            break;
        case 'INFO_MOVE_UP_DOWN':
            {
             
                $lDoSetValue = false;
            }
            break;

            case 'CURRENT_ABSOLUTE_POSITION_BLINDS_PERCENTAGE':
                {
                    if( $lBeforeValue == 0 )
                    {
                        $Ident = 'INFO_MOVE_UP_DOWN';
                        $Value = 0;  // hochfahren
                        $lDoSetValue = false;
                        $lDoSetOrigValue = true;
                    }
                    else if( $lBeforeValue == 100 )
                    {
                        $Ident = 'INFO_MOVE_UP_DOWN';
                        $Value = 1;  // runterfahren
                        $lDoSetValue = false;
                        $lDoSetOrigValue = true;
                    }
                }
                break;
        }

        if( $Ident != $lOrigIdent )
        {
            $this->SendDebug(__FUNCTION__,"convert $lOrigIdent ($lBeforeValue) -> $Ident ($Value)",0 );
        }

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

                   // PUT Datapoint Value
                   // {$lDeviceID}.{$lChannel}.{$lDatapoint}
                   $lSendData = [ 'datapoint' => $lDatapoint, 'value' => $SendValue ];
                   $lResult = $this->sendData('setDatapoint', json_encode($lSendData) );
                   $this->SendDebug(__FUNCTION__,json_encode($lResult),0 );

                   if( $lDoSetValue )
                   {
                        // Date im Abbild direkt übernehmen ohne auf die Rückmeldung zu warten
                        $this->do_SetValue( $Ident, $SendValue );
                   }

                   if( $lDoSetOrigValue )
                   {
                       $this->do_SetValue( $lOrigIdent, strval($lOrigValue) );
                   }
  
                   $lbPollData = true;
                }
            }

  //           if( $lbPollData )
  //          {
  ////              $this->SendDebug(__FUNCTION__,'update data',0 );
  ////              $lResult = $this->sendData('getDevice' );
  ////              $this->AssignData( $lResult );
  ////              $this->SendDebug(__FUNCTION__,json_encode($lResult),0 );
  //          }

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
