<?php


class FID
{
    const SWITCH_ACTUATOR                                   = 0x0007;   // A (binary) switch actuator  
    const ROOM_TEMPERATURE_CONTROLLER_MASTER_WITHOUT_FAN    = 0x0023;   // A master room temperature controller that does not include a fan */
    const DIMMING_ACTUATOR                                  = 0x0012;   // A dimming actuator  */
    const RGB_W_ACTUATOR                                    = 0x002E;   // A dimming actuator that can also control the light hue */
    const RGB_ACTUATOR                                      = 0x002F;   // A dimming actuator that can also control the light hue */
    const DES_AUTOMATIC_DOOR_OPENER_ACTUATOR                = 0x0020;   // An automatic door opener */
    const SMOKE_DETECTOR                                    = 0x007D;   // A smoke detector */
    const MOVEMENT_DETECTOR                                 = 0x0011;   // A motion detector */
    const DES_DOOR_OPENER_ACTUATOR                          = 0x001A;   // A door opener */
    const SHUTTER_ACTUATOR                                  = 0x0009;   // A shutter actuator */
    const BLIND_ACTUATOR                                    = 0x0061;   // A roller blind actuator */
    const ATTIC_WINDOW_ACTUATOR                             = 0x0062;   // An attic window actuator  */
    const AWNING_ACTUATOR                                   = 0x0063;   // An awning actuator */
    const WINDOW_DOOR_SENSOR                                = 0x000F;   // A binary door or window sensor */
    const WINDOW_DOOR_POSITION_SENSOR                       = 0x0064;   // A door or window sensor that also reports the door or window position */
    const SWITCH_SENSOR                                     = 0x0000;   // A (binary) switch sensor */
    const DIMMING_SENSOR                                    = 0x0001;   // A dimming sensor */
    const LIGHT_GROUP                                       = 0x4000;   // A light group */
    const SCENE                                             = 0x4800;   // A scene */
    const SPECIAL_SCENE_PANIC                               = 0x4801;   // The special panic scene */
    const SPECIAL_SCENE_ALL_OFF                             = 0x4802;   // The special all-off scene */
    const SPECIAL_SCENE_ALL_BLINDS_UP                       = 0x4803;   // The special all blinds up scene */
    const SPECIAL_SCENE_ALL_BLINDS_DOWN                     = 0x4804;   // The special all blinds down scene */
    const SCENE_SENSOR                                      = 0x0006;   // A scene sensor */
    const STAIRCASE_LIGHT_SENSOR                            = 0x0004;   // A staircase light sensor */
    const TRIGGER                                           = 0x0045;   // A generic trigger */
    const BRIGHTNESS_SENSOR                                 = 0x0041;   // A brightness sensor */
    const TEMPERATURE_SENSOR                                = 0x0043;   // A temperature sensor */
    const RADIATOR_ACTUATOR_MASTER                          = 0x003E;   // A master radiator actuator */
    const DIMMING_SENSOR_ROCKER_TYPE0                       = 0x1010;   // A wireless rocker type dimming sensor */
    const DIMMING_SENSOR_PUSHBUTTON_TYPE2                   = 0x101A;   // A wireless push button type dimming sensor */
    const DIMMING_ACTUATOR_TYPE0                            = 0x1810;   // A wireless dimming actuator */

	
	const mMapNames = [
		self::SWITCH_ACTUATOR        => 'SWITCH_ACTUATOR',
		self::DIMMING_ACTUATOR_TYPE0 => 'DIMMING_ACTUATOR_TYPE0',
	];
	
	
	
	// aktuell unterstützte Funktions ID's
	const SupportedIDs = [
        self::SWITCH_ACTUATOR,
        self::DIMMING_ACTUATOR_TYPE0,
    ];
	
    public static function GetName( string $a_Id )
    {
        $lFunctionId = hexdec( $a_Id );

        if( isset( self::mMapNames[$lFunctionId] ) )
        {
            return self::mMapNames[$lFunctionId];
        }
        return '['.$a_Id.']';      
    }
		
    public static function IsSupportedID( string $a_Id )
    {
        $lFunctionId = hexdec( $a_Id );
        return in_array($lFunctionId, self::SupportedIDs );
    }

	public static function FilterSupportedChannels( $a_Channels )	
	{
        IPS_LogMessage( 0, __FUNCTION__.":  ".json_encode($a_Channels) );
        $lResult = array();
		foreach($a_Channels as $lChannelNr => $lChannelValue)			
		{             			
            IPS_LogMessage( 0, __FUNCTION__.":  ".json_encode($lChannelValue) );
			if( isset($lChannelValue->functionID )  )
            {
                $lFunctionId = hexdec( $lChannelValue->functionID );
                if( in_array($lFunctionId, self::SupportedIDs ) )
                {
                    $lResult[$lChannelNr]= $lChannelValue;
                }
            }
        }	
		return $lResult;
	}
}

