<?php
Class Flyt_OrderSender_Model_Observer{
	
	public function controller_action_predispatch($observer){
		$event = $observer->getEvent();
		$controllerAction = $event->getControllerAction();
		$params = $controllerAction->getRequest()->getParams();
		//echo $_SERVER['HTTP_REFERER'].'<br>';
		//echo $_SERVER['SERVER_NAME'].'<br>';
		//echo $_COOKIE['landing_url'].'<br>';
		if($_SERVER['HTTP_REFERER'] != "" && $_SERVER['HTTP_REFERER'] !="/" && !strpos($_SERVER['SERVER_NAME'] , $_SERVER['HTTP_REFERER']) && !isset($_COOKIE['landing_url'])){
			
			if(!isset($_COOKIE['landing_url'])){
				setcookie('landing_url', $_SERVER['HTTP_REFERER'], time() + (86400 * 30), "/");
				if(isset($_GET['ref']) && $_GET['ref'] !=""){
					setcookie('referal_url',  $_GET['ref'], time() + (86400 * 30), "/");	
				}
			
			}elseif(!strpos($_SERVER['SERVER_NAME'] , $_SERVER['HTTP_REFERER'])){
				setcookie('landing_url', $_SERVER['HTTP_REFERER'], time() + (86400 * 30), "/");
				if(isset($_GET['ref']) && $_GET['ref'] !=""){
					setcookie('referal_url',  $_GET['ref'], time() + (86400 * 30), "/");	
				}
			}
		}	
		if(isset($_GET['ref']) && $_GET['ref'] != ""){
			setcookie('referal_url',  $_GET['ref'], time() + (86400 * 30), "/");	
		} 
		//echo '<pre>';print_r($_COOKIE);exit;
		return true;
	}
	
	public function sales_order_place_after($observer){
		$session = Mage::getSingleton('core/session');
		$user_id = Mage::getStoreConfig('ordersender_config/api_config/user_id');
		$api_key = Mage::getStoreConfig('ordersender_config/api_config/api_key');
		//$url = Mage::getStoreConfig('apiconfig/sales_api/sales_info_url');
		$url = 'https://magento.flyt.it/apis/putSalesOrder.php';
		
		$order = $observer->getOrder();
		//print_r($order->getData());exit;
		$data = array(
			'order_id' => $order->getIncrementId(),
			'created_at' => $order->getCreatedAt(),
			'updated_at' => $order->getCreatedAt(),
			'order_amount' => $order->getGrandTotal(),
			'currency' => $order->getOrderCurrencyCode(),
			'status' => $order->getStatus(),
			'referal_url' => $_COOKIE['referal_url'],
			'landing_url' => $_COOKIE['landing_url'],
			'store_name' => $_SERVER['HTTP_HOST'],
			//'order_data_string' => $this->ordersDataJson($order->getId()),
		);
		//echo '<pre>';print_r($data);exit;
		$header_arr = array(
			"content-type: application/x-www-form-urlencoded",
			"flyt-api-key: $api_key",
			"flyt-user-id: $user_id"
		); 
		$ajax_response = $this->callPOSTCurl($url, $data, $header_arr);
		$response = json_decode($ajax_response);
		
		$status = $response->status;
		$message = $response->message;
		//print_r($message);exit;
		
		if($status == 'success'){
			$session->addSuccess($message);
		}else{
			$session->addError($message);
		}
		$this->unsetCookieByName('landing_url');
		$this->unsetCookieByName('referal_url');
		
		return true;
	}
	
	public function sales_order_status_after($observer){
		$session = Mage::getSingleton('core/session');
		$user_id = Mage::getStoreConfig('ordersender_config/api_config/user_id');
		$api_key = Mage::getStoreConfig('ordersender_config/api_config/api_key');
		//$url = Mage::getStoreConfig('apiconfig/sales_api/sales_info_url');
		$url = 'https://magento.flyt.it/apis/changeOrderStatus.php';
		
		$order = $observer->getOrder();
		$orderId = $order->getId();
		$status = $observer->getStatus();
		if($status == 'pending'){
			return true;
		}
		$data = array(
			'order_id' => $order->getIncrementId(),
			'updated_at' => $order->getUpdatedAt(),
			'status' => $status,
		);
		//echo '<pre>';print_r($data);exit;
		$header_arr = array(
			"content-type: application/x-www-form-urlencoded",
			"flyt-api-key: $api_key",
			"flyt-user-id: $user_id"
		); 
		$ajax_response = $this->callPOSTCurl($url, $data, $header_arr);
		$response = json_decode($ajax_response);
		
		$status = $response->status;
		$message = $response->message;
		//print_r($message);exit;
		
		if($status == 'success'){
			$session->addSuccess($message);
		}else{
			$session->addError($message);
		}
		
		return true;
		
	}
	/**
	* call ajax by cURL with PUT method
	* 
	* @param url $url
	* @param array $data
	* @param array $header_arr
	* 
	* @return response response from cURL
	*/
	public function callPUTCurl($url, $data, $header_arr){
		$ch = curl_init($url);
		//curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header_arr);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
		$response = curl_exec($ch);
		
		return $response;
	}
	
	/**
	* call ajax by cURL with GET method
	* 
	* @param string $url
	* @param array $data
	* @param array $header_arr
	* 
	* @return response response from cURL
	*/
	public function callGETCurl($url, $data, $header_arr){
		
		$ch = curl_init($url);
		//curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header_arr);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
		$response = curl_exec($ch);
		
		return $response;
	}
	
	/**
	* call ajax by cURL with GET method
	* 
	* @param string $url
	* @param array $data
	* @param array $header_arr
	* 
	* @return response response from cURL
	*/
	public function callPOSTCurl($url, $data, $header_arr){
		
		$ch = curl_init($url);
		//curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header_arr);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
		$response = curl_exec($ch);
		
		return $response;
	}
	/**
	* 
	* @param int $order_id
	* 
	* @return response json of all orders related data.
	*/
	public function ordersDataJson($order_id){
		$order = Mage::getModel("sales/order")->load($order_id);
		$paymentMethod = $order->getPayment()->getMethodInstance()->getTitle();
		$shippingMethod = $order->getShippingDescription();
				
		//display shipping address
		$shipData = $order->getShippingAddress()->getData();
		$shippDataArr = array("firstname"=>$shipData['firstname'],"lastname"=>$shipData['lastname'],"street"=>$shipData['street'],"postcode"=>$shipData['postcode'],"city"=>$shipData['city'],"state"=>$shipData['region'],"country"=>$shipData['country_id'],"company"=>$shipData['company'],"mobile"=>$shipData['telephone']);
		
		//display shipping address
		$billData = $order->getBillingAddress()->getData();
		$billDataArr = array("firstname"=>$billData['firstname'],"lastname"=>$billData['lastname'],"street"=>$billData['street'],"postcode"=>$billData['postcode'],"city"=>$billData['city'],"state"=>$billData['region'],"country"=>$billData['country_id'],"company"=>$billData['company'],"mobile"=>$billData['telephone']);
			  	
		//get cart data of a particular order
		$itemsAll = $order->getAllItems();
		$itemDetails = array();
		$line_items = array();
		foreach($itemsAll as $item){
			
			$line_items[] = array(
				'id' => $item->getId(),
				'variant_id' => $item->getProductID(),
				'title' => $item->getName(),
				'quantity' => $item->getQty(),
				'price' => $item->getPriceInclTax(),
				'grams' => $item->getWeight(),
				'sku' => $item->getSku(),
				'variant_title' => $item->getName(),
				'vendor' => '',
				'fulfillment_service' => '',
				'product_id' => $item->getProductID(),
				'requires_shipping' => true,
				'taxable' => true,
				'gift_card' => false,
				'name' => $item->getName(),
				'variant_inventory_management' => 'magento',     
				'fulfillable_quantity' => 1,
				'total_discount' => $item->getDiscountAmount(),
				'fulfillment_status' => '',
				'product_exists' => true,
			);
			$properties = array();
			$tax_lines = array();
			$origin_location = array(
				'id' => '',
				'country_code' => '',
				'province_code' => '',
				'name' => '',
				'address1' => '',
				'address2' => '',
				'city' => '',
				'zip' => '',
			);
			$destination_location = array(
				'id' => '',
				'country_code' => '',
				'province_code' => '',
				'name' => '',
				'address1' => '',
				'address2' => '',
				'city' => '',
				'zip' => '',
			);
		}
		
		$discount_codes = array ();
		$note_attributes = array ();
		$payment_gateway_names = array ($paymentMethod);
		$tax_lines = array ();
		
		$orderData[] = array(
			'id' => $order_id,
			'email' => $order->getCustomerEmail(),
			'closed_at' => '',
			'created_at' => $order->getCreatedAt(),
			'updated_at' => $order->getUpdatedAt(),
			'number' => $order->getIncrementId(),
			'note' => $order->getCustomerNote(),
			'token' => '',
			'gateway' => $paymentMethod,
			'test' => false,
			'total_price' => $order->getGrandTotal(),
			'subtotal_price' => $order->getSubtotal(),
			'total_weight' => $order->getWeight(),
			'total_tax' => $order->getTaxAmount(),
			'taxes_included' => true,
			'currency' => $order->getOrderCurrencyCode(),
			'financial_status' => $order->getStatus(),
			'confirmed' => true,
			'total_discounts' => $order->getDiscountAmount(),
			'total_line_items_price' => $order->getSubtotal(),
			'cart_token' => '',
			'buyer_accepts_marketing' => true,
			'name' => $order->getCustomerFirstname().' '.$order->getCustomerLastname(),
			'referring_site' => $_COOKIE['referal_url'],
			'landing_site' => $_COOKIE['landing_url'],
			'cancelled_at' => $order->getUpdatedAt(),
			'cancel_reason' => '',
			'total_price_usd' => $order->getGrandTotal(),
			'checkout_token' => '',
			'reference' => '',
			'user_id' => $order->getCustomerId(),
			'location_id' => '',
			'source_identifier' => '',
			'source_url' => '',
			'processed_at' => '2016-07-19T18:09:51-07:00',
			'device_id' => '',
			'browser_ip' => $_SERVER['REMOTE_ADDR'],
			'landing_site_ref' => '',
			'order_number' => $order->getIncrementId(),
			'processing_method' => $shippingMethod,
			'checkout_id' => '',
			'source_name' => 'web',
			'fulfillment_status' => '',
			'tags' => '',
			'contact_email' => $order->getCustomerEmail(),
			'order_status_url' => '',
			'discount_codes' => $discount_codes,
			'note_attributes' => $note_attributes,
			'payment_gateway_names' => $payment_gateway_names,
			'tax_lines' => $tax_lines,
			'line_items' => $line_items,
			'billing_address' => $billDataArr,
			'shipping_address' => $shippDataArr,
		);
		
		
		return json_encode($orderData);
	}
	
	/**
	* 
	* @param string $cookie_name
	* 
	* @return boolean true 
	*/
	public function unsetCookieByName($cookie_name){
		unset($_COOKIE[$cookie_name]);
		// empty value and expiration one hour before
		$res = setcookie($cookie_name, '', time() - 3600);
		return true;
	}
}