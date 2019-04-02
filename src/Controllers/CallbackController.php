<?php
/**
 * This module is used for real time processing of
 * Novalnet payment module of customers.
 * Released under the GNU General Public License.
 * This free contribution made by request.
 * 
 * If you have found this script useful a small
 * recommendation as well as a comment on merchant form
 * would be greatly appreciated.
 *
 * @author       Novalnet AG
 * @copyright(C) Novalnet AG. 
 * All rights reserved. https://www.novalnet.de/payment-plugins/kostenpflichtig/lizenz
 */

namespace Novalnet\Controllers;

use Plenty\Plugin\ConfigRepository;
use Plenty\Plugin\Controller;
use Plenty\Plugin\Http\Request;
use Novalnet\Helper\PaymentHelper;
use Novalnet\Services\PaymentService;
use Plenty\Plugin\Templates\Twig;
use Novalnet\Services\TransactionService;
use Plenty\Plugin\Log\Loggable;
use Plenty\Plugin\Mail\Contracts\MailerContract;
use Plenty\Modules\Order\Contracts\OrderRepositoryContract;
use Plenty\Modules\Account\Address\Contracts\AddressRepositoryContract;
use \Plenty\Modules\Authorization\Services\AuthHelper;
use Plenty\Modules\Payment\Contracts\PaymentRepositoryContract;
use Plenty\Modules\Payment\Models\Payment;
use Plenty\Modules\Payment\Models\PaymentProperty;
use \stdClass;

/**
 * Class CallbackController
 *
 * @package Novalnet\Controllers
 */
class CallbackController extends Controller
{
	use Loggable;

	/**
	 * @var config
	 */
	private $config;

	/**
	 * @var PaymentHelper
	 */
	private $paymentHelper;

	/**
	 * @var twig
	 */
	private $twig;

	/**
	 * @var transaction
	 */
	private $transaction;

	 /**
	 * @var paymentService
	 */
	private $paymentService;
	
	/**
	 * @var paymentRepository
	 */
	private $paymentRepository;
	
	/**
	 * @var AddressRepositoryContract
	 */
	private $addressRepository;
	
	/**
	 * @var orderRepository
	 */
	private $orderRepository;

	/*
	 * @var aryPayments
	 * @Array Type of payment available - Level : 0
	 */
	protected $aryPayments = ['DIRECT_DEBIT_SEPA', 'GUARANTEED_DIRECT_DEBIT_SEPA', 'PAYPAL'];

	/**
	 * @var aryChargebacks
	 * @Array Type of Chargebacks available - Level : 1
	 */
	protected $aryChargebacks = ['REVERSAL', 'RETURN_DEBIT_SEPA', 'PAYPAL_BOOKBACK', 'REFUND_BY_BANK_TRANSFER_EU', 'GUARANTEED_SEPA_BOOKBACK'];

	/**
	 * @var aryCollection
	 * @Array Type of CreditEntry payment and Collections available - Level : 2
	 */
	protected $aryCollection = ['CREDIT_ENTRY_SEPA', 'CREDIT_ENTRY_DE', 'DEBT_COLLECTION_SEPA', 'DEBT_COLLECTION_DE'];

	/**
	 * @var aryPaymentGroups
	 */
	protected $aryPaymentGroups = [
			'novalnet_sepa'  => [
							'DIRECT_DEBIT_SEPA',
							'RETURN_DEBIT_SEPA',
							'CREDIT_ENTRY_SEPA',
							'DEBT_COLLECTION_SEPA',
							'GUARANTEED_DIRECT_DEBIT_SEPA',
							'GUARANTEED_SEPA_BOOKBACK',
							'REFUND_BY_BANK_TRANSFER_EU',
							'TRANSACTION_CANCELLATION',
							'REVERSAL',
							'SUBSCRIPTION_STOP',
						],
			'novalnet_paypal'=> [
							'PAYPAL',
							'SUBSCRIPTION_STOP',
							'PAYPAL_BOOKBACK',
							'REFUND_BY_BANK_TRANSFER_EU'
						],
			
			];

	/**
	 * @var aryCaptureParams
	 * @Array Callback Capture parameters
	 */
	protected $aryCaptureParams = [];

