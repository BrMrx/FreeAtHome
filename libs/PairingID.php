<?php


class PID
{
	const mMapPairingID = [
		'INVALID'                                       =>[ 'ID' => 0x0000 ], // invalid pairing
		'SWITCH_ON_OFF'                                 =>[ 'ID' => 0x0001 ], // DPT_SWITCH	1BIT
		'TIMED_START_STOP'                              =>[ 'ID' => 0x0002 ], // DPT_START	1BIT
		'FORCED'                                        =>[ 'ID' => 0x0003 ], // DPT_SWITCH_CONTROL	2BIT
		'SCENE_CONTROL'                                 =>[ 'ID' => 0x0004 ], // DPT_SCENE_CONTROL	1BYTE
		'DOOR_OPENER'                                   =>[ 'ID' => 0x0005 ], // DPT_START	1BIT
		'TIMED_MOVEMENT'                                =>[ 'ID' => 0x0006,   // DPT_START	1BIT
															'info' => 'Motion',
															'type'  	=> 0,                    // bool
															'profile' 	=> '~Motion'],         // Darstellungsprofil
		'TIMED_PRESENCE'                                =>[ 'ID' => 0x0007 ], // DPT_START	1BIT
		'RELATIVE_SET_VALUE_CONTROL'                    =>[ 'ID' => 0x0010 ], // DPT_CONTROL_DIMMING	4BIT
		'ABSOLUTE_SET_VALUE_CONTROL'                    =>[ 'ID' => 0x0011 ], // DPT_SCALING	1BYTE	Absolute control of the set value
		'NIGHT'                                         =>[ 'ID' => 0x0012 ], // DPT_BOOL	1BIT
		'RESET_ERROR'                                   =>[ 'ID' => 0x0013 ], // Resets load failures / short circuits / etc
		'NIGHT_ACTUATOR_FOR_SYSAP'                      =>[ 'ID' => 0x0014 ], // DPT_BOOL	1BIT
		'RGB'                                           =>[ 'ID' => 0x0015 ], // DPT_COLOUR_RGB	3BYTE
		'COLOR_TEMPERATURE'                             =>[ 'ID' => 0x0016 ], // DPT_SCALING	1BYTE	Color temperature
		'HSV'                                           =>[ 'ID' => 0x0017 ], // DPT_COLOUR_HSV	4BYTE
		'COLOR'                                         =>[ 'ID' => 0x0018 ], // DPT_BIT_SET_16	2BYTE	Hue (2 Byte)
		'SATURATION'                                    =>[ 'ID' => 0x0019 ], // DPT_SCALING	1BYTE	Saturation (1 Byte)
		'ABSOLUTE_SET_VALUE_CONTROL_HUE'                =>[ 'ID' => 0x001A ], // DPT_SCALING	1BYTE	Absolute control of the set value (does not switch on the light)
		'MOVE_UP_DOWN'                                  =>[ 'ID' => 0x0020 ], // DPT_UP_DOWN	1BIT
		'STOP_STEP_UP_DOWN'                             =>[ 'ID' => 0x0021 ], // DPT_STEP	1BIT
		'DEDICATED_STOP'                                =>[ 'ID' => 0x0022 ], // DPT_START	1BIT
		'SET_ABSOLUTE_POSITION_BLINDS_PERCENTAGE'       =>[ 'ID' => 0x0023 ], // DPT_SCALING	1BYTE	Moves the sunblinds into a specified position
		'SET_ABSOLUTE_POSITION_SLATS_PERCENTAGE'        =>[ 'ID' => 0x0024 ], // DPT_SCALING	1BYTE	Moves the slats into a specified position
		'WIND_ALARM'                                    =>[ 'ID' => 0x0025,   // DPT_ALARM	1BIT
															'info' => 'Wind alert',
															'type'  	=> 0,                    // bool
															'profile' 	=> 'FAH.WindAlarm'],         // Darstellungsprofil
		'FROST_ALARM'                                   =>[ 'ID' => 0x0026, // DPT_ALARM	1BIT
															'info' => 'Frost',
															'type'  	=> 0,                    // bool
															'profile' 	=> 'FAH.FrostAlarm'],         // Darstellungsprofil
		'RAIN_ALARM'                                    =>[ 'ID' => 0x0027,   // DPT_ALARM	1BIT
															'info' => 'Rain',
															'type'  	=> 0,                    // bool
															'profile' 	=> '~Raining'],         // Darstellungsprofil
		'FORCED_UP_DOWN'                                =>[ 'ID' => 0x0028 ], // DPT_SWITCH_CONTROL	2BIT
		'WINDOW_DOOR_POSITION'                          =>[ 'ID' => 0x0029 ], // DPT_SCALING	1BYTE	Delivers position for Window/Door (Open / Tilted / Closed)
		'ACTUATING_VALUE_HEATING'                       =>[ 'ID' => 0x0030 ], // DPT_SCALING	1BYTE	Determines the through flow volume of the control valve
		'FAN_COIL_LEVEL'                                =>[ 'ID' => 0x0031 ], // DPT_ENUM_0_TO_3	2BIT	Display value of the fan coil speed. (0=off / 1=lowest - 5=fastest)
		'ACTUATING_VALUE_COOLING'                       =>[ 'ID' => 0x0032 ], // DPT_SCALING	1BYTE	Determines the through flow volume of the control valve
		'SET_POINT_TEMPERATURE'                         =>[ 'ID' => 0x0033 ], // DPT_VALUE_TEMP	2BYTE	Defines the displayed set point temperature of the system
		'RELATIVE_SET_POINT_TEMPERATURE'                =>[ 'ID' => 0x0034 ], // DPT_VALUE_TEMP	2BYTE	Defines the relative set point temperature of the system
		'WINDOW_DOOR'                                   =>[ 'ID' => 0x0035,   // DPT_WINDOW_DOOR	1BIT
															'info' => 'State',
															'type'  	=> 0,                    // bool
															'profile' 	=> '~Window'],         // Darstellungsprofil
		'STATE_INDICATION'                              =>[ 'ID' => 0x0036 ], // DPT_BIT_SET_8	1BYTE	states: on/off heating/cooling; eco/comfort; frost/not frost
		'FAN_MANUAL_ON_OFF'                             =>[ 'ID' => 0x0037 ], // DPT_SWITCH	1BIT
		'CONTROLLER_ON_OFF'                             =>[ 'ID' => 0x0038 ], // DPT_SWITCH	1BIT
		'RELATIVE_SET_POINT_REQUEST'                    =>[ 'ID' => 0x0039 ], // DPT_VALUE_TEMP	2BYTE	Request for a new relative set point value
		'ECO_ON_OFF'                                    =>[ 'ID' => 0x003A ], // DPT_SWITCH	1BIT
		'COMFORT_TEMPERATURE'                           =>[ 'ID' => 0x003B ], // DPT_VALUE_TEMP	2BYTE	Sends the current comfort temperature
		'ABSOLUTE_SET_VALUE_CONTROL_WHITE'              =>[ 'ID' => 0x003C ], // DPT_SCALING	1BYTE	Absolute control of the white set value
		'SELECTED_HEATING_COOLING_MODE_REQUEST'         =>[ 'ID' => 0x003D ], // DPT_BIT_SET_8	1BYTE	Request a change in selected heating/cooling mode
		'INFO_HEATING_COOLING_MODE'                     =>[ 'ID' => 0x003E ], // DPT_BIT_SET_8	1BYTE	Info heating/cooling mode
		'FAN_STAGE_REQUEST'                             =>[ 'ID' => 0x0040 ], // DPT_ENUM_0_TO_3	2BIT	Request for a new manual fan stage
		'FAN_MANUAL_ON_OFF_REQUEST'                     =>[ 'ID' => 0x0041 ], // DPT_SWITCH	1BIT
		'CONTROLLER_ON_OFF_REQUEST'                     =>[ 'ID' => 0x0042 ], // DPT_SWITCH	1BIT
		'VALUE_ADDITIONAL_HEATING'                      =>[ 'ID' => 0x0043 ], // DPT_SWITCH	1BIT
		'ECO_ON_OFF_INDICATION'                         =>[ 'ID' => 0x0044 ], // DPT_SWITCH	1BIT
		'AWAY'                                          =>[ 'ID' => 0x0050 ], // DPT_ENUM_8	1BYTE	Indicates auto mode
		'INFO_ON_OFF'                                   =>[ 'ID' => 0x0100,   // DPT_SWITCH	1BIT
															'info'  	=> 'State',             // Zustand
															'type'  	=> 0,                   // bool
															'profile' 	=> '~Switch',           // Darstellungsprofil
															'action' 	=> 'SWITCH_ON_OFF'],    // Action die damit verbunden ist
		'INFO_FORCE'                                    =>[ 'ID' => 0x0101 ], // DPT_ENUM_8	1BYTE	Indicates the cause of forced operation (0 = not forced)
		'SYSAP_INFO_ON_OFF'                             =>[ 'ID' => 0x0105 ], // DPT_SWITCH	1BIT
		'SYSAP_INFO_FORCE'                              =>[ 'ID' => 0x0106 ], // DPT_ENUM_8	1BYTE	Indicates whether the actuator group is forced (1) or not forced (0)
		'INFO_ACTUAL_DIMMING_VALUE'                     =>[ 'ID' => 0x0110,   // DPT_SCALING	1BYTE	Reflects the actual value of the actuator
															'info'  	=> 'Brightness',        // Helligkeit
															'type'  	=> 1,                   // int
															'profile' 	=> '~Intensity.100',    // Helligkeit 0-100%
															'action' 	=> 'ABSOLUTE_SET_VALUE_CONTROL'],    // Helligkeit 0-100% setzen
		'INFO_ERROR'                                    =>[ 'ID' => 0x0111 ], // DPT_BIT_SET_32	4BYTE	Indicates load failures / short circuits / etc
		'SYSAP_INFO_ACTUAL_DIMMING_VALUE'               =>[ 'ID' => 0x0115 ], // DPT_SCALING	1BYTE	Reflects the actual value of the actuator group
		'SYSAP_INFO_ERROR'                              =>[ 'ID' => 0x0116 ], // DPT_BIT_SET_32	4BYTE	Indicates load failures / short circuits / etc
		'INFO_RGB'                                      =>[ 'ID' => 0x0117,	  // DPT_COLOUR_RGB	3BYTE
															'info' 		=> 'Colour',
															'type' 		=> 1,
															'profile' 	=> '~HexColor',
														    'action' 	=> 'RGB' ], // DPT_COLOUR_RGB	3BYTE
		'INFO_COLOR_TEMPERATURE'                        =>[ 'ID' => 0x0118 ], // DPT_SCALING	1BYTE	Color temperature
		'SYSAP_INFO_RGB'                                =>[ 'ID' => 0x0119 ], // DPT_COLOUR_RGB	3BYTE
		'SYSAP_INFO_COLOR_TEMPERATURE'                  =>[ 'ID' => 0x011A ], // DPT_SCALING	1BYTE	Color temperature
		'INFO_HSV'                                      =>[ 'ID' => 0x011B ], // DPT_COLOUR_HSV	4BYTE
		'SYSAP_INFO_HSV'                                =>[ 'ID' => 0x011C ], // DPT_COLOUR_HSV	4BYTE
		'INFO_COLOR_MODE'                               =>[ 'ID' => 0x011D ], // DPT_COLOUR_MODE	1BIT
		'SYSAP_INFO_COLOR_MODE'                         =>[ 'ID' => 0x011E ], // DPT_COLOUR_MODE	1BIT
		'COLOR_MODE'                                    =>[ 'ID' => 0x011F ], // DPT_COLOUR_MODE	1BIT
		'INFO_MOVE_UP_DOWN'                             =>[ 'ID' => 0x0120 ], // DPT_ENUM_0_TO_3	2BIT	Indicates last moving direction and whether moving currently or not
		'CURRENT_ABSOLUTE_POSITION_BLINDS_PERCENTAGE'   =>[ 'ID' => 0x0121,
															'info' 		=> 'Position',
															'type' 		=> 1,
															'profile' 	=> '~Shutter',
															'action' 	=> 'SET_ABSOLUTE_POSITION_BLINDS_PERCENTAGE' ], // DPT_SCALING	1BYTE	Indicate the current position of the sunblinds in percentage
		'CURRENT_ABSOLUTE_POSITION_SLATS_PERCENTAGE'    =>[ 'ID' => 0x0122 ], // DPT_SCALING	1BYTE	Indicate the current position of the slats in percentage
		'VALID_CURRENT_ABSOLUTE_POSITION'               =>[ 'ID' => 0x0123 ], // DPT_SWITCH	1BIT
		'SYSAP_INFO_MOVE_UP_DOWN'                       =>[ 'ID' => 0x0125 ], // DPT_ENUM_0_TO_3	2BIT	Indicates last moving direction and whether moving currently or not of the actuator group
		'SYSAP_CURRENT_ABSOLUTE_POSITION_BLINDS_PERCENTAGE' =>[ 'ID' => 0x0126 ], 	// DPT_SCALING	1BYTE	indicate the current position of the sunblinds in percentage of the actuator group
		'SYSAP_CURRENT_ABSOLUTE_POSITION_SLATS_PERCENTAGE' =>[ 'ID' => 0x0127 ], 	// DPT_SCALING	1BYTE	indicate the current position of the slats in percentage of the actuator group
		'CAPBP_AND_CAPSP_VALID'                         =>[ 'ID' => 0x0128 ], // Indicates whether the Datapoints CAPBP CAPSP are valid
		'MEASURED_TEMPERATURE'                          =>[ 'ID' => 0x0130 ], // DPT_VALUE_TEMP	2BYTE	Indicates the actual measured temperature
		'INFO_VALUE_HEATING'                            =>[ 'ID' => 0x0131 ], // DPT_SCALING	1BYTE	States the current flow volume of the conrol valve
		'INFO_VALUE_COOLING'                            =>[ 'ID' => 0x0132 ], // DPT_SCALING	1BYTE	States the current flow volume of the conrol valve
		'RESET_OVERLOAD'                                =>[ 'ID' => 0x0133 ], // DPT_SWITCH	1BIT
		'OVERLOAD_DETECTION'                            =>[ 'ID' => 0x0134 ], // Indicates overload / short circuit / etc
		'HEATING_COOLING'                               =>[ 'ID' => 0x0135 ], // DPT_SWITCH	1BIT
		'ACTUATING_FAN_STAGE_HEATING'                   =>[ 'ID' => 0x0136 ], // DPT_VALUE_1_UCOUNT	1BYTE	Requests a new manual fan stage from actuator in heating mode
		'DEPRECATED_0137'                               =>[ 'ID' => 0x0137 ], // Switches Fan in manual heating control mode
		'DEPRECATED_0138'                               =>[ 'ID' => 0x0138 ], // Feedback for current fan stage in heating mode
		'DEPRECATED_0139'                               =>[ 'ID' => 0x0139 ], // Feedback for manual fan control heating mode
		'INFO_ABSOLUTE_SET_POINT_REQUEST'               =>[ 'ID' => 0x0140 ], // DPT_VALUE_TEMP	2BYTE	Absolute set point temperature input for timer
		'INFO_ACTUATING_VALUE_ADD_HEATING'              =>[ 'ID' => 0x0141 ], // DPT_SWITCH	1BIT
		'INFO_ACTUATING_VALUE_ADD_COOLING'              =>[ 'ID' => 0x0142 ], // DPT_SWITCH	1BIT
		'ACTUATING_VALUE_ADD_HEATING'                   =>[ 'ID' => 0x0143 ], // DPT_SWITCH	1BIT
		'ACTUATING_VALUE_ADD_COOLING'                   =>[ 'ID' => 0x0144 ], // DPT_SWITCH	1BIT
		'INFO_FAN_ACTUATING_STAGE_HEATING'              =>[ 'ID' => 0x0145 ], // DPT_VALUE_1_UCOUNT	1BYTE	Feedback from FCA
		'INFO_FAN_MANUAL_ON_OFF_HEATING'                =>[ 'ID' => 0x0146 ], // DPT_SWITCH	1BIT
		'ACTUATING_FAN_STAGE_COOLING'                   =>[ 'ID' => 0x0147 ], // DPT_VALUE_1_UCOUNT	1BYTE	Requests a new manual fan stage from actuator in cooling mode
		'DEPRECATED_0148'                               =>[ 'ID' => 0x0148 ], // Switches Fan in manual cooling control mode
		'INFO_FAN_ACTUATING_STAGE_COOLING'              =>[ 'ID' => 0x0149 ], // DPT_VALUE_1_UCOUNT	1BYTE	Feedback for current fan stage in cooling mode
		'INFO_FAN_MANUAL_ON_OFF_COOLING'                =>[ 'ID' => 0x014A ], // DPT_SWITCH	1BIT
		'HEATING_ACTIVE'                                =>[ 'ID' => 0x014B ], // DPT_SWITCH	1BIT
		'COOLING_ACTIVE'                                =>[ 'ID' => 0x014C ], // DPT_SWITCH	1BIT
		'HEATING_DEMAND'                                =>[ 'ID' => 0x014D ], // DPT_SCALING	1BYTE
		'COOLING_DEMAND'                                =>[ 'ID' => 0x014E ], // DPT_SCALING	1BYTE
		'INFO_HEATING_DEMAND'                           =>[ 'ID' => 0x014F ], // DPT_SCALING	1BYTE
		'INFO_COOLING_DEMAND'                           =>[ 'ID' => 0x0150 ], // DPT_SCALING	1BYTE
		'HUMIDITY'                                      =>[ 'ID' => 0x0151 ], // DPT_SCALING	1BYTE	Measured Humidity
		'AUX_ON_OFF_REQUEST'                            =>[ 'ID' => 0x0152 ], // DPT_SWITCH	1BIT
		'AUX_ON_OFF_RESPONSE'                           =>[ 'ID' => 0x0153 ], // DPT_SWITCH	1BIT
		'HEATING_ON_OFF_REQUEST'                        =>[ 'ID' => 0x0154 ], // DPT_SWITCH	1BIT
		'COOLING_ON_OFF_REQUEST'                        =>[ 'ID' => 0x0155 ], // DPT_SWITCH	1BIT
		'INFO_OPERATION_MODE'                           =>[ 'ID' => 0x0156 ], // DPT_BIT_SET_8	1BYTE
		'INFO_SWING_MODE'                               =>[ 'ID' => 0x0157 ], // DPT_BIT_SET_8	1BYTE
		'SUPPORTED_FEATURES'                            =>[ 'ID' => 0x0158 ], // DPT_BIT_SET_32	4BYTE
		'EXTENDED_STATUS'                               =>[ 'ID' => 0x0159 ], // DPT_BIT_SET_32	4BYTE
		'EXTENDED_STATUS_US'                            =>[ 'ID' => 0x015A ], // DPT_BIT_SET_16	2BYTE
		'AUX_HEATING_ON_OFF_REQUEST'                    =>[ 'ID' => 0x015B ], // DPT_SWITCH	1BIT
		'EMERGENCY_HEATING_ON_OFF_REQUEST'              =>[ 'ID' => 0x015C ], // DPT_SWITCH	1BIT
		'RELATIVE_FAN_SPEED_CONTROL'                    =>[ 'ID' => 0x0160 ], // DPT_CONTROL_DIMMING	4BIT
		'ABSOLUTE_FAN_SPEED_CONTROL'                    =>[ 'ID' => 0x0161 ], // DPT_SCALING	1BYTE	Absolute control of the set value
		'INFO_ABSOLUTE_FAN_SPEED'                       =>[ 'ID' => 0x0162 ], // DPT_SCALING	1BYTE	Reflects the actual value of the actuator
		'SYSAP_INFO_ABSOLUTE_FAN_SPEED'                 =>[ 'ID' => 0x0163 ], // DPT_SCALING	1BYTE	Reflects the actual value of the actuator
		'TIMED_MOVEMENT_REQUEST'                        =>[ 'ID' => 0x0164 ], // DPT_START	1BIT
		'INFO_TIMED_MOVEMENT'                           =>[ 'ID' => 0x0165 ], // DPT_ENUM_0_TO_63	6BIT	Reflects the actual value of the actuator
		'MOVEMENT_DETECTOR_STATUS'                      =>[ 'ID' => 0x0166 ], // DPT_BIT_SET_8	1BYTE	Reflects the actual value of the actuator

		'LOCK_SENSOR'                                   =>[ 'ID' => 0x0167 ], // DPT_SWITCH	1BIT
		'INFO_LOCKED_SENSOR'                            =>[ 'ID' => 0x0168 ], // DPT_STATE	1BIT
		'SYSAP_INFO_LOCKED_SENSOR'                      =>[ 'ID' => 0x0169 ], // DPT_STATE	1BIT
		'INFO_VALUE_WHITE'                              =>[ 'ID' => 0x0170 ], // DPT_SCALING	1BYTE	Feedback value white
		'SYSAP_INFO_VALUE_WHITE'                        =>[ 'ID' => 0x0171 ], // DPT_SCALING	1BYTE	SysAP Feedback value white
		'NOTIFICATION_FLAGS'                            =>[ 'ID' => 0x01A0 ], // DPT_BIT_SET_16	2BYTE	Notifications of RF devices (e. g. Battery low)
		'INFO_LOCAL_TIMER_CONTROL_8'                    =>[ 'ID' => 0x01A1 ], // DPT_TIMER_CONTROL_8	2BYTE
		'INFO_GROUP_TIMER_CONTROL_8'                    =>[ 'ID' => 0x01A2 ], // DPT_TIMER_CONTROL_8	2BYTE
		'MWIRE_SWITCH_ON_OFF'                           =>[ 'ID' => 0x01A3 ], // DPT_SWITCH	1BIT
		'MWIRE_RELATIVE_SET_VALUE_CONTROL'              =>[ 'ID' => 0x01A4 ], // DPT_CONTROL_DIMMING	4BIT
		'MWIRE_MOVE_UP_DOWN'                            =>[ 'ID' => 0x01A5 ], // DPT_UP_DOWN	1BIT
		'MWIRE_STOP_STEP_UP_DOWN'                       =>[ 'ID' => 0x01A6 ], // DPT_STEP	1BIT
		'MWIRE_PRESET'                                  =>[ 'ID' => 0x01A7 ], // DPT_SCENE_A_B	1BIT
		'INFO_LOCAL_TIMER_CONTROL_32'                   =>[ 'ID' => 0x01A8 ], // DPT_TIMER_CONTROL_32	8BYTE
		'INFO_GROUP_TIMER_CONTROL_32'                   =>[ 'ID' => 0x01A9 ], // DPT_TIMER_CONTROL_32	8BYTE
		'TRIGGERED_PIR_MASK'                            =>[ 'ID' => 0x01AA ], // DPT_BIT_SET_8	1BYTE
		'TIMEFRAME_MOVEMENT'                            =>[ 'ID' => 0x01AB ], // DPT_START	1BIT
		'TIMED_DIMMING'                                 =>[ 'ID' => 0x01AC ], // DPT_SCALING_SPEED	3BYTE
		'INFO_TIMED_DIMMING'                            =>[ 'ID' => 0x01AD ], // DPT_SCALING_SPEED	3BYTE
		'DEPRECATED_0200'                               =>[ 'ID' => 0x0200 ], // Notification
		'BOOL_VALUE_1'                                  =>[ 'ID' => 0x0280 ], // DPT_SWITCH	1BIT
		'BOOL_VALUE_2'                                  =>[ 'ID' => 0x0281 ], // DPT_SWITCH	1BIT
		'BOOL_VALUE_3'                                  =>[ 'ID' => 0x0282 ], // DPT_SWITCH	1BIT
		'BOOL_VALUE_4'                                  =>[ 'ID' => 0x0283 ], // Bool Value 4
		'SCALING_VALUE_1'                               =>[ 'ID' => 0x0290 ], // DPT_SCALING	1BYTE	Scaling Value 1
		'SCALING_VALUE_2'                               =>[ 'ID' => 0x0291 ], // DPT_SCALING	1BYTE	Scaling Value 2
		'SCALING_VALUE_3'                               =>[ 'ID' => 0x0292 ], // DPT_SCALING	1BYTE	Scaling Value 3
		'SCALING_VALUE_4'                               =>[ 'ID' => 0x0293 ], // Scaling Value 4
		'UNLOCK'                                        =>[ 'ID' => 0x02A0 ], // DPT_TRIGGER	1BIT
		'LOCATOR_BEEP'                                  =>[ 'ID' => 0x02C0 ], // DPT_TRIGGER	1BIT
		'SWITCH_TEST_ALARM'                             =>[ 'ID' => 0x02C1 ], // DPT_SWITCH	1BIT
		'TEST_ALARM_ACTIVE'                             =>[ 'ID' => 0x02C2 ], // DPT_ALARM	1BIT
		'FIRE_ALARM_ACTIVE'                             =>[ 'ID' => 0x02C3 ], // DPT_ALARM	1BIT
		'CO_ALARM_ACTIVE'                               =>[ 'ID' => 0x02C4 ], // DPT_ALARM	1BIT
		'REMOTE_LOCATE'                                 =>[ 'ID' => 0x02C5 ], // DPT_TRIGGER	1BIT
		'DETECTOR_PAIRING_MODE'                         =>[ 'ID' => 0x02C6 ], // DPT_SWITCH	1BIT
		'INFO_DETECTOR_PAIRING_MODE'                    =>[ 'ID' => 0x02C7 ], // DPT_SWITCH	1BIT
		'FLOOD_ALARM'                                   =>[ 'ID' => 0x02C8 ], // DPT_SWITCH	1BIT
		'SET_OPERATING_MODE'                            =>[ 'ID' => 0x0300 ], // Operating mode of thermostat
		'HEATING_COOLING_DOMUS'                         =>[ 'ID' => 0x0301 ], // Switch from Heating (0) Cooling (1)
		'OUTDOOR_TEMPERATURE'                           =>[ 'ID' => 0x0400, // DPT_VALUE_TEMP	2BYTE	Outdoor Temperature
															'info' => 'Temperature',
															'type'  	=> 2,                    // float
															'profile' 	=> '~Temperature'],     // Darstellungsprofil
		'WIND_FORCE'                                    =>[ 'ID' => 0x0401, // DPT_BEAUFORT_WIND_FORCE_SCALE	1BYTE	Wind force
															'info' => 'Wind force',
															'type'  	=> 1,                    // int
															'profile' 	=> 'FAH.WindForce'],     // Darstellungsprofil
		'BRIGHTNESS_ALARM'                              =>[ 'ID' => 0x0402,  // DPT_SWITCH	1BIT
															'info' => 'Illumination Alarm',
															'type'  	=> 0,                    // bool
															'profile' 	=> 'FAH.IlluminationAlert'],         // Darstellungsprofil
		'BRIGHTNESS_LEVEL'                              =>[ 'ID' => 0x0403, // DPT_VALUE_LUX	2BYTE	Weatherstation brightness level
															'info' => 'Illumination',
															'type'  	=> 1,                    // int
															'profile' 	=> '~Illumination'],     // Darstellungsprofil
		'WIND_SPEED'                                    =>[ 'ID' => 0x0404, // DPT_VALUE_WSP	2BYTE	Wind speed
															'info' => 'Wind speed',
															'type'  	=> 2,                    // float
															'profile' 	=> '~WindSpeed.ms'],     // Darstellungsprofil
		'RAIN_SENSOR_ACTIVATION_PERCENTAGE'             =>[ 'ID' => 0x0405 ], // DPT_SCALING	1BYTE
		'RAIN_SENSOR_FREQUENCY'                         =>[ 'ID' => 0x0406 ], // DPT_KNX_FLOAT	2BYTE
		'MEDIA_PLAY'                                    =>[ 'ID' => 0x0440 ], // DPT_TRIGGER	1BIT
		'MEDIA_PAUSE'                                   =>[ 'ID' => 0x0441 ], // DPT_TRIGGER	1BIT
		'MEDIA_NEXT'                                    =>[ 'ID' => 0x0442 ], // DPT_TRIGGER	1BIT
		'MEDIA_PREVIOUS'                                =>[ 'ID' => 0x0443 ], // DPT_TRIGGER	1BIT
		'MEDIA_PLAY_MODE'                               =>[ 'ID' => 0x0444 ], // DPT_ENUM_8	1BYTE	Play mode (shuffle / repeat)
		'MEDIA_MUTE'                                    =>[ 'ID' => 0x0445 ], // DPT_SWITCH	1BIT
		'RELATIVE_VOLUME_CONTROL'                       =>[ 'ID' => 0x0446 ], // DPT_CONTROL_DIMMING	4BIT
		'ABSOLUTE_VOLUME_CONTROL'                       =>[ 'ID' => 0x0447 ], // DPT_SCALING	1BYTE	Set player volume
		'GROUP_MEMBERSHIP'                              =>[ 'ID' => 0x0448 ], // DPT_BIT_SET_32	4BYTE
		'PLAY_FAVORITE'                                 =>[ 'ID' => 0x0449 ], // DPT_ENUM_8	1BYTE
		'PLAY_NEXT_FAVORITE'                            =>[ 'ID' => 0x044A ], // DPT_TRIGGER	1BIT
		'PLAYBACK_STATUS'                               =>[ 'ID' => 0x0460 ], // DPT_ENUM_8	1BYTE
		'INFO_MEDIA_CURRENT_ITEM_METADATA'              =>[ 'ID' => 0x0461 ], // DPT_STRING_51	51BYTE
		'INFO_MUTE'                                     =>[ 'ID' => 0x0462 ], // DPT_SWITCH	1BIT
		'INFO_ACTUAL_VOLUME'                            =>[ 'ID' => 0x0463 ], // DPT_SCALING	1BYTE
		'ALLOWED_PLAYBACK_ACTIONS'                      =>[ 'ID' => 0x0464 ], // DPT_ENUM_8	1BYTE
		'INFO_GROUP_MEMBERSHIP'                         =>[ 'ID' => 0x0465 ], // DPT_BIT_SET_32	4BYTE
		'INFO_PLAYING_FAVORITE'                         =>[ 'ID' => 0x0466 ], // DPT_ENUM_8	1BYTE
		'ABSOLUTE_GROUP_VOLUME_CONTROL'                 =>[ 'ID' => 0x0467 ], // DPT_SCALING	1BYTE
		'INFO_ABSOLUTE_GROUP_VOLUME'                    =>[ 'ID' => 0x0468 ], // DPT_SCALING	1BYTE
		'INFO_CURRENT_MEDIA_SOURCE'                     =>[ 'ID' => 0x0469 ], // DPT_STRING_14	14BYTE
		'SOLAR_POWER_PRODUCTION'                        =>[ 'ID' => 0x04A0 ], // DPT_POWER	2BYTE	Power from the sun
		'INVERTER_OUTPUT_POWER'                         =>[ 'ID' => 0x04A1 ], // DPT_POWER	2BYTE	Output power of inverter (pbatt+Psun)
		'SOLAR_ENERGY_TODAY'                            =>[ 'ID' => 0x04A2 ], // DPT_ACTIVE_ENERGY	4BYTE	Produced Energy
		'INJECTED_ENERGY_TODAY'                         =>[ 'ID' => 0x04A3 ], // DPT_ACTIVE_ENERGY	4BYTE	Energy into the grid
		'PURCHASED_ENERGY_TODAY'                        =>[ 'ID' => 0x04A4 ], // DPT_ACTIVE_ENERGY	4BYTE	Energy from the grid
		'NOTIFICATION_RUN_STANDALONE'                   =>[ 'ID' => 0x04A5 ], // DPT_ALARM	1BIT
		'SELF_CONSUMPTION'                              =>[ 'ID' => 0x04A6 ], // DPT_SCALING	1BYTE	production PV/ Total consumption
		'SELF_SUFFICIENCY'                              =>[ 'ID' => 0x04A7 ], // DPT_SCALING	1BYTE	Consumption from PV/ Total consumption
		'HOME_POWER_CONSUMPTION'                        =>[ 'ID' => 0x04A8 ], // DPT_POWER	2BYTE	Power in home (PV and grid)
		'POWER_TO_GRID'                                 =>[ 'ID' => 0x04A9 ], // DPT_POWER	2BYTE	Power from and to the grid: Purchased (less than 0), Injection (more than 0)
		'CONSUMED_ENERGY_TODAY'                         =>[ 'ID' => 0x04AA ], // DPT_ACTIVE_ENERGY	4BYTE	Energy bought from grid per day
		'NOTIFICATION_METER_COMMUNICATION_ERROR_WARNING' =>[ 'ID' => 0x04AB ], // DPT_ALARM	1BIT
		'SOC'                                           =>[ 'ID' => 0x04AC ], // DPT_SCALING	1BYTE	Battery level
		'BATTERY_POWER'                                 =>[ 'ID' => 0x04AD ], // DPT_POWER	2BYTE	Batter power: Discharge (less then 0), Charge (more then 0)
		'BOOST_ENABLE_REQUEST'                          =>[ 'ID' => 0x04B0 ], // DPT_SWITCH	1BIT
		'SWITCH_CHARGING'                               =>[ 'ID' => 0x04B1 ], // DPT_SWITCH	1BIT
		'STOP_ENABLE_CHARGING_REQUEST'                  =>[ 'ID' => 0x04B2 ], // DPT_SWITCH	1BIT
		'INFO_BOOST'                                    =>[ 'ID' => 0x04B3 ], // DPT_SWITCH	1BIT
		'INFO_WALLBOX_STATUS'                           =>[ 'ID' => 0x04B4 ], // DPT_BIT_SET_8	1BYTE	Wallbox status 00000001: car plugged in, 00000002: Authorization granted, 00000004: Not charging, battery fully loaded, 40000000: charging stopped due to blackout prevention, 80000000: Ground fault error
		'INFO_CHARGING'                                 =>[ 'ID' => 0x04B5 ], // DPT_SWITCH	1BIT
		'INFO_CHARGING_ENABLED'                         =>[ 'ID' => 0x04B6 ], // DPT_SWITCH	1BIT
		'INFO_INSTALLED_POWER'                          =>[ 'ID' => 0x04B7 ], // DPT_POWER	2BYTE	Installed power (e.g. 20 kW)
		'INFO_ENERGY_TRANSMITTED'                       =>[ 'ID' => 0x04B8 ], // DPT_ACTIVE_ENERGY	4BYTE	Energy transmitted so far per session (in Wh)
		'INFO_CAR_RANGE'                                =>[ 'ID' => 0x04B9 ], // DPT_VALUE_LENGTH	4BYTE	Car range in km per sessions
		'INFO_START_OF_CHARGING_SESSION'                =>[ 'ID' => 0x04BA ], // DPT_TIME_OF_DAYUTC	3BYTE
		'INFO_LIMIT_FOR_CHARGER'                        =>[ 'ID' => 0x04BB ], // DPT_POWER	2BYTE	Limit for charger (in kW)
		'INFO_LIMIT_FOR_CHARGER_GROUP'                  =>[ 'ID' => 0x04BC ], // DPT_POWER	2BYTE	Limit for group of charger (in kW)
		'INFO_ALBUM_COVER_URL'                          =>[ 'ID' => 0x04BD ], // DPT_STRING_51	51BYTE	Album cover URL
		'INFO_CURRENT_SOLAR_POWER'                      =>[ 'ID' => 0x04BE ], // DPT_VALUE_POWER	4BYTE	Current Solar power
		'INFO_CURRENT_INVERTER_OUTPUT_POWER'            =>[ 'ID' => 0x04BF ], // DPT_VALUE_POWER	4BYTE	Output power of inverter (pbatt+Psun)
		'INFO_CURRENT_HOME_POWER_CONSUMPTION'           =>[ 'ID' => 0x04C0 ], // DPT_VALUE_POWER	4BYTE	Power in home (PV and grid)
		'INFO_CURRENT_POWER_TO_GRID'                    =>[ 'ID' => 0x04C1 ], // DPT_VALUE_POWER	4BYTE	Power from and to the grid: Purchased (less than 0), Injection (more than 0)
		'INFO_CURRENT_BATTERY_POWER'                    =>[ 'ID' => 0x04C2 ], // DPT_POWER	2BYTE	Batter power: Discharge (less then 0), Charge (more then 0)
		'INFO_TOTAL_ENERGY_FROM_GRID'                   =>[ 'ID' => 0x04C3 ], // DPT_ACTIVE_ENERGY_KWH	4BYTE	Total energy from grid
		'INFO_TOTAL_ENERGY_TO_GRID'                     =>[ 'ID' => 0x04C4 ], // DPT_ACTIVE_ENERGY_KWH	4BYTE	Total energy to grid
		'MEASURED_CURRENT_POWER_CONSUMED'               =>[ 'ID' => 0x04C5 ], // DPT_VALUE_POWER	4BYTE	Current power consumed
		'MEASURED_IMPORTED_ENERGY_TODAY'                =>[ 'ID' => 0x04C6 ], // DPT_ACTIVE_ENERGY	4BYTE	Production and import of energy for today (grid purchase, battery discharge, PV production)
		'MEASURED_EXPORTED_ENERGY_TODAY'                =>[ 'ID' => 0x04C7 ], // DPT_ACTIVE_ENERGY	4BYTE	Consumption and export of energy for today (grid feed-in, battery charging, consumer consumption)
		'MEASURED_TOTAL_ENERGY_IMPORTED'                =>[ 'ID' => 0x04C8 ], // DPT_ACTIVE_ENERGY_KWH	4BYTE	Total production and import of energy (grid purchase, battery discharge, PV production)
		'MEASURED_TOTAL_ENERGY_EXPORTED'                =>[ 'ID' => 0x04C9 ], // DPT_ACTIVE_ENERGY_KWH	4BYTE	Total consumption and export of energy (grid feed-in, battery charging, consumer consumption)
		'SWITCH_ECO_CHARGING_ON_OFF'                    =>[ 'ID' => 0x04CA ], // DPT_SWITCH	1BIT
		'INFO_ECO_CHARGING_ON_OFF'                      =>[ 'ID' => 0x04CB ], // DPT_SWITCH	1BIT
		'LIMIT_FOR_CHARGER'                             =>[ 'ID' => 0x04CC ], // DPT_POWER	2BYTE	Limit for charger (in kW)
		'MEASURED_CURRENT_EXCESS_POWER'                 =>[ 'ID' => 0x04CD ], // DPT_VALUE_POWER	4BYTE	Current excess power
		'MEASURED_TOTAL_WATER'                          =>[ 'ID' => 0x04CE ], // DPT_VOLUME_LIQUID_LITRE	4BYTE	Measured total water consumption
		'MEASURED_TOTAL_GAS'                            =>[ 'ID' => 0x04CF ], // DPT_VOLUME_M3	4BYTE	Measured total gas consumption
		'CONSUMED_WATER_TODAY'                          =>[ 'ID' => 0x04D0 ], // DPT_VOLUME_LIQUID_LITRE	4BYTE	Consumed water today
		'CONSUMED_GAS_TODAY'                            =>[ 'ID' => 0x04D1 ], // DPT_VOLUME_M3	4BYTE	Consumed gas today
		'MEASURED_VOLTAGE'                              =>[ 'ID' => 0x04D2 ], // DPT_VALUE_ELECTRIC_POTENTIAL	4BYTE	Measured voltage
		'MEASURED_CURRENT'                              =>[ 'ID' => 0x04D3 ], // DPT_VALUE_ELECTRIC_CURRENT	4BYTE	Measured current
		'SYSTEM_STATE_DOMUS'                            =>[ 'ID' => 0x0500 ], // System state Domustech
		'DISARM_SYSTEM'                                 =>[ 'ID' => 0x0501 ], // DPT_DOMUS_SECURE_SWITCH	16BYTE	Encrypted control datapoint for domus alarm center
		'DISARM_COUNTER'                                =>[ 'ID' => 0x0502 ], // DPT_DOMUS_DISARM_DATA	10BYTE
		'SMS_TRIGGER_EVENT'                             =>[ 'ID' => 0x0503 ], // Info about the next counter to disarm the system
		'INFO_INTRUSION_ALARM'                          =>[ 'ID' => 0x0504 ], // DPT_SWITCH	1BIT
		'INFO_SAFETY_ALARM'                             =>[ 'ID' => 0x0505 ], // DPT_SWITCH	1BIT
		'ARMED'                                         =>[ 'ID' => 0x0506 ], // Indicates armed / disarmed state
		'INFO_ERROR_STATUS'                             =>[ 'ID' => 0x0507 ], // DPT_DOMUS_INFO_ERROR_STATUS	9BYTE
		'ENABLE_CONFIGURATION'                          =>[ 'ID' => 0x0508 ], // DPT_DOMUS_SECURE_SWITCH	16BYTE	Encrypted control datapoint for entering configuration mode
		'DOMUS_ZONE_CONTROL'                            =>[ 'ID' => 0x0509 ], // DPT_DOMUS_DISARM_COMMAND	16BYTE
		'DOMUS_KEY_INFO'                                =>[ 'ID' => 0x050A ], // DPT_DOMUS_KEY_INFO	22BYTE
		'ZONE_STATUS'                                   =>[ 'ID' => 0x050B ], // DPT_BIT_SET_32	4BYTE	Zone status
		'SENSOR_STATUS'                                 =>[ 'ID' => 0x050C ], // Sensor status (1 = alarm active, 0 = alarm inaktive)
		'INFO_CONFIGURATION_STATUS'                     =>[ 'ID' => 0x050D ], // DPT_DOMUS_CONFIG_STATUS	4BYTE
		'DOMUS_DISARM_DELAY_TIME'                       =>[ 'ID' => 0x050E ], // DPT_TIME_PERIOD_SEC	2BYTE	Absolute number of seconds when the zone will be armed
		'DOMUS_IM_ALARM'                                =>[ 'ID' => 0x05D0 ], // Internal, used for imaginary pairings
		'DOMUS_IM_ALARM_FEEDBACK'                       =>[ 'ID' => 0x05D1 ], // Internal, used for imaginary pairings
		'DOMUS_IM_SAFETY'                               =>[ 'ID' => 0x05D2 ], // Internal, used for imaginary pairings
		'DOMUS_IM_SAFETY_FEEDBACK'                      =>[ 'ID' => 0x05D3 ], // Internal, used for imaginary pairings
		'DOMUS_IM_SIREN'                                =>[ 'ID' => 0x05D4 ], // Internal, used for imaginary pairings
		'DOMUS_IM_REMOTE'                               =>[ 'ID' => 0x05D5 ], // Internal, used for imaginary pairings
		'DOMUS_IM_REMOTE_FEEDBACK'                      =>[ 'ID' => 0x05D6 ], // Internal, used for imaginary pairings
		'DOMUS_REMOTE_TRIGGER'                          =>[ 'ID' => 0x05D7 ], // Programmable button on domus remote
		'DOMUS_SIGNAL_STRENGTH'                         =>[ 'ID' => 0x05D8 ], // DPT_BIT_SET_256	32BYTE	2 Bit per Channel
		'START_STOP'                                    =>[ 'ID' => 0x0600 ], // DPT_SWITCH	1BIT
		'PAUSE_RESUME'                                  =>[ 'ID' => 0x0601 ], // DPT_TRIGGER	1BIT
		'SELECT_PROGRAM'                                =>[ 'ID' => 0x0602 ], // DPT_ENUM_8	1BYTE
		'DELAYED_START_TIME'                            =>[ 'ID' => 0x0603 ], // DPT_TIME_OF_DAY	3BYTE
		'INFO_STATUS'                                   =>[ 'ID' => 0x0604 ], // DPT_ENUM_8	1BYTE
		'INFO_REMOTE_START_ENABLED'                     =>[ 'ID' => 0x0605 ], // DPT_SWITCH	1BIT
		'INFO_PROGRAM'                                  =>[ 'ID' => 0x0606 ], // DPT_ENUM_8	1BYTE
		'INFO_FINISH_TIME'                              =>[ 'ID' => 0x0607 ], // DPT_TIME_OF_DAY	3BYTE
		'INFO_DELAYED_START_TIME'                       =>[ 'ID' => 0x0608 ], // DPT_TIME_OF_DAY	3BYTE
		'INFO_DOOR'                                     =>[ 'ID' => 0x0609 ], // DPT_SWITCH	1BIT
		'INFO_DOOR_ALARM'                               =>[ 'ID' => 0x060A ], // DPT_SWITCH	1BIT
		'SWITCH_SUPERCOOL'                              =>[ 'ID' => 0x060B ], // DPT_SWITCH	1BIT
		'SWITCH_SUPERFREEZE'                            =>[ 'ID' => 0x060C ], // DPT_SWITCH	1BIT
		'INFO_SWITCH_SUPERCOOL'                         =>[ 'ID' => 0x060D ], // DPT_SWITCH	1BIT
		'INFO_SWITCH_SUPERFREEZE'                       =>[ 'ID' => 0x060E ], // DPT_SWITCH	1BIT
		'CURRENT_TEMPERATURE_APPLIANCE_1'               =>[ 'ID' => 0x060F ], // DPT_VALUE_TEMP	2BYTE
		'CURRENT_TEMPERATURE_APPLIANCE_2'               =>[ 'ID' => 0x0610 ], // DPT_VALUE_TEMP	2BYTE
		'SETPOINT_TEMPERATURE_APPLIANCE_1'              =>[ 'ID' => 0x0611 ], // DPT_VALUE_TEMP	2BYTE
		'SETPOINT_TEMPERATURE_APPLIANCE_2'              =>[ 'ID' => 0x0612 ], // DPT_VALUE_TEMP	2BYTE
		'CHANGE_OPERATION'                              =>[ 'ID' => 0x0613 ], // DPT_TRIGGER	1BIT
		'INFO_VERBOSE_STATUS'                           =>[ 'ID' => 0x0614 ], // DPT_STRING_51	51BYTE
		'INFO_REMAINING_TIME'                           =>[ 'ID' => 0x0615 ], // DPT_TIME_OF_DAYUTC	3BYTE
		'INFO_STATUS_CHANGED_TIME'                      =>[ 'ID' => 0x0616 ], // DPT_TIME_OF_DAYUTC	3BYTE
		'ACTIVE_ENERGY_V64'                             =>[ 'ID' => 0x0617 ], // DPT_ACTIVE_ENERGY_V64	8BYTE	Active Energy (8 Byte)
		'LOCK_UNLOCK_COMMAND'                           =>[ 'ID' => 0x0618 ], // DPT_SWITCH	1BIT
		'INFO_LOCK_UNLOCK_COMMAND'                      =>[ 'ID' => 0x0619 ], // DPT_SWITCH	1BIT
		'INFO_PRESSURE'                                 =>[ 'ID' => 0x061A ], // DPT_VALUE_PRES	2BYTE	Measured air pressure
		'INFO_CO_2'                                     =>[ 'ID' => 0x061B ], // DPT_VALUE_AIR_QUALITY	2BYTE	Carbon dioxide level
		'INFO_CO'                                       =>[ 'ID' => 0x061C ], // DPT_VALUE_AIR_QUALITY	2BYTE	Carbon monoxide level
		'INFO_NO_2'                                     =>[ 'ID' => 0x061D ], // DPT_VALUE_AIR_QUALITY	2BYTE	Nitrogen dioxide level
		'INFO_O_3'                                      =>[ 'ID' => 0x061E ], // DPT_CONCENTRATION_UGM3	2BYTE	Ozone level
		'INFO_PM_10'                                    =>[ 'ID' => 0x061F ], // DPT_CONCENTRATION_UGM3	2BYTE	PM10 level
		'INFO_PM_2_5'                                   =>[ 'ID' => 0x0620 ], // DPT_CONCENTRATION_UGM3	2BYTE	PM2.5 level
		'INFO_VOC'                                      =>[ 'ID' => 0x0621 ], // DPT_VOC	2BYTE	VOC level
		'INFO_VOC_INDEX'                                =>[ 'ID' => 0x0622 ], // DPT_VOC_INDEX	2BYTE	VOC level indexed
		'TRIGGER_CAMERA_CONFIG'                         =>[ 'ID' => 0x0623 ], // DPT_START	1BIT
		'INFO_CAMERA_CONFIG'                            =>[ 'ID' => 0x0626 ], // DPT_SWITCH	1BIT
		'INFO_CAMERA_ID'                                =>[ 'ID' => 0x0627 ], // DPT_STRING_51	51BYTE	Info camera id
		'CO2_ALERT'                                     =>[ 'ID' => 0x0628 ], // DPT_ALARM	1BIT
		'VOC_ALERT'                                     =>[ 'ID' => 0x0629 ], // DPT_ALARM	1BIT
		'HUMIDITY_ALERT'                                =>[ 'ID' => 0x062A ], // DPT_ALARM	1BIT
		'AUTONOMOUS_SWITCH_OFF_TIME'                    =>[ 'ID' => 0x062B ], // DPT_TIME_PERIOD_SEC	2BYTE
		'INFO_AUTONOMOUS_SWITCH_OFF_TIME'               =>[ 'ID' => 0x062C ], // DPT_TIME_PERIOD_SEC	2BYTE
		'INFO_PLAYLIST'                                 =>[ 'ID' => 0x062D ], // DPT_BIT_SET_16	2BYTE
		'INFO_AUDIO_INPUT'                              =>[ 'ID' => 0x062E ], // DPT_BIT_SET_16	2BYTE
		'SELECT_PROFILE'                                =>[ 'ID' => 0x062F ], // DPT_BIT_SET_32	4BYTE
		'TIME_OF_DAY'                                   =>[ 'ID' => 0xF001 ], // DPT_TIME_OF_DAY	3BYTE
		'DATE'                                          =>[ 'ID' => 0xF002 ], // DPT_DATE	3BYTE
		'MESSAGE_CENTER_NOTIFICATION'                   =>[ 'ID' => 0xF003 ], // DPT_VALUE_1_UCOUNT	1BYTE	Notification from message center
		'SWITCH_ENTITY_ON_OFF'                          =>[ 'ID' => 0xF101 ], // DPT_SWITCH	1BIT
		'INFO_SWITCH_ENTITY_ON_OFF'                     =>[ 'ID' => 0xF102 ], // DPT_SWITCH	1BIT
		'CONSISTENCY_TAG'                               =>[ 'ID' => 0xF104 ], // DPT_VALUE_2_UCOUNT	2BYTE	Notifications of RF devices (e. g. Battery low)
		'BATTERY_STATUS'                                =>[ 'ID' => 0xF105 ], // DPT_SCALING	1BYTE	Notifications of RF devices (e. g. Battery low)
		'STAY_AWAKE'                                    =>[ 'ID' => 0xF106 ], // DPT_BOOL	1BIT
		'CYCLIC_SLEEP_TIME'                             =>[ 'ID' => 0xF10B ], // DPT_TIME_PERIOD_SEC	2BYTE	Time of sleep cycles
		'SYSAP_PRESENCE'                                =>[ 'ID' => 0xF10C ], // DPT_START	1BIT
		'SYSAP_TEMPERATURE'                             =>[ 'ID' => 0xF10D ], // DPT_VALUE_TEMP	2BYTE	SysAP temperature
		'STANDBY_STATISTICS'                            =>[ 'ID' => 0xF10E ], // DPT_BIT_SET_32	4BYTE	Statistics about standby usage for battery devices
		'HEARTBEAT_DELAY'                               =>[ 'ID' => 0xF10F ], // DPT_TIME_PERIOD_SEC	2BYTE	Time period between two heartbeats
		'INFO_HEARTBEAT_DELAY'                          =>[ 'ID' => 0xF110 ], // DPT_TIME_PERIOD_SEC	2BYTE	Time period between two heartbeats
		'MEASURED_TEMPERATURE_1'                        =>[ 'ID' => 0xFF01 ], // DPT_VALUE_TEMP	2BYTE	For debug purposes
		'MEASURED_TEMPERATURE_2'                        =>[ 'ID' => 0xFF02 ], // DPT_VALUE_TEMP	2BYTE	For debug purposes
		'MEASURED_TEMPERATURE_3'                        =>[ 'ID' => 0xFF03 ], // DPT_VALUE_TEMP	2BYTE	For debug purposes
		'MEASURED_TEMPERATURE_4'                        =>[ 'ID' => 0xFF04 ], // DPT_VALUE_TEMP	2BYTE	For debug purposes
		'IGNORE'                                        =>[ 'ID' => 0xFFFE ], // Ignore this datapoint
		'INVALID'                                       =>[ 'ID' => 0xFFFF ]  // Mark the datapoint as invalid
		];



	
	
