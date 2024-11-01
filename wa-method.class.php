<?php
include_once((__dir__).'/Crypt/RSA.php');
date_default_timezone_set("Asia/Jakarta");

class WhatsappApiDoni {
    public function index() {
		return $this;
	}

    public function sendMessageWa($target,$pesan,$type,$token) {
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => 'http://144.91.95.163:5002/sendRealtime',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_POSTFIELDS =>'{ "data" : 
				[
					{
						"number":"'.$target.'",
						"message":'.$pesan.',
						"type": "'.$type.'"
					}
				],
			"token":"'.$token.'"
			}
			',
			CURLOPT_HTTPHEADER => array(
				'Content-Type: application/json'
			),
		));

		$response = curl_exec($curl);

		curl_close($curl);

		return true;
	}

	public function waMessageNewOrder($order,$target,$type,$token,$linkPayment = "") {
		$pesan = "New Order Received \n";
		$pesan .= "----------------------\n";
		$pesan .= "Hello, ".$order->get_billing_first_name() . " ". $order->get_billing_last_name()."\n";
		$pesan .= "Order Number : ".$order->get_order_number()."\n";
		$pesan .= "Date : ".date("Y-m-d H:i:s")."\n";
		$pesan .= "Email : ".$order->get_billing_email()."\n";
		$pesan .= "Total Amount : ".number_format($order->get_total())."\n";
		$pesan .= "----------------------\n";
		$pesan .= "Order details: \n";
		$no = 1;

		foreach ($order->get_items() as $item_id => $item ) {
			$pesan .= $no . ". ".$item->get_name().", Qty : ".$item->get_quantity()." (Rp.".number_format($item->get_total()).") \n";
			$no++;
		}
		$pesan .= "----------------------\n";
		$pesan .= "Shipping Total : ".number_format($order->get_shipping_total())."\n";
		$pesan .= "Subtotal : ".number_format($order->get_subtotal())."\n";
		$pesan .= "Total : ".number_format($order->get_total())."\n";
		$pesan .= "----------------------\n";
		$pesan .= "Shipping Method : ".$order->get_shipping_method()."\n";
		$pesan .= "Payment Method : ".$order->get_payment_method()."\n";
		$pesan .= "----------------------\n";

		if ($linkPayment != "") {
			$pesan .= "Pay Now : ".$linkPayment."\n";
		}

		$tempObj = [];
		$tempObj['data'] = [];
		$tempObj['token'] = $token;

		$tempData = [];
		$tempData['number'] = $target;
		$tempData['message'] = $pesan;
		$tempData['type'] = $type;
		
		array_push($tempObj['data'],$tempData);

		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => 'http://144.91.95.163:5002/sendRealtime',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_POSTFIELDS =>json_encode($tempObj),
			CURLOPT_HTTPHEADER => array(
				'Content-Type: application/json'
			),
		));

		$response = curl_exec($curl);

		curl_close($curl);

		return true;
	}

	public function waMessageProcessing($order,$target,$type,$token,$linkPayment = "",$notes = "") {
		$pesan = "Order is Processing (Packaging) \n";
		$pesan .= "----------------------\n";
		$pesan .= "Hello, ".$order->get_billing_first_name() . " ". $order->get_billing_last_name()."\n";
		$pesan .= "Order Number : ".$order->get_order_number()."\n";
		$pesan .= "Email : ".$order->get_billing_email()."\n";
		$pesan .= "Total Amount : ".number_format($order->get_total())."\n";
		$pesan .= "----------------------\n";
		$pesan .= "Order details: \n";
		$no = 1;

		foreach ($order->get_items() as $item_id => $item ) {
			$pesan .= $no . ". ".$item->get_name().", Qty : ".$item->get_quantity()." (Rp.".number_format($item->get_total()).") \n";
			$no++;
		}

		$pesan .= "Shipping Method : ".$order->get_shipping_method()."\n";

		$pesan .= "----------------------\n";
		
		if ($notes != "") {
			$pesan .= "Information : \n";
			$pesan .= $notes;
		}

		$tempObj = [];
		$tempObj['data'] = [];
		$tempObj['token'] = $token;

		$tempData = [];
		$tempData['number'] = $target;
		$tempData['message'] = $pesan;
		$tempData['type'] = $type;
		
		array_push($tempObj['data'],$tempData);

		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => 'http://144.91.95.163:5002/sendRealtime',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_POSTFIELDS =>json_encode($tempObj),
			CURLOPT_HTTPHEADER => array(
				'Content-Type: application/json'
			),
		));

		$response = curl_exec($curl);

		curl_close($curl);

		return true;
	}
}