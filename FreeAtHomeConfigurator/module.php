<?php

require_once __DIR__ . '/../libs/FunctionID.php';
require_once __DIR__ . '/../libs/PairingID.php';

class FreeAtHomeConfigurator extends IPSModule
{
    const mBridgeDataId     = '{BC9334EC-8C5C-61C2-C5DD-96FE9368F38D}';      // DatenId der Bridge
    const mDeviceModuleId   = '{BDE4603B-E68A-D3AF-2510-9462C7374097}';      // Device Modul Id 
    const mParentId         = '{9AFFB383-D756-8422-BCA0-EFD3BB1E3E29}';      // Parent Id (Bridge)
    const mChildId          = '{7E471B91-3407-F7EE-347B-64B459E33D76}';      // Child Id 

    

    const m_Types = array( 
        'RF' => 'free@home wireless',
        'smokealarm' => 'free@home smokealarm',
        'hue' => 'Philips Hue');


    public function Create()
    {
        //Never delete this line!
        parent::Create();
        $this->ConnectParent(self::mParentId);
        $this->RegisterPropertyString('Serialnumber', '');
        $this->RegisterPropertyInteger('RF_TargetCategory', 0);
        $this->RegisterPropertyInteger('HUE_TargetCategory', 0);

        // Cache-Buffer für getFAH_AllDevices (siehe dort für Hintergrund).
        // Bleibt bis zum manuellen "Aktualisieren" des Users persistent.
        $this->SetBuffer('AllDevicesCache', '');
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();
    }

    
    public function GetConfigurationForm()
    {
        $Form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
        $AllDevices = $this->getFAH_AllDevices();

        $this->SendDebug('GetConfigurationForm',
            'AllDevices: type=' . gettype($AllDevices)
            . ' count=' . (is_array($AllDevices) ? count($AllDevices) : '-')
            . ' first_key=' . (is_array($AllDevices) && count($AllDevices) > 0 ? array_keys($AllDevices)[0] : '-'),
            0);

        $Devices = $this->FilterDeviceList( $AllDevices, false, ['hue','huegroup']);  // alles ausser Hue Devices
        $HueDevices = $this->FilterDeviceList( $AllDevices, true, ['hue']); // nur Hue Devices

        $this->SendDebug('GetConfigurationForm',
            'After filter: Devices count=' . count((array)$Devices)
            . ' HueDevices count=' . count((array)$HueDevices),
            0);


        $lAllDeviceGroups = [
            'free@home Devices'    => [ 'category'     => 'RF_TargetCategory',
                                        'name'         => 'devices',
                                        'id'           => 99999,
                                        'objects'      => $Devices       ],
            'Philips Hue Devices'  => [ 'category'     => 'HUE_TargetCategory',
                                        'name'         => 'hue_devices',
                                        'id'           => 99998,
                                        'objects'      => $HueDevices    ]

        ];


        $Values = [];

        foreach( $lAllDeviceGroups as $lGroupName => $lVal )
        {
            // Busch und Jäger Komponenten.
            //
            // Hinweis zu 'location' im create-Block weiter unten: IP-Symcon
            // interpretiert den Pfad relativ zur bereits gewählten Ziel-Kategorie
            // und legt ihn dort als Unterstruktur an. Wenn wir den absoluten
            // Pfad der Ziel-Kategorie übergeben, entsteht deshalb eine
            // doppelte Verschachtelung. Wir übergeben daher ein leeres Array –
            // die neue Instanz landet dann direkt in der gewählten Ziel-Kategorie.
            $lAddTypeCategory = true;
            foreach ($lVal['objects'] as $key => $lDevice) {
                $lListFunctionIds = FID::FilterSupportedChannels( (object)$lDevice['channels'] );
                $lFunctionIdIndex=0;
                foreach( $lListFunctionIds as $lChannel => $lChannelValue )
                {
                    $lFunctionIdIndex += 1;
                    $lChannelData = (object)$lChannelValue;
                    // beim ersten Elemnent vorher die Typencategorie hinzufügen
                    if( $lAddTypeCategory  )
                    {
                        $AddValueLights = [
                            'id'                    => 1,
                            'ID'                    => '',
                            'name'                  => $lVal['name'],
                            'DisplayName'           => $this->translate($lGroupName),
                            'Type'                  => '',
                            'DeviceInputs'          => '',
                            'Manufacturername'      => '',
                            'DeviceOutputs'         => ''
                        ];
            
                        $Values[] = $AddValueLights;
                        $lAddTypeCategory = false;
                    }
    
                    $lDeviceName = $lChannelData->displayName;
                    $lDeviceType = FID::GetName($lChannelData->functionID);
                    if(count($lListFunctionIds)> 1)
                    {
                        $lDeviceName = $lDeviceName.' - '.$this->translate($lDeviceType);
                    }
    
                    $lInputs = json_encode((object)PID::FilterSupportedType($lChannelData,'inputs'));
                    $lOutputs = json_encode((object)PID::FilterSupportedType($lChannelData,'outputs'));
    
    //                 IPS_LogMessage( $this->InstanceID, __FUNCTION__.' Outputs:'. $lOutputs );
    
    
                    $instanceID = $this->getFAHDeviceInstances($key, $lChannel );
                    $AddValueLights = [
                        'parent'                => 1,
                        'ID'                    => $key,
                        'DisplayName'           => $lDeviceName,
                        'name'                  => $lDeviceName,
                        'Type'                  => $lDeviceType,
                        'DeviceInputs'          => $lInputs,
                        'Manufacturername'      => ((array_key_exists($lDevice['interface'], self::m_Types)) ? self::m_Types[$lDevice['interface']] : '?'.$lDevice->interface.'?'),
                        'DeviceOutputs'         => $lOutputs,
                        'instanceID'            => $instanceID
                    ];
        
                    $AddValueLights['create'] = [
                        'moduleID'      => self::mDeviceModuleId,
                        'configuration' => [
                            'FAHDeviceID'    => $key,
                            'DeviceType'     => $lDeviceType,
                            'Channel'        => $lChannel,
                            'Inputs'         => $lInputs,
                            'Outputs'        => $lOutputs
                            ],
                        'location' => []
                    ];
    
                    $Values[] = $AddValueLights;
                }
            }
        }

        $Form['actions'][0]['values'] = $Values;
        return json_encode($Form);
    }

        
          
        
    private function getFAHDeviceInstances(string $DeviceID, string $Channel) : int
    {
       $InstanceIDs = IPS_GetInstanceListByModuleID(self::mDeviceModuleId); //FAHDevice
       foreach ($InstanceIDs as $id) {
           if (IPS_GetProperty($id, 'FAHDeviceID') == $DeviceID && IPS_GetProperty($id, 'Channel') == $Channel) {
               if (IPS_GetInstance($id)['ConnectionID'] == IPS_GetInstance($this->InstanceID)['ConnectionID']) {
                   return $id;
               }
           }
       }
       return 0;
    }


