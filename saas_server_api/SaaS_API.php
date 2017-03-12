<?php
// First, include Requests
include('library/Requests.php');
// Next, make sure Requests can load internal classes
Requests::register_autoloader();
class SaaSApplication {

	private $host;
	private $port;
	private $protocol;
	private $username;
	private $password;
	private $key;

	public function __construct($host, $username, $password, $key, $port = "80", $protocol = "http://") {
		$this->host = $host;
		$this->port = $port;
		$this->protocol = $protocol;
		$this->username = $username;
		$this->password = $password;
		$this->key = $key;
	}

	protected function generatePassword() {
		return md5(time() . microtime(true) . mt_rand());
	}

	protected function checkIfAllParamsGood($params, $required) {
		$i = count($required);
		$j = 0;
		foreach($required as $key){
			if(array_key_exists($key, $params) && $params[$key] != '')
				$j++;
		}
		if($i == $j)
			return true;
		else
			return false;
	}

	protected function callServer(array $params = array()) {
		// Query string action
		$queryString = $this->buildQueryString($params);
		// Login to api
		$options = array(
			'auth' => array($this->username, $this->password)
		);
		// Access key
		$header = array(
			'SAAS_AUTH_KEY' => $this->key
		);

		$url = $this->protocol . $this->host . ":" . $this->port . "/api/index.php?" . $queryString;
		$response = Requests::get($url, $header, $options);
		return $response->body;
	}

	private function buildQueryString(array $query = array(), $questionMark = false) {
		if (empty($query)) {
			return "";
		}
		if (!is_array($query)) {
			return "";
		}
		if ($questionMark) {
			$queryString = "?";
		} else {
			$queryString = "";
		}
		foreach ($query as $key => $value) {
			if ($queryString !== "?" && $queryString !== "") {
				$queryString .= "&";
			}
			if (is_numeric($key)) {
				$queryString .= $value . "=";
			} else {
				$queryString .= $key . "=" . $value;
			}
		}
		$queryString .= '&json=1&multiline';
		return $queryString;
	}
}

class SaaSInstance extends SaaSApplication {

	public function create_domain(array $input = array()) {
		$required = array(
			'domain',
			'plan',
			'title',
			'username',
			'password',
			'email'
		);

		if ($this->checkIfAllParamsGood($input, $required)) {
			$params = array(
				'program' => 'create-domain',
				'domain' => $input['domain'],
				'plan' => $input['plan'],
				'field-config_title' => $input['title'],
				'field-admin_username' => $input['username'],
				'field-admin_password' => $input['password'],
				'field-admin_email' => $input['email'],
				'pass' => $this->generatePassword(),
				'mysql' => '',
				'web' => '',
				'template' => 'Default Settings',
				'unix' => '',
				'dir' => '',
				'logrotate' => '',
			);
			return $this->callServer($params);
		} else {
			return json_encode(array(
				'error' => 'Missing params',
				'status' => 'failure',
				'required-params' => $required
			));
		}
	}

	public function delete_domain(array $input = array()) {
		$required = array(
			'domain'
		);

		if ($this->checkIfAllParamsGood($input, $required)) {
			$params = array(
				'program' => 'delete-domain',
				'domain' => $input['domain']
			);
			return $this->callServer($params);
		} else {
			return json_encode(array(
				'error' => 'Missing params',
				'status' => 'failure',
				'required-params' => $required
			));
		}
	}

	public function disable_domain(array $input = array()) {
		$required = array(
			'domain'
		);

		if ($this->checkIfAllParamsGood($input, $required)) {
			$params = array(
				'program' => 'disable-domain',
				'domain' => $input['domain']
			);
			return $this->callServer($params);
		} else {
			return json_encode(array(
				'error' => 'Missing params',
				'status' => 'failure',
				'required-params' => $required
			));
		}
	}

	public function enable_domain(array $input = array()) {
		$required = array(
			'domain'
		);

		if ($this->checkIfAllParamsGood($input, $required)) {
			$params = array(
				'program' => 'enable-domain',
				'domain' => $input['domain']
			);
			return $this->callServer($params);
		} else {
			return json_encode(array(
				'error' => 'Missing params',
				'status' => 'failure',
				'required-params' => $required
			));
		}
	}

