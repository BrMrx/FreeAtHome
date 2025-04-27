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
	
	const Name = [
		self::SWITCH_ON_OFF           => 'SWITCH_ON_OFF',
		self::TIMED_START_STOP        => 'TIMED_START_STOP',
		self::INFO_ON_OFF        	  => 'INFO_ON_OFF',
	];
	
	
	
	// aktuell unterstützte Funktions ID's
	const SupportedIDs = array(
        self::SWITCH_ON_OFF,
		self::INFO_ON_OFF,
	);
		
	public static function FilterSupportedType( $a_Channel, string $a_Type )	
	{
		$lChannelObj = (object)$a_Channel;

		$lResult = array();
		foreach($lChannelObj->{$a_Type} as $lChannelNr => $lChannelValue)			
		{    
			if( isset($lChannelValue->pairingID )  )
            {
				$lPairingId = $lChannelValue->pairingID;

                if( in_array($lPairingId, self::SupportedIDs ) )
                {
                    $lResult[$lChannelNr]= $lPairingId;
                }
            }
        }	
		IPS_LogMessage( 0, __FUNCTION__.' '.$a_Type.':'. $lResult );

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

