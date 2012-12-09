<?php

class bdPaygateBaoKim_XenForo_ControllerAdmin_UserUpgrade extends XFCP_bdPaygateBaoKim_XenForo_ControllerAdmin_UserUpgrade
{
	public function actionIndex()
	{
		$optionModel = $this->getModelFromCache('XenForo_Model_Option');
		$optionModel->bdPaygateBaoKim_hijackOptions();
		
		return parent::actionIndex();
	}
}