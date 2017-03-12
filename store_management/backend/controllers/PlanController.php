<?php
Yii::import('ext.ECurrencyHelper.*');
Yii::import('ext.DynamicTabularForm.*');

class PlanController extends RController
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

    public function actions()
    {
        return array(
            'getRowForm' => array(
                'class' => 'ext.DynamicTabularForm.actions.GetRowForm',
                'view' => '_rowForm',
                'modelClass' => 'Price'
            ),
        );
    }

    /**
     * Creates a new model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     */
    public function actionCreate()
    {
        $model = new Plan;
        $modelPrice = array(new Price);
        $transaction = $model->dbConnection->beginTransaction();
        $featureModel = new Feature;
        $featureList = $featureModel->getActiveFeature();

        // Uncomment the following line if AJAX validation is needed
        // $this->performAjaxValidation($model);
        if (isset($_POST['Plan'])) {
            $model->attributes = $_POST['Plan'];
            if($model->feature_id == null){
                $model->feature_id = array();
            }
            // selected to reshow form
            $model->feature_id = implode(',', $model->feature_id);

            /**
             * creating an array of plandetail objects
             */
            if (isset($_POST['Price'])) {
                $modelPrice = array();
                foreach ($_POST['Price'] as $key => $value) {
                    /*
                     * sladetail needs a scenario wherein the fk sla_id
                     * is not required because the ID can only be
                     * linked after the sla has been saved
                     */
                    $price = new Price('batchSave');
                    
                    // no use price->price now
                    $value['price'] = 0;
                    
                    $price->attributes = $value;
                    $modelPrice[$key] = $price;
                    
                    foreach ($_POST['PriceCurrency'][$key] as $i => $priceCurrency) {
                        $objPriceCurrency = new PriceCurrency;
                        $objPriceCurrency->attributes = $priceCurrency;
                        $modelPrice[$key]->priceCurrency[$i] = $objPriceCurrency;
                    }
                }
            }
            /**
             * validating the sla and array of sladetail
             */
            $valid = $model->validate();
            foreach ($modelPrice as $price) {
                $price->plan_id = 0;
                $valid = $price->validate() && $valid;
                foreach ($price->priceCurrency as $priceCurrency) {
                    $priceCurrency->plan_id = 0;
                    $priceCurrency->price_id = 0;
                    $valid = $priceCurrency->validate() && $valid;
                }
            }

            if ($valid) {
                try {
                    $model->save();
                    $model->refresh();

                    foreach ($modelPrice as $price) {
                        $price->plan_id = $model->id;
                        $price->save();

                        // save list currency
                        foreach ($price->priceCurrency as $priceCurrency) {
                            $priceCurrency->plan_id = $model->id;
                            $priceCurrency->price_id = $price->id;
                            $priceCurrency->save();
                        }
                    }
                    $transaction->commit();
                } catch (Exception $e) {
                    $transaction->rollback();
                }

                $this->redirect(array('index'));
            }

        }
        $featureModel->setSeletedFeature($featureList, $model->feature_id);
        $this->render('create', array(
            'model' => $model,
            'modelPrice' => $modelPrice,
            'featureList' => $featureList,
        ));
    }


    /**
     * Updates a particular model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id the ID of the model to be updated
     */
    public function actionUpdate($id)
    {

        $model = $this->loadModel('Plan', $id);
        $modelPrice = $model->price;
        $modelPriceCurrency = new PriceCurrency;
        $featureModel = new Feature;
        $featureList = $featureModel->getActiveFeature();
        if (isset($_POST['Plan'])) {
            if(!isset($_POST['Plan']['feature_id'])){
                $_POST['Plan']['feature_id'] = null;
            }
            
            $model->attributes = $_POST['Plan'];
            if($model->feature_id == null){
                $model->feature_id = array();
            }
            // selected to reshow form
            $model->feature_id = implode(',', $model->feature_id);

            if (isset($_POST['Price'])) {
                $modelPrice = array();
                foreach ($_POST['Price'] as $key => $value) {
                    /**
                     * here we will take advantage of the updateType attribute so
                     * that we will be able to determine what we want to do
                     * to a specific row
                     */

                    if ($value['updateType'] == DynamicTabularForm::UPDATE_TYPE_CREATE){
                        $modelPrice[$key] = new Price();
                    }
                    else if ($value['updateType'] == DynamicTabularForm::UPDATE_TYPE_UPDATE){
                        $modelPrice[$key] = Price::model()->findByPk($value['id']);
                    }
                    else if ($value['updateType'] == DynamicTabularForm::UPDATE_TYPE_DELETE) {
                        $delete = Price::model()->findByPk($value['id']);
                        if ($delete->delete()) {
                            // delete priceCurrency
                            PriceCurrency::model()->deleteByPrice($value['id']);
                            unset($modelPrice[$key]);
                            continue;
                        }
                    }
                    // no use price->price now
                    $value['price'] = 0;
                    $modelPrice[$key]->attributes = $value;
                    
                    // price currency
                    foreach ($_POST['PriceCurrency'][$key] as $i => $priceCurrency) {
                        $objPriceCurrency = $modelPriceCurrency->findByPk((int) $priceCurrency['id']);
                        if($objPriceCurrency == null){// create
                            $objPriceCurrency = new PriceCurrency();
                        }
                        $objPriceCurrency->attributes = $priceCurrency;
                        $modelPrice[$key]->priceCurrency[$i] = $objPriceCurrency;
                    }
                }
            }

            $valid = $model->validate();
            foreach ($modelPrice as $price) {
                $price->plan_id = $id;
                $valid = $price->validate() && $valid;
                foreach ($price->priceCurrency as $priceCurrency) {
                    $priceCurrency->plan_id = 0;
                    $priceCurrency->price_id = 0;
                    $valid = $priceCurrency->validate() && $valid;
                }
            }
            if ($valid) {
                $transaction = $model->dbConnection->beginTransaction();
                try {
                    $model->save();
                    $model->refresh();

                    foreach ($modelPrice as $price) {
                        $price->plan_id = $model->id;
                        $price->save();
                        
                        // save list currency
                        foreach ($price->priceCurrency as $priceCurrency) {
                            $priceCurrency->plan_id = $model->id;
                            $priceCurrency->price_id = $price->id;
                            $priceCurrency->save();
                        }
                    }
                    $transaction->commit();
                } catch (Exception $e) {
                    $transaction->rollback();
                }

                $this->redirect(array('index'));
            }
        }
        $featureModel->setSeletedFeature($featureList, $model->feature_id);
        $this->render('update', array(
            'model' => $model,
            'modelPrice' => $modelPrice,
            'featureList' => $featureList,
        ));
    }

    /**
     * Deletes a particular model.
     * If deletion is successful, the browser will be redirected to the 'admin' page.
     * @param integer $id the ID of the model to be deleted
     */
    public function actionDelete($id)
    {
        $plan = Plan::model()->findByPk($id);
        if(!$plan->is_trial){
            $this->loadModel('Plan', $id)->delete();
            Price::model()->deleteAll("plan_id = $id");
            PriceCurrency::model()->deleteByPlan($id);
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
        $model = new Plan('search');
        $model->unsetAttributes(); // clear any default values
        if (isset($_GET['Plan']))
            $model->attributes = $_GET['Plan'];

        $this->render('index', array(
            'model' => $model,
        ));
    }

    /**
     * Performs the AJAX validation.
     * @param Plan $model the model to be validated
     */
    protected function performAjaxValidation($model)
    {
        if (isset($_POST['ajax']) && $_POST['ajax'] === 'plan-form') {
            echo CActiveForm::validate($model);
            Yii::app()->end();
        }
    }
	
	public function actionAjaxGetPlanList(){
	    $request = Yii::app()->request;
	    $type = $request->getQuery('type');
	    
	    $planModel = new Plan();  
	    $planList = $planModel->getPlanByType($type);
	    $arrData = SaasHelper::convertListToSelectOptions($planList);
	    echo json_encode($arrData);
	    exit;
	}
	
	public function actionAjaxIsTrialPlan(){
	    $request = Yii::app()->request;
	    $planId = $request->getQuery('plan_id');
	    
	    $planModel = new Plan();  
	    $plan = $planModel->findByPk($planId);
	    echo $plan->is_trial;
	    exit;
	}
}
