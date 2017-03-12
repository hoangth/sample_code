<?php

class CurrencyController extends RController
{
	/**
	 * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
	 * using two-column layout. See 'protected/views/layouts/column2.php'.
	 */
	public $layout='//layouts/main';

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
		$model=new Currency;

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if(isset($_POST['Currency']))
		{
			$model->attributes=$_POST['Currency'];
			if($model->save())
				$this->redirect(array('index'));
		}

		$this->render('create',array(
			'model'=>$model,
		));
	}

	/**
	 * Updates a particular model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param integer $id the ID of the model to be updated
	 */
	public function actionUpdate($id)
	{
		$model=$this->loadModel('Currency',$id);

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if(isset($_POST['Currency']))
		{
			$model->attributes=$_POST['Currency'];
			if($model->save())
				$this->redirect(array('index'));
		}

		$this->render('update',array(
			'model'=>$model,
		));
	}

	/**
	 * Deletes a particular model.
	 * If deletion is successful, the browser will be redirected to the 'admin' page.
	 * @param integer $id the ID of the model to be deleted
	 */
	public function actionDelete($id)
	{
		$this->loadModel('Currency',$id)->delete();

		// if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
		if(!isset($_GET['ajax']))
			$this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('index'));
	}

    /**
     * Default a particular model.
     * If set default is successful, the browser will be redirected to the 'admin' page.
     * @param integer $id the ID of the model to be deleted
     */
/*     public function actionDefault($id){
        $model=$this->loadModel('Currency',$id);
        $model->default = 1;
        $model->status = 1;
        if($model->update()){
            Currency::model()->updateAll(array( "default" => 0), "id != $id" );
            $this->redirect(array('index'));
        }
    } */

	/**
	 * Manages all models.
	 */
	public function actionDefault($id)
	{
		$model=new Currency('search');
		$newDefaultId = $id;

		if(isset($_POST['Currency']))
		{
		    if(count($_POST['Currency'] > 0)){
	            $currencyModel = new Currency();
	            $oldDefault = $currencyModel->getDefaultCurrency();
	            $newDefault = $currencyModel->findByPk($newDefaultId);
	            
	            // set new default
	            $currencyModel->setDefaultCurrency($newDefaultId);
	            
	            // all new value just input
	            $oldDefaultValue = $_POST['Currency'][$oldDefault->id];
	            $newDefaultValue = $_POST['Currency'][$newDefaultId];
	            
	            // update new value for the list currency
		        foreach($_POST['Currency'] as $id => $val){
		            $currencyModel->updateByPk($id, array('value' => $val));
		        }
		        $priceModel = new Price();
		        $priceModel->changeDefaultCurrency($oldDefaultValue, $newDefaultValue);
		        
		        $promoModel = new Promotion();
		        $promoModel->changeDefaultCurrency($oldDefaultValue, $newDefaultValue);
		        
		        $this->redirect(array('index'));
		    }
		}
		
		
		$model->unsetAttributes();  // clear any default values
		if(isset($_GET['Currency']))
			$model->attributes=$_GET['Currency'];

		$this->render('default_currency',array(
			'model'=>$model,
		    'newDefaultCurrency' => $newDefaultId
		));
	}

	/**
	 * Manages all models.
	 */
	public function actionIndex()
	{
		$model=new Currency('search');
		$model->unsetAttributes();  // clear any default values
		if(isset($_GET['Currency']))
			$model->attributes=$_GET['Currency'];

		$this->render('index',array(
			'model'=>$model,
		));
	}


	/**
	 * Performs the AJAX validation.
	 * @param Currency $model the model to be validated
	 */
	protected function performAjaxValidation($model)
	{
		if(isset($_POST['ajax']) && $_POST['ajax']==='currency-form')
		{
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}
}
