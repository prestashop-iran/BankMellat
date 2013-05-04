<?php 
class BankMellat extends PaymentModule
{  
	private $_html = '';
	
	public $_webservice = 'https://pgws.bpm.bankmellat.ir/pgwchannel/services/pgw?wsdl';
	public $_new_webservice = 'https://pgwsf.bpm.bankmellat.ir/pgwchannel/services/pgw?wsdl';
	private $_postErrors = array();
	
	public function __construct(){  
		$this->name = 'bankmellat';  
		$this->tab = 'payments_gateways';
		$this->version = '1.6';  
		$this->author = 'Presta-Shop.IR';
		
		$this->currencies = true;
  		$this->currencies_mode = 'checkbox';
		
		parent::__construct();  		
		
		$this->page = basename(__FILE__, '.php');
		$this->displayName = $this->l('Mellat Payment');  
		$this->description = $this->l('A free module to pay online for Mellat.');  
		$this->confirmUninstall = $this->l('Are you sure, you want to delete your details?');
		if (!sizeof(Currency::checkPaymentCurrencies($this->id)))
			$this->warning = $this->l('No currency has been set for this module');
		$config = Configuration::getMultiple(array('Bank_Mellat_TerminalId', ''));			
		if (!isset($config['Bank_Mellat_TerminalId']))
			$this->warning = $this->l('Your Mellat TerminalId must be configured in order to use this module');
		$config = Configuration::getMultiple(array('Bank_Mellat_UserName', ''));			
		if (!isset($config['Bank_Mellat_UserName']))
			$this->warning = $this->l('Your Mellat username must be configured in order to use this module');
			
			$config = Configuration::getMultiple(array('Bank_Mellat_UserPassword', ''));			
		if (!isset($config['Bank_Mellat_UserPassword']))
			$this->warning = $this->l('Your Mellat password must be configured in order to use this module');
			
		if ($_SERVER['SERVER_NAME'] == 'localhost')
			$this->warning = $this->l('Your are in localhost, Mellat Payment can\'t validate order');
	}  
	public function install(){
		if (!parent::install()
	    	OR !Configuration::updateValue('Bank_Mellat_TerminalId', '')
			
			OR !Configuration::updateValue('Bank_Mellat_newWebservice', 0)
	    	OR !Configuration::updateValue('Bank_Mellat_UserName', '')
			OR !Configuration::updateValue('Bank_Mellat_UserPassword', '')
	      	OR !$this->registerHook('payment')
	      	OR !$this->registerHook('paymentReturn')){
			    return false;
		}else{
		    return true;
		}
	}
	public function uninstall(){
		if (!Configuration::deleteByName('Bank_Mellat_TerminalId') 
			OR !Configuration::deleteByName('Bank_Mellat_UserName') 
			OR !Configuration::deleteByName('Bank_Mellat_UserPassword')
			OR !parent::uninstall())
			return false;
		return true;
	}
	
	public function displayFormSettings()
	{
		$this->_html .= '
		<form action="'.$_SERVER['REQUEST_URI'].'" method="post">
			<fieldset>
				<legend><img src="../img/admin/cog.gif" alt="" class="middle" />'.$this->l('Settings').'</legend>
				<label>'.$this->l('terminalId').'</label>
				<div class="margin-form"><input type="text" size="30" name="terminalId" value="'.Configuration::get('Bank_Mellat_TerminalId').'" /></div>
				<label>'.$this->l('userName').'</label>
				<div class="margin-form"><input type="text" size="30" name="userName" value="'.Configuration::get('Bank_Mellat_UserName').'" /></div>
				<label>'.$this->l('userPassword').'</label>
				<div class="margin-form"><input type="password" size="30" name="userPassword" value="'.Configuration::get('Bank_Mellat_UserPassword').'" /></div>
				
				<label>'.$this->l('New Webservice').'</label>
				<div class="margin-form"><input type="checkbox" name="newWebservice" '.(Configuration::get('Bank_Mellat_newWebservice') ? "checked" : "").' /> <span>'.$this->l('yes').'</span></div>
				<center><input type="submit" name="submitMellat" value="'.$this->l('Update Settings').'" class="button" /></center>			
			</fieldset>
		</form>';
	}
	
