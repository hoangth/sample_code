<?php

class CustomerController extends RController
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
        $model = new Customer;
        $model->scenario = 'create';
        // Uncomment the following line if AJAX validation is needed
        // $this->performAjaxValidation($model);

        if (isset($_POST['Customer'])) {
            $model->attributes = $_POST['Customer'];
            if ($model->save())
                $this->redirect(array('index'));
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
        $model = $this->loadModel('Customer', $id);

        // Uncomment the following line if AJAX validation is needed
        // $this->performAjaxValidation($model);

        if (isset($_POST['Customer'])) {
            $model->attributes = $_POST['Customer'];
            if ($model->save())
                $this->redirect(array('index'));
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
        $this->loadModel('Customer', $id)->delete();

        // if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
        if (!isset($_GET['ajax']))
            $this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('index'));
    }

    /**
     * Manages all models.
     */
    public function actionIndex()
    {
        $model = new Customer('search');
        $model->unsetAttributes(); // clear any default values
        if (isset($_GET['Customer']))
            $model->attributes = $_GET['Customer'];

        $this->render('index', array(
            'model' => $model,
        ));
    }


    /**
     * Performs the AJAX validation.
     * @param Customer $model the model to be validated
     */
    protected function performAjaxValidation($model)
    {
        if (isset($_POST['ajax']) && $_POST['ajax'] === 'customer-form') {
            echo CActiveForm::validate($model);
            Yii::app()->end();
        }
    }

    public function actionAjaxList($id = null)
    {
        header('Content-Type: application/json; charset="UTF-8"');
        $callback = $_GET['callback'];
        $criteria = new CDbCriteria;
        $criteria->select = 'id, email, firstname, lastname';

        if ($id) {
            // Search
            $criteria->condition = 'id=' . $id; //.;
            // Data
            $customer = Customer::model()->find($criteria);
            // Output
            $json = CJavaScript::jsonEncode($customer);
            echo $callback . '(' . $json . ')';
            Yii::app()->end();
        } else {
            // Search email
            $q = $_GET['q'];
            $criteria->addSearchCondition('email', $q);
            // Limit record
            $page_limit = $_GET['page_limit'];
            $page = $_GET['page'];
            $total = Customer::model()->count($criteria);
            $pages = new CPagination($total);
            $pages->pageSize = $page_limit;
            $pages->currentPage = $page;
            $pages->applyLimit($criteria);
            // List data
            $customer = Customer::model()->findAll($criteria);
            // Output
            $data = array(
                'total' => $total,
                'customer' => $customer,
                'links' => array(
                    'self' => $this->createAbsoluteUrl('store/customerslist?q=' . $q . '&page_limit=' . $page_limit . '&page=' . $page),
                    'next' => $this->createAbsoluteUrl('store/customerslist?q=' . $q . '&page_limit=' . $page_limit . '&page=' . ($page + 1)),
                ),
                'link_template' => $this->createAbsoluteUrl('store/customerslist?q={search-term}&page_limit={results-per-page}&page={page-number}')
            );
            $json = CJavaScript::jsonEncode($data);
            echo $callback . '(' . $json . ')';
            Yii::app()->end();
        }
    }
}
