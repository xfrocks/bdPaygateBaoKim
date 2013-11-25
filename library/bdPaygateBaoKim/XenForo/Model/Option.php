<?php

class bdPaygateBaoKim_XenForo_Model_Option extends XFCP_bdPaygateBaoKim_XenForo_Model_Option
{
	// this property must be static because XenForo_ControllerAdmin_UserUpgrade::actionIndex
	// for no apparent reason use XenForo_Model::create to create the optionModel
	// (instead of using XenForo_Controller::getModelFromCache)
	private static $_bdPaygateBaoKim_hijackOptions = false;
	
	public function getOptionsByIds(array $optionIds, array $fetchOptions = array())
	{
		if (self::$_bdPaygateBaoKim_hijackOptions === true)
		{
			$optionIds[] = 'bdPaygateBaoKim_id';
			$optionIds[] = 'bdPaygateBaoKim_pass';
			$optionIds[] = 'bdPaygateBaoKim_email';
		}
		
		$options = parent::getOptionsByIds($optionIds, $fetchOptions);
		
		self::$_bdPaygateBaoKim_hijackOptions = false;

		return $options;
	}
	
	public function bdPaygateBaoKim_hijackOptions()
	{
		self::$_bdPaygateBaoKim_hijackOptions = true;
	}
}