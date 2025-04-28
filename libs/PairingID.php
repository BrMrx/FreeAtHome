<?php


class PID
{
    const SWITCH_ON_OFF                                     = 0x0001; // Binary Switch value
																	  //		1: on
																	  //		0: off

    const TIMED_START_STOP                                  = 0x0002; // For staircase lighting or movement detection
                       												  // 	1: start
																	  //	0: stop
	
	const INFO_ON_OFF										= 0x0100; // Reflects the binary state of the actuator
																	  //		1: on
																	  //		0: off
				
	const ABSOLUTE_SET_VALUE_CONTROL	= 0x0011; // Absolute control of the set value																		
																			// 1. Byte
	const INFO_ACTUAL_DIMMING_VALUE = 0x0110; // Reflects the actual value of the actuator
																			// 1. Byte
	
	const mMapNames = [
		self::SWITCH_ON_OFF           			=> [ 'name' => 'SWITCH_ON_OFF'],
		self::TIMED_START_STOP        			=> [ 'name' => 'TIMED_START_STOP'],
		self::INFO_ON_OFF        	  			=> [ 'name' => 'INFO_ON_OFF',
													 'info'  => 'State',
													 'type'  => 0,
													 'profile' => '~Switch'],
		self::INFO_ACTUAL_DIMMING_VALUE     	=> [ 'name' => 'INFO_ACTUAL_DIMMING_VALUE',
													 'info'  => 'Brightness',
													 'type'  => 1,
													 'profile' => '~Intensity.255'],
	];
	
	
	
	// aktuell unterstützte Funktions ID's
	const SupportedIDs = array(
        self::SWITCH_ON_OFF,
		self::INFO_ON_OFF,
		self::INFO_ACTUAL_DIMMING_VALUE,
		self::ABSOLUTE_SET_VALUE_CONTROL,
	);
		

	public static function GetName( $a_Id )
    {
        $lFunctionId = $a_Id;

        if( isset( self::mMapNames[$lFunctionId] ) )
        {
            return self::mMapNames[$lFunctionId]['name'];
        }
        return '['.$a_Id.']';      
    }
				
				public static function GetType( $a_Id )
    {
        $lFunctionId = $a_Id;

        if( isset( self::mMapNames[$lFunctionId] ) )
        {
            return self::mMapNames[$lFunctionId]['type'];
        }
        return 0;      
    }

				public static function GetInfo( $a_Id )
    {
        $lFunctionId = $a_Id;

        if( isset( self::mMapNames[$lFunctionId] ) )
        {
            return self::mMapNames[$lFunctionId]['info'];
        }
        return '?'.$a_Id.'?';      
    }
				
				public static function GetProfile( $a_Id )
    {
        $lFunctionId = $a_Id;

        if( isset( self::mMapNames[$lFunctionId] ) )
        {
            return self::mMapNames[$lFunctionId]['profile'];
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
                    $lResult[$lChannelNr]= $lPairingId;
                }
            }
        }	

		return $lResult;
	}
	
	public static function FilterSupportedType2( $a_Channel, string $a_Type )	
	{
		$lChannelObj = (object)$a_Channel;
		IPS_LogMessage( 0, __FUNCTION__.' ChannelData :'. json_encode($lChannelObj) );

		$lResult = array();
		foreach($lChannelObj->{$a_Type} as $lChannelNr => $lValue)			
		{    
			$lChannelValue = (object)$lValue;
			IPS_LogMessage( 0, __FUNCTION__.' ChannelData '.$a_Type.':'. json_encode($lChannelValue) );

			if( isset($lChannelValue->pairingID )  )
            {
				IPS_LogMessage( 0, __FUNCTION__.' IsSet '.$a_Type.':'. json_encode($lChannelValue) );
				$lPairingId = $lChannelValue->pairingID;

                if( in_array($lPairingId, self::SupportedIDs ) )
                {
					IPS_LogMessage( 0, __FUNCTION__.' is supported '.$lPairingId.':'. json_encode($lChannelValue) );
                    $lResult[$lChannelNr]= $lPairingId;
                }
				else
				{
					IPS_LogMessage( 0, __FUNCTION__.' is not supported '.$lPairingId.':'. json_encode($lChannelValue) );
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

