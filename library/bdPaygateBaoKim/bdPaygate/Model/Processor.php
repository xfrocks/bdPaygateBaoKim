<?php

class bdPaygateBaoKim_bdPaygate_Model_Processor extends XFCP_bdPaygateBaoKim_bdPaygate_Model_Processor
{
	public function getProcessorNames()
	{
		$names = parent::getProcessorNames();
		
		$names['baokim'] = 'bdPaygateBaoKim_Processor';
		
		return $names;
	}
}