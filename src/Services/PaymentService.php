<?php
/**
 * This module is used for real time processing of
 * Novalnet payment module of customers.
 * Released under the GNU General Public License.
 * This free contribution made by request.
 * If you have found this script useful a small
 * recommendation as well as a comment on merchant form
 * would be greatly appreciated.
 *
 * @author       Novalnet AG
 * @copyright(C) Novalnet AG
 * All rights reserved. https://www.novalnet.de/payment-plugins/kostenpflichtig/lizenz
 */

namespace Novalnet\Services;

use Plenty\Modules\Basket\Models\Basket;
use Plenty\Plugin\ConfigRepository;
use Plenty\Modules\Frontend\Session\Storage\Contracts\FrontendSessionStorageFactoryContract;
use Plenty\Modules\Account\Address\Contracts\AddressRepositoryContract;
use Plenty\Modules\Order\Shipping\Countries\Contracts\CountryRepositoryContract;
use Plenty\Modules\Basket\Contracts\BasketRepositoryContract;
use Plenty\Modules\Helper\Services\WebstoreHelper;
use Novalnet\Helper\PaymentHelper;
use Plenty\Plugin\Log\Loggable;
use Plenty\Modules\Frontend\Services\AccountService;
use Novalnet\Constants\NovalnetConstants;
use Novalnet\Services\TransactionService;

/**
 * Class PaymentService
 *
 * @package Novalnet\Services
 */
class PaymentService
{

    use Loggable;

    /**
     * @var ConfigRepository
     */
    private $config;
   
    /**
     * @var FrontendSessionStorageFactoryContract
     */
    private $sessionStorage;

    /**
     * @var AddressRepositoryContract
     */
    private $addressRepository;

    /**
     * @var CountryRepositoryContract
     */
    private $countryRepository;

    /**
     * @var WebstoreHelper
     */
    private $webstoreHelper;

    /**
     * @var PaymentHelper
     */
    private $paymentHelper;
	
	/**
	 * @var basket
	 */
	private $basketRepository;
    
	
    /**
     * @var TransactionLogData
     */
    private $transactionLogData;
    
    //private $redirectPayment = ['NOVALNET_SOFORT', 'NOVALNET_PAYPAL', 'NOVALNET_IDEAL', 'NOVALNET_EPS', 'NOVALNET_GIROPAY', 'NOVALNET_PRZELEWY'];

    /**
     * Constructor.
     *
     * @param ConfigRepository $config
     * @param FrontendSessionStorageFactoryContract $sessionStorage
     * @param AddressRepositoryContract $addressRepository
     * @param CountryRepositoryContract $countryRepository
     * @param WebstoreHelper $webstoreHelper
     * @param PaymentHelper $paymentHelper
     * @param TransactionService $transactionLogData
     */
    public function __construct(ConfigRepository $config,
                                FrontendSessionStorageFactoryContract $sessionStorage,
                                AddressRepositoryContract $addressRepository,
                                CountryRepositoryContract $countryRepository,
								BasketRepositoryContract $basketRepository,
                                WebstoreHelper $webstoreHelper,
                                PaymentHelper $paymentHelper,
                                TransactionService $transactionLogData)
    {
        $this->config                   = $config;
        $this->sessionStorage           = $sessionStorage;
        $this->addressRepository        = $addressRepository;
        $this->countryRepository        = $countryRepository;
		$this->basketRepository  		= $basketRepository;
        $this->webstoreHelper           = $webstoreHelper;
        $this->paymentHelper            = $paymentHelper;
        $this->transactionLogData       = $transactionLogData;
    }
    
