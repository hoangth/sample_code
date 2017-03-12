<?php

class InvoiceController extends RController
{
    /**
     * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
     * using two-column layout. See 'protected/views/layouts/column2.php'.
     */
    public $layout = '//layouts/main';

    /**
     * @return array action filters
     */
    public function filters()
    {
        return array(
            'rights',
        );
    }

    /**
     * Displays a particular model.
     * @param integer $id the ID of the model to be displayed
     */
    public function actionView($id)
    {
        $this->render('view', array(
            'model' => $this->loadModel("Subscription", $id),
        ));
    }

    /**
     * Displays a particular model.
     * @param integer $id the ID of the model to be displayed
     */
    public function actionDownload($id)
    {
        # You can easily override default constructor's params
        $mPDF1 = Yii::app()->ePdf->mpdf('', 'A4');

        # renderPartial (only 'view' of current controller)
        $subscription = $this->loadModel("Subscription", $id);
        $store = Store::model()->findByPk($subscription->store_id);
        $html = $this->renderPartial('pdf', array('subscription' => $subscription, 'store' => $store), true);
        $mPDF1->WriteHTML($html);

        # Outputs ready PDF
        $mPDF1->Output();
    }

    /**
     * Creates a new model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     */
    public function actionCreate()
    {
        $model = new Subscription;

        // Uncomment the following line if AJAX validation is needed
        // $this->performAjaxValidation($model);

        if (isset($_POST['Subscription'])) {
            $model->attributes = $_POST['Subscription'];
            if ($model->save())
                $this->redirect(array('view', 'id' => $model->id));
        }

        $this->render('create', array(
            'model' => $model,
        ));
    }

    /**
     * Updates a particular model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id the ID of the model to be updated
     */
    public function actionUpdate($id)
    {
        $model = $this->loadModel("Subscription", $id);

        // Uncomment the following line if AJAX validation is needed
        // $this->performAjaxValidation($model);

        if (isset($_POST['Subscription'])) {
            $model->attributes = $_POST['Subscription'];
            if ($model->save())
                $this->redirect(array('view', 'id' => $model->id));
        }

        $this->render('update', array(
            'model' => $model,
        ));
    }

    /**
     * Deletes a particular model.
     * If deletion is successful, the browser will be redirected to the 'admin' page.
     * @param integer $id the ID of the model to be deleted
     */
    public function actionDelete($id)
    {
        $model = $this->loadModel("Subscription", $id);
        if (time() > strtotime($model->start_date) && $model->status != 'PAID'){
            $model->delete();
            // disable store
        }


        // if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
        if (!isset($_GET['ajax']))
            $this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('index'));
    }

    /**
     * Manages all models.
     */
    public function actionIndex()
    {
        $model = new Subscription('search');
        $model->unsetAttributes(); // clear any default values
        if (isset($_GET['Subscription']))
            $model->attributes = $_GET['Subscription'];

        $this->render('index', array(
            'model' => $model,
        ));
    }

    /**
     * Performs the AJAX validation.
     * @param Subscription $model the model to be validated
     */
    protected function performAjaxValidation($model)
    {
        if (isset($_POST['ajax']) && $_POST['ajax'] === 'subscription-form') {
            echo CActiveForm::validate($model);
            Yii::app()->end();
        }
    }

    public function actionAjaxCalculateTotal()
    {
        $request = Yii::app()->request;
        $currencyCode = $request->getPost('currency_code');
        $planId = $request->getPost('plan_id');
        
        $plan = Plan::model()->findByPk($planId);
        
        // trial
        if($plan->is_trial){
            echo 0;
            Yii::app()->end();
        }
        
        $billingCycle = $request->getPost('billing_cycle');

        $currencyModel = new Currency();
        $currency = $currencyModel->getCurrencyByCode($currencyCode);
        if ($currency == null) {
            Yii::app()->end();
        }
        $priceModel = new Price();
        $price = $priceModel->getPrice($planId, $billingCycle);
        if ($price == null) {
            Yii::app()->end();
        }

        $currency_value = $currency->value;
                    
        $priceCurrencyModel = new PriceCurrency();
        $priceCurrency = $priceCurrencyModel->getPriceCurrency($price->id, $currencyCode);
                    
        $price = $priceCurrency->price;
        $tax = 0;
        $total = $price + $tax;
        echo $total;
        Yii::app()->end();
    }
}
