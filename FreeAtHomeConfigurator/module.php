<?php

declare(strict_types=1);

class FreeAtHomeConfigurator extends IPSModule
{
    const mBridgeDataId     = '{BC9334EC-8C5C-61C2-C5DD-96FE9368F38D}';      // DatenId der Bridge
    const mDeviceModuleId   = '{BDE4603B-E68A-D3AF-2510-9462C7374097}';      // Device Modul Id 
    const mParentId         = '{9AFFB383-D756-8422-BCA0-EFD3BB1E3E29}';      // Parent Id (Bridge)

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

        $this->RegisterAttributeInteger('ProgressStatus', -1);
        $this->RegisterTimer('ProgressNewDevices', 0, 'FAHCONF_ProgressUpdateNewDevicesList(' . $this->InstanceID . ');');
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


            IPS_LogMessage( $this->InstanceID, __FUNCTION__.": ".$key." -> ".json_encode($lDevice) );

            $instanceID = $this->getFAHDeviceInstances($key, 'devices');
            $AddValueLights = [
                'parent'                => 1,
                'ID'                    => $key,
                'DisplayName'           => $lDevice['displayName'],
                'name'                  => $lDevice['displayName'],
                'Type'                  => json_encode($lDevice['channels']),
                'ModelID'               => '-',
                'Manufacturername'      => ((array_key_exists($lDevice['interface'], self::m_Types)) ? self::m_Types[$lDevice['interface']] : '?'.$lDevice->interface.'?'),
                'Productname'           => '-',
                'instanceID'            => $instanceID
            ];

            $AddValueAllDevicesLights = [
                'parent'                => 99999,
                'id'                    => $key,
                'DeviceID'              => $key,
                'DeviceName'            => $lDevice['displayName'],
                'DeviceType'            => json_encode($lDevice['channels'])
            ];

            $AddValueLights['create'] = [
                'moduleID'      => self::mDeviceModuleId,
                'configuration' => [
                    'FAHDeviceID'    => $key,
                    'DeviceType'     => json_encode($lDevice['channels'])
                ],
                'location' => $location
            ];

            $Values[] = $AddValueLights;
            $ValuesAllDevices[] = $AddValueAllDevicesLights;
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

    public function reloadAllDevices()
    {
        $Devices = $this->getFAH_AllDevices();
        $Scenes = $this->getFAHScenes();

        //Lights
        if (count($Devices) > 0) {
            $AddValueAllDevicesLights = [
                'id'                    => 99999,
                'DeviceID'              => '',
                'DeviceName'            => $this->translate('Lights'),
                'DeviceType'            => '',
                'expanded'              => true
            ];
        }
        $ValuesAllDevices[] = $AddValueAllDevicesLights;

        foreach ($Devices as $key => $light) {
            $AddValueAllDevicesLights = [
                'parent'                => 99999,
                'id'                    => $key,
                'DeviceID'              => $key,
                'DeviceName'            => $light['name'],
                'DeviceType'            => 'lights'
            ];
            $ValuesAllDevices[] = $AddValueAllDevicesLights;
        }
        //Sensors
        if (count($Scenes) > 0) {
            $AddValueAllDevicesScenes = [
                'id'                    => 99998,
                'DeviceID'              => '',
                'DeviceName'            => $this->translate('Sensors'),
                'DeviceType'            => '',
                'expanded'              => true
            ];
            $ValuesAllDevices[] = $AddValueAllDevicesScenes;
            foreach ($Scenes as $key => $sensor) {
                $AddValueAllDevicesScenes = [
                    'parent'                => 99998,
                    'id'                    => $key,
                    'DeviceID'              => $key,
                    'DeviceName'            => $sensor['name'],
                    'DeviceType'            => 'sensors'
                ];
                $ValuesAllDevices[] = $AddValueAllDevicesScenes;
            }
        }
        $this->UpdateFormField('AllDevices', 'values', json_encode($ValuesAllDevices));
    }

    //Functions for Device Management / Pairing (New Devices)

    public function renameDevice(string $NewName, int $DeviceID, string $DeviceType)
    {
        $Data = [];
        $Buffer = [];
        $Data['DataID'] = self::mBridgeDataId;
        $Buffer['Command'] = 'renameDevice';
        $Buffer['DeviceType'] = $DeviceType;
        $Buffer['DeviceID'] = $DeviceID;
        $Buffer['Params'] = ['name' => $NewName];
        $Data['Buffer'] = $Buffer;
        $Data = json_encode($Data);
        $result = json_decode($this->SendDataToParent($Data), true);
        if (!$result) {
            return [];
        }
        $this->parseError($result);
        $this->reloadAllDevices();
    }

    public function deleteDevice(int $DeviceID, string $DeviceType)
    {
        $Data = [];
        $Buffer = [];
        $Data['DataID'] = self::mBridgeDataId;
        $Buffer['Command'] = 'deleteDevice';
        $Buffer['DeviceType'] = $DeviceType;
        $Buffer['DeviceID'] = $DeviceID;
        $Data['Buffer'] = $Buffer;
        $Data = json_encode($Data);
        $result = json_decode($this->SendDataToParent($Data), true);
        if (!$result) {
            return [];
        }
        $this->parseError($result);
        $this->reloadAllDevices();
    }