    /**
     * Push notification
     *
     */
    public function pushNotification($message, $type, $code = 0) {
		$notifications = json_decode($this->sessionStorage->getPlugin()->getValue('notifications'), true);	
		
		$notification = [
            'message'       => ($type == 'success') ? '&check; '.$message : '&cross; '.$message,
            'code'          => $code,
            'stackTrace'    => []
           ];
        
		$lastNotification = $notifications[$type];

        if( !is_null($lastNotification) )
		{
			$notification['stackTrace'] = $lastNotification['stackTrace'];
			$lastNotification['stackTrace'] = [];
			array_push( $notification['stackTrace'], $lastNotification );
		}
        
        $notifications[$type] = $notification;

		$this->sessionStorage->getPlugin()->setValue('notifications', json_encode($notifications));
	}
    /**
     * Validate  the response data.
     *
     */
    public function validateResponse()
    {
        $nnPaymentData = $this->sessionStorage->getPlugin()->getValue('nnPaymentData');

        $this->sessionStorage->getPlugin()->setValue('nnPaymentData', null);
        
        $nnPaymentData['order_no']       = $this->sessionStorage->getPlugin()->getValue('nnOrderNo');
        $nnPaymentData['mop']            = $this->sessionStorage->getPlugin()->getValue('mop');
        $nnPaymentData['payment_method'] = strtolower($this->paymentHelper->getPaymentKeyByMop($nnPaymentData['mop']));
        $this->executePayment($nnPaymentData);

        $transactionData = [
            'amount'           => $nnPaymentData['amount'] * 100,
            'callback_amount'  => $nnPaymentData['amount'] * 100,
            'tid'              => $nnPaymentData['tid'],
            'ref_tid'          => $nnPaymentData['tid'],
            'payment_name'     => $nnPaymentData['payment_method'],
            'order_no'         => $nnPaymentData['order_no'],
        ];
	    
	    
		if(in_array($nnPaymentData['tid_status'], ['85','90']))
            $transactionData['callback_amount'] = 0;	
	    
	    
        $this->transactionLogData->saveTransaction($transactionData);
        
        if(strtoupper($nnPaymentData['payment_method']) == 'NOVALNET_SEPA') {
            $this->sendPostbackCall($nnPaymentData);
        }
     }
     
	/**
     * Creates the payment for the order generated in plentymarkets.
     *
     * @param array $requestData 
     * @param bool $callbackfailure
     * 
     * @return array
     */
    public function executePayment($requestData, $callbackfailure = false)
    {
        try {
            if(!$callbackfailure &&  in_array($requestData['status'], ['100', '90'])) {
		    $this->getLogger(__METHOD__)->error('call1', 'enter');
				if($requestData['tid_status'] == '90') {
					$this->getLogger(__METHOD__)->error('call2', 'enter');
					$requestData['order_status'] = trim($this->config->get('Novalnet.novalnet_paypal_payment_pending_status'));
					$requestData['paid_amount'] = 0;
				} elseif($requestData['tid_status'] == '75') {
					$this->getLogger(__METHOD__)->error('call3', 'enter');
					$requestData['order_status'] = trim($this->config->get('Novalnet.novalnet_sepa_payment_guarantee_status'));
					$requestData['paid_amount'] = 0;
				} elseif(in_array($requestData['tid_status'], ['85', '99'])) {
					$this->getLogger(__METHOD__)->error('call4', 'enter');
					$requestData['order_status'] = trim($this->config->get('Novalnet.novalnet_onhold_confirmation_status'));
					$requestData['paid_amount'] = 0;
				} 
				else {
					$this->getLogger(__METHOD__)->error('call5', 'enter');
					$requestData['order_status'] = trim($this->config->get('Novalnet.'. $requestData['payment_method'] .'_order_completion_status'));
					$requestData['paid_amount'] = ($requestData['tid_status'] == '100') ? $requestData['amount'] : '0';
				}
            } else {
		    $this->getLogger(__METHOD__)->error('call6', 'enter');
                $requestData['order_status'] = trim($this->config->get('Novalnet.novalnet_order_cancel_status'));
                $requestData['paid_amount'] = '0';
            }
            $transactionComments = $this->getTransactionComments($requestData);
            $this->paymentHelper->createPlentyPayment($requestData);
            $this->paymentHelper->updateOrderStatus((int)$requestData['order_no'], $requestData['order_status']);
            $this->paymentHelper->createOrderComments((int)$requestData['order_no'], $transactionComments);
            return [
                'type' => 'success',
                'value' => $this->paymentHelper->getNovalnetStatusText($requestData)
            ];
        } catch (\Exception $e) {
            $this->getLogger(__METHOD__)->error('ExecutePayment failed.', $e);
            return [
                'type'  => 'error',
                'value' => $e->getMessage()
            ];
        }
    }