	public function modify_domain(array $input = array()) {
		$required = array(
			'domain'
		);

		if ($this->checkIfAllParamsGood($input, $required)) {
			$params = array(
				'program' => 'modify-domain',
				'domain' => $input['domain']
			);
			if (isset($input['plan']) && $input['plan'] != '') $params['apply-plan'] = $input['plan'];
			if (isset($input['newdomain']) && $input['newdomain'] != '') $params['newdomain'] = $input['newdomain'];
			return $this->callServer($params);
		} else {
			return json_encode(array(
				'error' => 'Missing params',
				'status' => 'failure',
				'required-params' => $required
			));
		}
	}

	public function validate_domain(array $input = array()) {
		$required = array(
			'domain'
		);

		if ($this->checkIfAllParamsGood($input, $required)) {
			$params = array(
				'program' => 'validate-domains',
				'domain' => $input['domain'],
				'all-features' => '',
			);
			return $this->callServer($params);
		} else {
			return json_encode(array(
				'error' => 'Missing params',
				'status' => 'failure',
				'required-params' => $required
			));
		}
	}

	public function list_domain(array $input = array()) {
		$params = array(
			'program' => 'list-domains'
		);
		if (isset($input['plan']) && $input['plan'] != '') $params['plan'] = $input['plan'];
		if (isset($input['domain'])&& $input['domain'] != '') $params['domain'] = $input['domain'];

		return $this->callServer($params);
	}

	public function bandwidth_domain(array $input = array()) {
		$params = array(
			'program' => 'list-bandwidth'
		);

		if (isset($input['domain']) && $input['domain'] != '') {
			$params['domain'] = $input['domain'];
		} else {
			$params['all-domains'] = '';
		}

		if (isset($input['start']) && $input['start'] != '') $params['start'] = $input['start'];
		if (isset($input['end']) && $input['end'] != '') $params['end'] = $input['end'];

		return $this->callServer($params);
	}

}

class SaaSPlans extends SaaSApplication {

	public function create_plan(array $input = array()) {
		$required = array(
			'name',
			'max-bw',
			'quota'
		);

		if ($this->checkIfAllParamsGood($input, $required)) {
			$params = array(
				'program' => 'create-plan',
				'name' => $input['name'],
				'max-bw' => $input['max-bw']*1024*1024,
				'quota' => $input['quota']*1024,
				'admin-quota' => $input['quota'],
				'features' => 'web dir mysql',
				'max-mailbox' => '0',
				'max-alias' => '0',
				'max-dbs' => '1',
				'max-doms' => '0',
				'max-aliasdoms' => '0',
				'max-realdoms' => '0'
			);
			return $this->callServer($params);
		} else {
			return json_encode(array(
				'error' => 'Missing params',
				'status' => 'failure',
				'required-params' => $required
			));
		}
	}

	public function delete_plan(array $input = array()) {
		$required = array(
			'name'
		);

		if ($this->checkIfAllParamsGood($input, $required)) {
			$params = array(
				'program' => 'delete-plan',
				'name' => $input['name']
			);
			return $this->callServer($params);
		} else {
			return json_encode(array(
				'error' => 'Missing params',
				'status' => 'failure',
				'required-params' => $required
			));
		}
	}

	public function modify_plan(array $input = array()) {
		$required = array(
			'name'
		);

		if ($this->checkIfAllParamsGood($input, $required)) {
			$params = array(
				'program' => 'modify-plan',
				'apply' => '',
				'name' => $input['name']
			);
			if (isset($input['new-name']) && $input['new-name'] != '') $params['new-name'] = $input['new-name'];
			if (isset($input['quota']) && $input['quota'] != '') $params['quota'] = $input['quota']*1024;
			if (isset($input['quota']) && $input['quota'] != '') $params['admin-quota'] = $input['quota']*1024;
			if (isset($input['max-bw']) && $input['max-bw'] != '') $params['max-bw'] = $input['max-bw']*1024*1024;

			return $this->callServer($params);
		} else {
			return json_encode(array(
				'error' => 'Missing params',
				'status' => 'failure',
				'required-params' => $required
			));
		}
	}

	public function list_plan(array $input = array()) {
		$params = array(
			'program' => 'list-plans'
		);
		if (isset($input['name']) && $input['name'] != '') $params['name'] = $input['name'];
		return $this->callServer($params);
	}

}

