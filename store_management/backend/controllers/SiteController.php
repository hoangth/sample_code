<?php
/**
 * SiteController class
 *
 * @author Antonio Ramirez <amigo.cobos@gmail.com>
 * @link http://www.ramirezcobos.com/
 * @link http://www.2amigos.us/
 * @copyright 2013 2amigOS! Consultation Group LLC
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */
Yii::import('ext.saas.*');
class SiteController extends RController
{

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
     * Renders index
     */
    public function actionIndex()
    {
        // Status of server
        /*
        $server = new SaaSInfo(Yii::app()->params['saas']);
        $serverStatic = json_decode($server->serverStatus(),true);
        if(!is_array($serverStatic)){
            throw new CHttpException(400,"Can't connect to SaaS server.");
        }
        */

        // Instance Report
        $max_instance = Yii::app()->params['max_instance'];
        $used_instance = Store::model()->count();;
        $used_percent = ($used_instance*100/$max_instance);
        $instance = array(
            'used_percent' => $used_percent,
            'free_percent' => (100 - $used_percent),
            'used_instance' => $used_instance,
            'free_instance' => ($max_instance - $used_instance),
        );

        // Latest 10 stores
        $criteria = array(
            'order' => 'created_at DESC',
            'limit' => 10
        );
        $dataLatestStore = new CActiveDataProvider('Store',array('criteria' => $criteria));

        // Render
        $this->render('index',array(
            'dataLatestStore'=>$dataLatestStore,
            'instance'=>$instance,
            //'serverStatic'=>$serverStatic,
        ));
    }

    /**
     * Renders dashboard
     */
    public function actionDashboard()
    {
        $this->render('dashboard');
    }

    public function actionKeepAlive()
    {
        echo 'OK';
        Yii::app()->end();
    }

    /**
     * This is the action to handle external exceptions.
     */
    public function actionError()
    {
        if ($error = Yii::app()->errorHandler->error) {
            if (Yii::app()->request->isAjaxRequest)
                echo $error['message'];
            else
                $this->layout = 'error';
                $this->render('error', $error);
        }
    }
}