	public function checkWebservices()
	{
		$this->_html .= '<form action="'.$_SERVER['REQUEST_URI'].'" method="post">
		<fieldset>		
		<legend><img src="../img/admin/cog.gif" alt="" class="middle" />'.$this->l('بررسی وب سرویس').'</legend><p>';
		if (Tools::getValue('submitCheck'))
		{
			$connection = @fsockopen('pgws.bpm.bankmellat.ir', '443');
			if (is_resource($connection))
				$this->_html .= 'وب سرویس قدیمی: بله</p><p>';
			else $this->_html .= 'وب سرویس قدیمی: خیر</p><p>';
			$connection = @fsockopen('pgwsf.bpm.bankmellat.ir', '443');
			if (is_resource($connection))
				$this->_html .= 'وب سرویس جدید: بله</p>';
			else $this->_html .= 'وب سرویس جدید: خیر</p>';
		}
		
		$this->_html .= '
		
		<center><input type="submit" name="submitCheck" value="'.$this->l('بررسی امکان اتصال به وب سرویس').'" class="button" />
		<p style="text-align:center;">این عمل ممکن است مدتی طول بکشد. شکیبا باشید.</p></center>
		</fieldset></form>';
	}
	public function displayConf()
	{
	
		$this->_html .= '<div class="conf confirm"> '.$this->l('Settings updated').'</div>';
	}
	
	public function displayErrors()
	{
		foreach ($this->_postErrors AS $err)
		$this->_html .= '<div class="alert error">'. $err .'</div>';
	}
       	public function getContent()
	{
		$this->_html = '<h2>'.$this->l('Mellat Payment').'</h2>';
		if (isset($_POST['submitMellat']))
		{
				if (empty($_POST['terminalId']))
				$this->_postErrors[] = $this->l('Mellat TerminalId is required.');
				
			if (empty($_POST['userName']))
				$this->_postErrors[] = $this->l('Your username is required.');
			
			if (empty($_POST['userPassword']))
				$this->_postErrors[] = $this->l('Your password is required.');
			if (!sizeof($this->_postErrors))
			{
				
				Configuration::updateValue('Bank_Mellat_TerminalId', $_POST['terminalId']);
			
				Configuration::updateValue('Bank_Mellat_UserName', $_POST['userName']);
			
				Configuration::updateValue('Bank_Mellat_UserPassword', $_POST['userPassword']);
				
				Configuration::updateValue('Bank_Mellat_newWebservice', $_POST['newWebservice']);
				$this->displayConf();
			}
			else
				$this->displayErrors();
		}
		$this->displayFormSettings();
		$this->checkWebservices();
		return $this->_html;
	}
	public function execPayment($cart)
	{
        include('lib/nusoap.php');
		global $cookie, $smarty;
		
		
		$use_new_webservise = Configuration::get('Bank_Mellat_newWebservice');
  		if ($use_new_webservise)
			$webservice = $this->_new_webservice;
		else
			$webservice = $this->_webservice;
		$soapclient = new nusoap_client($webservice);
		$namespace='http://interfaces.core.sw.bps.com/';
		
		if (!$err = $soapclient->getError())
			$soapProxy = $soapclient->getProxy() ;
		if ( (!$soapclient) OR $err ) {
				$this->_postErrors[] = $this->l('Could not connect to bank or service.');
			   	$this->displayErrors();
  		} else {
			
			//$ParsURL = 'payment.php';
			$purchase_currency = new Currency(Currency::getIdByIsoCode('IRR'));
			$purchase_currency = new Currency ($purchase_currency->id);
			$current_currency = new Currency($cookie->id_currency);			
			//$OrderDesc = Configuration::get('PS_SHOP_NAME'). $this->l(' Order');
			if($cookie->id_currency==$purchase_currency->id)
				$PurchaseAmount= number_format($cart->getOrderTotal(true, 3), 0, '', '');		 
			else
				$PurchaseAmount= number_format($this->convertPriceFull($cart->getOrderTotal(true, 3), $current_currency, $purchase_currency), 0, '', '');	 
			
			//date_default_timezone_set('Asia/Tehran');
			$terminalId = Configuration::get('Bank_Mellat_TerminalId');	
			$userName = Configuration::get('Bank_Mellat_UserName');
			$userPassword = Configuration::get('Bank_Mellat_UserPassword');
			$ld=date('Ymd');
			$lt=date("H:i:s");
			$newstr = str_replace(":","",$lt);
			$lt=$newstr;
			$orderId = $cart->id;
			$orderId = ($cart->id).date('Y').date('H').date('i').date('s');
			$localDate = $ld;
			$localTime = $lt;
			$additionalData = "Cart Number: ".$orderId." Customer ID: ".$cart->id_customer;
			$payerID = 0;
			
			
			
			$CpiReturnUrl = (Configuration::get('PS_SSL_ENABLED') ?'https://' :'http://').$_SERVER['HTTP_HOST'].__PS_BASE_URI__.'modules/bankmellat/validation.php';
			
		    $params = array(
						'terminalId' =>  $terminalId,
						'userName' => $userName,
						'userPassword' => $userPassword,
						'orderId' => $orderId,
		                'amount' => (int)$PurchaseAmount,
						'callBackUrl' => $CpiReturnUrl,
						'localDate' => $localDate,
						'localTime' => $localTime,
						'additionalData' => $additionalData,
						'payerId' => $payerID
		              );
			
			$res = $soapclient->call('bpPayRequest', $params, $namespace);
			
			if ($soapclient->fault OR $err = $soapclient->getError()) {
				echo '<h2>Fault</h2><pre>';
				print_r($res);
				print_r($err);
				echo '</pre>';
				die();
			} 
			else {
			// Check for errors
			
				
				/* foreach ($res as $arr){
					$resultStr = $arr;
				} */

				/* $err = $soapclient->getError();
				if ($err) {
					// Display the error
					echo '<h2>Error</h2><pre>' . $err . '</pre>';
					die();
				} 
				else */ {
					// Display the result
					if (is_array($res))
						$ress = explode (',',$res['return']);
					else
						$ress = explode (',',$res);
					$ResCode = $ress[0];
					$RefId     = $ress[1];
					if ($ResCode == "0") {
						setcookie("RefId", $RefId, time()+1800);
						setcookie("amount", (int)$PurchaseAmount, time()+1800);
						// Update table, Save RefId
							echo'<script type="text/javascript">
							setTimeout("document.forms.frmmellatpaymen.submit();",10);
							</script>';
								echo "<form name=\"frmmellatpaymen\" action=\"https://pgw.bpm.bankmellat.ir/pgwchannel/startpay.mellat\" method=\"post\">
							<input type=\"hidden\" id=\"RefId\" name=\"RefId\" value=\"$RefId\" />
							</form>";
					} 
					else {
						echo $this->l('An error accured: '). $resultStr;
						echo $this->showMessages($resultStr);
					}
				}// end Display the result
			}// end Check for errors
           
        }	
        return $this->_html;
	}
		
