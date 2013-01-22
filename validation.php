<?php

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../header.php');
include_once(dirname(__FILE__).'/bankmellat.php');

	if (!$cookie->isLogged())
		Tools::redirect('authentication.php?back=order.php');
           
	$currency_default = Currency::getCurrency(intval(Configuration::get('PS_CURRENCY_DEFAULT')));        
	$bankmellat= new bankmellat();
    $RefId = $_POST['RefId'];
    $ResCode = $_POST['ResCode'];
	$saleOrderId = $_POST['SaleOrderId'];
	$SaleReferenceId = $_POST['SaleReferenceId'];
	$amount = (int)$_COOKIE['amount'];
    
	echo '<h4>' .$bankmellat->l('Validate your order payment throw ').$bankmellat->displayName. '</h4>';

    if ( !isset($_POST['ResCode']) OR $ResCode != "0")
    {
		setcookie("RefId", "", -1);
		setcookie("amount","", -1);
		//$bankmellat->_postErrors[] = $bankmellat->l('Your Payment is invalid. Please try again. ').$errCode;
		if (isset($_POST['ResCode']))
			echo $bankmellat->showErrorMessages($ResCode);
		else 
		echo $bankmellat->showErrorMessages(55);
		include_once(dirname(__FILE__).'/../../footer.php');
		die();
	}
	
	$terminalId = Configuration::get('terminalId');
	$userName = Configuration::get('userName');
	$userPassword = Configuration::get('userPassword');
	
	include('lib/nusoap.php');
	global $cookie, $smarty;
	//$order_cart = new Cart((int)$cookie->id_cart);
	$customer = new Customer($cart->id_customer);

	$namespace='http://interfaces.core.sw.bps.com/';
	$soapclient = new nusoap_client('https://pgws.bpm.bankmellat.ir/pgwchannel/services/pgw?wsdl', true);
	if (!$err = $soapclient->getError())
	   $soapProxy = $soapclient->getProxy() ; 
	if ( (!$soapclient) OR ($err = $soapclient->getError()) ) {
			die(Tools::displayError('Could not connect to bank or service.'));

	}
	else {
		$verifyOrderId = date('Y').date('H').date('i').date('s');
		// Params For Verify
		$params = array(
					'terminalId' =>  $terminalId,
					'userName' => $userName,
					'userPassword' => $userPassword,
					'orderId' => $verifyOrderId,
					'saleOrderId' => $saleOrderId,
					'saleReferenceId' => $SaleReferenceId);
		
		$result = $soapclient->call('bpVerifyRequest', $params, $namespace);
	}
	
	if ($result['return'] != 0 AND $result['return'] != 43){
		setcookie("RefId", "", -1);
		setcookie("amount","", -1);
		echo $bankmellat->showErrorMessages($result['return']);
		include_once(dirname(__FILE__).'/../../footer.php');
		die();
	}
	
	$information = array(
		'SaleOrderId' => $SaleOrderId,
		'SaleReferenceId' => $SaleReferenceId,
	);
	$tr = $bankmellat->l('Transaction ID:').' '.$saleOrderId;
	$ri = $bankmellat->l('Reference Code:').' '.$SaleReferenceId;
	$show_info = $tr.' <br/>'.$ri;
	echo '<div class="confirmation">'.$show_info.'
		<br/>' .$bankmellat->l('Please keep this information.'). '</div>';
	//Params for settle
	$params = array(
					'terminalId' =>  $terminalId,
					'userName' => $userName,
					'userPassword' => $userPassword,
					'orderId' => date('Y').date('H').date('i').date('s'),
					'saleOrderId' => $saleOrderId,
					'saleReferenceId' => $SaleReferenceId);
	$result = $soapclient->call('bpSettleRequest', $params, $namespace);

	// if we have a valid completed order, validate it
	if ($result['return'] != 0 OR $amount != (int)$cart->getOrderTotal(true, 3)){
		echo $bankmellat->showErrorMessages($result['return']);
		$bankmellat->validateOrder($cart->id, _PS_OS_ERROR_,$amount, $bankmellat->displayName,$show_info, $information ,$cookie->id_currency,false, $customer->secure_key);
		echo '<div class="error">'.$bankmellat->l('An error accured but your order registered. Please contact our support and say about errors. Keep transaction and reference codes.').'</div>';
		include_once(dirname(__FILE__).'/../../footer.php');
		setcookie("RefId", "", -1);
		setcookie("amount","", -1);
		die();
	}
	$validate_result = $bankmellat->validateOrder($cart->id, _PS_OS_PAYMENT_,$amount, $bankmellat->displayName,$show_info, $information,$cookie->id_currency,false, $customer->secure_key);
	if($validate_result)
		echo '<div class="validation">
		<p class="confirmation">'.$bankmellat->l('Your order accepted. Thank you for your shoping.').'</p>
		<p><a href="/history.php">'.$bankmellat->l('Return to orders history').'</a></p></div>';
	else echo '<div class="error">'.$bankmellat->l('An error accured but your order registered. Please contact our support and say about errors. Keep transaction and reference codes.').'</div>';
	setcookie("RefId", "", -1);
    setcookie("amount","", -1);

	include_once(dirname(__FILE__).'/../../footer.php');		

?>
