<?php

class SocialAccountsController extends RController
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
                   'actions' => array( 'index', 'view', 'facebookConnect', 'twitterConnect', 'checkUserPin' ),
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
     * Lists all models.
     */
    public function actionIndex()
    {
        $dataProvider = new SettingConfig();
        $data         = $dataProvider->findAllByAttributes( array( 'group' => 'social_accounts' ) );

        $social_accounts_info = array();

        $default = array(
            'facebook_app_id'         => '',
            'facebook_app_secret'     => '',
            'facebook_page_id'        => '',
            'facebook_access_token'        => '',
            'facebook_page_access_token'        => '',
            'twitter_consumer_key'    => '',
            'twitter_consumer_secret' => '',
            'twitter_access_token'    => '',
            'twitter_access_secret'   => '',
            'pinterest_username'      => '',
            'pinterest_password'      => '',
            'pinterest_board'         => '',
        );

        if ( !empty( $data ) ) {
            foreach ( $data as $item ) {
                $item                                   = $item->attributes;
                $social_accounts_info[ $item[ 'key' ] ] = $item[ 'value' ];
            }
        }

        $social_accounts_info = array_merge( $default, $social_accounts_info );

        // Uncomment the following line if AJAX validation is needed
        // $this->performAjaxValidation($model);
        if ( isset( $_POST[ 'social_accounts' ] ) ) {
            $dataProvider->deleteAllByAttributes( array( 'group' => 'social_accounts' ) );
            $tmp_data                                        = $_POST[ 'social_accounts' ];
            Yii::app()->session[ 'button_connect_facebook' ] = $_POST[ 'button_connect_facebook' ];
            foreach ( $tmp_data as $key => $social_account ) {
                $save_data         = array(
                    'group'      => 'social_accounts',
                    'key'        => $key,
                    'value'      => $social_account,
                    'serialized' => 0
                );
                $model             = new SettingConfig();
                $model->attributes = $save_data;
                $model->save();
            }

            $storeModel = new Store();
            $allStore = $storeModel->findAll();

            if(is_array($allStore) && count($allStore) > 0){
                foreach($allStore as $store){
                    $model->updateSocialAccounts($store->id);
                }
            }

            $this->redirect( 'SocialAccounts' );
        }
        else {
            $button_connect_facebook = Yii::app()->session[ 'button_connect_facebook' ];
            Yii::app()->session[ 'button_connect_facebook' ] = 0;
            $tai_facebook = new TaiFacebook();
            $this->render( 'index', array(
                'dataProvider'            => $social_accounts_info,
                'button_connect_facebook' => $button_connect_facebook,
                'fb_permission'           => implode( ",", $tai_facebook->getPermission() ),
            ) );
        }

    }

    public function actionFacebookConnect()
    {
        $json = array();

        if ( isset( $_POST ) && count($_POST) ) {
            $tai_facebook = new TaiFacebook();

            $tai_facebook->setAppId( $_POST[ 'etai_facebook_app_id' ] );
            $tai_facebook->setAppSecret( $_POST[ 'etai_facebook_app_secret' ] );
            $tai_facebook->setPageId( $_POST[ 'etai_facebook_page_id' ] );

            $tai_facebook->connectFacebook();
            $long_access_token                                                = $tai_facebook->genarateLongToken( $_POST[ 'facebook_access_token' ] );
            $fb_page_access_token                                             = $tai_facebook->pageAccessToken( $long_access_token );
            $facebook_data[ 'facebook_info' ][ 'facebook_access_token' ]      = $long_access_token;
            $facebook_data[ 'facebook_info' ][ 'facebook_page_access_token' ] = $fb_page_access_token;
            $facebook_data[ 'facebook_info' ][ 'facebook_account' ]           = $_POST[ 'facebook_account' ];

//            foreach ( $facebook_data[ 'facebook_info' ] as $key => $data ) {
//                $save_data         = array(
//                    'group'      => 'social_accounts',
//                    'key'        => $key,
//                    'value'      => $data,
//                    'serialized' => 0
//                );
//                $model             = new SettingConfig();
//                $model->attributes = $save_data;
//                $model->save();
//            }

            echo json_encode($facebook_data[ 'facebook_info' ]);
        }
    }

    public function actionTwitterConnect()
    {
        if ( isset( $_POST[ 'social_accounts' ] ) ) {
            $twitter_consumer_key    = $_POST[ 'social_accounts' ][ 'twitter_consumer_key' ];
            $twitter_consumer_secret = $_POST[ 'social_accounts' ][ 'twitter_consumer_secret' ];
            $twitter_access_token    = $_POST[ 'social_accounts' ][ 'twitter_access_token' ];
            $twitter_access_secret   = $_POST[ 'social_accounts' ][ 'twitter_access_secret' ];

            $tai_twitter = new TaiTwitter( $twitter_consumer_key, $twitter_consumer_secret );

            $tai_twitter->setToken( $twitter_access_token );
            $tai_twitter->setSecret( $twitter_access_secret );

            $twitter_user = $tai_twitter->getTwitterUser();

            if ( !isset( $twitter_user->errors ) ) {
                echo FALSE;
            }
            else {
                echo json_encode( $twitter_user->errors );
            }
        }
    }

    public function actionCheckUserPin()
    {
        $json = array();

        if ( isset( $_POST[ 'social_accounts' ] ) ) {
            if ( trim( $_POST[ 'social_accounts' ][ 'pinterest_username' ] ) == '' ) {
                $json[ 'error' ][ 'pinterest_username' ] = 'Username can not be blank!';
            }

            if ( trim( $_POST[ 'social_accounts' ][ 'pinterest_password' ] ) == '' ) {
                $json[ 'error' ][ 'pinterest_password' ] = 'Password can not be blank!';
            }
        }
        else {
            $json[ 'error' ][ 'warning' ] = 'Please input Username and Password.';
        }

        if ( !$json ) {
            $tai_pinterest = new TaiPinterest();
            $tai_pinterest->setUsername( $_POST[ 'social_accounts' ][ 'pinterest_username' ] );
            $tai_pinterest->setPassword( $_POST[ 'social_accounts' ][ 'pinterest_password' ] );

            $boards = $tai_pinterest->getUserBoards();

            if ( $boards && $boards[ 'data' ] != NULL ) {
                $json[ 'boards' ] = $boards;
            }
        }

        if ( !$json ) {
            $json[ 'error' ][ 'warning' ] = 'Pinterest account invalid!';
        }

        echo json_encode( $json );
    }
}
