<?php

/**
 * Created by PhpStorm.
 * User: Hoang
 * Date: 5/14/14
 * Time: 11:06 AM
 */
class BackgroundController extends EController
{

    public function actionIndex()
    {
//        $now   = new DateTime;
//        $clone = $now;        //this doesnot clone so:
//        $clone->modify( '-1 day' );
//
//        echo $now->format( 'd-m-Y' ), "\n", $clone->format( 'd-m-Y' );
//        echo '----', "\n";
//
//        // will print same.. if you want to clone make like this:
//        $now   = new DateTime;
//        $clone = clone $now;
//        $clone->modify( '-1 day' );
//
//        echo $now->format( 'd-m-Y' ), "\n", $clone->format( 'd-m-Y' );

        echo dirname(__FILE__);

    }

    public function actionIndexa()
    {
        $store = new Store();
        $data = array(
            'store_db' => 'etai_shop2',
            'store_prefix' => '',
            'store_username' => 'hoang',
            'store_password' => 'hoang',
            'store_email' => 'hoangscp@gmail.com',
            'store_url' => 'http://test1.dev',
            'store_title' => 'test1',
        );
        echo $store->installStore($data);
        echo $store->removeStore('etaidemo_test1');

        CFileHelper::copyDirectory('F:\Development\TechAtrium\SaaS\store-merchant-data\shop2', 'F:\Development\TechAtrium\SaaS\store-merchant-data\shop3');
        CFileHelper::removeDirectory('F:\Development\TechAtrium\SaaS\store-merchant-data\shop3');

        //exit();
        $job = Yii::app()->background->start(array('test/testbackground'));
        echo "Progress: <div id='test'></div>";
        echo '<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>';
        echo CHtml::script("$(function(){ setInterval(function(){ $('#test').load('" . $this->createUrl('test/getStatus', array('id' => $job)) . "');}, 1000);});");
        Yii::app()->end();
    }

    public function actionGetStatus($id)
    {
        echo json_encode(Yii::app()->background->getStatus($id));
        Yii::app()->end();
    }

    public function actionTestbackground()
    {
        Yii::app()->background->update(1);
        echo "Job started.";
        sleep(3);
        Yii::app()->background->update(20);
        sleep(3);
        Yii::app()->background->update(40);
        echo "Job in progress.";
        sleep(3);
        Yii::app()->background->update(60);
        sleep(3);
        Yii::app()->background->update(80);
        sleep(3);
        echo "Job done.";
        Yii::app()->end();
    }

    public function actionReload()
    {
        $a = $this->loadModel('Store', 21)->updateStore();
        echo $a->email;
        //$a = $this->loadModel('Store',10)->updateStore()->find("owner = 1");
        //print_r($a);
    }
}

?>