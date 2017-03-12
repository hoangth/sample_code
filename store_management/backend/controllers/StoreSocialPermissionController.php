<?php

class StoreSocialPermissionController extends RController
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
            array( 'allow',  // allow all users to perform 'index' and 'view' actions
                   'actions' => array( 'index', 'view' ),
                   'users'   => array( '*' ),
            ),
            array( 'allow', // allow authenticated user to perform 'create' and 'update' actions
                   'actions' => array( 'create', 'update' ),
                   'users'   => array( '@' ),
            ),
            array( 'allow', // allow admin user to perform 'admin' and 'delete' actions
                   'actions' => array( 'admin', 'delete' ),
                   'users'   => array( 'admin' ),
            ),
            array( 'deny',  // deny all users
                   'users' => array( '*' ),
            ),
        );
    }

    /**
     * Updates a particular model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id the ID of the model to be updated
     */
    public function actionUpdate( $id )
    {
        $model = $this->loadModel( 'StoreSocialPermission', $id );

        // Uncomment the following line if AJAX validation is needed
        // $this->performAjaxValidation($model);

        if ( isset( $_POST[ 'StoreSocialPermission' ] ) ) {
            $model->attributes = $_POST[ 'StoreSocialPermission' ];
            if ( $model->save() ) {
                $storeId = $model->store->id;
                $modelSettingConfig = new SettingConfig();
                $modelSettingConfig->updateStoreSocialPermission( $storeId, $model->id );
                $this->redirect( array( 'index' ) );
            }
        }

        $this->render( 'update', array(
            'model' => $model,
        ) );
    }

    /**
     * Lists all models.
     */
    public function actionIndex()
    {
        $model = new StoreSocialPermission( 'search' );
        $model->unsetAttributes();  // clear any default values
        if ( isset( $_GET[ 'StoreSocialPermission' ] ) ) {
            $model->attributes = $_GET[ 'StoreSocialPermission' ];
        }

        $this->render( 'index', array(
            'model' => $model,
        ) );
    }

    /**
     * Performs the AJAX validation.
     * @param StoreSocialPermission $model the model to be validated
     */
    protected function performAjaxValidation( $model )
    {
        if ( isset( $_POST[ 'ajax' ] ) && $_POST[ 'ajax' ] === 'store-social-permission-form' ) {
            echo CActiveForm::validate( $model );
            Yii::app()->end();
        }
    }
}