	/**
	 * @var ipAllowed
	 * @IP-ADDRESS Novalnet IP, is a fixed value, DO NOT CHANGE!!!!!
	 */
	protected $ipAllowed = ['195.143.189.210', '195.143.189.214'];

	/**
	 * CallbackController constructor.
	 *
	 * @param Request $request
	 * @param ConfigRepository $config
	 * @param PaymentHelper $paymentHelper
	 * @param PaymentRepositoryContract $paymentRepository
	 * @param PaymentService $paymentService
	 * @param Twig $twig
	 * @param AddressRepositoryContract $addressRepository
	 * @param TransactionService $tranactionService
	 * @param OrderRepositoryContract $orderRepository
	 */
	public function __construct(  Request $request,
								  ConfigRepository $config,
								  PaymentHelper $paymentHelper,
								  PaymentRepositoryContract $paymentRepository,
								  PaymentService $paymentService,
								  Twig $twig,
								  AddressRepositoryContract $addressRepository,
								  TransactionService $tranactionService,
								  OrderRepositoryContract $orderRepository
								)
	{
		$this->config               = $config;
		$this->paymentHelper        = $paymentHelper;
		$this->paymentRepository    = $paymentRepository;
		$this->paymentService       = $paymentService;
		$this->addressRepository    = $addressRepository;
		$this->twig                 = $twig;
		$this->transaction          = $tranactionService;
		$this->orderRepository      = $orderRepository;
		$this->aryCaptureParams     = $request->all();
	}