    /**
     * Build transaction comments for the order
     *
     * @param array $requestData
     * @return string
     */
    public function getTransactionComments($requestData)
    {
        $lang = strtolower((string)$requestData['lang']);
		$comments = '';
        $comments .= PHP_EOL . $this->paymentHelper->getTranslatedText('nn_tid', $lang) . $requestData['tid'];
	    
        if(!empty($requestData['test_mode'])) {
            $comments .= PHP_EOL . $this->paymentHelper->getTranslatedText('test_order', $lang);
        }
        
        if($requestData['status'] != '100')
		{
			$responseText = $this->paymentHelper->getNovalnetStatusText($requestData);
			$comments .= PHP_EOL . $this->paymentHelper->getTranslatedText('transaction_cancellation', $lang) . $responseText . PHP_EOL;    
		} else {
			if($requestData['payment_id'] == '40') {
				$comments .= PHP_EOL . $this->paymentHelper->getTranslatedText('guarantee_text');
				if( $requestData['tid_status'] == '75' && $requestData['payment_id'] == '40')
				{
					$comments .= PHP_EOL . $this->paymentHelper->getTranslatedText('gurantee_sepa_pending_payment_text');
				}
			}

		}

        return $comments;
    }

    /**
     * Build Novalnet server request parameters
     *
     * @param Basket $basket
     * @param PaymentKey $paymentKey
     *
     * @return array
     */
    public function getRequestParameters(Basket $basket, $paymentKey = '')
    {
        $billingAddressId = $basket->customerInvoiceAddressId;
        $address = $this->addressRepository->findAddressById($billingAddressId);
        if(!empty($basket->customerShippingAddressId)){
            $shippingAddress = $this->addressRepository->findAddressById($basket->customerShippingAddressId);
        }
	
		foreach ($address->options as $option) {
		if ($option->typeId == 12) {
	            $name = $option->value;
		}
	}
	$customerName = explode(' ', $name);
	$firstname = $customerName[0];
	if( count( $customerName ) > 1 ) {
	    unset($customerName[0]);
	    $lastname = implode(' ', $customerName);
	} else {
	    $lastname = $firstname;
	}
	$firstName = empty ($firstname) ? $lastname : $firstname;
	$lastName = empty ($lastname) ? $firstname : $lastname;
	
        $account = pluginApp(AccountService::class);
        $customerId = $account->getAccountContactId();
        $paymentKeyLower = strtolower((string) $paymentKey);
        $testModeKey = 'Novalnet.' . $paymentKeyLower . '_test_mode';

        $paymentRequestData = [
            'vendor'             => $this->paymentHelper->getNovalnetConfig('novalnet_vendor_id'),
            'auth_code'          => $this->paymentHelper->getNovalnetConfig('novalnet_auth_code'),
            'product'            => $this->paymentHelper->getNovalnetConfig('novalnet_product_id'),
            'tariff'             => $this->paymentHelper->getNovalnetConfig('novalnet_tariff_id'),
            'test_mode'          => (int)($this->config->get($testModeKey) == 'true'),
            'first_name'         => !empty($address->firstName) ? $address->firstName : $firstName,
            'last_name'          => !empty($address->lastName) ? $address->lastName : $lastName,
            'email'              => $address->email,
            'gender'             => 'u',
            'city'               => $address->town,
            'street'             => $address->street,
            'country_code'       => $this->countryRepository->findIsoCode($address->countryId, 'iso_code_2'),
            'zip'                => $address->postalCode,
            'customer_no'        => ($customerId) ? $customerId : 'guest',
            'lang'               => strtoupper($this->sessionStorage->getLocaleSettings()->language),
            'amount'             => $this->paymentHelper->ConvertAmountToSmallerUnit($basket->basketAmount),
            'currency'           => $basket->currency,
            'remote_ip'          => $this->paymentHelper->getRemoteAddress(),
            'system_ip'          => $this->paymentHelper->getServerAddress(),
            'system_url'         => $this->webstoreHelper->getCurrentWebstoreConfiguration()->domainSsl,
            'system_name'        => 'Plentymarkets',
            'system_version'     => NovalnetConstants::PLUGIN_VERSION,
            'notify_url'         => $this->webstoreHelper->getCurrentWebstoreConfiguration()->domainSsl . '/payment/novalnet/callback/',
            'key'                => $this->getkeyByPaymentKey($paymentKey),
            'payment_type'       => $this->getTypeByPaymentKey($paymentKey)
        ];

        if(!empty($address->houseNumber))
        {
            $paymentRequestData['house_no'] = $address->houseNumber;
        }
        else
        {
            $paymentRequestData['search_in_street'] = '1';
        }

        if(!empty($address->companyName)) {
            $paymentRequestData['company'] = $address->companyName;
        } elseif(!empty($shippingAddress->companyName)) {
            $paymentRequestData['company'] = $shippingAddress->companyName;
        }

        if(!empty($address->phone)) {
            $paymentRequestData['tel'] = $address->phone;
        }

        if(is_numeric($referrerId = $this->paymentHelper->getNovalnetConfig('referrer_id'))) {
            $paymentRequestData['referrer_id'] = $referrerId;
        }
        $url = $this->getPaymentData($paymentKey, $paymentRequestData);
        return [
            'data' => $paymentRequestData,
            'url'  => $url
        ];
    }

