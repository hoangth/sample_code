<?php

require( '../extensions/SimpleHTMLDom/simple_html_dom.php' );

class SettingConfigController extends RController
{
	/**
	 * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
	 * using two-column layout. See 'protected/views/layouts/column2.php'.
	 */
	public  $layout = '//layouts/main';
	private $error  = array();


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
	public function actionUpdate()
	{
		$dataProvider = new SettingConfig();
		$data         = $dataProvider->findByAttributes( array( 'group' => 'payment', 'key' => 'payment_config' ) );
		$setting_id   = isset( $data->attributes[ 'setting_id' ] ) ? $data->attributes[ 'setting_id' ] : '0';

		if ( !empty( $data->attributes[ 'value' ] ) ) {
			$data = unserialize( $data->attributes[ 'value' ] );
		}
		else {
			$data = array();
		}

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if ( isset( $_POST[ 'payment_config' ] ) ) {

			$tmp_data = $_POST[ 'payment_config' ];
			//validate post data
			$sort_order = array();

			foreach ( $tmp_data as $key => $value ) {
				$sort_order[ $key ] = $value[ 'sort_order' ];
			}

			array_multisort( $sort_order, SORT_ASC, $tmp_data );
			//
			if ( $this->validateUpdateData( $tmp_data ) ) {
				$save_data = array(
					'group'      => 'payment',
					'key'        => 'payment_config',
					'value'      => serialize( $tmp_data ),
					'serialized' => 1
				);
				if ( !( $data ) ) {
					$model             = new SettingConfig();
					$model->attributes = $save_data;
					$model->save();
				}
				else {
					$model             = $this->loadModel( 'SettingConfig', $setting_id );
					$model->attributes = $save_data;
					$model->save();
				}
				$this->redirect( 'index' );
			}
			else {

				$this->render( 'update', array(
					'dataProvider' => $tmp_data,
					'error'        => $this->error
				) );
			}
		}
		else {
			$this->render( 'update', array(
				'dataProvider' => $data,
			) );
		}

	}


	/**
	 * Lists all models.
	 */
	public function actionIndex()
	{
		$dataProvider = new SettingConfig();

		$data = $dataProvider->findByAttributes( array( 'group' => 'payment', 'key' => 'payment_config' ) );

		if ( !empty( $data->attributes[ 'value' ] ) ) {
			$data = unserialize( $data->attributes[ 'value' ] );
		}
		else {
			$data = array();
		}

		$model_currency   = new Currency();
		$currency_default = $model_currency->findByAttributes( array( 'status' => 1, 'default' => 1 ) );

		if ( !empty( $currency_default->attributes ) ) {
			$currency_sign = $currency_default->attributes[ 'symbol' ];
		}
		else {
			$currency_sign = '';
		}

		//        $ocbc_exchange_rate = $this->actionGetExchangeRateFromOCBC();

		$ocbcExchangeRateData = array();
		$ocbcExchangeRate     = new OcbcExchangeRate;
		foreach ( $ocbcExchangeRate->findAll() as $row ) {
			$ocbcExchangeRateData[ ] = array(
				'code' => $row->code,
				'rate' => number_format( $row->rate, 4 ),
			);
		}

		$exchangeRateData = array();
		$exchangeRate     = new ExchangeRate;
		foreach ( $exchangeRate->findAll() as $row ) {
			$exchangeRateData[ ] = array(
				'code' => $row->code,
				'rate' => number_format( $row->rate, 4 ),
			);
		}

		$auto_update  = $dataProvider->findByAttributes( array( 'group' => 'exchange_rate', 'key' => 'auto_update' ) );
		$last_updated = $dataProvider->findByAttributes( array( 'group' => 'exchange_rate', 'key' => 'last_updated' ) );

		if ( $auto_update ) {
			$auto_update = $auto_update->value;
		}
		else {
			$auto_update = 0;
		}

		if ( $last_updated ) {
			$last_updated = $last_updated->value;
		}
		else {
			$last_updated = '';
		}

		$this->render( 'index', array(
			'dataProvider'            => $data,
			'currency_default_symbol' => $currency_sign,
			'ocbc_exchange_rate'      => $ocbcExchangeRateData,
			'exchange_rate'           => $exchangeRateData,
			'auto_update'             => $auto_update,
			'last_updated'            => $last_updated,
			'error'                   => $this->error,
		) );
	}

	/**
	 * Performs the AJAX validation.
	 * @param SettingConfig $model the model to be validated
	 */
	protected function performAjaxValidation( $model )
	{
		if ( isset( $_POST[ 'ajax' ] ) && $_POST[ 'ajax' ] === 'setting-config-form' ) {
			echo CActiveForm::validate( $model );
			Yii::app()->end();
		}
	}