	public function showMessages($result)
	{                
		switch($result)
		{ 
			case 0:  $this->_postErrors[]=$this->l('تراکنش با موفقیت انحام شد'); break;
			case 11: $this->_postErrors[]=$this->l('شماره کارت نامعتبر است'); break;
			case 12: $this->_postErrors[]=$this->l('موجودی کافی نیست'); break;
			case 13: $this->_postErrors[]=$this->l('رمز نادرست است'); break;  
			case 14: $this->_postErrors[]=$this->l('تعداد دفعات وارد کردن رمز بیش از حد مجاز است'); break;    
			case 15: $this->_postErrors[]=$this->l('کارت نامعتبر است'); break;
			case 16: $this->_postErrors[]=$this->l('دفعات برداشت وجه بیش از حد مجاز است'); break;
			case 17: $this->_postErrors[]=$this->l('کاربر از انجام تراکنش منصرف شده است'); break;
			case 18: $this->_postErrors[]=$this->l('تاریخ انقضای کارت گذشته است'); break;
			case 19: $this->_postErrors[]=$this->l('مبلغ برداشت وجه بیش از حد مجاز است'); break;
			case 111: $this->_postErrors[]=$this->l('صادر کننده کارت نامعتبر است'); break;
			case 112: $this->_postErrors[]=$this->l('خطای سوییچ صادر کننده کارت'); break;
			case 113: $this->_postErrors[]=$this->l('پاسخی از صادر کننده کارت دریافت نشد'); break;
			case 114: $this->_postErrors[]=$this->l('دارنده کارت مجاز به انجام این تراکنش نیست'); break;
			case 21: $this->_postErrors[]=$this->l('پذیرنده نامعتبر است'); break;
			case 23: $this->_postErrors[]=$this->l('خطای امنیتی رخ داده است'); break;
			case 24: $this->_postErrors[]=$this->l('اطلاعات کاربری پذیرنده نامعتبر است'); break;
			case 25: $this->_postErrors[]=$this->l('مبلغ نامعتبر است'); break;
			case 31: $this->_postErrors[]=$this->l('پاسخ نامعتبر است'); break;
			case 32: $this->_postErrors[]=$this->l('فرمت اطلاعات وارد شده صحیح نمی باشد'); break;
			case 33: $this->_postErrors[]=$this->l('حساب نامعتبر است'); break;
			case 34: $this->_postErrors[]=$this->l('خطای سیستمی'); break;
			case 35: $this->_postErrors[]=$this->l('تاریخ نامعتبر است'); break;
			case 41: $this->_postErrors[]=$this->l('شماره درخواست تکراری است'); break;
			case 42: $this->_postErrors[]=$this->l('تراکنش Sale یافت نشد'); break;
			case 43: $this->_postErrors[]=$this->l('قبلا درخواست Verify داده شده است'); break;
			case 44: $this->_postErrors[]=$this->l('درخواست Verify یافت نشد'); break;
			case 45: $this->_postErrors[]=$this->l('تراکنش Settle شده است'); break;
			case 46: $this->_postErrors[]=$this->l('تراکنش Settle نشده است'); break;
			case 47: $this->_postErrors[]=$this->l('تراکنش Settle یافت نشد'); break;
			case 48: $this->_postErrors[]=$this->l('تراکنش Reverse شده است'); break;
			case 49: $this->_postErrors[]=$this->l('تراکنش Refund یافت شند'); break;
			case 412: $this->_postErrors[]=$this->l('شناسه قبض نادرست است'); break;
			case 413: $this->_postErrors[]=$this->l('شناسه پرداخت نادرست است'); break;
			case 414: $this->_postErrors[]=$this->l('سازمان صادر کننده قبض نامعتبر است'); break;
			case 415: $this->_postErrors[]=$this->l('زمان جلسه کاری به پایان رسیده است'); break;
			case 416: $this->_postErrors[]=$this->l('خطا در ثبت اطلاعات'); break;
			case 417: $this->_postErrors[]=$this->l('شناسه پرداخت کننده نامعتبر است'); break;
			case 418: $this->_postErrors[]=$this->l('اشکال در تعریف اطلاعات مشتری'); break;
			case 419: $this->_postErrors[]=$this->l('تعداد دفعات ورود اطلاعات از حد مجاز گذشته است'); break;
			case 421: $this->_postErrors[]=$this->l('IP نامعتبر است'); break;
			case 51: $this->_postErrors[]=$this->l('تراکنش تکراری است'); break;
			case 54: $this->_postErrors[]=$this->l('تراکنش مرجع موجود نیست'); break;
			case 55: $this->_postErrors[]=$this->l('تراکنش نامعتبر است'); break;
			case 61: $this->_postErrors[]=$this->l('خطا در واریز'); break;
			}
		$this->displayErrors();
		return $this->_html;
	}
	
