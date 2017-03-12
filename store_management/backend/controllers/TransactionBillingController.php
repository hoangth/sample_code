<?php

class TransactionBillingController extends RController
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
    public function actionDownload($id)
    {
    	$model_report=ReportFinancial::model()->findByPk($id);
    	
    	$currency = new ECurrencyHelper();
    	$search = array(
    			'{base_url}',
    			'{firstname}',
    			'{lastname}',
    			'{address}',
    			'{telephone}',
    			'{email}',
    			'{shop_name}',
    			'{invoice_no}',
    			'{date}',
    			'{transaction_no}',
    			'{order_no}',
    			'{total_bill}',
    			'{charge_rate}',
    			'{total_charge}',
    			'{transaction_status}',
    			'{currency_code}',
    			'{left_symbol}',
    			'{right_symbol}',
    			'{discounted_left}',
    			'{discounted_right}',
    			'{total_charge_left}',
    			'{total_charge_right}',
    			'{final_charge_left}',
    			'{final_charge_right}',
    			'{paypal_email}'
    	);
    	$discounted=$currency->formatCurrencyToArray($model_report->discounted, $model_report->currency_code);
    	$charge=$currency->formatCurrencyToArray($model_report->charge, $model_report->currency_code);
    	$final_charge=$currency->formatCurrencyToArray($model_report->final_charge, $model_report->currency_code);
    	$left_symbol=$final_charge['left_symbol'];
    	$right_symbol=$final_charge['right_symbol'];
    	$replace = array(
    			Yii::app()->getBaseUrl(true),
    			$model_report->firstname,
    			$model_report->lastname,
    			$model_report->address,
    			$model_report->telephone,
    			$model_report->email,
    			$model_report->storename,
    			'ETAI '.($model_report->invoice_no + 1000),
    			SaasHelper::datetimeFormat($model_report->date, 'd M Y'),
    			$model_report->id,
    			$model_report->order_id,
    			$currency->formatCurrency($model_report->value, $model_report->currency_code),
    			$model_report->charge_rate."%",
    			$currency->formatCurrency($model_report->charge, $model_report->currency_code),
    			$model_report->status ? "Completed" : "Failed",
    			$model_report->currency_code,
    			$left_symbol,
    			$right_symbol,
    			$discounted['left'],
    			$discounted['right'],
    			$charge['left'],
    			$charge['right'],
    			$final_charge['left'],
    			$final_charge['right'],
    			substr($model_report->paypal_email,0,3)."X@XXXX".substr($model_report->paypal_email,-5)
    	);
    	
    	$body = str_replace($search, $replace, $this->renderPartial('pdf', array(), true));
    	//echo $body; die;
    	
        # You can easily override default constructor's params
        $mPDF1 = Yii::app()->ePdf->mpdf('', 'A4',0,'',1,1,1);
        
        $mPDF1->WriteHTML($body);

        # Outputs ready PDF
        $mPDF1->Output(); 
    }

    /**
     * Manages all models.
     */
    public function actionIndex()
    {
        $model = new ReportFinancial();
        if (isset($_GET['filter_store_id']) && $_GET['filter_store_id']) {
    		$rawData = $model->getReportData($_GET, 0, 'id DESC, order_id DESC');
        } else {
        	$rawData = array();
        }
        
        // convert to display format
        /* foreach ($rawData as &$value) {
        	$value['date'] = SaasHelper::dateFormat($value['date']);
        } */
        
        $data = new CArrayDataProvider($rawData, array(
        		'id' => 'report_financial',
        		'sort' => array(
        				'multiSort' => false,
        				'attributes' => array(
        						'id', 'order_id', 'value', 'charge_rate', 'final_charge', 'status', 'date'
        				),
        		),
        		'pagination' => array(
        				'pageSize' => Yii::app()->params['page_size']
        		),
        ));
            
        $this->render('index', array(
            'model' => $data,
        ));
    }
    
    /**
     * Performs the AJAX validation.
     * @param Subscription $model the model to be validated
     */
    protected function performAjaxValidation($model)
    {
        if (isset($_POST['ajax']) && $_POST['ajax'] === 'billing-invoice-form') {
            echo CActiveForm::validate($model);
            Yii::app()->end();
        }
    }
}
