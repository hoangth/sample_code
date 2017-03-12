<?php

use Payum\Core\Security\Util\Random;
class ReportFinancialController extends RController
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
			'accessControl', // perform access control for CRUD operations
			'postOnly + delete', // we only allow deletion via POST request
		);
	}

	/**
	 * Specifies the access control rules.
	 * This method is used by the 'accessControl' filter.
	 * @return array access control rules
	 */
	public function accessRules()
	{
		return array(
			array('allow',  // allow all users to perform 'index' and 'view' actions
				'actions'=>array(/* 'index','view',  'report'*/'index'),
				'users'=>array('*'),
			),
			array('allow', // allow authenticated user to perform 'create' and 'update' actions
				'actions'=>array(/* 'create','update' */),
				'users'=>array('@'),
			),
			array('allow', // allow admin user to perform 'admin' and 'delete' actions
				'actions'=>array(/* 'admin','delete' */),
				'users'=>array('admin'),
			),
			array('deny',  // deny all users
				'users'=>array('*'),
			),
		);
	}


	/**
	 * Creates a new model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 */
	public function actionCreate()
	{
	    $model=new ReportFinancial;
	
	    // Uncomment the following line if AJAX validation is needed
	    // $this->performAjaxValidation($model);
	    
// 	    $store = array(91, 104, 105, 106, 112, 115, 120, 121, 122, 124, 147);
// 	    for($i=2; $i <300; $i++){
// 	    $model=new ReportFinancial;
// 	    $model->store_id = $store[array_rand($store)];
// 	    $model->order_id = $i;
// 	    $model->value = rand(100, 10000);
// 	    $model->charge = rand(100, 10000);
// 	    $model->date = date('Y-m-d',rand(time() - 12*30*24*60*60, time()));
// 	    $model->status = rand(0, 1);
// 	    $model->save();
// 	    }
	
	    if(isset($_POST['ReportFinancial']))
	    {
	        $model->attributes=$_POST['ReportFinancial'];
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
	    $model=$this->loadModel('ReportFinancial',$id);
	
	    // Uncomment the following line if AJAX validation is needed
	    // $this->performAjaxValidation($model);
	
	    if(isset($_POST['ReportFinancial']))
	    {
	        $model->attributes=$_POST['ReportFinancial'];
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
	    $this->loadModel('ReportFinancial',$id)->delete();
	
	    // if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
	    if(!isset($_GET['ajax']))
	        $this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('index'));
	}
	
	/**
	 * Manages all models.
	 */
	public function actionIndex()
	{
	    /* $model = new ReportFinancial('search');
	    $model->unsetAttributes(); // clear any default values
	    if (isset($_GET['ReportFinancial']))
	        $model->attributes = $_GET['ReportFinancial'];
	
	    $this->render('index', array(
	            'model' => $model,
	    )); */
	    $this->actionReport();
	}
	
	/**
	 * Performs the AJAX validation.
	 * @param ReportFinancial $model the model to be validated
	 */
	protected function performAjaxValidation($model)
	{
	    if(isset($_POST['ajax']) && $_POST['ajax']==='report-financial-form')
	    {
	        echo CActiveForm::validate($model);
	        Yii::app()->end();
	    }
	}
	

    public function actionHanh(){
        echo ("123");
    }
	/**
	 * Manages all models.
	 */
	public function actionReport()
	{
	    $model = new ReportFinancial();
	    $reportData = $model->getReportData($_GET);
	    $ranges = $model->getRanges();
	    $groupReport = $model->groupReportByRange($reportData, $ranges);
	    
	    if(!isset($_GET['filter_year'])){
	        $_GET['filter_year'] = date('Y');
	    }
	    $groupReportYear = $model->getReportByYear($_GET);
	    
	    $this->render('report', array(
	            'groupReport' => $groupReport,
	            'groupReportYear' => $groupReportYear,
	            'filter_year' => $_GET['filter_year'],
	    ));
	}
}