    /**
     * Get payment related param
     *
     * @param array $paymentRequestData
     * @param string $paymentKey
     */
    public function getPaymentData($paymentKey, &$paymentRequestData )
    {
	    $url = $this->getpaymentUrl($paymentKey);
		$onHoldLimit = $this->paymentHelper->getNovalnetConfig(strtolower($paymentKey) . '_on_hold');
		$onHoldAuthorize = $this->paymentHelper->getNovalnetConfig(strtolower($paymentKey) . '_payment_action');
		if((is_numeric($onHoldLimit) && $paymentRequestData['amount'] >= $onHoldLimit && $onHoldAuthorize == 'true') || ($onHoldAuthorize == 'true' && empty($onHoldLimit))) {
		    $paymentRequestData['on_hold'] = '1';
		}
		
		if($paymentKey == 'NOVALNET_SEPA') {
			$dueDate = $this->paymentHelper->getNovalnetConfig('novalnet_sepa_due_date');
			if(is_numeric($dueDate) && $dueDate >= 2 && $dueDate <= 14) {
				$paymentRequestData['sepa_due_date'] = $this->paymentHelper->dateFormatter($dueDate);
			}
		} 
		
	    if($paymentKey == 'NOVALNET_PAYPAL')
	    {
			$paymentRequestData['uniqid'] = $this->paymentHelper->getUniqueId();
			$this->encodePaymentData($paymentRequestData);
			$paymentRequestData['implementation'] = 'ENC';
			$paymentRequestData['return_url'] = $paymentRequestData['error_return_url'] = $this->getReturnPageUrl();
			$paymentRequestData['return_method'] = $paymentRequestData['error_return_method'] = 'POST';
			$paymentRequestData['user_variable_0'] = $this->webstoreHelper->getCurrentWebstoreConfiguration()->domainSsl;
	     }
        
        return $url;
    }

    /**
     * Send postback call to server for updating the order number for the transaction
     *
     * @param array $requestData
     */
    public function sendPostbackCall($requestData)
    {
        $postbackData = [
            'vendor'         => $requestData['vendor'],
            'product'        => $requestData['product'],
            'tariff'         => $requestData['tariff'],
            'auth_code'      => $requestData['auth_code'],
            'key'            => $requestData['payment_id'],
            'status'         => 100,
            'tid'            => $requestData['tid'],
            'order_no'       => $requestData['order_no'],
            'remote_ip'      => $this->paymentHelper->getRemoteAddress()
        ];

		$this->paymentHelper->executeCurl($postbackData, NovalnetConstants::PAYPORT_URL);
    }

    /**
     * Encode the server request parameters
     *
     * @param array
     */
    public function encodePaymentData(&$paymentRequestData)
    {
        foreach (['auth_code', 'product', 'tariff', 'amount', 'test_mode'] as $key) {
            // Encoding payment data
            $paymentRequestData[$key] = $this->paymentHelper->encodeData($paymentRequestData[$key], $paymentRequestData['uniqid']);
        }

        // Generate hash value
        $paymentRequestData['hash'] = $this->paymentHelper->generateHash($paymentRequestData);
    }

    /**
     * Get the payment response controller URL to be handled
     *
     * @return string
     */
    private function getReturnPageUrl()
    {
        return $this->webstoreHelper->getCurrentWebstoreConfiguration()->domainSsl . '/payment/novalnet/paymentResponse/';
    }

    /**
    * Get the direct payment process controller URL to be handled
    *
    * @return string
    */
    public function getProcessPaymentUrl()
    {
        return $this->webstoreHelper->getCurrentWebstoreConfiguration()->domainSsl . '/payment/novalnet/processPayment/';
    }