	protected function validateUpdateData( &$data )
	{
		$rows_num = count( $data );
		for ( $i = 0; $i < $rows_num; $i++ ) {
			if ( $i > 0 ) {
				if ( $data[ $i ][ 'max' ] <= $data[ $i - 1 ][ 'max' ] ) {
					$data[ $i ][ 'error' ]    = 'Invalid Number';
					$this->error[ 'warning' ] = 'Please correct your input';
				}
			}
			if ( $data[ $i ][ 'fee' ] < 0 || $data[ $i ][ 'fee' ] > 100 ) {
				$data[ $i ][ 'error' ]    = 'Invalid Number';
				$this->error[ 'warning' ] = 'Please correct your input';
			}
		}

		if ( $this->error ) {
			return FALSE;
		}
		else {
			for ( $i = 1; $i < $rows_num; $i++ ) {
				$data[ $i ][ 'fee' ] = round( $data[ $i ][ 'fee' ], 2 );
				if ( $i > 0 ) {
					$data[ $i ][ 'min' ] = $data[ $i - 1 ][ 'max' ] + 0.01;
				}
			}

			return TRUE;
		}
	}

	public function actionGetExchangeRateFromOCBC()
	{
		$error = FALSE;

		Yii::log( 'actionGetExchangeRateFromOCBC', 'error' );
		$html = file_get_html( 'http://ocbc.com.sg/rates/daily_price_fxx.html' );

		$objects = $html->find( 'table.MsoNormalTable', 2 );

		$i    = 0;
		$data = array();

		if ( !count( $objects ) ) {
			$error[ 'code' ] = '001';
			$error[ 'msg' ]  = 'URL or HTML is error! Cannot get Exchange Rate table.';
		}


		if ( !$error ) {
			foreach ( $objects->find( 'table tr' ) as $tr ) {
				if ( $i != 0 && $i != 1 ) {
					$td = $tr->find( 'td' );

					$code = trim( $td[ 0 ]->plaintext );
					$rate = $td[ 3 ]->plaintext / (int)$td[ 1 ]->plaintext;

					if ( $code == '' || !is_numeric( $rate ) ) {
						$error[ 'code' ] = '002';
						$error[ 'msg' ]  = 'Exchange Rate data is error!';
					}

					$data[ $i ] = array(
						'code' => trim( $td[ 0 ]->plaintext ),
						'rate' => number_format( $td[ 3 ]->plaintext / (int)$td[ 1 ]->plaintext, 4 ),
					);
				}
				$i++;
			}

			$ocbcExchangeRate = new OcbcExchangeRate;
			$ocbcExchangeRate->deleteAll();

			foreach ( $data as $row ) {
				$ocbcExchangeRate             = new OcbcExchangeRate;
				$ocbcExchangeRate->primaryKey = $row[ 'code' ];
				$ocbcExchangeRate->rate       = $row[ 'rate' ];
				$ocbcExchangeRate->save();
			}

			$dataProvider = new SettingConfig();
			$auto_update  = $dataProvider->findByAttributes( array( 'group' => 'exchange_rate', 'key' => 'auto_update' ) );

			if ( $auto_update && $auto_update->value ) {
				$exchangeRate = new ExchangeRate;
				$exchangeRate->deleteAll();
				foreach ( $data as $row ) {
					$exchangeRate             = new ExchangeRate;
					$exchangeRate->primaryKey = $row[ 'code' ];
					$exchangeRate->rate       = $row[ 'rate' ];
					$exchangeRate->save();
				}
				$exchangeRate->updateExchangeTime();
			}
		}

		if ( $error ) {
			$exchangeRate = new ExchangeRate;
			$exchangeRate->updateAutoUpdateExchangeRate( 0 );
			$mail = new Email();
			$mail->emailExchangeRateFailure( $error );

			return $error;
		}

		return $data;
	}

	public function actionUpdateExchangeRateToEtai()
	{
		$ocbcExchangeRate = new OcbcExchangeRate;
		$data             = $ocbcExchangeRate->findAll();

		$exchangeRate = new ExchangeRate;
		$exchangeRate->deleteAll();

		foreach ( $data as $row ) {
			$exchangeRate             = new ExchangeRate;
			$exchangeRate->primaryKey = $row->code;
			$exchangeRate->rate       = number_format( $row->rate, 8 );
			$exchangeRate->save();
		}

		$exchangeRate->updateExchangeTime();

		$this->redirect( 'index' );
	}

	public function actionUpdateExchangeRate()
	{
		if ( isset( $_POST[ 'exchange_rate' ] ) ) {
			$exchangeRate = new ExchangeRate;
			$exchangeRate->deleteAll();
			foreach ( $_POST[ 'exchange_rate' ] as $index => $row ) {
				$exchangeRate = new ExchangeRate;
				$exchangeRate->primaryKey = $row[ 'code' ];
				$exchangeRate->rate       = number_format( $row[ 'rate' ], 8 );
				$exchangeRate->save();

			}
			$exchangeRate->updateExchangeTime();
		}

		if ( isset( $_POST[ 'auto_update' ] ) ) {
			$exchangeRate = new ExchangeRate;
			$auto         = $_POST[ 'auto_update' ] == 'on' ? 1 : 0;
			$exchangeRate->updateAutoUpdateExchangeRate( $auto );
		}

		$this->redirect( 'index' );
	}
}
