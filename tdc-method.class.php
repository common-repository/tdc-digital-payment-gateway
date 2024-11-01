<?php
include_once((__dir__).'/Crypt/RSA.php');

class VendorTdcPayment {
	private $error = array();
	private $urlApi = null;
	private $merchantId = null;
	private $apiToken = null;
	private $clientId = null;
	private $keyText = null;

	public function index() {
		return $this;
	}

	public function set($postData) {
		$this->urlApi = $postData['urlApiTdc'];
		$this->apiToken = $postData['apiToken'];
		$this->clientId = $postData['clientId'];
		$this->merchantId = $postData['merchantId'];
		$this->keyText = $postData['rsaKeyPublic'];

		return $this;
	}

	public function callEwallet($postData) {
		$urlPayment = $postData['urlPayment'];
		$transactionId = $postData['noInv'];
		$namePayment = $postData['namePayment'];
		$phone = $postData['phone'];
		$username = $postData['username'];
		$redirectUrl = $postData['redUrl'];
		$amount = (int)$postData['amount'];

		$clearKeyText = preg_replace("/	/","",$this->keyText);

		$rsa = new Crypt_RSA();
		$rsa->loadKey($clearKeyText);

		$params = array(
			"paymentMethod" => "EWALLET",
			"transactionId" => (string)$transactionId,
			"ewalletName" => $namePayment,
			"amount" => $amount,
			"phoneNumber" => $phone,
			"redirectUrl" => $redirectUrl,
			"tag" => $username."#".$this->clientId
		);

		$paramsEncoded = json_encode($params);
		$ciphertext = $rsa->encrypt($paramsEncoded);
		$dataEn = base64_encode($ciphertext);

		$param = array(	
			"data" => $dataEn
		);
		$paramEncoded = json_encode($param);

		$postRemote = array(
			'method' => 'POST',
			'timeout' => 45,
			'redirection' => 5,
			'httpversion' => '1.1',
			'blocking' => true,
			'headers' => array(
				"Content-Type" => "application/json",
				"Authorization" => "Bearer ".base64_encode($this->apiToken)
			),
			'body' => $paramEncoded,
			'sslverify'   => false,
			'data_format' => 'body',
			'encoding' => ''
		);

		$response = wp_remote_post($this->urlApi.$urlPayment, $postRemote);
		$response = json_decode( wp_remote_retrieve_body( $response ) );
		return $response;
	}

	public function callVirtualAccount($postData) {
		$urlPayment = $postData['urlPayment'];
		$transactionId = $postData['noInv'];
		$bankName = $postData['bankName'];
		$nameStore = $postData['nameStore'];
		$amount = (int)$postData['amount'];

		$clearKeyText = preg_replace("/	/","",$this->keyText);

		$rsa = new Crypt_RSA();

		$rsa->loadKey($clearKeyText);

		$params = array(
			"paymentMethod" => "VA",
			"transactionId" => (string)$transactionId,
			"bankName" => $bankName,
			"amount" => $amount,
			"name" => $nameStore
		);


		$paramsEncoded = json_encode($params);
		$ciphertext = $rsa->encrypt($paramsEncoded);
		$dataEn = base64_encode($ciphertext);

		$param = array(	
			"data" => $dataEn
		);
		$paramEncoded = json_encode($param);

		$postRemote = array(
			'method' => 'POST',
			'timeout' => 45,
			'redirection' => 5,
			'httpversion' => '1.1',
			'blocking' => true,
			'headers' => array(
				"Content-Type" => "application/json",
				"Authorization" => "Bearer ".base64_encode($this->apiToken)
			),
			'body' => $paramEncoded,
			'sslverify'   => false,
			'data_format' => 'body',
			'encoding' => ''
		);

		$response = wp_remote_post($this->urlApi.$urlPayment, $postRemote);

		$response = json_decode( wp_remote_retrieve_body( $response ) );

		return $response;
	}

