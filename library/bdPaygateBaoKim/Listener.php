<?php

class bdPaygateBaoKim_Listener
{
	public static function load_class($class, array &$extend)
	{
		static $classes = array(
			'bdPaygate_Model_Processor',
		
			'XenForo_ControllerAdmin_UserUpgrade',
			'XenForo_Model_Option',
		);
		
		if (in_array($class, $classes))
		{
			$extend[] = 'bdPaygateBaoKim_' . $class;
		}
	}
	
	public static function file_health_check(XenForo_ControllerAdmin_Abstract $controller, array &$hashes)
	{
		$hashes += bdPaygateBaoKim_FileSums::getHashes();
	}
}