	/**
	 * Execute callback process for the payment levels
	 *
	 */
	public function processCallback()
	{
		$displayTemplate = $this->validateIpAddress();

		if ($displayTemplate)
		{
			return $this->renderTemplate($displayTemplate);
		}

		$displayTemplate = $this->validateCaptureParams($this->aryCaptureParams);

		if ($displayTemplate)
		{
			return $this->renderTemplate($displayTemplate);
		}

		$this->aryCaptureParams['shop_tid'] = $this->aryCaptureParams['tid'];

		if(in_array($this->aryCaptureParams['payment_type'], array_merge($this->aryChargebacks, $this->aryCollection)))
		{
			$this->aryCaptureParams['shop_tid'] = $this->aryCaptureParams['tid_payment'];
		}

		if(!empty($this->aryCaptureParams['signup_tid']) && isset($this->aryCaptureParams['subs_billing']) && $this->aryCaptureParams['subs_billing'] == 1)
		{   // Subscription
			$this->aryCaptureParams['shop_tid'] = $this->aryCaptureParams['signup_tid'];
		}

		if(empty($this->aryCaptureParams['vendor_activation']))
		{
			$nnTransactionHistory = $this->getOrderDetails();

			if(is_string($nnTransactionHistory))
			{
				return $this->renderTemplate($nnTransactionHistory);
			}

		$orderob = $this->orderObject($nnTransactionHistory->orderNo);

		$orderLanguage= $this->orderLanguage($orderob);
		
			if ($this->aryCaptureParams['payment_type'] == 'TRANSACTION_CANCELLATION')
			{
				$callbackComments  = '</br>';
				$callbackComments .= '</br>' . sprintf($this->paymentHelper->getTranslatedText('callback_transaction_cancellation',$orderLanguage),date('d.m.Y'), date('H:i:s'));
				$this->paymentHelper->updateOrderStatus($nnTransactionHistory->orderNo, (float) $this->config->get('Novalnet.novalnet_order_cancel_status'));
				$this->paymentHelper->createOrderComments($nnTransactionHistory->orderNo, $callbackComments);
                $this->paymentHelper->updatePayments($this->aryCaptureParams['tid'], $this->aryCaptureParams['tid_status'], $nnTransactionHistory->orderNo);
				return $this->renderTemplate($callbackComments);
			}
			if($this->getPaymentTypeLevel() == 2 && $this->aryCaptureParams['tid_status'] == '100')
			{
				$callbackComments  = '</br>';
				$callbackComments .= sprintf($this->paymentHelper->getTranslatedText('callback_initial_execution',$orderLanguage), $this->aryCaptureParams['shop_tid'], ($this->aryCaptureParams['amount'] / 100), $this->aryCaptureParams['currency'], date('Y-m-d H:i:s'), $this->aryCaptureParams['tid'] ).'</br>';
				$this->paymentHelper->createOrderComments($nnTransactionHistory->orderNo, $callbackComments);		
				$this->sendCallbackMail($callbackComments);
				return $this->renderTemplate($callbackComments);

			}
			else if($this->getPaymentTypeLevel() == 1 && $this->aryCaptureParams['tid_status'] == 100)
			{
				$callbackComments = '</br>';
				$callbackComments .= (in_array($this->aryCaptureParams['payment_type'], ['PAYPAL_BOOKBACK', 'REFUND_BY_BANK_TRANSFER_EU', 'GUARANTEED_SEPA_BOOKBACK'])) ? sprintf($this->paymentHelper->getTranslatedText('callback_bookback_execution',$orderLanguage), $nnTransactionHistory->tid, sprintf('%0.2f', ($this->aryCaptureParams['amount']/100)) , $this->aryCaptureParams['currency'], date('Y-m-d H:i:s'), $this->aryCaptureParams['tid'] ) . '</br>' : sprintf( $this->paymentHelper->getTranslatedText('callback_chargeback_execution',$orderLanguage), $nnTransactionHistory->tid, sprintf( '%0.2f',( $this->aryCaptureParams['amount']/100) ), $this->aryCaptureParams['currency'], date('Y-m-d H:i:s'), $this->aryCaptureParams['tid'] ) . '</br>';

				$this->saveTransactionLog($nnTransactionHistory);

				$paymentData['currency']    = $this->aryCaptureParams['currency'];
				$paymentData['paid_amount'] = (float) ($this->aryCaptureParams['amount']/100);
				$paymentData['tid']         = $this->aryCaptureParams['tid'];
				$paymentData['type']        = 'debit';
				$paymentData['order_no']    = $nnTransactionHistory->orderNo;
				$paymentData['mop']         = $nnTransactionHistory->mopId;

				$this->paymentHelper->createPlentyPayment($paymentData);
				$this->paymentHelper->createOrderComments($nnTransactionHistory->orderNo, $callbackComments);
				$this->sendCallbackMail($callbackComments);
				return $this->renderTemplate($callbackComments);
			}
			elseif($this->getPaymentTypeLevel() == 0 )
			{
				if($this->aryCaptureParams['payment_type'] == 'PAYPAL' && $this->aryCaptureParams['status'] == '100' && $this->aryCaptureParams['tid_status'] == '100')
				{
					if ($nnTransactionHistory->order_paid_amount < $nnTransactionHistory->order_total_amount)
					{
						$callbackComments  = '</br>';
						$callbackComments .= sprintf($this->paymentHelper->getTranslatedText('callback_initial_execution',$orderLanguage), $this->aryCaptureParams['shop_tid'], ($this->aryCaptureParams['amount']/100), $this->aryCaptureParams['currency'], date('Y-m-d H:i:s'), $this->aryCaptureParams['tid'] ).'</br>';

						$this->saveTransactionLog($nnTransactionHistory, true);

						$paymentData['currency']    = $this->aryCaptureParams['currency'];
						$paymentData['paid_amount'] = (float) ($this->aryCaptureParams['amount']/100);
						$paymentData['tid']         = $this->aryCaptureParams['tid'];
						$paymentData['order_no']    = $nnTransactionHistory->orderNo;
						$paymentData['mop']         = $nnTransactionHistory->mopId;
						
						$orderStatus = (float) $this->config->get('Novalnet.novalnet_paypal_order_completion_status');
						
						$this->paymentHelper->createPlentyPayment($paymentData);
						
						$this->paymentHelper->updateOrderStatus($nnTransactionHistory->orderNo, $orderStatus);
						$this->paymentHelper->updatePayments($this->aryCaptureParams['tid'], $this->aryCaptureParams['tid_status'], $nnTransactionHistory->orderNo);
						$this->paymentHelper->createOrderComments($nnTransactionHistory->orderNo, $callbackComments);
						$this->sendCallbackMail($callbackComments);

						return $this->renderTemplate($callbackComments);
					}
					else
					{
						return $this->renderTemplate('Novalnet Callbackscript received. Order already Paid');
					}
				}  elseif (in_array($this->aryCaptureParams['payment_type'], ['DIRECT_DEBIT_SEPA', 'GUARANTEED_DIRECT_DEBIT_SEPA'] )) {
				
					$transactionStatus = $this->payment_details($nnTransactionHistory->orderNo);
			               
					// Checks for Guarantee Onhold
					if($this->aryCaptureParams['tid_status'] == '99' && $transactionStatus == '75') {
					   
						$callbackComments = '</br>' . sprintf($this->paymentHelper->getTranslatedText('callback_pending_to_onhold_status_change',$orderLanguage), $this->aryCaptureParams['tid'], date('d.m.Y'), date('H:i:s'));
						
						$this->paymentHelper->createOrderComments($nnTransactionHistory->orderNo, $callbackComments);
						$orderStatus = $this->config->get('Novalnet.novalnet_onhold_confirmation_status'); 
						$this->paymentHelper->updateOrderStatus($nnTransactionHistory->orderNo, (float)$orderStatus);
			
					} elseif ($this->aryCaptureParams['tid_status'] == '100' && in_array($transactionStatus, [ '75', '99' ])) {
				
						$orderStatus = $this->config->get('Novalnet.novalnet_sepa_order_completion_status');	

							$callbackComments = '</br>' . sprintf($this->paymentHelper->getTranslatedText('callback_order_confirmation_text',$orderLanguage), $this->aryCaptureParams['tid'], date('d.m.Y'), date('H:i:s'));
							$this->paymentHelper->createOrderComments($nnTransactionHistory->orderNo, $callbackComments);
							$this->paymentHelper->updateOrderStatus($nnTransactionHistory->orderNo, (float)$orderStatus);

							$paymentData['currency']    = $this->aryCaptureParams['currency'];
							$paymentData['paid_amount'] = (float) ($this->aryCaptureParams['amount'] / 100);
							$paymentData['tid']         = $this->aryCaptureParams['tid'];
							$paymentData['order_no']    = $nnTransactionHistory->orderNo;
							$paymentData['mop']         = $nnTransactionHistory->mopId;
							$this->paymentHelper->createPlentyPayment($paymentData);
							$this->sendTransactionConfirmMail($callbackComments, $nnTransactionHistory->orderNo); 
					} 
					$this->paymentHelper->updatePayments($this->aryCaptureParams['tid'], $this->aryCaptureParams['tid_status'], $nnTransactionHistory->orderNo);
					return $this->renderTemplate($callbackComments);
				}  
				else
				{
					$error = 'Novalnet Callbackscript received. Payment type ( '.$this->aryCaptureParams['payment_type'].' ) is not applicable for this process!';
					return $this->renderTemplate($error);
				}
			}
			else
			{
				return $this->renderTemplate('Novalnet callback received. TID Status ('.$this->aryCaptureParams['tid_status'].') is not valid: Only 100 is allowed');
			}
		}
		return $this->renderTemplate('Novalnet callback received. Callback Script executed already.');
	}

