<?php

declare(strict_types=1);
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
        'hue' => 'Phillips HUE');


    public function Create()
    {
        //Never delete this line!
        parent::Create();
        $this->ConnectParent(self::mParentId);
        $this->RegisterPropertyString('Serialnumber', '');
        $this->RegisterPropertyInteger('RF_TargetCategory', 0);
        $this->RegisterPropertyInteger('HUE_TargetCategory', 0);
        $this->RegisterPropertyInteger('Scene_TargetCategory', 0);

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

        $Devices = $this->FilterDeviceList( $AllDevices, false, ['hue','huegroup']);  // alles ausser Hue Devices
        $HueDevices = $this->FilterDeviceList( $AllDevices, true, ['hue']); // nur Hue Devices
        $Scenes = array(); 


        $lAllDeviceGroups = [
            'free@home Devices'    => [ 'category'     => 'RF_TargetCategory',
                                        'name'         => 'devices',
                                        'id'           => 99999,
                                        'objects'      => $Devices       ],
            'Phillips Hue Devices' => [ 'category'     => 'HUE_TargetCategory',
                                        'name'         => 'hue_devices',
                                        'id'           => 99998,
                                        'objects'      => $HueDevices    ]

        ];


        $Values = [];

        foreach( $lAllDeviceGroups as $lGroupName => $lVal )
        {
            // Busch und Jäger Komponenten
            $location = $this->getPathOfCategory($this->ReadPropertyInteger($lVal['category']));
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
                            'ModelID'               => '',
                            'Manufacturername'      => '',
                            'Productname'           => ''
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
    
    
                    $instanceID = $this->getFAHDeviceInstances($key, $lDeviceType );
                    $AddValueLights = [
                        'parent'                => 1,
                        'ID'                    => $key,
                        'DisplayName'           => $lDeviceName,
                        'name'                  => $lDeviceName,
                        'Type'                  => $lDeviceType,
                        'ModelID'               => $lInputs,
                        'Manufacturername'      => ((array_key_exists($lDevice['interface'], self::m_Types)) ? self::m_Types[$lDevice['interface']] : '?'.$lDevice->interface.'?'),
                        'Productname'           => $lOutputs,
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
                        'location' => $location
                    ];
    
                    $Values[] = $AddValueLights;
                }
            }
        }

        $Form['actions'][0]['values'] = $Values;
        return json_encode($Form);
    }

        
          
        
    private function getFAHDeviceInstances($DeviceID, $DeviceType)
    {
       $InstanceIDs = IPS_GetInstanceListByModuleID(self::mDeviceModuleId); //FAHDevice
       foreach ($InstanceIDs as $id) {
           if (IPS_GetProperty($id, 'FAHDeviceID') == $DeviceID && IPS_GetProperty($id, 'DeviceType') == $DeviceType) {
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



    private function getFAH_AllDevices()
    {
        $Data = [];
        $Buffer = [];

        $Data['DataID'] = self::mBridgeDataId;
        $Buffer['Command'] = 'getAllDevices';
        $Buffer['Params'] = '';
        $Data['Buffer'] = $Buffer;
        $Data = json_encode($Data);
        $ResultfromParent = $this->SendDataToParent($Data);
        $result = json_decode($ResultfromParent, true);
        if (!$result) {
            return [];
        }
        return $result;
    }

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

    private function getFAHScenes()
    {
        $Data = [];
        $Buffer = [];

        $Data['DataID'] = self::mBridgeDataId;
        $Buffer['Command'] = 'getAllSensors';
        $Buffer['Params'] = '';
        $Data['Buffer'] = $Buffer;
        $Data = json_encode($Data);
        $result = json_decode($this->SendDataToParent($Data), true);
        if (!$result) {
            return [];
        }
        return $result;
    }

    private function getPathOfCategory(int $categoryId): array
    {
        if ($categoryId === 0) {
            return [];
        }

        $path[] = IPS_GetName($categoryId);
        $parentId = IPS_GetObject($categoryId)['ParentID'];

        while ($parentId > 0) {
            $path[] = IPS_GetName($parentId);
            $parentId = IPS_GetObject($parentId)['ParentID'];
        }

        return array_reverse($path);
    }

}  
   