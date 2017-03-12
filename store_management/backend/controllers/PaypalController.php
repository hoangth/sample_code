<?php

/**
 * Created by PhpStorm.
 * User: Hoang
 * Date: 6/27/14
 * Time: 9:49 AM
 */

class PaypalController extends EController
{
    /**
     * @return \Payum\YiiExtension\PayumComponent
     */
    private function getPayum()
    {
        return Yii::app()->payum;
    }
    
    public function actionPrepare()
    {
        $paymentName = 'paypal';
        
        $payum = $this->getPayum();

        $registry = $payum->getRegistry();
        $tokenStorage = $payum->getTokenStorage();
        $storage = $registry->getStorageForClass(
                'PaymentDetails',
                $paymentName
        );
        // end config
        
        $agreementDetails = $storage->createModel();
        $agreementDetails['PAYMENTREQUEST_0_AMT'] = 1;
        $agreementDetails['L_BILLINGTYPE0'] = 'RecurringPayments';
        $agreementDetails['L_BILLINGAGREEMENTDESCRIPTION0'] = 'Subscribe to weather forecast for a week. It is 0.05$ per day.';
        $agreementDetails['NOSHIPPING'] = 1;
        $storage->updateModel($agreementDetails);
        

        // recurring token
        $recurringToken = $tokenStorage->createModel();
        $recurringToken->setPaymentName($paymentName);
        $recurringToken->setDetails($storage->getIdentificator($agreementDetails));
        $recurringToken->setTargetUrl(
            $this->createAbsoluteUrl('paypal/recurring', array('payum_token' => $recurringToken->getHash()))
        );
        $tokenStorage->updateModel($recurringToken);
        

        // capture token
        $captureToken = $tokenStorage->createModel();
        $captureToken->setPaymentName('paypal');
        $captureToken->setDetails($storage->getIdentificator($agreementDetails));
        $captureToken->setTargetUrl(
                $this->createAbsoluteUrl('payment/capture', array('payum_token' => $captureToken->getHash()))
        );
        $captureToken->setAfterUrl($recurringToken->getTargetUrl());
        $tokenStorage->updateModel($captureToken);
        
        $agreementDetails['RETURNURL'] = $captureToken->getTargetUrl();
        $agreementDetails['CANCELURL'] = $captureToken->getTargetUrl();
        $storage->updateModel($agreementDetails);
        
        header("Location: ".$captureToken->getTargetUrl());
    }
    
    public function actionRecurring(){

        $paymentName = 'paypal';
        
        $payum = $this->getPayum();
        
        $registry = $payum->getRegistry();
        $tokenStorage = $payum->getTokenStorage();
        $storage = $payum->getRegistry()->getStorageForClass(
                'PaymentDetails',
                $paymentName
        );
        // end config

        $token = $payum->getHttpRequestVerifier()->verify($_REQUEST);
        $payum->getHttpRequestVerifier()->invalidate($token);
        
        $payment = $registry->getPayment($token->getPaymentName());
        
        $agreementStatus = new \Payum\Core\Request\BinaryMaskStatusRequest($token);
        $payment->execute($agreementStatus);
        
        $recurringPaymentStatus = null;
        if (false == $agreementStatus->isSuccess()) {
            header('HTTP/1.1 400 Bad Request', true, 400);
            exit;
        }
        
        $agreementDetails = $agreementStatus->getModel();
        
        $recurringPaymentDetails = $storage->createModel();
        $recurringPaymentDetails['TOKEN'] = $agreementDetails->offsetGet('TOKEN');
        $recurringPaymentDetails['DESC'] = 'Subscribe to weather forecast for a week. It is 0.05$ per day.';
        $recurringPaymentDetails['EMAIL'] = $agreementDetails->offsetGet('EMAIL');
        $recurringPaymentDetails['AMT'] = 0.05;
        $recurringPaymentDetails['CURRENCYCODE'] = 'USD';
        $recurringPaymentDetails['BILLINGFREQUENCY'] = 7;
        $recurringPaymentDetails['PROFILESTARTDATE'] = date(DATE_ATOM);
        $recurringPaymentDetails['BILLINGPERIOD'] = 'Day';
        
        $payment->execute(new Payum\Paypal\ExpressCheckout\Nvp\Request\Api\CreateRecurringPaymentProfileRequest($recurringPaymentDetails));
        $payment->execute(new Payum\Core\Request\SyncRequest($recurringPaymentDetails));
        

        // done token
        $doneToken = $tokenStorage->createModel();
        $doneToken->setPaymentName($paymentName);
        $doneToken->setDetails($storage->getIdentificator($recurringPaymentDetails));
        $doneToken->setTargetUrl(
                $this->createAbsoluteUrl('paypal/done', array('payum_token' => $doneToken->getHash()))
        );
        $tokenStorage->updateModel($doneToken);
        
        
        header("Location: ".$doneToken->getTargetUrl());
    }

    public function actionDone()
    {
        $token = $this->getPayum()->getHttpRequestVerifier()->verify($_REQUEST);
        $payment = $this->getPayum()->getRegistry()->getPayment($token->getPaymentName());

        $payment->execute($status = new \Payum\Core\Request\BinaryMaskStatusRequest($token));

        $content = '';
        if ($status->isSuccess()) {

            $content .= '<h3>Payment status is success.</h3>';
        } else {
            $content .= '<h3>Payment status IS NOT success.</h3>';
        }

        $content .= '<br /><br />' . json_encode(iterator_to_array($status->getModel()), JSON_PRETTY_PRINT);

        echo '<pre>', $content, '</pre>';
        exit;
    }
}