	/**
	 * Validate the IP control check
	 *
	 * @return bool|string
	 */
	public function validateIpAddress()
	{
		$client_ip = $this->paymentHelper->getRemoteAddress();
		if(!in_array($client_ip, $this->ipAllowed) && $this->config->get('Novalnet.novalnet_callback_test_mode') != 'true')
		{
			return 'Novalnet callback received. Unauthorised access from the IP '. $client_ip;
		}
		return false;
	}

	/**
	 * Validate request param
	 *
	 * @param array $aryCaptureParams
	 * @return array|string
	 */
	public function validateCaptureParams($aryCaptureParams)
	{
		if(!isset($aryCaptureParams['vendor_activation']))
		{
			$paramsRequired       = ['vendor_id', 'tid', 'payment_type', 'status', 'tid_status'];
			if(isset($this->aryCaptureParams['payment_type']) && in_array($this->aryCaptureParams['payment_type'], array_merge($this->aryChargebacks, $this->aryCollection)))
			{
				$paramsRequired[] = 'tid_payment';
			}

			foreach ($paramsRequired as $param)
			{
				if (empty($aryCaptureParams[$param]))
				{
					return 'Required param ( ' . $param . '  ) missing!';
				}

				if (in_array($param, ['tid', 'tid_payment', 'signup_tid']) && !preg_match('/^\d{17}$/', $aryCaptureParams[$param]))
				{
					return 'Novalnet callback received. Invalid TID ['. $aryCaptureParams[$param] . '] for Order.';
				}
			}
		}
		return false;
	}

