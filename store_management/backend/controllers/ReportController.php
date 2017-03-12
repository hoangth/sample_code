<?php
Yii::import('ext.saas.*');
Yii::import('ext.ECurrencyHelper.*');

class ReportController extends RController
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

    public function actionIndex()
    {
        // read list file report
        $model = new Report('search');
        $model->unsetAttributes(); // clear any default values
        if (isset($_GET['Report']))
            $model->attributes = $_GET['Report'];
        $this->render('//serverReport/index', array(
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
        $model = $this->loadModel('Report', $id);
        $model->delete();
        $file = Yii::app()->basePath . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . $model->file_name;
        if (file_exists($file)) {
            unlink($file);
        }
        // if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
        if (!isset($_GET['ajax']))
            $this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));
    }

    public function actionCheckPid($id)
    {
        $server = new SaaSReport(Yii::app()->params['saas']);
        $saas = json_decode($server->serverPidStatus($_GET['pid']));
        if ($saas->status == 'success') {
            if ($saas->pid == 'Completed') {
                $report = Report::model()->findByPk($id);
                $report->status = '1';
                $report->save(); // save the change to database
            } // else Running
            $status = $saas->pid;
        } else {
            $status = "Error";
        }
        echo $status;
        Yii::app()->end();
    }

    public function actionPlans()
    {
        // overview count
        $report = new Report;
        // get plan
        $model_plan = new Plan;
        
        $sales = $report->planSales($_GET);
        // render
        $this->render('plans', array(
            'overview' => $report->SaaSOverview(),
            'sales' => $sales,
            'plans' => $model_plan->findAll(),
        ));
    }

    public function actionPlanDetails()
    {
        $model = new Report;
        if (!isset($_GET['report_date'])) $_GET['report_date'] = date('m-Y');
        $model->attributes = $_GET;
        $rangeYear = $model->rangeYear();
        $report = false;
        $date = explode("-", $_GET['report_date']);
        $date = array(
            'month' => $date[0],
            'year' => $date[1]
        );
        if (isset($_GET['id']) && $_GET['id'] > 0) {
            $id = $_GET['id'];
            $currencyCode = $_GET['currency_code'];
            $billingCycle = $_GET['billing_cycle'];
            $billingCountry = $_GET['billing_country'];
            if ($model->validate(array('id', 'report_date'))) {
                $date = explode("-", $_GET['report_date']);
                $month = $date[0];
                $year = $date[1];
                $report = $model->planSaleDetails($id, $month, $year, $currencyCode, $billingCycle, $billingCountry);
                $date = array(
                    'month' => $month,
                    'year' => $year
                );
            } else {
                $month = date('m');
                $year = date('Y');
                $report = $model->planSaleDetails($id, $month, $year, $currencyCode, $billingCycle, $billingCountry);
            }
        } else {
            $this->redirect(array('plans'));
        }
        $this->render('plan_details', array("rangeYear" => $rangeYear, 'date' => $date, 'report' => $report, 'errors' => $model->getErrors()));
    }

    public function actionStores()
    {
       $this->displayReport('stores');
    }

    public function actionBuyers()
    {
        $this->displayReport('buyers');
    }

    public function actionBuyerDetails()
    {
        if (isset($_GET['email'])) {
            $this->displayReport('buyer_details');
        } else {
            $this->redirect(array("buyers"));
        }
    }

    public function actionOrderDetails()
    {
        $this->displayReport('order_details');
    }

    protected function statusRender($model, $row)
    {
        //get the view from the address CRUD controller (generated with gii)
        if ($model->status == 1) {
            $status = "Completed";
        } else {
            $status = "Running";
        }
        return $this->renderPartial('//serverReport/status_pid', array('model' => $model, 'row' => $row, 'status' => $status), true); //set $return = true, don't display direct
    }

    private function displayReport($type) {
        $model = new Report;
        if (!isset($_GET['report_date'])){
            $_GET['report_date'] = date('m-Y');
        }
        if (!isset($_GET['store_id'])){
            $_GET['store_id'] = 0;
        }
        $model->attributes = $_GET;
        // set range year
        $rangeYear = $model->rangeYear();
        $date = array(
            'month' => date('m'),
            'year' => date('Y')
        );
        $report = false;
        $report_generate = null;
        // validate report_date
        if ($model->validate(array('report_date'))) {
            // set month year
            $date = explode("-", $_GET['report_date']);
            $date = array(
                'month' => $date[0],
                'year' => $date[1]
            );
                
                // get report record
            $report_generate = $model->getReportByDate($_GET['report_date']);
            
            if ($report_generate instanceof Report && $report_generate->status == 1) {
                $report_generate->reportUpdateRate();
                switch ($type) {
                    case 'stores':
                        $report = $report_generate->reportStores($_GET['store_id']);
                        break;
                    case 'buyers':
                        $report = $report_generate->reportBuyers($_GET['store_id']);
                        break;
                    case 'order_details':
                        $report = $report_generate->reportOrderDetails($_GET['order_id']);
                        break;
                    case 'buyer_details':
                        $report = $report_generate->reportBuyerDetails($_GET['email']);
                        break;
                }
            }else{
                Yii::app()->user->setFlash('warning', 'Report not found.');
            }
            $this->render($type, array("rangeYear" => $rangeYear, 'date' => $date, 'report' => $report, 'report_generate' => $report_generate, 'errors' => $model->getErrors()));
        }else{
            Yii::app()->user->setFlash('error', 'Invalid report date.');
            $this->render($type, array("rangeYear" => $rangeYear, 'date' => $date, 'report' => $report, 'report_generate' => $report_generate, 'errors' => $model->getErrors()));
        }
    }

    private function generateReport($date){
        // generate report input
        $server = new SaaSReport(Yii::app()->params['saas']);
        $data = array(
            'type' => 'orders',
            'report_date' => $date
        );
        // request to saas server
        $saas = json_decode($server->serverReport($data));
        // check data response
        if ($saas->status == 'success') {
            // create new record report and delete all old report
            $model = new Report;
            $model->type = 'orders';
            $model->file_name = $saas->file_name;
            $model->report_date = $date;
            $model->pid = (int)$saas->pid;
            $model->status = 0;
            if ($model->save()) {
                $file = Yii::app()->basePath . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . $model->file_name;
                if (file_exists($file)) {
                    unlink($file);
                }
                Report::model()->deleteAll(
                    'report_date = :report_date AND type = :type AND id < :id',
                    array(
                        ":report_date" => $model->report_date,
                        ":type" => $model->type,
                        ":id" => $model->id
                    )
                );
                Yii::app()->user->setFlash('warning', $model->report_date . ' is being created, please wait for completed.');
            }
        } else {
            Yii::app()->user->setFlash('error', $saas->error);
        }
    }

    private function checkTime($report_generate){
        // check time
        date_default_timezone_set('Asia/Singapore');
        $report_date = explode("-", $report_generate->report_date);
        $report_date = new DateTime($report_date[1] . '-' . $report_date[0] . '-00 00:00:00');
        $report_created = new DateTime($report_generate->created_at);
        $report_now = new DateTime("now");
        $interval = $report_date->diff($report_created);
        $month = $interval->format('%r%m');
        $year = $interval->format('%r%y');

        if ($month > 0 || $year > 0) {
            $time = true;
        } else {
            $interval = $report_created->diff($report_now);
            $day = $interval->format('%r%a');
            $hour = $interval->format('%r%h');
            if ($day == 0 && $hour <= 1) {  // 1 hour for test
                $time = true;
                Yii::app()->user->setFlash('notify','This report was created at: ' . $report_generate->created_at);
            } else {
                $time = false;
            }
        }
        return $time;
    }
}
