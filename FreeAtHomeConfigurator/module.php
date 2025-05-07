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

    //    $this->RegisterAttributeInteger('ProgressStatus', -1);
    //    $this->RegisterTimer('ProgressNewDevices', 0, 'FAHCONF_ProgressUpdateNewDevicesList(' . $this->InstanceID . ');');
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

        $Devices = $this->FilterDeviceList( $AllDevices, 'RF');
        $HueDevices = array(); // = $this->FilterDeviceList( $AllDevices, 'hue'); 
        $Scenes = array(); 

 
 //      $HueDevices = $this->getHUEDevices();
 //      $Scenes = $this->getFAHScenes();

 //      if (array_key_exists('error', $Devices)) {
 //          $this->LogMessage('FAH Configuration Error: ' . $Devices['error']['type'] . ': ' . $Devices['error']['description'], KL_ERROR);
 //          return $Form;
 //      }
 //      if (array_key_exists('error', $HueDevices)) {
 //          $this->LogMessage('FAH Configuration Error: ' . $HueDevices['error']['type'] . ': ' . $HueDevices['error']['description'], KL_ERROR);
 //          return $Form;
 //      }
 //      if (array_key_exists('error', $Scenes)) {
 //          $this->LogMessage('FAH Configuration Error: ' . $Scenes['error']['type'] . ': ' . $Scenes['error']['description'], KL_ERROR);
 //          return $Form;
 //      }

        $this->SendDebug(__FUNCTION__ . ' Devises', json_encode($Devices), 0);

        $this->SendDebug(__FUNCTION__ . ' HUE-Devices', json_encode($HueDevices), 0);
        $this->SendDebug(__FUNCTION__ . ' Scenes', json_encode($Scenes), 0);

        $Values = [];
        $ValuesAllDevices = [];

        // Busch und Jäger Komponenten
        $location = $this->getPathOfCategory($this->ReadPropertyInteger('RF_TargetCategory'));
        $lAddTypeCategory = true;
        foreach ($Devices as $key => $lDevice) {
            $lListFunctionIds = FID::FilterSupportedChannels( (object)$lDevice['channels'] );
            $lFunctionIdIndex=1;
            foreach( $lListFunctionIds as $lChannel => $lChannelValue )
            {
                $lChannelData = (object)$lChannelValue;
                // beim ersten Elemnent vorher die Typencategorie hinzufügen
                if( $lAddTypeCategory  )
                {
                    $AddValueLights = [
                        'id'                    => 1,
                        'ID'                    => '',
                        'name'                  => 'devices',
                        'DisplayName'           => $this->translate('free@home Devices'),
                        'Type'                  => '',
                        'ModelID'               => '',
                        'Manufacturername'      => '',
                        'Productname'           => ''
                    ];
        
                    $AddValueAllDevicesLights = [
                        'id'                    => 99999,
                        'DeviceID'              => '',
                        'DeviceName'            => $this->translate('free@home devices'),
                        'DeviceType'            => ''
                    ];

                    $Values[] = $AddValueLights;
                    $ValuesAllDevices[] = $AddValueAllDevicesLights;
                    $lAddTypeCategory = false;
                }

                $lDeviceName = $lChannelData->displayName;
                if(count($lListFunctionIds)> 1)
                {
                    $lDeviceName = $lDeviceName.':'.$lFunctionIdIndex;
                }
                $lDeviceType = FID::GetName($lChannelData->functionID);

 

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

                $AddValueAllDevicesLights = [
                    'parent'                => 99999,
                    'id'                    => $key,
                    'DeviceID'              => $key,
                    'DeviceName'            => $lDeviceName,
                    'DeviceType'            => $lDeviceType,
                    'Channel'               => $lChannel,
                    'Inputs'                => $lInputs,
                    'Outputs'               => $lOutputs
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
                $ValuesAllDevices[] = $AddValueAllDevicesLights;
            }
        }

        // Scenen Komponenten
        $location = $this->getPathOfCategory($this->ReadPropertyInteger('Scene_TargetCategory'));
        $lAddTypeCategory = true;
        if (count($Scenes) > 0) {
            $AddValueScenes = [
                'id'                    => 2,
                'ID'                    => '',
                'name'                  => 'scene',
                'DisplayName'           => $this->translate('Scenes'),
                'Type'                  => '',
                'ModelID'               => '',
                'Manufacturername'      => '',
                'Productname'           => ''
            ];

            $AddValueAllDevicesScenes = [
                'id'                    => 99998,
                'DeviceID'              => '',
                'DeviceName'            => $this->translate('Scenes'),
                'DeviceType'            => ''
            ];

            $Values[] = $AddValueScenes;
            $ValuesAllDevices[] = $AddValueAllDevicesScenes;

            foreach ($Scenes as $key => $sensor) {
                $instanceID = $this->getFAHDeviceInstances($key, 'sensors');
                $AddValueScenes = [
                    'parent'                => 2,
                    'ID'                    => $key,
                    'DisplayName'           => $sensor['name'],
                    'name'                  => $sensor['name'],
                    'Type'                  => $sensor['type'],
                    'ModelID'               => $sensor['modelid'],
                    'Manufacturername'      => $sensor['manufacturername'],
                    'Productname'           => '-',
                    'instanceID'            => $instanceID
                ];

                $AddValueAllDevicesScenes = [
                    'parent'                => 99998,
                    'id'                    => $key,
                    'DeviceID'              => $key,
                    'DeviceName'            => $sensor['name'],
                    'DeviceType'            => 'scene'
                ];

                $AddValueScenes['create'] = [
                    'moduleID'      => self::mDeviceModuleId,
                    'configuration' => [
                        'FAHDeviceID'    => strval($key),
                        'DeviceType'     => 'scene',
                        'SensorType'     => $sensor['type']
                    ],
                    'location' => $location
                ];

                $Values[] = $AddValueScenes;
                $ValuesAllDevices[] = $AddValueAllDevicesScenes;
            }
        }

        //DeviceManagement AllDevices
        $Form['actions'][1]['items'][6]['values'] = $ValuesAllDevices;

        //Groups
        if (count($HueDevices) > 0) {
            $AddValueGroups = [
                'id'                    => 3,
                'ID'                    => '',
                'name'                  => 'Groups',
                'DisplayName'           => $this->translate('HUE devices'),
                'Type'                  => '',
                'ModelID'               => '',
                'Manufacturername'      => '',
                'Productname'           => ''
            ];
            $Values[] = $AddValueGroups;
            foreach ($HueDevices as $key => $group) {
                $instanceID = $this->getFAHDeviceInstances($key, 'scene');

                if ($group['type'] != 'Entertainment') {
                    $AddValueGroups = [
                        'parent'                => 3,
                        'ID'                    => $key,
                        'DisplayName'           => $group['name'],
                        'name'                  => $group['name'],
                        'Type'                  => $group['type'],
                        'DeviceType'            => 'scene',
                        'ModelID'               => '-',
                        'Manufacturername'      => '-',
                        'Productname'           => '-',
                        'instanceID'            => $instanceID
                    ];

                    $AddValueGroups['create'] = [
                        'moduleID'      => self::mDeviceModuleId,
                        'configuration' => [
                            'FAHDeviceID'    => strval($key),
                            'DeviceType'     => 'scene'
                        ],
                        'location' => $location
                    ];
                    $Values[] = $AddValueGroups;
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


    private function FilterDeviceList( $AllDevices, $a_InterfaceType )
    {
        $lRetValue = new stdClass();

        foreach($AllDevices as $lDeviceId => $DeviceValue)
        {        
            if( isset($DeviceValue['interface'] ) )
            {
                if( $DeviceValue['interface'] == $a_InterfaceType )
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

    
   