	public function callCreditCard($postData) {
		$urlPayment = $postData['urlPayment'];
		$transactionId = $postData['noInv'];
		$redUrl = $postData['redUrl'];
		$amount = (int)$postData['amount'];

		$clearKeyText = preg_replace("/	/","",$this->keyText);

		$rsa = new Crypt_RSA();
		$rsa->loadKey($clearKeyText);

		$params = array(
			"paymentMethod" => "CC",
			"transactionId" => (string)$transactionId,
			"amount" => $amount,
			"redirectUrl" => (string)$redUrl
		);

		$paramsEncoded = json_encode($params);
		$ciphertext = $rsa->encrypt($paramsEncoded);
		$dataEn = base64_encode($ciphertext);

		$param = array(	
			"data" => $dataEn
		);
		$paramEncoded = json_encode($param);

		$postRemote = array(
			'method' => 'POST',
			'timeout' => 45,
			'redirection' => 5,
			'httpversion' => '1.1',
			'blocking' => true,
			'headers' => array(
				"Content-Type" => "application/json",
				"Authorization" => "Bearer ".base64_encode($this->apiToken)
			),
			'body' => $paramEncoded,
			'sslverify'   => false,
			'data_format' => 'body',
			'encoding' => ''
		);

		$response = wp_remote_post($this->urlApi.$urlPayment, $postRemote);
		$response = json_decode( wp_remote_retrieve_body( $response ) );
		return $response;
	}

	public function callGopay($postData) {
		$urlPayment = $postData['urlPayment'];
		$transactionId = $postData['noInv'];
		$amount = (int)$postData['amount'];

		$clearKeyText = preg_replace("/	/","",$this->keyText);

		$rsa = new Crypt_RSA();
		$rsa->loadKey($clearKeyText);

		$params = array(
			"paymentMethod" => "GOPAY",
			"transactionId" => (string)$transactionId,
			"amount" => $amount
		);

		$paramsEncoded = json_encode($params);
		$ciphertext = $rsa->encrypt($paramsEncoded);
		$dataEn = base64_encode($ciphertext);

		$param = array(	
			"data" => $dataEn
		);
		$paramEncoded = json_encode($param);

		$postRemote = array(
			'method' => 'POST',
			'timeout' => 45,
			'redirection' => 5,
			'httpversion' => '1.1',
			'blocking' => true,
			'headers' => array(
				"Content-Type" => "application/json",
				"Authorization" => "Bearer ".base64_encode($this->apiToken)
			),
			'body' => $paramEncoded,
			'sslverify'   => false,
			'data_format' => 'body',
			'encoding' => ''
		);

		$response = wp_remote_post($this->urlApi.$urlPayment, $postRemote);
		$response = json_decode( wp_remote_retrieve_body( $response ) );
		return $response;
	}

	public function callQris($postData) {
		$urlPayment = $postData['urlPayment'];
		$transactionId = $postData['noInv'];
		$amount = (int)$postData['amount'];
		$merchantId = $postData['merchantId'];

		$clearKeyText = preg_replace("/	/","",$this->keyText);

		$rsa = new Crypt_RSA();
		$rsa->loadKey($clearKeyText);

		if(empty($merchantId) || $merchantId == ""){
		$params = array(
			"paymentMethod" => "QRIS",
			"transactionId" => (string)$transactionId,
			"amount" => $amount
		);
		} else{
			$params = array(
			"paymentMethod" => "QRIS",
			"transactionId" => (string)$transactionId,
			"amount" => $amount,
			"mid" => $merchantId,
			"merchantId" => $merchantId,
			);
		}

		$paramsEncoded = json_encode($params);
		$ciphertext = $rsa->encrypt($paramsEncoded);
		$dataEn = base64_encode($ciphertext);

		$param = array(	
			"data" => $dataEn
		);

		$paramEncoded = json_encode($param);

		$postRemote = array(
			'method' => 'POST',
			'timeout' => 45,
			'redirection' => 5,
			'httpversion' => '1.1',
			'blocking' => true,
			'headers' => array(
				"Content-Type" => "application/json",
				"Authorization" => "Bearer ".base64_encode($this->apiToken)
			),
			'body' => $paramEncoded,
			'sslverify'   => false,
			'data_format' => 'body',
			'encoding' => ''
		);

		$response = wp_remote_post($this->urlApi.$urlPayment, $postRemote);
		$response = json_decode( wp_remote_retrieve_body( $response ) );
		return $response;
	}

