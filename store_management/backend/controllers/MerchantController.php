<?php

class MerchantController extends RController
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
     * Creates a new model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     */
    public function actionCreate()
    {
        $model = new Merchant;

        // Uncomment the following line if AJAX validation is needed
        // $this->performAjaxValidation($model);

        if (isset($_POST['Merchant'])) {
            $model->attributes = $_POST['Merchant'];
            if ($model->save()) {
                $this->loadModel('Merchant', $model->id)->installMerchant();
                $this->redirect(array('index'));
            }

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
        $model = $this->loadModel('Merchant', $id);

        // Uncomment the following line if AJAX validation is needed
        // $this->performAjaxValidation($model);

        if (isset($_POST['Merchant'])) {
            $model->attributes = $_POST['Merchant'];
            if ($model->save()) {
                $model->updateMerchant();
                $this->redirect(array('index'));
            }
        }

        $this->render('update', array(
            'model' => $model,
        ));
    }

    public function actionTransferOwner(){

    }

    /**
     * Deletes a particular model.
     * If deletion is successful, the browser will be redirected to the 'admin' page.
     * @param integer $id the ID of the model to be deleted
     */
    public function actionDelete($id)
    {
        $model = $this->loadModel('Merchant', $id);
        if($model->owner != 1) {
            $model->removeMerchant();
            $model->delete();
        }

        // if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
        if (!isset($_GET['ajax']))
            $this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));
    }


    /**
     * Manages all models.
     */
    public function actionIndex()
    {
        $model = new Merchant('search');
        $model->unsetAttributes(); // clear any default values
        if (isset($_GET['Merchant']))
            $model->attributes = $_GET['Merchant'];

        $this->render('index', array(
            'model' => $model,
        ));
    }

    public function actionZone()
    {
        if (Yii::app()->getRequest()->getIsAjaxRequest()) {
            // Get Merchant[country_id]
            if (isset($_POST['Merchant']['country_id'])) $country_id = (int)$_POST['Merchant']['country_id'];
            else $country_id = 0;
            $criteria = new CDbCriteria;
            $criteria->select = "*";
            $criteria->condition = "country_id = " . $country_id;
            $list_zone = Zone::model()->findAll($criteria);


            // Return Data
            foreach ($list_zone as $zone) {
                echo CHtml::tag('option', array('value' => $zone->zone_id), CHtml::encode($zone->name), true);
            }
            Yii::app()->end();
        } else {
            $this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('create'));
        }
    }

    /**
     * Performs the AJAX validation.
     * @param Merchant $model the model to be validated
     */
    protected function performAjaxValidation($model)
    {
        if (isset($_POST['ajax']) && $_POST['ajax'] === 'merchant-form') {
            echo CActiveForm::validate($model);
            Yii::app()->end();
        }
    }
    
    public function actionAjaxGetPhoneCode(){
	    $request = Yii::app()->request;
        $countryId = $request->getPost('country_id');

        $countryModel = new Country();
        $country = $countryModel->findByPk($countryId);
        if($country == null){
            Yii::app()->end();
        }
        echo $country->phonecode;
        Yii::app()->end();
    }


    public function actionSetOwner($id){
        $merchantModel = new Merchant();
        $merchant = $merchantModel->findByPk($id);
        if($merchantModel->updateOwner($merchant->store_id, $merchant->id)){
            $this->redirect(array('index'));
        }
    }
}