	/**
	 * Find and retrieves the shop order ID for the Novalnet transaction
	 *
	 * @return object|string
	 */
	public function getOrderDetails()
	{
		$order = $this->transaction->getTransactionData('tid', $this->aryCaptureParams['shop_tid']);

		$orderId= (!empty($this->aryCaptureParams['order_no'])) ? $this->aryCaptureParams['order_no'] : '';

		if(!empty($order))
		{
			$orderDetails = $order[0]; // Setting up the order details fetched
			$orderObj                     = pluginApp(stdClass::class);

			$orderObj->tid                = $this->aryCaptureParams['shop_tid'];
			$orderObj->order_total_amount = $orderDetails->amount;
			// Collect paid amount information from the novalnet_callback_history
			$orderObj->order_paid_amount  = 0;
			$orderObj->orderNo            = $orderDetails->orderNo;
			$orderObj->paymentName        = $orderDetails->paymentName;

			$mop = $this->paymentHelper->getPaymentMethodByKey(strtolower($orderDetails->paymentName));
			$orderObj->mopId              = $mop;

			$paymentTypeLevel = $this->getPaymentTypeLevel();

			if ($paymentTypeLevel != 1)
			{
				$orderAmountTotal = $this->transaction->getTransactionData('orderNo', $orderDetails->orderNo);
				if(!empty($orderAmountTotal))
				{
					$amount = 0;
					foreach($orderAmountTotal as $data)
					{
						$amount += $data->callbackAmount;
					}
					$orderObj->order_paid_amount = $amount;
				}
			}

			if (!isset($orderDetails->paymentName) || !in_array($this->aryCaptureParams['payment_type'], $this->aryPaymentGroups[$orderDetails->paymentName]))
			{
				return 'Novalnet callback received. Payment Type [' . $this->aryCaptureParams['payment_type'] . '] is not valid.';
			}

			if (!empty($this->aryCaptureParams['order_no']) && $this->aryCaptureParams['order_no'] != $orderDetails->orderNo)
			{
				return 'Novalnet callback received. Order Number is not valid.';
			}
		}
		else
		{
			if(!empty($orderId))
			{
				$order_ref = $this->orderObject($orderId);
				if(empty($order_ref))
				{
					$mailNotification = $this->build_notification_message();
					$message = $mailNotification['message'];
					$subject = $mailNotification['subject'];

					$mailer = pluginApp(MailerContract::class);
					$mailer->sendHtml($message,'technic@novalnet.de',$subject,[],[]);
					return $this->renderTemplate($mailNotification['message']);
				}

				$this->handleCommunicationBreak($order_ref);
				return  $this->renderTemplate('Novalnet handlecommunication break executed successfully.');
			}	
				else 
				{
					return 'Transaction mapping failed';
				}
		}
		return $orderObj;
	}
	
	/**
	 * Get payment details
	 *
	 * @param int $orderId
	 * @return int
	 */
	public function payment_details($orderId, $bankDetails=false)
	{
	$payments = $this->paymentRepository->getPaymentsByOrderId( $orderId);
	foreach ($payments as $payment)
		{
		$property = $payment->properties;
		foreach($property as $proper)
		{
		  if ($proper->typeId == 30)
		  {
			$status = $proper->value;
		  }
		if ($proper->typeId == 21) 
			 {
				 $invoiceDetails = $proper->value;
			 }
		}
		}
		$transactionDetails = ($bankDetails == 'true' ) ? $invoiceDetails : $status;
		return $transactionDetails;
	}

