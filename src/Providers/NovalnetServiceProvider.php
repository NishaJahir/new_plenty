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

namespace Novalnet\Providers;

use Plenty\Plugin\ServiceProvider;
use Plenty\Plugin\Events\Dispatcher;
use Plenty\Modules\Payment\Events\Checkout\ExecutePayment;
use Plenty\Modules\Payment\Events\Checkout\GetPaymentMethodContent;
use Plenty\Modules\Basket\Events\Basket\AfterBasketCreate;
use Plenty\Modules\Basket\Events\Basket\AfterBasketChanged;
use Plenty\Modules\Basket\Events\BasketItem\AfterBasketItemAdd;
use Plenty\Modules\Payment\Method\Contracts\PaymentMethodContainer;
use Plenty\Modules\Basket\Contracts\BasketRepositoryContract;
use Plenty\Modules\Payment\Method\Contracts\PaymentMethodRepositoryContract;
use Plenty\Modules\Account\Address\Contracts\AddressRepositoryContract;
use Plenty\Modules\Frontend\Session\Storage\Contracts\FrontendSessionStorageFactoryContract;
use Plenty\Plugin\Log\Loggable;
use Novalnet\Helper\PaymentHelper;
use Novalnet\Services\PaymentService;
use Novalnet\Services\TransactionService;
use Plenty\Plugin\Templates\Twig;
use Plenty\Plugin\ConfigRepository;

use Novalnet\Methods\NovalnetSepaPaymentMethod;
use Novalnet\Methods\NovalnetPaypalPaymentMethod;

use Plenty\Modules\EventProcedures\Services\Entries\ProcedureEntry;
use Plenty\Modules\EventProcedures\Services\EventProceduresService;

/**
 * Class NovalnetServiceProvider
 *
 * @package Novalnet\Providers
 */
class NovalnetServiceProvider extends ServiceProvider
{
    use Loggable;

    /**
     * Register the route service provider
     */
    public function register()
    {
        $this->getApplication()->register(NovalnetRouteServiceProvider::class);
    }

