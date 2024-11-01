<?php
include_once "tdc-method.class.php";
include_once "wa-method.class.php";

add_filter( 'woocommerce_payment_gateways', 'tdc_add_gateway_class' );

function tdc_add_gateway_class( $gateways ) {
	$gateways[] = 'WC_Tdc_Gateway';
    
	return $gateways;
}

add_action('wc_ajax_checkFee','checkout_fee_set_session');
add_action('wc_ajax_setFeeZero','set_fee_zero');
add_action( 'woocommerce_order_status_processing','sendWaChangeOrderProcessing');

function set_fee_zero( ) {
    global $woocommerce;

    $woocommerce->session->set( 'totalOrderFee', 0 );
}

function checkout_fee_set_session( ) {
    if ( ! $_POST || ( is_admin() && ! is_ajax() ) ) {
        echo "Not Access";
        return;
    }

    global $woocommerce;

    $woocommerce->session->set( 'totalOrderFee', 0 );

    $dataCheckFee = array(
        "urlPayment" => "/v2/getFeeDetail",
        "payMethod" => $_POST['paymentMethod'],
        "payType" => $_POST['namePayment'],
        "amount" => $woocommerce->cart->get_totals()['total']
    );

    $gateway = new WC_Tdc_Gateway();

    if ($gateway->show_fee === "yes") {
        $getFee = $gateway->TdcMethod->set($gateway->configTdc)->checkFee($dataCheckFee);

        $woocommerce->session->set( 'totalOrderFee', (float)$getFee['totalFee'] );
    }

    return;
}

add_action( 'woocommerce_cart_calculate_fees', 'addPaymentTdcFee' );
add_action( 'woocommerce_checkout_update_order_review', 'addPaymentTdcFee' );

function addPaymentTdcFee(){
    $gateway = new WC_Tdc_Gateway();

    if ($gateway->show_fee === "yes") {
        global $woocommerce;
        $woocommerce->cart->add_fee( 'Fee', $woocommerce->session->get( 'totalOrderFee' ), true, '' );
        $new_total = $woocommerce->cart->cart_contents_total + (float)$woocommerce->session->get( 'totalOrderFee' );
        return wc_price($new_total);
    }
}

function sendWaChangeOrderProcessing($order_id) {
    
    $notes = get_notes_order_product( $order_id );

    if (count($notes) > 0) {
        $notes = $notes[0]['note_content'];
    } else {
        $notes = "";
    }

    $gateway = new WC_Tdc_Gateway();
    
    $order = wc_get_order( $order_id );

    if ($gateway->wa_enable == 'yes') {
        $gateway->WaMethod->waMessageProcessing($order,$order->get_billing_phone(),$gateway->whatsapp_type,$gateway->whatsapp_token,"COBA COBA PAYMENT",$notes);
    }

    return;
}