	/**
	 * Build the mail subject and message for the Novalnet Technic Team
	 *
	 * @return array
	 */
	public function build_notification_message()
	{
		$subject = 'Critical error on shop system plentymarkets:seo: order not found for TID: ' . $this->aryCaptureParams['shop_tid'];
		$message = "Dear Technic team,<br/><br/>Please evaluate this transaction and contact our Technic team and Backend team at Novalnet.<br/><br/>";
		foreach( ['vendor_id', 'product_id', 'tid', 'tid_payment', 'tid_status', 'order_no', 'payment_type', 'email'] as $key) {
			if (!empty($this->aryCaptureParams[$key])) {
								$message .= "$key: " . $this->aryCaptureParams[$key] . '<br/>';
						}
		}

		return ['subject'=>$subject, 'message'=>$message];
	}

	/**
	 * Retrieves the order object from shop order ID
	 *
	 * @param int $orderId
	 * @return object
	 */
	public function orderObject($orderId)
	{
	  $orderId = (int)$orderId;
		$authHelper = pluginApp(AuthHelper::class);
				$order_ref = $authHelper->processUnguarded(
				function () use ($orderId) {
					$order_obj = $this->orderRepository->findOrderById($orderId);
					return $order_obj;
				});
				return $order_ref;

	}

	/**
	 * Get the order language based on the order object
	 *
	 * @param object $orderObj
	 * @return string
	 */
	public function orderLanguage($orderObj)
	{
		foreach($orderObj->properties as $property)
		{
			if($property->typeId == '6' )
			{
				$language = $property->value;

				return $language;
			}
		}
	}

	/**
	 * Get the callback payment level based on the payment type
	 *
	 * @return int
	 */
	public function getPaymentTypeLevel()
	{
		if(in_array($this->aryCaptureParams['payment_type'], $this->aryPayments))
		{
			return 0;
		}
		else if(in_array($this->aryCaptureParams['payment_type'], $this->aryChargebacks))
		{
			return 1;
		}
		else if(in_array($this->aryCaptureParams['payment_type'], $this->aryCollection))
		{
			return 2;
		}
	}

	/**
	 * Setup the transction log for the callback executed
	 *
	 * @param $txnHistory
	 * @param $initialLevel
	 */
	public function saveTransactionLog($txnHistory, $initialLevel = false)
	{
		$insertTransactionLog['callback_amount'] = ($initialLevel) ? $txnHistory->order_total_amount : $this->aryCaptureParams['amount'];
		$insertTransactionLog['amount']          = $txnHistory->order_total_amount;
		$insertTransactionLog['tid']             = $this->aryCaptureParams['shop_tid'];
		$insertTransactionLog['ref_tid']         = $this->aryCaptureParams['tid'];
		$insertTransactionLog['payment_name']    = $txnHistory->paymentName;
		$insertTransactionLog['order_no']        = $txnHistory->orderNo;

		$this->transaction->saveTransaction($insertTransactionLog);
	}

	/**
	 * Send the vendor script email for the execution
	 *
	 * @param $mailContent
	 * @return bool
	 */
	public function sendCallbackMail($mailContent)
	{
		try
		{
			$enableTestMail = ($this->config->get('Novalnet.novalnet_enable_email') == 'true');

			if($enableTestMail)
			{
				$toAddress  = $this->config->get('Novalnet.novalnet_email_to');
				$bccAddress = $this->config->get('Novalnet.novalnet_email_bcc');
				$subject    = 'Novalnet Callback Script Access Report';

				if(!empty($bccAddress))
				{
					$bccMail = explode(',', $bccAddress);
				}
				else
				{
					$bccMail = [];
				}

				$ccAddress = []; # Setting it empty as we handle only to and bcc addresses.

				$mailer = pluginApp(MailerContract::class);
				$mailer->sendHtml($mailContent, $toAddress, $subject, $ccAddress, $bccMail);
			}
		} catch (\Exception $e) {
			$this->getLogger(__METHOD__)->error('Novalnet::CallbackMailNotSend', $e);
			return false;
		}
	}

	/**
	 * Render twig template for callback message
	 *
	 * @param $templateData
	 * @return string
	 */
	public function renderTemplate($templateData)
	{
		return $this->twig->render('Novalnet::callback.NovalnetCallback', ['comments' => $templateData]);
	}

	/**
	 * Handling communication breakup
	 *
	 * @param array $orderObj
	 * @return none
	 */
	public function handleCommunicationBreak($orderObj)

