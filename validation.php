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
	
	$purchase_currency = new Currency(Currency::getIdByIsoCode('IRR'));
	$current_currency = new Currency($cookie->id_currency);
	$amount = (int)$_COOKIE['amount'];
	if($cookie->id_currency == $purchase_currency->id)
		$OrderAmount = number_format($cart->getOrderTotal(true, 3), 0, '', '');
	else
		$OrderAmount = number_format($bankmellat->convertPriceFull($cart->getOrderTotal(true, 3), $current_currency, $purchase_currency), 0, '', ''); 
	
    
	echo '<h4>' .$bankmellat->l('تأييد پرداخت شما توسط').' '.$bankmellat->displayName. '</h4>';

    if ( !isset($_POST['ResCode']) OR $ResCode != "0")
    {
		setcookie("RefId", "", -1);
		setcookie("amount","", -1);
		//$bankmellat->_postErrors[] = $bankmellat->l('پرداخت شما نامعتبر است. دوباره امتحان کنيد.').$errCode;
		if (isset($_POST['ResCode']))
			echo $bankmellat->showErrorMessages($ResCode);
		else 
		echo $bankmellat->showErrorMessages(55);
		include_once(dirname(__FILE__).'/../../footer.php');
		die();
	}
	
	$terminalId = Configuration::get('Bank_Mellat_TerminalId');
	$userName = Configuration::get('Bank_Mellat_UserName');
	$userPassword = Configuration::get('Bank_Mellat_UserPassword');
	
	include('lib/nusoap.php');
	global $cookie, $smarty;
	//$order_cart = new Cart((int)$cookie->id_cart);
	$customer = new Customer($cart->id_customer);

	$namespace='http://interfaces.core.sw.bps.com/';
	$use_new_webservise = Configuration::get('Bank_Mellat_newWebservice');
	if ($use_new_webservise)
		$webservice = $bankmellat->_new_webservice;
	else
		$webservice = $bankmellat->_webservice;
	$soapclient = new nusoap_client($webservice);
	
	if ( (!$soapclient) OR ($err = $soapclient->getError()) ) {
		echo '<div class="error">'.$bankmellat->l('نمي توان به بانک متصل شد. اين صفحه را بازخواني (refresh) کنيد و يا به فروشگاه بازگرديد و دوباره خريد کنيد.').'<br />'.$err.'</div>';
		die();

	}
	else {
		$soapProxy = $soapclient->getProxy(); 
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
	$tr = $bankmellat->l('شناسه تراکنش:').' '.$saleOrderId;
	$ri = $bankmellat->l('کد مرجع بانک:').' '.$SaleReferenceId;
	$show_info = $tr.' <br/>'.$ri;
	echo '<div class="confirmation">'.$show_info.'
		<br/>' .$bankmellat->l('براي اطمينان، لطفاً اين اطلاعات را نزد خود نگهداري کنيد.'). '</div>';
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
	if ($result['return'] != 0 OR $amount != $OrderAmount){
		echo $bankmellat->showErrorMessages($result['return']);
		$bankmellat->validateOrder($cart->id, _PS_OS_ERROR_,$amount, $bankmellat->displayName,$show_info, $information ,$purchase_currency->id,false, $customer->secure_key);
		echo '<div class="error">'.$bankmellat->l('خطايي روي داد اما سفارش شما ثبت شده است. با پشتيباني سايت تماس بگيريد و در مورد خطاهاي روي داده توضيح دهيد. شما بايد شناسه سفارش و کد مرجع را نزد خود نگه داريد.').'</div>';
		include_once(dirname(__FILE__).'/../../footer.php');
		setcookie("RefId", "", -1);
		setcookie("amount","", -1);
		die();
	}
	$validate_result = $bankmellat->validateOrder($cart->id, _PS_OS_PAYMENT_,$cart->getOrderTotal(true, 3), $bankmellat->displayName,$show_info, $information,$cookie->id_currency,false, $customer->secure_key);
	if($validate_result)
		echo '<div class="validation">
		<p class="confirmation">'.$bankmellat->l('سفارش شما دريافت شد. از خريد شما سپاس گذاريم.').'</p>
		<p><a href="http://'.$_SERVER['HTTP_HOST'].__PS_BASE_URI__.'history.php">'.$bankmellat->l('بازگشت به تاريخچه سفارش ها').'</a></p></div>';
	else echo '<div class="error">'.$bankmellat->l('خطايي روي داد اما سفارش شما ثبت شده است. با پشتيباني سايت تماس بگيريد و در مورد خطاهاي روي داده توضيح دهيد. شما بايد شناسه سفارش و کد مرجع را نزد خود نگه داريد.').'</div>';
	setcookie("RefId", "", -1);
    setcookie("amount","", -1);

	include_once(dirname(__FILE__).'/../../footer.php');		

?>
