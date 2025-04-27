<?php


class PID
{
    const SWITCH_ON_OFF                                     = 0x0001; // Binary Switch value
																	  //		1: on
																	  //		0: off

    const TIMED_START_STOP                                  = 0x0002; // For staircase lighting or movement detection
                       												  // 	1: start
																	  //	0: stop
	

	
	const Name = [
		self::SWITCH_ON_OFF           => 'SWITCH_ON_OFF',
		self::TIMED_START_STOP        => 'TIMED_START_STOP',
	];
	
	
	
	// aktuell unterstützte Funktions ID's
	const SupportedIDs = array(
        self::SWITCH_ON_OFF,
	);
		
	public static function FilterSupportedType( $a_Channel, string $a_Type )	
	{
		$lResult = array();
		foreach($a_Channel[$a_Type] as $lChannelNr => $lChannelValue)			
		{             			
			if( isset($lChannelValue->pairingID )  )
            {
                $lFunctionId = hexdec( $lChannelValue->pairingID );
                if( in_array($lFunctionId, self::SupportedIDs ) )
                {
                    $lResult[$lChannelNr]= $lChannelValue;
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