	// aktuell unterstützte Funktions ID
	const SupportedIDs = array(
        self::mMapPairingID['SWITCH_ON_OFF']['ID'],
		self::mMapPairingID['INFO_ON_OFF']['ID'],
		self::mMapPairingID['WINDOW_DOOR']['ID'],
		self::mMapPairingID['INFO_ACTUAL_DIMMING_VALUE']['ID'],
		self::mMapPairingID['ABSOLUTE_SET_VALUE_CONTROL']['ID'],
		self::mMapPairingID['INFO_RGB']['ID'],
		self::mMapPairingID['RGB']['ID'],
		self::mMapPairingID['CURRENT_ABSOLUTE_POSITION_BLINDS_PERCENTAGE']['ID'],
		self::mMapPairingID['BRIGHTNESS_LEVEL']['ID'],
		self::mMapPairingID['BRIGHTNESS_ALARM']['ID'],
		self::mMapPairingID['RAIN_ALARM']['ID'],
		self::mMapPairingID['FROST_ALARM']['ID'],
		self::mMapPairingID['OUTDOOR_TEMPERATURE']['ID'],
		self::mMapPairingID['WIND_ALARM']['ID'],
		self::mMapPairingID['WIND_FORCE']['ID'],
		self::mMapPairingID['WIND_SPEED']['ID'],
		self::mMapPairingID['TIMED_MOVEMENT']['ID'],
	);

	
	public static function GetID( string $a_Name ) : int
	{
		if( isset(self::mMapPairingID[$a_Name] ))
		{
			return 	self::mMapPairingID[$a_Name]['ID'];
		}
		return 0;
	}