function get_notes_order_product( $order_id){
    global $wpdb;

    $table_perfixed = $wpdb->prefix . 'comments';

    $results = $wpdb->get_results("
        SELECT *
        FROM $table_perfixed
        WHERE  `comment_post_ID` = $order_id
        AND  `comment_type` =  'order_note' 
        ORDER BY comment_date DESC LIMIT 1
    ");

    foreach($results as $note){
        $order_note[]  = array(
            'note_id'      => $note->comment_ID,
            'note_date'    => $note->comment_date,
            'note_author'  => $note->comment_author,
            'note_content' => $note->comment_content,
        );
    }
    return $order_note;
}

add_action( 'plugins_loaded', 'tdc_init_gateway_class' );

function tdc_init_gateway_class() {

	class WC_Tdc_Gateway extends WC_Payment_Gateway {
 		public function __construct() {
            $this->TdcMethod = new VendorTdcPayment();
            $this->WaMethod = new WhatsappApiDoni();

            $this->id = 'tdc'; 
            $this->icon = 'https://images.glints.com/unsafe/glints-dashboard.s3.amazonaws.com/company-logo/2c13e6f97a5315b9ea855113f7507edc.png'; 
            $this->has_fields = true; 
            $this->method_title = 'TDC Gateway';
            $this->method_description = 'Description of TDC payment gateway'; 

            $this->supports = array(
                'products'
            );

	        $this->init_form_fields();

            // Load the settings.
            $this->init_settings();
            $this->title = $this->get_option( 'title' );
            $this->description = $this->get_option( 'description' );
            $this->enabled = $this->get_option( 'enabled' );
            $this->device = $this->get_option( 'device' );
            $this->rsa_public_key = $this->get_option( 'rsa_public_key' );
            $this->api_token = $this->get_option( 'api_token' );
            $this->api_url_endpoint = $this->get_option( 'api_url_endpoint' );
            $this->client_id = $this->get_option( 'client_id' );
            $this->merchant_id = $this->get_option( 'merchant_id' );
            $this->show_fee = $this->get_option( 'show_fee' );
            $this->virtual_product = $this->get_option( 'virtual_product' );
            $this->enable_e_wallet = $this->get_option( 'enable_e_wallet' );
            $this->enable_gopay = $this->get_option( 'enable_gopay' );
            $this->enable_virtual_account = $this->get_option( 'enable_virtual_account' );
            $this->enable_qris = $this->get_option( 'enable_qris' );
            $this->enable_cc = $this->get_option( 'enable_cc' );
            $this->json_enable = $this->get_option('json_enable');
            $this->wa_enable = $this->get_option('wa_enable');
            $this->whatsapp_number = $this->get_option('whatsapp_number');
            $this->whatsapp_type = $this->get_option('whatsapp_type');
            $this->whatsapp_token = $this->get_option('whatsapp_token');
            
            
            $this->configTdc = NULL;

            if ($this->rsa_public_key !== "" && $this->client_id !== "" && $this->api_url_endpoint !== "" && $this->api_token !== "") {
                $this->configTdc = array(
                    "urlApiTdc"    => $this->api_url_endpoint,
                    "apiToken"    => $this->api_token,
                    "clientId"    => $this->client_id,
                    "rsaKeyPublic"    => $this->rsa_public_key
                );
            }
            
            
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
            
            add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
            
            
            // You can also register a webhook here
            add_action( 'woocommerce_api_tdc_callback', array( $this, 'callback_handler' ) );

            add_action( 'woocommerce_before_thankyou', 'success_message_after_payment' );
 		}

        function success_message_after_payment( $order_id ){
            // Get the WC_Order Object
            $order = wc_get_order( $order_id );
        
            if ( $order->has_status('processing') ){
                wc_print_notice( __("Your payment has been successful", "woocommerce"), "success" );
            }

            if ( $order->has_status('completed') ){
                wc_print_notice( __("Your payment has been successful", "woocommerce"), "success" );
            }
         }

 		public function init_form_fields(){

            $this->form_fields = array(
                'enabled' => array(
                    'title'       => 'Enable/Disable',
                    'label'       => 'Enable TDC Gateway',
                    'type'        => 'checkbox',
                    'description' => 'Example URL Callback : https://domain.com/?wc-api=tdc_callback .<I>(Must using HTTPS)</I>',
                    'default'     => 'no'
                ),
                'title' => array(
                    'title'       => 'Title',
                    'type'        => 'text',
                    'description' => 'Payment with TDC Digital',
                    'default'     => 'TDC Payment',
                    'desc_tip'    => true,
                ),
                'description' => array(
                    'title'       => 'Description',
                    'type'        => 'textarea',
                    'description' => 'Payment E-Wallet, Virtual Account, Gojek, And QRIS',
                    'default'     => 'Pay Sample and Fast',
                ),
                'rsa_public_key' => array(
                    'title'       => 'RSA Public Key',
                    'type'        => 'textarea'
                ),
                'api_token' => array(
                    'title'       => 'API Token',
                    'type'        => 'text',
                ),
                'client_id' => array(
                    'title'       => 'Client ID',
                    'type'        => 'text'
                ),
                'merchant_id' => array(
                    'title'       => 'Merchant ID',
                    'type'        => 'text',
                    'description' => 'Masukkan Marchant ID Anda untuk metode pembayaran QRIS, kosongkan jika tidak ada'
                ),
                'api_url_endpoint' => array(
                    'title'       => 'API URL Endpoint',
                    'type'        => 'text',
                    'default'     => 'https://api.tdcdigital.id/api',
                    'description' => 'Example : https://api.tdcdigital.id/api',
                    'desc_tip'    => true
                ),
                'show_fee' => array(
                    'title'       => 'Add Fee',
                    'label'       => 'Enable Fee',
                    'type'        => 'checkbox',
                    'description' => 'Jika disable, maka fee tidak akan dibebankan oleh user. Dan biaya Fee sesuai perjanjian antara Toko dan TDC Digital',
                    'default'     => 'yes',
                    'desc_tip'    => true
                ),
                'json_enable' => array(
                    'title'       => 'Output Json Only',
                    'label'       => 'Enable Output Json',
                    'type'        => 'checkbox',
                    'description' => 'Jika enable, maka outputnya hanya json saja',
                    'default'     => 'no',
                    'desc_tip'    => true
                ),
                'wa_enable' => array(
                    'title'       => 'Enable Whatsapp API',
                    'label'       => 'Enable Whatsapp API',
                    'type'        => 'checkbox',
                    'description' => 'Jika enable, maka API Whatsapp akan berfungsi',
                    'default'     => 'no',
                    'desc_tip'    => true
                ),
                'whatsapp_number' => array(
                    'title'       => 'Whatsapp Number For API',
                    'type'        => 'number',
                    'default'     => '',
                    'description' => 'Example : must with country code : 6285777123123',
                    'desc_tip'    => true
                ),
                'whatsapp_type' => array(
                    'title'       => 'Whatsapp Type',
                    'type'        => 'text',
                    'default'     => '',
                    'description' => 'Get Whatsapp API access by calling to number 6285777038748',
                    'desc_tip'    => true
                ),
                'whatsapp_token' => array(
                    'title'       => 'Whatsapp Token',
                    'type'        => 'text',
                    'default'     => '',
                    'description' => 'Get Whatsapp API access by calling to number 6285777038748',
                    'desc_tip'    => true
                ),
                
                'virtual_product' => array(
                    'title'       => 'Virtual Product ?',
                    'label'       => 'Enable Virtual Product',
                    'type'        => 'checkbox',
                    'description' => 'Jika enable, maka status pembayaran dari hold -> complete',
                    'default'     => 'no',
                    'desc_tip'    => true
                ),
                'enable_e_wallet' => array(
                    'title'             => 'Enable E-Wallet',
                    'type'              => 'multiselect',
                    'class'             => 'wc-enhanced-select',
                    'css'               => 'width: 400px;',
                    'default'           => '',
                    'description'       => 'Pilih E-Wallet yang ingin Anda Aktifkan',
                    'options'           => array(
                        "OVO" => "OVO",
                        "DANA" => "DANA",
                        "LINKAJA" => "LINKAJA",
                        "SHOPEEPAY" => "SHOPEEPAY",
                        "CIMBCLICK" => "CIMBCLICK"
                    ),
                    'desc_tip'          => true,
                    'custom_attributes' => array(
                      'data-placeholder' => __( 'Select E-Wallet', 'woocommerce' ),
                    ),
                ),
                'enable_gopay' => array(
                    'title'             => 'Enable Gopay',
                    'type'              => 'multiselect',
                    'class'             => 'wc-enhanced-select',
                    'css'               => 'width: 400px;',
                    'default'           => '',
                    'description'       => 'Akftikan Gopay',
                    'options'           => array(
                        "GOPAY" => "GOPAY"
                    ),
                    'desc_tip'          => true,
                    'custom_attributes' => array(
                      'data-placeholder' => __( 'Enable Gopay', 'woocommerce' ),
                    ),
                ),
                'enable_virtual_account' => array(
                    'title'             => 'Enable Virtual Account',
                    'type'              => 'multiselect',
                    'class'             => 'wc-enhanced-select',
                    'css'               => 'width: 400px;',
                    'default'           => '',
                    'description'       => 'Pilih Virtual Account yang ingin Anda Aktifkan',
                    'options'           => array(
                        "BCA" => "BCA",
                        "BNI" => "BNI",
                        "BRI" => "BRI",
                        "MANDIRI" => "MANDIRI",
                        "PERMATA" => "PERMATA"
                    ),
                    'desc_tip'          => true,
                    'custom_attributes' => array(
                      'data-placeholder' => __( 'Select Virtual Account', 'woocommerce' ),
                    ),
                ),
                'enable_qris' => array(
                    'title'             => 'Enable QRIS',
                    'type'              => 'multiselect',
                    'class'             => 'wc-enhanced-select',
                    'css'               => 'width: 400px;',
                    'default'           => '',
                    'description'       => 'Akftikan QRIS',
                    'options'           => array(
                        "QRIS" => "QRIS"
                    ),
                    'desc_tip'          => true,
                    'custom_attributes' => array(
                      'data-placeholder' => __( 'Enable QRIS', 'woocommerce' ),
                    ),
                ),
                'enable_cc' => array(
                    'title'             => 'Enable Credit Card',
                    'type'              => 'multiselect',
                    'class'             => 'wc-enhanced-select',
                    'css'               => 'width: 400px;',
                    'default'           => '',
                    'description'       => 'Akftikan Kartu Kredit',
                    'options'           => array(
                        "CC" => "Credit Card"
                    ),
                    'desc_tip'          => true,
                    'custom_attributes' => array(
                      'data-placeholder' => __( 'Enable Credit Card', 'woocommerce' ),
                    ),
                )
            );
	 	}

		public function payment_fields() {
            if ( $this->description ) {
                echo wpautop( wp_kses_post( $this->description ) );
            }
        
            // $this->get_order_total()
        
            do_action( 'woocommerce_tdc_payment_form_start', $this->id );

            // $dataGopay = array(
            //     "GOPAY|0" => "Select Gopay",
            // );

            // foreach ($this->enable_gopay as $value) {
            //     $dataGopay["GOPAY|".$value] = $value;
            // };

            // if (count($dataGopay) > 0) {
            //     echo woocommerce_form_field(
            //         'GOPAY',
            //         array(
            //             'type'        => 'select',
            //             'required'    => false, 
            //             'label'       => 'Gopay',
            //             'class' => array('form-control'),
            //             'options' => $dataGopay,
            //             'default' => 'GOPAY|0'
            //         )
            //     );
            // }

            $flagQrisActive = 1;

            $dataEWallet = array(
                "WALLET|0" => "Select E-Wallet",
            );
            
    
            if (is_array($this->enable_gopay)) {
                $flagQrisActive = 0;
                foreach ($this->enable_gopay as $value) {
                    $dataEWallet["GOPAY|".$value] = $value;
                };
            }

            if (is_array($this->enable_e_wallet)) {
                $flagQrisActive = 0;
                foreach ($this->enable_e_wallet as $value) {
                    $dataEWallet["WALLET|".$value] = $value;
                };
            }

            if (count($dataEWallet) > 1) {
                echo woocommerce_form_field(
                    'E_WALLET',
                    array(
                        'type'        => 'select',
                        'required'    => false, 
                        'label'       => 'Pembayaran Instant',
                        'class' => array('form-control'),
                        'options' => $dataEWallet,
                        'default' => 'WALLET|0'
                    )
                );
            }

            $dataVirtualAccount = array(
                "VA|0" => "Select VA",
            );

            if (is_array($this->enable_virtual_account)) {
                $flagQrisActive = 0;
                foreach ($this->enable_virtual_account as $value) {
                    $dataVirtualAccount["VA|".$value] = $value;
                };
            }

            if (count($dataVirtualAccount) > 1) {
                echo woocommerce_form_field(
                    'VIRTUAL_ACCOUNT',
                    array(
                        'type'        => 'select',
                        'required'    => false, 
                        'label'       => 'Pembayaran Virtual Account',
                        'class' => array('form-control'),
                        'options' => $dataVirtualAccount,
                        'default' => 'VA|0'
                    )
                );
            }

            $dataQris = array(
                "QRIS|0" => "Select QRIS",
            );

            if (is_array($this->enable_qris)) {
                foreach ($this->enable_qris as $value) {
                    $dataQris["QRIS|".$value] = $value;
                };
            }

            $defaultQris = 'QRIS|0';
            
            if (count($dataQris) > 1) {
                echo woocommerce_form_field(
                    'QRIS',
                    array(
                        'type'        => 'select',
                        'required'    => false, 
                        'label'       => 'Pembayaran QRIS',
                        'class' => array('form-control'),
                        'options' => $dataQris,
                        'default' => $defaultQris
                    )
                );
            }

            $defaultCc = 'CC|0';

            $dataCc = array(
                "CC|0" => "Select Credit Card",
            );

            if (is_array($this->enable_cc)) {
                foreach ($this->enable_cc as $value) {
                    $dataCc["CC|".$value] = $value;
                };
            }
            
            if (count($dataCc) > 1) {
                echo woocommerce_form_field(
                    'CC',
                    array(
                        'type'        => 'select',
                        'required'    => false, 
                        'label'       => 'Pembayaran Kartu Kredit',
                        'class' => array('form-control'),
                        'options' => $dataCc,
                        'default' => $defaultCc
                    )
                );
            }

           

            $flagQrisActive = 0;
        
            echo woocommerce_form_field(
                'device_tdc',
                array(
                    'type'        => 'hidden',
                    'required'    => true,
                    'default' => 'desktop'
                )
                );

            echo "<div class='form-row form-control' id='divPaymentTdcProcessing' style='display:none;margin-top: 20px;text-align: center;'>";
                echo "<h4>Set Payment is Processing ...</h4>";
            echo "</div>";

            do_action( 'woocommerce_tdc_payment_form_start', $this->id );
		}

		/*
		 * Custom CSS and JS, in most cases required only when you decided to go with a custom credit card form
		 */
	 	public function payment_scripts() {
            // we need JavaScript to process a token only on cart/checkout pages, right?
            if ( ! is_cart() && ! is_checkout() && ! isset( $_GET['pay_for_order'] ) ) {
                return;
            }

            // if our payment gateway is disabled, we do not have to enqueue JS too
            if ( 'no' === $this->enabled ) {
                return;
            }

            wp_register_script( 'woocommerce_tdc', plugins_url( '/assets/js/tdc_method.js', __FILE__ ), array( 'jquery' ),'1.9', true );

            wp_localize_script( 'woocommerce_tdc', 'tdc_params', array(
                // 'is_logged_in' => is_user_logged_in(),
            ) );

            wp_enqueue_script( 'woocommerce_tdc' );
	 	}

		/*
 		 * Fields validation, more in Step 5
		 */
		public function validate_fields() {
            if ($this->virtual_product == "no") {
                if( empty( $_POST[ 'billing_first_name' ]) ) {
                    wc_add_notice(  'First name is required!', 'error' );
                    return false;
                }
            }

            return true;
		}

		public function process_payment( $order_id ) {
            global $woocommerce;

            $responsePost = $_POST;
            $postDataPayment = array();   
            
            $response = NULL;

            $deviceType = $responsePost['device_tdc'];
            $methodPaymentTdc = "";

            $order = wc_get_order( $order_id );

            if (isset($responsePost['E_WALLET']) && $responsePost['E_WALLET'] != "WALLET|0") {
                // E-Wallet Payment
                $namePayment = explode("|",$responsePost['E_WALLET'])[1];

                $postDataPayment['urlPayment'] = "/v2/ewalletPayment";
                $postDataPayment['noInv'] = $order_id;
                $postDataPayment['namePayment'] = $namePayment;

                if ($responsePost['ovoNumber'] != NULL && $responsePost['ovoNumber'] != "") {
                    $first_character = substr($responsePost['ovoNumber'], 0, 1);
                    if ($first_character == "0") {
                        $newString = substr_replace($responsePost['ovoNumber'], "+62", 1, 0);
	                    $responsePost['ovoNumber'] = ltrim($newString, '0');
                    }
                } else {
                    $responsePost['ovoNumber'] = "";
                }
                $postDataPayment['phone'] = $responsePost['ovoNumber'];
                $postDataPayment['username'] = $responsePost['billing_email'];
                $postDataPayment['redUrl'] =  wc_get_checkout_url();
                $postDataPayment['amount'] =  $this->get_order_total();
                $methodPaymentTdc = "EWALLET";

                if ($namePayment  === "GOPAY") {
                    $postDataPayment['urlPayment'] = "/v2/gopayPayment";
                    $methodPaymentTdc = "GOPAY";

                    $response = $this->TdcMethod->set($this->configTdc)->callGopay($postDataPayment);
                } else {
                    $response = $this->TdcMethod->set($this->configTdc)->callEwallet($postDataPayment);
                }

            } else if (isset($responsePost['VIRTUAL_ACCOUNT']) && $responsePost['VIRTUAL_ACCOUNT'] != "VA|0") {
                // Virtual Account Payment
                $namePayment = explode("|",$responsePost['VIRTUAL_ACCOUNT'])[1];
                $postDataPayment['urlPayment'] = "/v2/vaPayment";
                $postDataPayment['noInv'] = $order_id;
                $postDataPayment['bankName'] = $namePayment;
                $postDataPayment['nameStore'] = $this->client_id;
                $postDataPayment['amount'] =  $this->get_order_total();
                $methodPaymentTdc = "VA";

                $response = $this->TdcMethod->set($this->configTdc)->callVirtualAccount($postDataPayment);
            } else if (isset($responsePost['QRIS']) && $responsePost['QRIS'] != "QRIS|0") {
                // QRIS Payment
                $namePayment = explode("|",$responsePost['QRIS'])[1];

                $postDataPayment['urlPayment'] = "/v2/qrisPayment";
                $postDataPayment['noInv'] = $order_id;
                $postDataPayment['namePayment'] = $namePayment;
                $postDataPayment['merchantId'] = $this->merchant_id;
                $postDataPayment['amount'] =  $this->get_order_total();
                $methodPaymentTdc = "QRIS";

                $response = $this->TdcMethod->set($this->configTdc)->callQris($postDataPayment);

            } else if (isset($responsePost['CC']) && $responsePost['CC'] != "CC|0") {
                // QRIS Payment
                $namePayment = explode("|",$responsePost['CC'])[1];

                $postDataPayment['urlPayment'] = "/v2/ccPayment";
                $postDataPayment['noInv'] = $order_id;
                $postDataPayment['namePayment'] = $namePayment;
                $postDataPayment['redUrl'] = $order->get_checkout_order_received_url();
                $postDataPayment['amount'] =  $this->get_order_total();
                $methodPaymentTdc = "CC";

                $response = $this->TdcMethod->set($this->configTdc)->callCreditCard($postDataPayment);

            } else {
                wc_add_notice(  'Payment Method not selected', 'error' );
                return;
            }
        
            
            if( !is_wp_error( $response ) ) {                
                if ( $response->Error != 1 ) {
                    $dataPayment = $response->data;

                    $linkRedirect = wc_get_checkout_url();
                    $textLinkOrder = '<a href="'.$this->get_return_url( $order ).'"><button type="button" class="button btn-primary">My Order</button></a>';
                    $textLinkPay = '';
                    $urlData = "";

                    $order->update_status('pending', 'Waiting Payment');

                    if ($methodPaymentTdc === "VA") {
                        $order->add_order_note( 'Hey, Silahkan lakukan pembayaran Anda. Ke Bank : '.$postDataPayment['bankName'].', Account Number : '.$dataPayment->accountNumber, true );
                        wc_add_notice(  'Silahkan lakukan pembayaran Anda. Ke Bank : '.$postDataPayment['bankName'].', Account Number : '.$dataPayment->accountNumber.'.'.$textLinkOrder );
                        $urlData = 'Bank : '.$postDataPayment['bankName'].' Account Number : '.$dataPayment->accountNumber;
                        
                    } else if ($methodPaymentTdc === "GOPAY") {
                        if ($deviceType === "mobile") {
                            $order->add_order_note( 'Hey, Silahkan lakukan pembayaran Anda. Access URL tersebut : '.$dataPayment->checkoutUrlMobile, true );

                            $textLinkPay = '<a target="_blank" href="'.$dataPayment->checkoutUrlMobile.'"><button type="button" class="button btn-primary">Pay</button></a>';
                            $urlData = $dataPayment->checkoutUrlMobile;
                        } else {
                            $order->add_order_note( 'Hey, Silahkan lakukan pembayaran Anda. Access URL tersebut : '.$dataPayment->checkoutUrlDesktop, true );
                            
                            $textLinkPay = '<a target="_blank" href="'.$dataPayment->checkoutUrlDesktop.'"><button type="button" class="button btn-primary">Pay</button></a>';
                            $urlData = $dataPayment->checkoutUrlDesktop;
                        }

                        wc_add_notice(  'Silahkan lakukan pembayaran Anda. Klik Button Pay.'.$textLinkPay.$textLinkOrder);
                    } else if ($methodPaymentTdc === "QRIS") {
                        $order->add_order_note( 'Hey, Silahkan lakukan pembayaran Anda. Access URL tersebut : '.$dataPayment->showQRUrl, true );

                        $textLinkPay = '<a target="_blank" href="'.$dataPayment->showQRUrl.'"><button type="button" class="button btn-primary">Pay</button></a>';
                        $linkRedirect = $dataPayment->showQRUrl;
                        wc_add_notice(  'Silahkan lakukan pembayaran Anda. Klik Button Pay.'.$textLinkPay.$textLinkOrder);
                        $urlData = $dataPayment->showQRUrl;
                    } else if ($methodPaymentTdc === "EWALLET") {
                        if ($deviceType === "mobile") {
                            $order->add_order_note( 'Hey, Silahkan lakukan pembayaran Anda. Access URL tersebut : '.$dataPayment->checkoutUrlMobile, true );

                            $textLinkPay = '<a target="_blank" href="'.$dataPayment->checkoutUrlMobile.'"><button type="button" class="button btn-primary">Pay</button></a>';
                            $urlData = $dataPayment->checkoutUrlMobile;
                        } else {
                            $order->add_order_note( 'Hey, Silahkan lakukan pembayaran Anda. Access URL tersebut : '.$dataPayment->checkoutUrlDesktop, true );

                            $textLinkPay = '<a target="_blank" href="'.$dataPayment->checkoutUrlDesktop.'"><button type="button" class="button btn-primary">Pay</button></a>';
                            $urlData = $dataPayment->checkoutUrlDesktop;
                        }

                        wc_add_notice(  'Silahkan lakukan pembayaran Anda. Klik Button Pay.'.$textLinkPay.$textLinkOrder);
                    } else if ($methodPaymentTdc === "CC") {
                        $order->add_order_note( 'Hey, Silahkan lakukan pembayaran Anda. Access URL tersebut : '.$dataPayment->checkoutUrl, true );

                        $textLinkPay = '<a href="'.$dataPayment->checkoutUrl.'"><button type="button" class="button btn-primary">Pay</button></a>';
                        $linkRedirect = $dataPayment->checkoutUrl;
                        wc_add_notice(  'Silahkan lakukan pembayaran Anda. Klik Button Pay.'.$textLinkPay.$textLinkOrder);
                        $urlData = $dataPayment->checkoutUrl;
                    }

                    if ($this->wa_enable == 'yes') {
                        $this->WaMethod->waMessageNewOrder($order,$order->get_billing_phone(),$this->whatsapp_type,$this->whatsapp_token,$urlData);
                    }
                    // Empty cart
                    $woocommerce->cart->empty_cart();
        
                    $woocommerce->session->set( 'totalOrderFee', 0 );

                    // return;
                    
                    if ($this->json_enable == 'yes') {
                        return array(
                            'result' => 'success',
                            'data' => $urlData
                        );
            
                    } else {
                        return array(
                            'result' => 'success',
                            'redirect' => $linkRedirect
                        );
                    }
                    
                } else {
                    // print_r($response);
                    wc_add_notice(  'Please try again. the payment method you choose is currently under maintenance','error' );
                    return;
                }
        
            } else {
                wc_add_notice(  'Please try again. Pastikan Payment method sudah dipilih', 'error' );
                return;
            }             
        }

        public function callback_handler() {
            header( 'Content-Type: application/json' );

            if ($_SERVER['REQUEST_METHOD'] === "POST") {
                header( 'HTTP/1.1 200 OK' );
                
                $getData = json_decode(file_get_contents('php://input'));
        
                $noTransaksi = $getData->transactionId;
                $status = $getData->status;
                
                $order = wc_get_order( $noTransaksi );
        
                if ($status === "SUCCESS" || $status === "settlement" || $status === "PAID" || $status === "SUCCEEDED") {
                    $order->payment_complete();
                    $order->reduce_order_stock();
        
                    if ($this->virtual_product == "yes") {
                        $order->update_status('wc-completed');
                    } else {
                        $order->update_status('processing');
                    }
                    
                    if ( $order->has_status('completed') ){
                        wc_print_notice( __("Your payment has been successful", "woocommerce"), "success" );
                    }

                    $order->add_order_note( 'Hey, Pembayaran berhasil. Pesanan akan diproses oleh Admin', true );

                    if ($this->wa_enable == 'yes') {
                        $this->WaMethod->sendMessageWa($order->get_billing_phone(),"Pembayaran Anda telah berhasil pada nomor transaksi id : '.$noTransaksi.'",$this->whatsapp_type,$this->whatsapp_token);
                    }
                    
                } else if ($status === "PENDING" || $status === "pending" || $status === "ACTIVE") {
                    $order->update_status('on-hold', 'Silakan lakukan pembayaran');
                    $order->add_order_note( 'Hey, Silahkan lakukan pembayaran', true );
                } else if ($status === "FAILED" || $status === "deny") {
                    $order->update_status('failed', 'Order dibatalkan oleh pembeli');
                    $order->add_order_note( 'Order telah dibatalkan, karena pembeli tidak melakukan pembayaran dalam waktu yang telah ditentukan', true );
                    if ($this->wa_enable == 'yes') {
                        $this->WaMethod->sendMessageWa($order->get_billing_phone(),"Pembayaran Anda telah gagal pada nomor transaksi id : '.$noTransaksi.'",$this->whatsapp_type,$this->whatsapp_token);
                    }
                    
                } else if ($status === "EXPIRED" || $status === "expire") {
                    $order->update_status('cancelled', 'Order Cancel');
                    $order->add_order_note( 'Order cancel atau Expired', true );

                    if ($this->wa_enable == 'yes') {
                        $this->WaMethod->sendMessageWa($order->get_billing_phone(),"Pembayaran Anda telah Expired pada nomor transaksi id : '.$noTransaksi.'",$this->whatsapp_type,$this->whatsapp_token);
                    }
                } else if ($status === "VOIDED") {
                    $order->update_status('cancelled', 'Terdapat kesalahan saat order produk');
                    $order->add_order_note( 'Order is Void', true );

                    if ($this->wa_enable == 'yes') {
                        $this->WaMethod->sendMessageWa($order->get_billing_phone(),"Pembayaran Anda telah gagal pada nomor transaksi id : '.$noTransaksi.'",$this->whatsapp_type,$this->whatsapp_token);
                    }
                }

                echo json_encode(["Message"=>"Success"]);	
            } else {
                header( 'HTTP/1.1 400 BAD REQUEST' );

                echo json_encode(["Message"=>"Failed. Method must POST"]);	
            }

            die();
        }
 	}
}