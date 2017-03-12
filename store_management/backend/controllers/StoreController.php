<?php
Yii::import('ext.ECurrencyHelper.*');
stream_wrapper_register( 's3', 'S3Wrapper' );
S3::setAuth( awsAccessKey, awsSecretKey );

class StoreController extends RController
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
            'model' => $this->loadModel('Store', $id),
        ));
    }

    /**
     * Creates a new model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     */
    public function actionCreate()
    {
        $modelStore = new Store;
        $modelMerchant = new Merchant;
        $modelSubscription = new Subscription;
        $modelStoreSocialPermission = new StoreSocialPermission;
        $transaction = $modelStore->dbConnection->beginTransaction();

        // Uncomment the following line if AJAX validation is needed
        // $this->performAjaxValidation($model);

        if (Yii::app()->getRequest()->getIsAjaxRequest() && isset($_POST['Store']) && isset($_POST['Merchant']) && isset($_POST['Subscription'])) {
            // store name is merchant name
            $_POST['Merchant']['username'] = $_POST['Store']['shop_name'];
            
            $modelStore->attributes = Yii::app()->session['Store'];
            $modelMerchant->attributes = Yii::app()->session['Merchant'];
            $modelSubscription->attributes = Yii::app()->session['Subscription'];
            
            // not paid
            if($modelSubscription->status == 'UNPAID'){
                $modelStore->status = 'DISABLE';
            }

            if ($modelStore->save()) {
                // Create merchant & subscription with new store id
                $modelMerchant->setAttribute('store_id', $modelStore->id);
                $modelSubscription->setAttribute('store_id', $modelStore->id);
                $modelStoreSocialPermission->insertNewStore($modelStore->id);

                if ($modelMerchant->save() && $modelSubscription->save()) {
                    $transaction->commit();

                    // install store
                    $store = $this->loadModel('Store', $modelStore->id);
                    if ($store->installStore()) {
                        // send mail
                        $emailModel = new Email();
                        $emailModel->emailAfterSetupStore($modelStore->id);
                        //$emailModel->emailAfterGenerateInvoice($modelStore->id, $modelSubscription->id);
                        $this->createS3Folder($modelStore->id);
                    }
                    // redirect
                    header('Content-Type: application/json; charset="UTF-8"');
                    $response = array(
                        'status' => 1,
                        'url' => $this->createUrl('store/index')
                    );
                    echo json_encode($response);
                    Yii::app()->end();
                }
            }
        }

        $this->render('create', array(
            'modelStore' => $modelStore,
            'modelMerchant' => $modelMerchant,
            'modelSubscription' => $modelSubscription,
        ));
    }

    /**
     * Updates a particular model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id the ID of the model to be updated
     */
    public function actionUpdate($id)
    {
        $model = $this->loadModel('Store', $id);

        // Uncomment the following line if AJAX validation is needed
        $this->performAjaxValidation($model);

        if (isset($_POST['Store'])) {
            $model->attributes = $_POST['Store'];
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
        $model = $this->loadModel('Store', $id);
        $model->removeAllStoreData();
//         $model->delete();
//         // Delete child
//         Merchant::model()->deleteAll('store_id=:store_id', array(':store_id' => $id));
//         Domain::model()->deleteAll('store_id=:store_id', array(':store_id' => $id));
//         Subscription::model()->deleteAll('store_id=:store_id', array(':store_id' => $id));
        // if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
        if (!isset($_GET['ajax']))
            $this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('index'));

    }

    /**
     * Lists all models.
     */
    public function actionIndex()
    {
        $model = new Store('search');
        $model->unsetAttributes(); // clear any default values
        if (isset($_GET['Store']))
            $model->attributes = $_GET['Store'];

        $this->render('index', array(
            'model' => $model,
        ));
    }


    /**
     * Performs the AJAX validation.
     * @param Store $model the model to be validated
     */
    protected function performAjaxValidation($model)
    {
        if (isset($_POST['ajax']) && $_POST['ajax'] === 'store-form') {
            echo CActiveForm::validate($model);
            Yii::app()->end();
        }
    }

    public function actionValidation()
    {
        if (Yii::app()->getRequest()->getIsAjaxRequest()) {
            if (isset($_POST['Store']) && isset($_POST['Subscription']) && isset($_POST['Merchant'])) {
                $_POST['Store']['status'] = 'ACTIVE';
                $currencyModel = new Currency;
                $priceModel = new Price;

                // Set var
                
                $code = '';
                $plan_id = $billing_cycle = 0;
                
                if(isset($_POST['Subscription']['currency_code'])){
                    $code = $_POST['Subscription']['currency_code'];
                }
                if(isset($_POST['Subscription']['plan_id'])){
                    $plan_id = (int) $_POST['Subscription']['plan_id'];
                }
                if(isset($_POST['Subscription']['billing_cycle'])){
                    $billing_cycle = (int) $_POST['Subscription']['billing_cycle'];
                }
                $currency = $currencyModel->findByAttributes(array('code' => $code));
                $currency_value = null;
                if (is_object($currency)) {
                    $currency_value = $currency->value;
                }
                $price_value = 0;
                // trial
				$plan = Plan::model()->findByPk($plan_id);
                if($plan->is_trial){
                    // trial alway Paid and total = 0
                    $_POST['Subscription']['status'] = 'PAID';
                }else{
                    $price = $priceModel->findByAttributes(array('plan_id' => $plan_id, 'billing_cycle' => $billing_cycle));
                    if (is_object($price)) {
                        $priceCurrencyModel = new PriceCurrency();
                        $priceCurrency = $priceCurrencyModel->getPriceCurrency($price->id, $code);
                        $price_value = $priceCurrency->price;
                    }
                }
                $tax = $balance = 0;
                $total = $price_value + $tax - $balance;
                $days = 30 * $billing_cycle; // always 1 month = 30days
                // set var date
                $now = new DateTime;
                $clone = clone $now;
                $clone->modify("+$billing_cycle month");
                $start_date = $now->format('Y-m-d');
                $end_date = $clone->format('Y-m-d');

                // Set post
                $_POST['Store']['plan_id'] = $plan_id;
                $_POST['Subscription']['start_date'] = $start_date;
                $_POST['Subscription']['end_date'] = $end_date;
                $_POST['Subscription']['tax'] = $tax;
                $_POST['Subscription']['currency_value'] = $currency_value;
                $_POST['Subscription']['price'] = $price_value;
                $_POST['Subscription']['total'] = $total;

                // Validate
                $modelStore = new Store;
                $modelSubscription = new Subscription;
                $store = json_decode(CActiveForm::validate($modelStore), true);
                $subscription = json_decode(CActiveForm::validate($modelSubscription), true);
                Yii::app()->session['Store'] = $_POST['Store'];
                Yii::app()->session['Subscription'] = $_POST['Subscription'];
//             } elseif (isset($_POST['Merchant'])) {
                $modelMerchant = new Merchant;
                $storeName = Yii::app()->session['Store']['shop_name'];
                $_POST['Merchant']['username'] = $storeName;
                $_POST['Merchant']['store_id'] = 0;
                $_POST['Merchant']['status'] = 1;
                $_POST['Merchant']['owner'] = 1;
                $merchant = json_decode(CActiveForm::validate($modelMerchant), true);
                Yii::app()->session['Merchant'] = $_POST['Merchant'];
                echo json_encode(array_merge($store, $subscription, $merchant));
            }
            Yii::app()->end();
        } else {
            $this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('create'));
        }
    }

    public function actionPrice()
    {
        if (Yii::app()->getRequest()->getIsAjaxRequest()) {
            // Get Plan ID
            if (isset($_POST['Subscription']['plan_id'])) $plan_id = (int)$_POST['Subscription']['plan_id'];
            else $plan_id = 0;
            $currency = new ECurrencyHelper();
            $criteria = new CDbCriteria;
            $criteria->select = "*";
            $criteria->condition = "plan_id = " . $plan_id;
            $plan_price = Price::model()->findAll($criteria);
            $currency_default = $currency->getDefaultCurrency();

            // Get Currency CODE
            if (isset($_POST['Subscription']['currency_code']) && $_POST['Subscription']['currency_code'] != '') $currency_code = $_POST['Subscription']['currency_code'];
            else $currency_code = $currency_default;
            
            $plan = Plan::model()->findByPk($plan_id);

            // Return Data
            $priceCurrencyModel = new PriceCurrency();
            foreach ($plan_price as $price) {
                // trial
                if($plan->is_trial){
                    $name = $price->billing_cycle . 'Month';
                }else{
                    $priceCurrency = $priceCurrencyModel->getPriceCurrency($price->id, $currency_code);
                    $name = $price->billing_cycle . 'Month - ' . $currency->formatCurrency($priceCurrency->price, $priceCurrency->currency_code);
                }
                echo CHtml::tag('option', array('value' => $price->billing_cycle), CHtml::encode($name), true);
            }
            Yii::app()->end();
        } else {
            $this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('create'));
        }
    }

    public function actionAjaxList($id = null)
    {
        if (Yii::app()->getRequest()->getIsAjaxRequest()) {
            header('Content-Type: application/json; charset="UTF-8"');
            $callback = $_GET['callback'];
            $criteria = new CDbCriteria;
            $criteria->select = 'id, shop_name';

            if ($id) {
                // Search
                $criteria->condition = 'id=' . $id; //.;
                // Data
                $store = Store::model()->find($criteria);
                // Output
                $json = CJavaScript::jsonEncode($store);
                echo $callback . '(' . $json . ')';
                Yii::app()->end();
            } else {
                // Search email
                $q = '';
                if(isset($_GET['q'])){
                    $q = $_GET['q'];
                }
                $criteria->addSearchCondition('shop_name', $q);
                // Limit record
                $page_limit = 0;
                if(isset($_GET['page_limit'])){
                    $page_limit = $_GET['page_limit'];
                }
                $page = 0;
                if(isset($_GET['page'])){
                    $page = $_GET['page'];
                }
                $total = Store::model()->count($criteria);
                $pages = new CPagination($total);
                $pages->pageSize = $page_limit;
                $pages->currentPage = $page;
                $pages->applyLimit($criteria);
                // List data
                $store = Store::model()->findAll($criteria);
                // Output
                $data = array(
                    'total' => $total,
                    'store' => $store,
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
        } else {
            $this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('create'));
        }
    }

    protected function createS3Folder($storeId)
    {
        $s3 = new s3( awsAccessKey, awsSecretKey );
        $bucketName = "etaipro";
        $environment = Yii::app()->params['environment'];
        $dir = "s3://{$bucketName}/{$environment}/{$environment}store_{$storeId}/image/";


        $excludeFolders = array(
            "../data/_etai/skeleton/image/flags",
        );

        if(!file_exists("s3://{$bucketName}/{$environment}/")){
            $s3->putObject( '', $bucketName, "{$environment}/" );
        }

        $localDir = "../data/_etai/skeleton/image";

        $files = glob("{$localDir}/*");

        $key = 0;

        while($key < count($files)){
            $file = $files[$key];
            if ( !in_array( $file, $excludeFolders ) ) {
                $path = "s3://{$bucketName}/{$environment}/{$environment}store_{$storeId}/image/" . str_replace( '../data/_etai/skeleton/image/', '', $file );
                $path2 = "{$environment}/{$environment}store_{$storeId}/image/" . str_replace( '../data/_etai/skeleton/image/', '', $file ) . '/';
                if ( is_dir( $file ) ) {

                    $files = array_merge( $files, glob( "{$file}/*" ) );
                    $s3->putObject( $file, $bucketName, $path2 );
                }
                else {
                    copy( $file, $path );
                }
            }
            $key++;
        }
    }

    public function actionCreateS3Folder()
    {
        $storeId = 2;
        $bucketName = "etaiprotest";
        $s3 = new s3( awsAccessKey, awsSecretKey );
        $environment = Yii::app()->params['environment'];
        $dir = "s3://{$bucketName}/{$environment}/{$environment}store_{$storeId}/image/";


        $excludeFolders = array(
            "../data/_etai/skeleton/image/flags",
        );

        if(!file_exists("s3://{$bucketName}/{$environment}/")){
            $s3->putObject( '', $bucketName, "{$environment}/" );
        }

        $localDir = "../data/_etai/skeleton/image";

        $files = glob("{$localDir}/*");

        $key = 0;

        while($key < count($files)){
            $file = $files[$key];
            if ( !in_array( $file, $excludeFolders ) ) {
                $path = "s3://{$bucketName}/{$environment}/{$environment}store_{$storeId}/image/" . str_replace( '../data/_etai/skeleton/image/', '', $file );
                $path2 = "{$environment}/{$environment}store_{$storeId}/image/" . str_replace( '../data/_etai/skeleton/image/', '', $file ) . '/';
                if ( is_dir( $file ) ) {

                    $files = array_merge( $files, glob( "{$file}/*" ) );
                    $s3->putObject( $file, $bucketName, $path2 );
                }
                else {
                    copy( $file, $path );
                }
            }
            $key++;
        }
    }
}