    private function FilterDeviceList( $AllDevices, bool $DoInclude, array $Interfaces )
    {
        $lRetValue = new stdClass();

        foreach($AllDevices as $lDeviceId => $DeviceValue)
        {        
            if( isset($DeviceValue['interface'] ) )
            {
                if( $DoInclude == in_array( $DeviceValue['interface'], $Interfaces ) )
                {
                    $lRetValue->$lDeviceId = $DeviceValue;
                }
            }
        }

         return $lRetValue;
    }



    /**
     * Holt die komplette Gerätekonfiguration von der Bridge (via REST-API).
     *
     * Diese Abfrage ist teuer (HTTPS-GET auf /fhapi/v1/api/rest/configuration,
     * typisch ~100-500 kB JSON). IP-Symcon ruft GetConfigurationForm in
     * manchen Situationen mehrfach pro Minute auf (z. B. wenn der Configurator-
     * Dialog in der Konsole offen ist). Deshalb wird das Ergebnis gecacht.
     *
     * Der Cache wird NICHT automatisch ablaufen – er bleibt bis der User
     * explizit auf den "Aktualisieren"-Button im Dialog klickt, was den
     * Cache über die public-Action RefreshDevices() invalidiert.
     */
    private function getFAH_AllDevices()
    {
        // Cache prüfen (kein Zeitlimit – Cache bleibt bis zur manuellen Invalidierung)
        $lCached = $this->GetBuffer('AllDevicesCache');
        $this->SendDebug(__FUNCTION__, 'cache length: ' . strlen($lCached), 0);
        if( $lCached !== '' )
        {
            $lDecoded = json_decode($lCached, true);
            if( is_array($lDecoded) )
            {
                $this->SendDebug(__FUNCTION__, 'cache hit, returning ' . count($lDecoded) . ' devices', 0);
                return $lDecoded;
            }
            $this->SendDebug(__FUNCTION__, 'cache decode failed, falling through', 0);
        }

        // Cache leer → frisch holen
        $Data = [];
        $Buffer = [];

        $Data['DataID'] = self::mBridgeDataId;
        $Buffer['Command'] = 'getAllDevices';
        $Buffer['Params'] = '';
        $Data['Buffer'] = $Buffer;
        $Data = json_encode($Data);
        $ResultfromParent = $this->SendDataToParent($Data);
        $this->SendDebug(__FUNCTION__,
            'SendDataToParent returned type=' . gettype($ResultfromParent)
            . ' length=' . (is_string($ResultfromParent) ? strlen($ResultfromParent) : '-')
            . ' head=' . (is_string($ResultfromParent) ? substr($ResultfromParent, 0, 200) : json_encode($ResultfromParent)),
            0);
        $result = json_decode($ResultfromParent, true);
        if (!$result) {
            $this->SendDebug(__FUNCTION__, 'json_decode returned falsy, json_last_error=' . json_last_error_msg(), 0);
            return [];
        }

        $this->SendDebug(__FUNCTION__, 'fresh result has ' . count($result) . ' devices', 0);

        // Ergebnis cachen
        $lEncoded = json_encode($result);
        $lSetOk = $this->SetBuffer('AllDevicesCache', $lEncoded);
        $this->SendDebug(__FUNCTION__, 'SetBuffer length=' . strlen($lEncoded) . ' returned=' . var_export($lSetOk, true), 0);

        return $result;
    }

