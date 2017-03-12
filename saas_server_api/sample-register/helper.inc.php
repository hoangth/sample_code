<?php
require_once "api.inc.php";
require_once "validator.inc.php";
function createStore($input) {
	$data = '';
	try {
		if($_SERVER['SERVER_NAME'] == 'register.ecweb.vn') {
			$domain = '.saas.ecweb.vn';
		} else {
			$domain = '.saas.techatrium.com';
		}
		$server = new virtualmin_remote_api('108.174.147.46', 'root', 'PQvZVHyA', '61380');
		$params = array(
			'domain' => $input['domain'] . $domain,
			'pass' => md5(time()),
			'plan' => 'TAIECOM',
			'field-config_title' => $input['title'],
			'field-admin_username' => $input['username'],
			'field-admin_password' => $input['password'],
			'field-admin_email' => $input['email'],
			'mysql' => '',
			'web' => '',
			'template' => 'Default Settings',
			'unix' => '',
			'dir' => '',
		);
		$response = $server->add_domain($params);
		if ($response->status == 'success') {

			$store = "http://" . $params['domain'];
			$admin = "http://" . $params['domain'] . "/admin";

			$data = "<p>Created store successful" . "</p>";
			$data .= "<p>Store : <a href='$store'>$store</a> </p>";
			$data .= "<p>Admin : <a href='$admin'>$admin</a> </p>";
			$data .= "<p>User :" . $params['field-admin_username'] . "</p>";
			$data .= "<p>Pass : " . $params['field-admin_password'] . "</p>";
			$data .= "<p>Thank you!</p>";
		}
	} catch (Exception $e) {
		return "<p>Create store failed: " . $e . "</p>";
	}
	return $data;
}

function validateStore($input) {
	$validator = new Validator($input);
	$validator->Expect("domain", "required, special");
	$validator->Expect("title", "required");
	$validator->Expect("username", "required, special");
	$validator->Expect("password", "required, special");
	$validator->Expect("email", "required, email");
	if ($validator->Validate()) {
		$_POST['title'] = mysql_real_escape_string($_POST['title']);
		return true;
	} else {
		return false;
	}
}
// init form
$form = true;;
if (isset($_POST['submit'])) {
	if (validateStore($_POST)) {
		$data = createStore($_POST);
		$form = false;
		$_POST = array();
	} else {
		$data = "<p>Data input not valid!</p>";
	}
} else {
	$data = "<p>You can sign up a store.</p>";
}