    /**
    * Get the redirect payment process controller URL to be handled
    *
    * @return string
    */
    public function getRedirectPaymentUrl()
    {
        return $this->webstoreHelper->getCurrentWebstoreConfiguration()->domainSsl . '/payment/novalnet/redirectPayment/';
    }

    /**
    * Get the payment process URL by using plenty payment key
    *
    * @param string $paymentKey
    * @return string
    */
    public function getpaymentUrl($paymentKey)
    {
        $payment = [
            'NOVALNET_SEPA'=>NovalnetConstants::PAYPORT_URL,
            'NOVALNET_PAYPAL'=>NovalnetConstants::PAYPAL_PAYMENT_URL,
        ];

        return $payment[$paymentKey];
    }

   /**
    * Get payment key by plenty payment key
    *
    * @param string $paymentKey
    * @return string
    */
    public function getkeyByPaymentKey($paymentKey)
    {
        $payment = [
            'NOVALNET_SEPA'=>'37',
            'NOVALNET_PAYPAL'=>'34',
        ];

        return $payment[$paymentKey];
    }

    /**
    * Get payment type by plenty payment Key
    *
    * @param string $paymentKey
    * @return string
    */
    public function getTypeByPaymentKey($paymentKey)
    {
        $payment = [
            'NOVALNET_SEPA'=>'DIRECT_DEBIT_SEPA',
            'NOVALNET_PAYPAL'=>'PAYPAL',
        ];

        return $payment[$paymentKey];
    }

    /**
    * Get the Payment Guarantee status
    *
    * @param object $basket
    * @param string $paymentKey
    * @return string
    */
    public function getGuaranteeStatus(Basket $basket)
    {
        
        $guaranteePayment = $this->config->get('Novalnet.novalnet_sepa_payment_guarantee_active');
        if ($guaranteePayment == 'true') {
            // Get guarantee minimum amount value
            $minimumAmount = $this->paymentHelper->getNovalnetConfig('novalnet_sepa_guarantee_min_amount');
            $minimumAmount = ((preg_match('/^[0-9]*$/', $minimumAmount) && $minimumAmount >= '999')  ? $minimumAmount : '999');
            $amount        = (sprintf('%0.2f', $basket->basketAmount) * 100);
            $this->getLogger(__METHOD__)->error('minamount', $minimumAmount);
            $billingAddressId = $basket->customerInvoiceAddressId;
            $billingAddress = $this->addressRepository->findAddressById($billingAddressId);
            $customerBillingIsoCode = strtoupper($this->countryRepository->findIsoCode($billingAddress->countryId, 'iso_code_2'));

            $shippingAddressId = $basket->customerShippingAddressId;

            $addressValidation = false;
            if(!empty($shippingAddressId))
            {
                $shippingAddress = $this->addressRepository->findAddressById($shippingAddressId);
                $customerShippingIsoCode = strtoupper($this->countryRepository->findIsoCode($shippingAddress->countryId, 'iso_code_2'));

                // Billing address
                $billingAddress = ['street_address' => (($billingAddress->street) ? $billingAddress->street : $billingAddress->address1),
                                   'city'           => $billingAddress->town,
                                   'postcode'       => $billingAddress->postalCode,
                                   'country'        => $customerBillingIsoCode,
                                  ];
                // Shipping address
                $shippingAddress = ['street_address' => (($shippingAddress->street) ? $shippingAddress->street : $shippingAddress->address1),
                                    'city'           => $shippingAddress->town,
                                    'postcode'       => $shippingAddress->postalCode,
                                    'country'        => $customerShippingIsoCode,
                                   ];

             }
             else
             {
                 $addressValidation = true;
             }
            // Check guarantee payment
            if ((((int) $amount >= (int) $minimumAmount && in_array(
                $customerBillingIsoCode,
                [
                 'DE',
                 'AT',
                 'CH',
                ]
            ) && $basket->currency == 'EUR' && ($addressValidation || ($billingAddress === $shippingAddress)))
            )) {
                $processingType = 'guarantee';
            } elseif ($this->config->get('Novalnet.novalnet_sepa_payment_guarantee_force_active') == 'true') {   
                $processingType = 'normal';
            } else {
                if ( ! in_array( $customerBillingIsoCode, array( 'AT', 'DE', 'CH' ), true ) ) {
					$processingType = $this->paymentHelper->getTranslatedText('guarantee_country_error');					
				} elseif ( $basket->currency !== 'EUR' ) {
					$processingType = $this->paymentHelper->getTranslatedText('guarantee_currency_error');					
				} elseif ( ! empty( array_diff( $billingAddress, $shippingAddress ) ) ) {
					$processingType = $this->paymentHelper->getTranslatedText('guarantee_address_error');					
				} elseif ( (int) $amount < (int) $minimumAmount ) {
					$processingType = $this->paymentHelper->getTranslatedText('guarantee_minimum_amount_error'). ' ' . $minimumAmount/100 . ' ' . 'EUR)';					
				}
            }
            return $processingType;
        }//end if
        return 'normal';
    }
	