	// to show only one error
	public function showErrorMessages($result)
	{
		$Message = $this->showMessages($result);
		$this->_html = '';
		$this->_postErrors = array();
		return $Message;
	}
	
	public function hookPayment($params){
		if (!$this->active)
			return ;
		
		return $this->display(__FILE__, 'payment.tpl');
	}
	
	public function hookPaymentReturn($params)
	{
		return ;
	}
	
	/**
	 *
	 * Convert amount from a currency to an other currency automatically
	 * @param float $amount
	 * @param Currency $currency_from if null we used the default currency
	 * @param Currency $currency_to if null we used the default currency
	 */
	public static function convertPriceFull($amount, Currency $currency_from = null, Currency $currency_to = null)
	{
		if ($currency_from === $currency_to)
			return $amount;
		if ($currency_from === null)
			$currency_from = new Currency(Configuration::get('PS_CURRENCY_DEFAULT'));
		if ($currency_to === null)
			$currency_to = new Currency(Configuration::get('PS_CURRENCY_DEFAULT'));
		if ($currency_from->id == Configuration::get('PS_CURRENCY_DEFAULT'))
			$amount *= $currency_to->conversion_rate;
		else
		{
            $conversion_rate = ($currency_from->conversion_rate == 0 ? 1 : $currency_from->conversion_rate);
			// Convert amount to default currency (using the old currency rate)
			$amount = Tools::ps_round($amount / $conversion_rate, 2);
			// Convert to new currency
			$amount *= $currency_to->conversion_rate;
		}
		return Tools::ps_round($amount, 2);
	}
}