	{
		$orderlanguage = $this->orderLanguage($orderObj);
		if($this->aryCaptureParams['payment_type'] == 'PAYPAL') {
		foreach($orderObj->properties as $property)
		{
			if($property->typeId == '3' && $this->paymentHelper->getPaymentKeyByMop($property->value))
			{
				$requestData = $this->aryCaptureParams;
				$requestData['lang'] = $orderlanguage;
				$requestData['mop']= $property->value;
				$payment_type = (string)$this->paymentHelper->getPaymentKeyByMop($property->value);
				$requestData['payment_id'] = $this->paymentService->getkeyByPaymentKey($payment_type);

				$transactionData                        = pluginApp(stdClass::class);
				$transactionData->paymentName           = $this->paymentHelper->getPaymentNameByResponse($requestData['payment_id']);
				$transactionData->orderNo               = $requestData['order_no'];
				$transactionData->order_total_amount    = (float) $requestData['amount']/100;
				
				$requestData['amount'] = (float) $requestData['amount']/100;

				if( in_array($this->aryCaptureParams['status'], [90,100])  && in_array($this->aryCaptureParams['tid_status'], [85,90,100]))
				{
					$this->paymentService->executePayment($requestData);
					$this->saveTransactionLog($transactionData);

				}
				else{
					$requestData['type'] = 'cancel';
					$this->paymentService->executePayment($requestData,true);
					$this->aryCaptureParams['amount'] = '0';
					$this->saveTransactionLog($transactionData);
				}

				$callbackComments = $this->paymentHelper->getTranslatedText('callback_handlecommunication',$requestData['lang']). date('Y-m-d H:i:s');
				$this->paymentHelper->createOrderComments($this->aryCaptureParams['order_no'], $callbackComments);
				$this->sendCallbackMail($callbackComments);
				return $this->renderTemplate($callbackComments);

			} else {
				return 'Novalnet callback received: Given payment type is not matched.';
			}
		}
		}
		return $this->renderTemplate('Novalnet_callback script executed.');
	}
	
	/**
	 * Get customer details
	 *
	 * @param int $orderNo
	 * @return object
	 */
	public function addressObj($orderNo) {
		
	 $orderobject = $this->orderObject($orderNo);
	foreach ($orderobject->addressRelations as $relations) 
		{
	   		$addressId = $relations->addressId;
	   		if( !empty ($addressId) ) {
		break;
	  			}
		}   
		
		$addressId = (int)$addressId;
			$authHelper = pluginApp(AuthHelper::class);
			$address_ref = $authHelper->processUnguarded(
				function () use ($addressId) {
					$address_obj = $this->addressRepository->findAddressById($addressId);
				return $address_obj;
				});

				return $address_ref;
	}
	
	/**
	 * Send the transaction confirmation mail
	 * 
	 * @param string $mailContent
	 * @param string $transactionDetails
	 * @param int $order_no
	 * 
	 * @return null
	 */
	public function sendTransactionConfirmMail($mailContent, $order_no)
	{		
   	    $addresses = $this->addressObj($order_no);				
		$toAddress  = $addresses->email;
		$subject    = 'Bestellbestätigung – Ihre Bestellung' . ' ' .$order_no. ' ' .'bei Plentymarkets wurde bestätigt!';
		$body = '<body style="background:#F6F6F6; font-family:Verdana, Arial, Helvetica, sans-serif; font-size:14px; margin:0; padding:0;"><div style="width:55%;height:auto;margin: 0 auto;background:rgb(247, 247, 247);border: 2px solid rgb(223, 216, 216);border-radius: 5px;box-shadow: 1px 7px 10px -2px #ccc;"><div style="min-height: 300px;padding:20px;"><b>Dear Mr./Ms./Mrs.</b>'.$addresses->name2 . ' ' . $addresses->name3.'<br><br>'.$addresses->name2 . ' ' . $addresses->name3.'<br><br><b>Payment Information:</b><br>'.nl2br($mailContent).'</div><div style="width:100%;height:20px;background:#00669D;"></div></div></body>';
		
		$mailer = pluginApp(MailerContract::class);
		$mailer->sendHtml($body, $toAddress, $subject);		
	}
	
}