	/**
	 * Execute capture and void process
	 *
	 * @param object $order
	 * @param object $paymentDetails
	 * @param int $tid
	 * @param int $key
	 * @param bool $capture
	 * @return none
	 */
	public function doCaptureVoid($order, $paymentDetails, $tid, $key, $invoiceDetails, $capture=false) 
	{
	    $bankDetails = json_decode($invoiceDetails);
		
	    try {
		$paymentRequestData = [
		    'vendor'         => $this->paymentHelper->getNovalnetConfig('novalnet_vendor_id'),
		    'auth_code'      => $this->paymentHelper->getNovalnetConfig('novalnet_auth_code'),
		    'product'        => $this->paymentHelper->getNovalnetConfig('novalnet_product_id'),
		    'tariff'         => $this->paymentHelper->getNovalnetConfig('novalnet_tariff_id'),
		    'key'            => $key, 
		    'edit_status'    => '1', 
		    'tid'            => $tid, 
		    'remote_ip'      => $this->paymentHelper->getRemoteAddress(),
		    'lang'           => 'de'  
		     ];
		
	    if($capture) {
		$paymentRequestData['status'] = '100';
	    } else {
		$paymentRequestData['status'] = '103';
	    }
		
	     $response = $this->paymentHelper->executeCurl($paymentRequestData, NovalnetConstants::PAYPORT_URL);
	     $responseData =$this->paymentHelper->convertStringToArray($response['response'], '&');
	     if ($responseData['status'] == '100') {
	     	if($responseData['tid_status'] == '100') {
			$transactionComments = '';

			if (in_array($key, ['34', '37'])) {
			$paymentData['currency']    = $paymentDetails[0]->currency;
			$paymentData['paid_amount'] = (float) $order->amounts[0]->invoiceTotal;
			$paymentData['tid']         = $tid;
			$paymentData['order_no']    = $order->id;
			$paymentData['mop']         = $paymentDetails[0]->mopId;
	    
			$this->paymentHelper->createPlentyPayment($paymentData);
			}
		     
	               $transactionComments .= PHP_EOL . sprintf($this->paymentHelper->getTranslatedText('transaction_confirmation', $paymentRequestData['lang']), date('d.m.Y'), date('H:i:s'));
		  } else {
			$transactionComments .= PHP_EOL . sprintf($this->paymentHelper->getTranslatedText('transaction_cancel', $paymentRequestData['lang']), date('d.m.Y'), date('H:i:s'));
		  }
			$this->paymentHelper->createOrderComments((int)$order->id, $transactionComments);
			$this->paymentHelper->updatePayments($tid, $responseData['tid_status'], $order->id);
	     } else {
	           $error = $this->paymentHelper->getNovalnetStatusText($responseData);
		   $this->getLogger(__METHOD__)->error('Novalnet::doCaptureVoid', $error);
	     }	
	} catch (\Exception $e) {
			$this->getLogger(__METHOD__)->error('Novalnet::doCaptureVoid', $e);
	  }
	}
	
	/**
	 * Show payment for allowed countries
	 *
	 * @param string $allowed_country
	 *
	 * @return bool
	 */
	public function allowedCountries($allowed_country) {
		$allowed_country = str_replace(' ', '', $allowed_country);
		$allowed_country_array = explode(',', $allowed_country);	
		$basket = $this->basketRepository->load();	
		$billingAddressId = $basket->customerInvoiceAddressId;
		$address = $this->addressRepository->findAddressById($billingAddressId);
		$country = $this->countryRepository->findIsoCode($address->countryId, 'iso_code_2');
		if (in_array ($country, $allowed_country_array)) {
			return true;
		}  
			return false;
	}
	
	
}
