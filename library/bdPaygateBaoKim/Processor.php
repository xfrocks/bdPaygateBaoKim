<?php

require_once(dirname(__FILE__) . '/3rdparty/BaoKimPayment.php');

class bdPaygateBaoKim_Processor extends bdPaygate_Processor_Abstract
{	
	public function getSupportedCurrencies()
	{
		return array(
			bdPaygate_Processor_Abstract::CURRENCY_USD,
			bdPaygate_Processor_Abstract::CURRENCY_CAD,
			bdPaygate_Processor_Abstract::CURRENCY_AUD,
			bdPaygate_Processor_Abstract::CURRENCY_GBP,
			bdPaygate_Processor_Abstract::CURRENCY_EUR,
		);
	}
	
	public function isRecurringSupported()
	{
		return false;
	}
	
	public function validateCallback(Zend_Controller_Request_Http $request, &$transactionId, &$paymentStatus, &$transactionDetails, &$itemId)
	{
		$req = '';
		foreach ($_POST as $key => $value)
		{
			$value = urlencode(stripslashes($value));
			$req .= "&$key=$value";
		}
		
		$transactionId = (!empty($_POST['transaction_id']) ? ('baokim_' . $_POST['transaction_id']) : '');
		$paymentStatus = bdPaygate_Processor_Abstract::PAYMENT_STATUS_OTHER;
		$transactionDetails = $_POST;
		$itemId = (!empty($_POST['order_id']) ? $_POST['order_id'] : '');
		$processorModel = $this->getModelFromCache('bdPaygate_Model_Processor');
		
		$log = $processorModel->getLogByTransactionId($transactionId);
		if (!empty($log))
		{
			$this->_setError("Transaction {$transactionId} has already been processed");
			return false;
		}
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->_sandboxMode()
			? 'http://sandbox.baokim.vn/bpn/verify'
			: 'https://www.baokim.vn/bpn/verify');
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // TODO: security risk?
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // TODO: security risk?
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
		$response = curl_exec($ch);
		$status = curl_getinfo($ch, CURLINFO_HTTP_CODE); 
		$error = curl_error($ch);
		
		if ($response != '' && strstr($response, 'VERIFIED') !== false && $status == 200)
		{
			if ($transactionStatus == 13)
			{
				$paymentStatus = bdPaygate_Processor_Abstract::PAYMENT_STATUS_ACCEPTED;
			}
		}
		else 
		{
			if (!empty($response))
			{
				$transactionDetails['validator_status'] = $status;
				$transactionDetails['validator_response'] = $response;
			}
			
			$this->_setError('Request not validated');
			return false;
		}
		
		return true;
	}
	
	public function generateFormData($amount, $currency, $itemName, $itemId, $recurringInterval = false, $recurringUnit = false, array $extraData = array())
	{
		$this->_assertAmount($amount);
		$this->_assertCurrency($currency);
		$this->_assertItem($itemName, $itemId);
		$this->_assertRecurring($recurringInterval, $recurringUnit);
		
		$bkp = new BaoKimPayment();
		
		$bkp->baokim_url = $this->_sandboxMode()
			? 'http://sandbox.baokim.vn/payment/order/version11'
			: 'https://www.baokim.vn/payment/order/version11';
		$options = XenForo_Application::getOptions();
		$bkp->merchant_id = $options->get('bdPaygateBaoKim_id');
		$bkp->secure_pass = $options->get('bdPaygateBaoKim_pass');
		$email = $options->get('bdPaygateBaoKim_email');
		
		$callToAction = new XenForo_Phrase('bdpaygatebaokim_call_to_action');
		$returnUrl = $this->_generateReturnUrl($extraData);
		$detailUrl = $this->_generateDetailUrl($extraData);
		
		// calculate amount in VND
		$currencyRate = 0;
		switch ($currency)
		{
			case bdPaygate_Processor_Abstract::CURRENCY_USD:
				$currencyRate = 21000;
				break;
			case bdPaygate_Processor_Abstract::CURRENCY_CAD:
				$currencyRate = 20000;
				break;
			case bdPaygate_Processor_Abstract::CURRENCY_AUD:
				$currencyRate = 21500;
				break;
			case bdPaygate_Processor_Abstract::CURRENCY_GBP:
				$currencyRate = 33000;
				break;
			case bdPaygate_Processor_Abstract::CURRENCY_EUR:
				$currencyRate = 27000;
				break;
			case bdPaygate_Processor_Abstract::CURRENCY_VND:
				$currencyRate = 1;
				break;
		}
		$amountVND = $amount * $currencyRate;
		
		$redirectUrl = $bkp->createRequestUrl($itemId, $email, $amountVND, 0, 0, $itemName, $returnUrl, $returnUrl, $detailUrl);
		
		$form = <<<EOF
<div>
	<a href="{$redirectUrl}">{$callToAction}</a>
</div>
EOF;
		
		return $form;
	}
}