    public function scanNewDevices()
    {
        $Data = [];
        $Buffer = [];

        $Data['DataID'] = self::mBridgeDataId;
        $Buffer['Command'] = 'scanNewDevices';
        $Buffer['Params'] = '';
        $Data['Buffer'] = $Buffer;
        $Data = json_encode($Data);
        $result = json_decode($this->SendDataToParent($Data), true);
        if (!$result) {
            return [];
        }
        $this->UpdateFormField('LastScan', 'caption', $result[0]['success']['/lights']);
        //Progress Timer für getNewDevice
        $this->SetTimerInterval('ProgressNewDevices', 1000);
        return $result;
    }

    public function getNewDevices(string $DeviceType)
    {
        $Data = [];
        $Buffer = [];

        $Data['DataID'] = self::mBridgeDataId;

        switch ($DeviceType) {
            case 'Lights':
                $Buffer['Command'] = 'getNewLights';
                break;
            case 'Sensors':
                $Buffer['Command'] = 'getNewSensors';
                break;
            default:
                return [];
            }

        $Buffer['Params'] = '';
        $Data['Buffer'] = $Buffer;
        $Data = json_encode($Data);
        $result = json_decode($this->SendDataToParent($Data), true);
        if (!$result) {
            return [];
        }
        return $result;
    }

    public function getGroupAttributes(int $id)
    {
        $Data = [];
        $Buffer = [];
        $Data['DataID'] = self::mBridgeDataId;
        $Buffer['Command'] = 'getGroupAttributes';
        $Buffer['Params'] = ['GroupID' => $id];
        $Data['Buffer'] = $Buffer;
        $Data = json_encode($Data);
        $result = json_decode($this->SendDataToParent($Data), true);
        if (!$result) {
            return [];
        }
        return $result;
    }

    public function ProgressUpdateNewDevicesList()
    {
        $ValuesLights = [];
        $ValuesSensors = [];
        $NewLights = $this->getNewDevices('Lights');
        $NewSensors = $this->getNewDevices('Sensors');

        $this->WriteAttributeInteger('ProgressStatus', $this->ReadAttributeInteger('ProgressStatus') + 1);
        $this->UpdateFormField('ProgressNewDevices', 'current', $this->ReadAttributeInteger('ProgressStatus'));

        $this->UpdateFormField('LastScan', 'caption', $NewLights['lastscan']);
        //For Debug
        //sleep(3);
        //$NewDevices = json_decode('{"7": {"name": "Hue Lamp 7"},"8": {"name": "Hue Lamp 8"},"lastscan": "2012-10-29T12:00:00"}',true);
        foreach ($NewLights as $key => $Light) {
            if ($key != 'lastscan') {
                $ValueNewLight = [
                    'DeviceID'   => $key,
                    'DeviceName' => $Light['name']
                ];
                $ValuesLights[] = $ValueNewLight;
            }
        }
        $this->UpdateFormField('NewLights', 'values', json_encode($ValuesLights));

        foreach ($NewSensors as $key => $Sensor) {
            if ($key != 'lastscan') {
                $ValueNewSensor = [
                    'DeviceID'   => $key,
                    'DeviceName' => $Sensor['name']
                ];
                $ValuesSensors[] = $ValueNewSensor;
            }
        }
        $this->UpdateFormField('NewSensors', 'values', json_encode($ValuesSensors));

        if ($NewLights['lastscan'] != 'active' && $NewSensors['lastscan'] != 'active') {
            $this->SetTimerInterval('ProgressNewDevices', 0);
            $this->WriteAttributeInteger('ProgressStatus', 0);
        }
    }

    //Group Function

    public function LoadGroupConfigurationForm()
    {
        $this->UpdateGroupsForConfiguration();
        $this->UpdateLightsForNewGroup();
    }

    public function UpdateAllLightsInGroupsForConfiguration(int $id)
    {
        $Group = $this->getGroupAttributes($id);
        foreach ($Group['lights'] as $key => $light) {
            $Value = [
                'DeviceID'   => $light,
                'DeviceName' => '',
            ];
            $Values[] = $Value;
        }

        if (empty($Group['lights'])) {
            $Values = [];
        }

        $this->UpdateFormField('AllLightsInGroup', 'values', json_encode($Values));
    }

