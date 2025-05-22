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
 
        $this->RegisterAttributeString('DeviceType', '');


        if (!IPS_VariableProfileExists('FAH.WindForce')) {
            IPS_CreateVariableProfile('HUE.WindForce', 1);
        }

        IPS_SetVariableProfileText('HUE.WindForce', '', 'Bft');
        IPS_SetVariableProfileValues('HUE.WindForce', 0, 17, 1);
        IPS_SetVariableProfileAssociation('HUE.WindForce', 0, '0 Bft: Windstille, weniger als 1 km/h', "Wind", 0x0000FF);
        IPS_SetVariableProfileAssociation('HUE.WindForce', 1, '1 Bft: leichter Zug, 1-5 km/h ', "Wind", 0x0000FF);
        IPS_SetVariableProfileAssociation('HUE.WindForce', 2, '2 Bft: leichte Brise, 6-11 km/h', "Wind", 0x00FF00);
        IPS_SetVariableProfileAssociation('HUE.WindForce', 3, '3 Bft: schwache Brise, 12-19 km/h', "Wind", 0x00FF00);
        IPS_SetVariableProfileAssociation('HUE.WindForce', 4, '4 Bft: mäßige Brise, 20-28 km/h', "Wind", 0x00FF00);
        IPS_SetVariableProfileAssociation('HUE.WindForce', 5, '5 Bft: frische Brise, 29-38 km/h', "Wind", 0xFF00FF);
        IPS_SetVariableProfileAssociation('HUE.WindForce', 6, '6 Bft: starker Wind, 39-49 km/h', "Wind", 0xFF00FF);
        IPS_SetVariableProfileAssociation('HUE.WindForce', 7, '7 Bft: steifer Wind, 50-61 km/h', "Wind", 0xFF00FF);
        IPS_SetVariableProfileAssociation('HUE.WindForce', 8, '8 Bft: stürmischer Wind, 62-74 km/h', "Wind", 0xFF00FF);
        IPS_SetVariableProfileAssociation('HUE.WindForce', 9, '9 Bft: Sturm, 75-88 km/h', "Wind", 0xFF00FF);
        IPS_SetVariableProfileAssociation('HUE.WindForce', 10, '10 Bft: schwerer Sturm, 89-102 km/h', "Wind", 0xFF00FF);
        IPS_SetVariableProfileAssociation('HUE.WindForce', 11, '11 Bft: Orkanartiger Sturm, 103-117 km/h', "Wind", 0xFF0000);
        IPS_SetVariableProfileAssociation('HUE.WindForce', 12, '12 Bft: Orkan, über 118–133 km/h', "Wind", 0xFF0000);
        IPS_SetVariableProfileAssociation('HUE.WindForce', 13, '13 Bft: Orkan, 134–149 km/h', "Wind", 0xFF0000);
        IPS_SetVariableProfileAssociation('HUE.WindForce', 14, '14 Bft: Orkan, 150–166 km/h', "Wind", 0xFF0000);
        IPS_SetVariableProfileAssociation('HUE.WindForce', 15, '15 Bft: Orkan, 167–183 km/h', "Wind", 0xFF0000);
        IPS_SetVariableProfileAssociation('HUE.WindForce', 16, '16 Bft: Orkan, 184–202 km/h', "Wind", 0xFF0000);
        IPS_SetVariableProfileAssociation('HUE.WindForce', 17, '17 Bft: Orkan, ≧203 km/h', "Wind", 0xFF0000);

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

    }

    public function GetConfigurationForm()
    {
        $jsonForm = json_decode(file_get_contents(__DIR__ . '/form.json'), true);

        return json_encode($jsonForm);
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
            $lConvertedBool = $lDataObj->{$lDeviceID}->unresponsive ? 'true' : 'false';
            $this->SendDebug(__FUNCTION__, 'unresponsive: '.$lConvertedBool, 0);
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

        if( isset( $lDataObj->{$lDeviceID}->displayName ) &&
                    $lDataObj->{$lDeviceID}->displayName != IPS_GetName( $this->InstanceID) )
        {
            $this->SendDebug(__FUNCTION__, 'displayName: '.$lDataObj->{$lDeviceID}->displayName, 0);
            IPS_LogMessage( $this->InstanceID, 'device name changed "'.IPS_GetName( $this->InstanceID).'" => "'.$lDataObj->{$lDeviceID}->displayName.'"' );
            IPS_SetName( $this->InstanceID, $lDataObj->{$lDeviceID}->displayName );
        }

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
           
            if(GetValueInteger($lId) != $lNewInt )
            {
                $this->SendDebug(__FUNCTION__ , $a_Ident.' => '.strval($lNewInt), 0);
                SetValueInteger($lId,$lNewInt);                           
            }
            break;
        }
    }

    public function RequestAction($Ident, $Value)
    {
        // Daten empfangen
        $this->SendDebug(__FUNCTION__, $Ident.' => '.$Value, 0);
        $lOrigIdent = $Ident;
        $lOrigValue = $Value;
        $lDoSetOrigValue = false;

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

                   // Date im Abbild direkt übernehmen ohne auf die Rückmeldung zu warten
                   $this->do_SetValue( $Ident, $SendValue );
                   if( $lDoSetOrigValue )
                   {
                        $this->do_SetValue( $lOrigIdent, strval($lOrigValue) );
                   }
                   $this->SendDebug(__FUNCTION__,json_encode($lResult),0 );

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