	public static function GetSettings( string $a_Name ) : array
	{
		if( isset(self::mMapPairingID[$a_Name] ))
		{
			return 	self::mMapPairingID[$a_Name];
		}
		return array();
	}
	public static function GetSettingsByID( int $a_ID ) : array
	{
		foreach( self::mMapPairingID as $lName => $lVal )
		{
			if( $lVal['ID'] == $a_ID )
			{
				return $lVal;
			}
		}
		return array();
	}

	public static function GetName( int $a_ID ) : string
    {
 		foreach( self::mMapPairingID as $lName => $lVal )
		{
			if( $lVal['ID'] == $a_ID )
			{
				return $lName;
			}
		}

        return '['.$a_ID.']';      
    }
				
	public static function GetType( int $a_ID ) : string
    {
 		foreach( self::mMapPairingID as $lName => $lVal )
		{
			if( $lVal['ID'] == $a_ID )
			{
				return $lVal['type'];
			}
		}

        return 0;      
    }

	public static function GetInfo( int $a_ID ) : string
    {
  		foreach( self::mMapPairingID as $lName => $lVal )
		{
			if( $lVal['ID'] == $a_ID )
			{
				if( isset($lVal['info']))
				{
					return $lVal['info'];
				}
				return "";
			}
		}
        return '?'.$a_ID.'?';      
    }
				