    /**
     * Boot additional services for the payment method
     *
     * @param Dispatcher $eventDispatcher
     * @param paymentHelper $paymentHelper
     * @param PaymentService $paymentService
     * @param BasketRepositoryContract $basketRepository
     * @param PaymentMethodContainer $payContainer
     * @param PaymentMethodRepositoryContract $paymentMethodService
     * @param FrontendSessionStorageFactoryContract $sessionStorage
     * @param TransactionService $transactionLogData
     * @param Twig $twig
     * @param ConfigRepository $config
     */
    public function boot( Dispatcher $eventDispatcher,
                          PaymentHelper $paymentHelper,
						  AddressRepositoryContract $addressRepository,
                          PaymentService $paymentService,
                          BasketRepositoryContract $basketRepository,
                          PaymentMethodContainer $payContainer,
                          PaymentMethodRepositoryContract $paymentMethodService,
                          FrontendSessionStorageFactoryContract $sessionStorage,
                          TransactionService $transactionLogData,
                          Twig $twig,
                          ConfigRepository $config,
                          EventProceduresService $eventProceduresService)
    {

        // Register the Novalnet payment methods in the payment method container
        
        $payContainer->register('plenty_novalnet::NOVALNET_SEPA', NovalnetSepaPaymentMethod::class,
            [
                AfterBasketChanged::class,
                AfterBasketItemAdd::class,
                AfterBasketCreate::class
            ]);
        
        $payContainer->register('plenty_novalnet::NOVALNET_PAYPAL', NovalnetPaypalPaymentMethod::class,
            [
                AfterBasketChanged::class,
                AfterBasketItemAdd::class,
                AfterBasketCreate::class
            ]);
            
		// Event for Onhold - Capture Process
		$captureProcedureTitle = [
            'de' => 'Novalnet | Bestätigen',
            'en' => 'Novalnet | Confirm',
        ];
        $eventProceduresService->registerProcedure(
            'Novalnet',
            ProcedureEntry::EVENT_TYPE_ORDER,
            $captureProcedureTitle,
            '\Novalnet\Procedures\CaptureEventProcedure@run'
        );
        
        // Event for Onhold - Void Process
        $voidProcedureTitle = [
            'de' => 'Novalnet | Stornieren',
            'en' => 'Novalnet | Cancel',
        ];
        $eventProceduresService->registerProcedure(
            'Novalnet',
            ProcedureEntry::EVENT_TYPE_ORDER,
            $voidProcedureTitle,
            '\Novalnet\Procedures\VoidEventProcedure@run'
        );
        
        // Event for Onhold - Refund Process
        $refundProcedureTitle = [
            'de' =>  'Novalnet | Rückerstattung',
            'en' =>  'Novalnet | Refund',
        ];
        $eventProceduresService->registerProcedure(
            'Novalnet',
            ProcedureEntry::EVENT_TYPE_ORDER,
            $refundProcedureTitle,
            '\Novalnet\Procedures\RefundEventProcedure@run'
        );
        
        // Listen for the event that gets the payment method content
        $eventDispatcher->listen(GetPaymentMethodContent::class,
                function(GetPaymentMethodContent $event) use($config, $paymentHelper, $addressRepository, $paymentService, $basketRepository, $paymentMethodService, $sessionStorage, $twig)
                {
			
                    if($paymentHelper->getPaymentKeyByMop($event->getMop()))
                    {		
						$paymentKey = $paymentHelper->getPaymentKeyByMop($event->getMop());	
						$guaranteeStatus = $paymentService->getGuaranteeStatus($basketRepository->load(), $paymentKey);
						$basket = $basketRepository->load();			
						$billingAddressId = $basket->customerInvoiceAddressId;
						$address = $addressRepository->findAddressById($billingAddressId);
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
			    			$endCustomerName = $firstName .' '. $lastName;
			    			$endUserName = $address->firstName .' '. $address->lastName;

						$name = trim($config->get('Novalnet.' . strtolower($paymentKey) . '_payment_name'));
						$paymentName = ($name ? $name : $paymentHelper->getTranslatedText(strtolower($paymentKey)));
							
						if ($paymentKey == 'NOVALNET_PAYPAL') { # Redirection payments
							$serverRequestData = $paymentService->getRequestParameters($basketRepository->load(), $paymentKey);
                            $sessionStorage->getPlugin()->setValue('nnPaymentData', $serverRequestData['data']);
                            $sessionStorage->getPlugin()->setValue('nnPaymentUrl', $serverRequestData['url']);
                            $content = '';
                            $contentType = 'continue';
						}  elseif($paymentKey == 'NOVALNET_SEPA') {
                                $paymentProcessUrl = $paymentService->getProcessPaymentUrl();
                                $contentType = 'htmlContent';
                                $guaranteeStatus = $paymentService->getGuaranteeStatus($basketRepository->load(), $paymentKey);
                                if($guaranteeStatus != 'normal' && $guaranteeStatus != 'guarantee')
                                {
                                    $contentType = 'errorCode';
                                    $content = $guaranteeStatus;
                                }
                                else
                                {
									$content = $twig->render('Novalnet::PaymentForm.novalnet_sepa', [
                                                                    'nnPaymentProcessUrl' => $paymentProcessUrl,
                                                                    'paymentMopKey'     =>  $paymentKey,
																	'paymentName' => $paymentName,	
																	'endcustomername'=> empty(trim($endUserName)) ? $endCustomerName : $endUserName,
                                                                    'nnGuaranteeStatus' =>  empty($address->companyName) ? $guaranteeStatus : ''
                                                 ]);
                                }
                            }
								$event->setValue($content);
								$event->setType($contentType);
						} 
                });

        // Listen for the event that executes the payment
        $eventDispatcher->listen(ExecutePayment::class,
            function (ExecutePayment $event) use ($paymentHelper, $paymentService, $sessionStorage, $transactionLogData,$config,$basketRepository)
            {
                if($paymentHelper->getPaymentKeyByMop($event->getMop())) {
                    $sessionStorage->getPlugin()->setValue('nnOrderNo',$event->getOrderId());
                    $sessionStorage->getPlugin()->setValue('mop',$event->getMop());
                    $paymentKey = $paymentHelper->getPaymentKeyByMop($event->getMop());
                    $sessionStorage->getPlugin()->setValue('paymentkey', $paymentKey);

                    if($paymentKey == 'NOVALNET_SEPA') {
                        $paymentService->validateResponse();
                    } else {
                        $paymentProcessUrl = $paymentService->getRedirectPaymentUrl();
                        $event->setType('redirectUrl');
                        $event->setValue($paymentProcessUrl);
                    }
                }
            }
        );
    }
}