    public function createGroup(string $GroupName, string $GroupType, string $class = 'Other', int $Light = 0)
    {
        $Buffer = [];
        $Data = [];

        if ($GroupType == 'Room') {
            $Buffer['Params'] = ['name' => $GroupName, 'type' => $GroupType, 'class' => $class];
        } else {
            if ($Light == 0) {
                $this->UpdateFormField('PopupLightGroupFailed', 'visible', 'true');
                return;
            }
            $Buffer['Params'] = ['name' => $GroupName, 'type' => $GroupType, 'lights' => [strval($Light)]];
        }
        $Data['DataID'] = self::mBridgeDataId;
        $Buffer['Command'] = 'createGroup';
        $Data['Buffer'] = $Buffer;
        $Data = json_encode($Data);
        $result = json_decode($this->SendDataToParent($Data), true);
        if (!$result) {
            return [];
        }
        if ($this->parseError($result)) {
            $this->LoadGroupConfigurationForm();
        }
    }

    public function addLightToGroup(int $DeviceID, int $GroupID)
    {
        $Group = $this->getGroupAttributes($GroupID);

        if (array_key_exists('lights', $Group)) {
            array_push($Group['lights'], strval($DeviceID));
        } else {
            $Group['lights'][0] = strval($DeviceID);
        }
        $params = ['name' => $Group['name'], 'lights' => $Group['lights']];

        $this->setGroupAttributes($GroupID, $params);
        $this->UpdateAllLightsInGroupsForConfiguration($GroupID);
    }

    public function deleteLightFromGroup(int $DeviceID, int $GroupID)
    {
        $Group = $this->getGroupAttributes($GroupID);

        if (array_key_exists('lights', $Group)) {
            $key = array_search($DeviceID, $Group['lights']);
            unset($Group['lights'][$key]);
            $Group['lights'] = array_values($Group['lights']);
        } else {
            return;
        }
        $params = ['name' => $Group['name'], 'lights' => $Group['lights']];

        $this->setGroupAttributes($GroupID, $params);
        $this->UpdateAllLightsInGroupsForConfiguration($GroupID);
    }

    public function deleteGroup(int $GroupID)
    {
        $Data = [];
        $Buffer = [];
        $Data['DataID'] = self::mBridgeDataId;
        $Buffer['Command'] = 'deleteGroup';
        $Buffer['GroupID'] = $GroupID;
        $Data['Buffer'] = $Buffer;
        $Data = json_encode($Data);
        $result = json_decode($this->SendDataToParent($Data), true);
        if (!$result) {
            return [];
        }
        if ($this->parseError($result)) {
            $this->LoadGroupConfigurationForm();
            $this->UpdateFormField('AllLightsInGroup', 'values', json_encode([]));
        }
    }

    private function getFAHDeviceInstances($HueDeviceID, $DeviceType)
    {
       $InstanceIDs = IPS_GetInstanceListByModuleID(self::mDeviceModuleId); //FAHDevice
       foreach ($InstanceIDs as $id) {
           if (IPS_GetProperty($id, 'FAHDeviceID') == $HueDeviceID && IPS_GetProperty($id, 'DeviceType') == $DeviceType) {
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

        IPS_LogMessage( $this->InstanceID, json_encode($lRetValue) );

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

    private function UpdateGroupsForConfiguration()
    {
        $Option = [
            'caption'   => 'All',
            'value'     => 0,
        ];

        $Options[] = $Option;

        $HueDevices = $this->getHUEDevices();
        foreach ($HueDevices as $key => $group) {
            if ($group['type'] != 'Entertainment') {
                $Option = [
                    'caption'   => $group['name'],
                    'value'     => $key,
                ];
                $Options[] = $Option;
            }
            $this->UpdateFormField('Groups', 'options', json_encode($Options));
        }
    }

    private function UpdateLightsForNewGroup()
    {
        $Devices = $this->getFAH_AllDevices();
        foreach ($Devices as $key => $light) {
            $Value = [
                'DeviceID'   => $key,
                'DeviceName' => $light['name']
            ];
            $Values[] = $Value;
        }
        $this->UpdateFormField('AllLights', 'values', json_encode($Values));
    }

    private function setGroupAttributes($GroupID, $params)
    {
        $Data = [];
        $Buffer = [];
        $Data['DataID'] = self::mBridgeDataId;
        $Buffer['Command'] = 'setGroupAttributes';
        $Buffer['GroupID'] = $GroupID;
        $Buffer['Params'] = $params;
        $Data['Buffer'] = $Buffer;
        $Data = json_encode($Data);
        $result = json_decode($this->SendDataToParent($Data), true);
        if (!$result) {
            return [];
        }
        $this->parseError($result);
    }

    //End Functions for Group Gonfigurator

    private function parseError($result)
    {
        if (array_key_exists('error', $result[0])) {
            $this->LogMessage('Philips HUE Error: ' . $result[0]['error']['type'] . ': ' . $result[0]['error']['address'] . ' - ' . $result[0]['error']['description'], KL_ERROR);
            $this->UpdateFormField('PopupFailed', 'visible', true);
            return false;
        } elseif (array_key_exists('success', $result[0])) {
            $this->UpdateFormField('PopupSuccess', 'visible', true);
            return true;
        } else {
            $this->LogMessage('Philips HUE unknown Error: ' . print_r($result, true), KL_ERROR);
            $this->UpdateFormField('PopupFailed', 'visible', true);
            return false;
        }
    }
}