	public static function GetProfile( int $a_ID ) : string
    {
  		foreach( self::mMapPairingID as $lName => $lVal )
		{
			if( $lVal['ID'] == $a_ID )
			{
				return $lVal['profile'];
			}
		}

        return '';      
    }
	public static function GetAction( int $a_ID ) : string
    {
  		foreach( self::mMapPairingID as $lName => $lVal )
		{
			if( $lVal['ID'] == $a_ID )
			{
				if( isset($lVal['action']) )
				{				
					return $lVal['action'];
				}
				break;			
			}
		}

        return '';      
    }
				

	public static function FilterSupportedType( $a_Channel, string $a_Type )	
	{
		$lChannelObj = (object)$a_Channel;

		$lResult = array();
		foreach($lChannelObj->{$a_Type} as $lChannelNr => $lValue)			
		{    
			$lChannelValue = (object)$lValue;
			if( isset($lChannelValue->pairingID )  )
            {
				$lPairingId = $lChannelValue->pairingID;

                if( in_array($lPairingId, self::SupportedIDs ) )
                {
					$lInfoIsSet = !empty(self::GetInfo($lPairingId));
					if( $a_Type == 'inputs' && !$lInfoIsSet )
					{
						$lResult[$lChannelNr]= $lPairingId;
					}
					else if ( $a_Type == 'outputs' && $lInfoIsSet )
					{
						$lResult[$lChannelNr]= $lPairingId;
					}
					else
					{
						// Wert nicht gültig oder Funktion Brighness nicht verfügbar
						IPS_LogMessage( 0, __FUNCTION__.'('.json_encode($a_Channel).', '.$a_Type.') '.self::GetName($lPairingId).' not channel found' );
					}
                }
            }
        }	

		return $lResult;
	}
	
		
	public static function FilterSupported( $a_Channel, array $a_Types = ['inputs','outputs'])	
	{
		$lResult = array();
	
		foreach( $a_Types as $lType )			
		{
			$lResultType = self::FilterSupportedType( $a_Channel,$lType);
			if( !empty( $lResultType) )
			{			
				array_push($lResult, $lResultType );
			}
		}
		return $lResult;				
	}
}