    /**
     * Cache invalidieren und Configurator-Dialog zum Neuladen zwingen.
     *
     * Wird vom "Aktualisieren"-Button im Dialog aufgerufen. IPS-Symcon
     * reloadet daraufhin das Configurator-Formular, wodurch
     * GetConfigurationForm erneut aufgerufen wird – dieses Mal ist der
     * Cache leer und es gibt einen frischen REST-Call.
     */
    public function RefreshDevices()
    {
        $this->SetBuffer('AllDevicesCache', '');
        $this->UpdateFormField('Configurator', 'values', json_encode([]));
        $this->ReloadForm();
    }

    // getHUEDevices ist momentan ungenutzt – die Bridge verteilt Hue-Devices
    // bereits über getAllDevices. Die Funktion bleibt als Platzhalter, falls
    // später getrennte Hue-Gruppen-Abfragen ergänzt werden.
    private function getHUEDevices()
    {
        $Data = [];
        $Buffer = [];

        $Data['DataID'] = self::mBridgeDataId;
        $Buffer['Command'] = 'getAllGroups';
        $Buffer['Params'] = '';
        $Data['Buffer'] = $Buffer;
        $Data = json_encode($Data);
        $result = json_decode($this->SendDataToParent($Data), true);
        if (!$result) {
            return [];
        }
        return $result;
    }

}  
   