	public function callPaypal($postData) {
		$urlPayment = $postData['urlPayment'];
		$transactionId = $postData['noInv'];
		$amount = (int)$postData['amount'];

		$clearKeyText = preg_replace("/	/","",$this->keyText);

		$rsa = new Crypt_RSA();
		$rsa->loadKey($clearKeyText);

		$params = array(
			"paymentMethod" => "PAYPAL",
			"transactionId" => (string)$transactionId,
			"amount" => $amount
		);

		$paramsEncoded = json_encode($params);
		$ciphertext = $rsa->encrypt($paramsEncoded);
		$dataEn = base64_encode($ciphertext);

		$param = array(	
			"data" => $dataEn
		);
		$paramEncoded = json_encode($param);

		$postRemote = array(
			'method' => 'POST',
			'timeout' => 45,
			'redirection' => 5,
			'httpversion' => '1.1',
			'blocking' => true,
			'headers' => array(
				"Content-Type" => "application/json",
				"Authorization" => "Bearer ".base64_encode($this->apiToken)
			),
			'body' => $paramEncoded,
			'sslverify'   => false,
			'data_format' => 'body',
			'encoding' => ''
		);

		$response = wp_remote_post($this->urlApi.$urlPayment, $postRemote);
		$response = json_decode( wp_remote_retrieve_body( $response ) );
		return $response;
	}

	public function callGetPaymentStatus($postData) {
		$transactionId = $postData['noInv'];

		$clearKeyText = preg_replace("/	/","",$this->keyText);

		$rsa = new Crypt_RSA();
		$rsa->loadKey($clearKeyText);

		$params = array(
			"transactionId" => (string)$transactionId
		);

		$paramsEncoded = json_encode($params);
		$ciphertext = $rsa->encrypt($paramsEncoded);
		$dataEn = base64_encode($ciphertext);

		$param = array(	
			"data" => $dataEn
		);
		$paramEncoded = json_encode($param);

		$postRemote = array(
			'method' => 'POST',
			'timeout' => 45,
			'redirection' => 5,
			'httpversion' => '1.1',
			'blocking' => true,
			'headers' => array(
				"Content-Type" => "application/json",
				"Authorization" => "Bearer ".base64_encode($this->apiToken)
			),
			'body' => $paramEncoded,
			'sslverify'   => false,
			'data_format' => 'body',
			'encoding' => ''
		);

		$response = wp_remote_post($this->urlApi.$urlPayment, $postRemote);
		$response = json_decode( wp_remote_retrieve_body( $response ) );
		return $response;
	}

    public function checkFee($postData) {
        // getFeeDetail
		$urlPayment = $postData['urlPayment'];
		$payMethod = $postData['payMethod'];
		$payType = $postData['payType'];
		$amount = (int)$postData['amount'];
		
		$clearKeyText = preg_replace("/	/","",$this->keyText);

		$rsa = new Crypt_RSA();
		$rsa->loadKey($clearKeyText);

		$params = array(
			"paymentMethod" => $payMethod,
			"paymentType" => $payType,
			"amount" => $amount,
		);


		$paramsEncoded = json_encode($params);
		$ciphertext = $rsa->encrypt($paramsEncoded);
		$dataEn = base64_encode($ciphertext);

		$param = array(	
			"data" => $dataEn
		);
		$paramEncoded = json_encode($param);

		$curl = curl_init();

		curl_setopt_array($curl, array(
			CURLOPT_URL => $this->urlApi.$urlPayment,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 60,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_POSTFIELDS => $paramEncoded,
			CURLOPT_HTTPHEADER => array(
				'Authorization: Bearer '.base64_encode($this->apiToken),
				'Content-Type: application/json'
			),
		));

		$response = curl_exec($curl);

		$error_msg = "";

		if (curl_errno($curl)) {
			$error_msg = curl_error($curl);
		}

		curl_close($curl);
		
		if ($error_msg !== "") {
			return $error_msg;
		}

        $jsonDecode = json_decode($response);

        $totalFee = 0;
        if ($jsonDecode->Error === false) {
            $totalFee = (float)$jsonDecode->Fee->totalFee + (float)$jsonDecode->Fee->totalPPN;
        }

        $resData = array(
            "totalFee" => $totalFee,
            "fee" => (float)$jsonDecode->Fee->totalFee,
            "ppn" => (float)$jsonDecode->Fee->totalPPN,
            "percentFee" => (float)$jsonDecode->Fee->percentFee,
            "fix" => (float)$jsonDecode->Fee->fixFee,
            "totalOrder" => $amount + $totalFee
        );

		return $resData;
	}
}
?>