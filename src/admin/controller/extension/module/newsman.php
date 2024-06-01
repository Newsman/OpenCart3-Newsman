<?php

require_once($_SERVER['DOCUMENT_ROOT'] . "/system/library/Newsman/Client.php");

//Admin Controller
class ControllerExtensionModuleNewsman extends Controller
{
	private $error = array();

	private $restCall = "https://ssl.newsman.app/api/1.2/rest/{{userid}}/{{apikey}}/{{method}}";
	private $restCallParams = "https://ssl.newsman.app/api/1.2/rest/{{userid}}/{{apikey}}/{{method}}{{params}}";

	public
	function index()
	{
		error_reporting(0);
		ini_set('display_errors', 1);
		ini_set('display_startup_errors', 1);
		error_reporting(E_ALL);
		$this->document->addStyle('./view/stylesheet/newsman.css');

		$this->editModule();
	}

	protected
	function editModule()
	{
		$this->load->model('setting/setting');

		$this->isOauth($data);

		$data = array();
		$data["message"] = "Credentials are valid";
		$error = false;

		$setting = $this->model_setting_setting->getSetting('newsman');

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		$data["button_save"] = "Save / Import";

		if (isset($_POST["newsmanSubmit"]))
		{
			$this->restCall = "https://ssl.newsman.app/api/1.2/rest/{{userid}}/{{apikey}}/{{method}}";
			$this->restCall = str_replace("{{userid}}", $_POST["userid"], $this->restCall);
			$this->restCall = str_replace("{{apikey}}", $_POST["apikey"], $this->restCall);
			$this->restCall = str_replace("{{method}}", "list.all.json", $this->restCall);

			$settings = $setting;
			$settings["newsmanuserid"] = $_POST["userid"];
			$settings["newsmanapikey"] = $_POST["apikey"];

			$allowAPI = "off";

			if(!empty($_POST["allowAPI"]))
				$allowAPI = "on";
		
			$settings["newsmanallowAPI"] = $allowAPI;

			$this->model_setting_setting->editSetting('newsman', $settings);

			try{
				$_data = $this->curlGet($this->restCall);
			
				if($_data != false)
					$_data = json_decode($_data, true);
				else
					$error = true;
			}
			catch(Exception $e)
			{
				$data["message"] .= PHP_EOL . "An error occurred, credentials might not be valid.";
				$error = true;
			}

			$data["list"] = "";

			if(!$error)
			{
				foreach ($_data as $list)
				{
					$data["list"] .= "<option value='" . $list["list_id"] . "'>" . $list["list_name"] . "</option>";
				}
			}
		}

		if (isset($_POST["newsmanSubmitSaveList"]))
		{
			$settings = $setting;
			$settings["newsmanlistid"] = $_POST["list"];

			$this->model_setting_setting->editSetting('newsman', $settings);

			$data["message"] .= PHP_EOL . "List is saved";
		}

		if (isset($_POST["newsmanSubmitSaveSegment"]))
		{
			$settings = $setting;
			$settings["newsmansegment"] = $_POST["segment"];

			$this->model_setting_setting->editSetting('newsman', $settings);

			$data["message"] .= PHP_EOL . "Segment is saved";
		}

		//List Import
		if (isset($_POST["newsmanSubmitList"]))
		{
			$this->restCallParams = "https://ssl.newsman.app/api/1.2/rest/{{userid}}/{{apikey}}/{{method}}{{params}}";
			$this->restCallParams = str_replace("{{userid}}", $setting["newsmanuserid"], $this->restCallParams);
			$this->restCallParams = str_replace("{{apikey}}", $setting["newsmanapikey"], $this->restCallParams);
			$this->restCallParams = str_replace("{{method}}", "import.csv.json", $this->restCallParams);
			$client = new Newsman_Client($setting["newsmanuserid"], $setting["newsmanapikey"]);
            
			$csvdata = array();
			$this->load->model('customer/customer');
			$csvdata = $this->model_customer_customer->getCustomers(array("filter_newsletter" => 1));
            
			if (empty($csvdata))
			{
				$data["message"] .= "\nNo customers in your store";
			}

			//Import
			$batchSize = 9999;
			$customers_to_import = array();
			$segments = null;
			if ($setting["newsmansegment"] != "1" && $setting["newsmansegment"] != null)
			{
				$segments = array($setting["newsmansegment"]);
			}

			foreach ($csvdata as $item)
			{	
				if($item["newsletter"] == "0")
				{
					continue;
				}

				$customers_to_import[] = array(
					"email" => $item["email"],
					"firstname" => $item["firstname"],
					"lastname" => $item["lastname"]
				);			

				if ((count($customers_to_import) % $batchSize) == 0)
				{
					$this->_importData($customers_to_import, $setting["newsmanlistid"], $client, $segments);
				}
			}
			if (count($customers_to_import) > 0)
			{
				$this->_importData($customers_to_import, $setting["newsmanlistid"], $client, $segments);
			}	
			
			unset($customers_to_import);

			$data["message"] .= PHP_EOL . "Customer Newsletter subscribers imported successfully";
		}
		//List Import

		$setting = $this->model_setting_setting->getSetting('newsman');
		if (!empty($setting["newsmanuserid"]) && !empty($setting["newsmanapikey"]) && $error == false)
		{
			$this->restCall = str_replace("{{userid}}", $setting["newsmanuserid"], $this->restCall);
			$this->restCall = str_replace("{{apikey}}", $setting["newsmanapikey"], $this->restCall);
			$this->restCall = str_replace("{{method}}", "list.all.json", $this->restCall);
			$this->restCallParams = "https://ssl.newsman.app/api/1.2/rest/{{userid}}/{{apikey}}/{{method}}{{params}}";
			$this->restCallParams = str_replace("{{userid}}", $setting["newsmanuserid"], $this->restCallParams);
			$this->restCallParams = str_replace("{{apikey}}", $setting["newsmanapikey"], $this->restCallParams);

            try{
                $_data = $this->curlGet($this->restCall);
            
                if($_data != false)
                    $_data = json_decode($_data, true);
                else
                    $error = true;
            }
			catch(Exception $e){
				$data["message"] .= PHP_EOL . "An error occurred, credentials might not be valid.";
				$error = true;
			}

			$data["list"] = "";
			$data["segment"] = "";
			$data["segment"] .= "<option value='1'>No segment</option>";
            
			if(!$error)
			{
				foreach ($_data as $list)
				{
					if (!empty($setting["newsmanlistid"]) && $setting["newsmanlistid"] == $list["list_id"])
					{
						$data["list"] .= "<option selected value='" . $list["list_id"] . "'>" . $list["list_name"] . "</option>";
						$this->restCallParams = str_replace("{{method}}", "segment.all.json", $this->restCallParams);
						$this->restCallParams = str_replace("{{params}}", "?list_id=" . $setting["newsmanlistid"], $this->restCallParams);
                        
						$_data = json_decode($this->curlGet($this->restCallParams), true);

						foreach ($_data as $segment)
						{
							$segmentId = isset($segment["segment_id"]) ? $segment["segment_id"] : "1";
							$segmentName = isset($segment["segment_name"]) ? $segment["segment_name"] : "";

							if (array_key_exists("newsmansegment", $setting) && isset($setting["newsmansegment"]) && isset($segment["segment_id"]) && $setting["newsmansegment"] == $segment["segment_id"])
							{
								$data["segment"] .= "<option selected value='" . $segmentId . "'>" . $segmentName . "</option>";
							} else
							{
								$data["segment"] .= "<option value='" . $segmentId . "'>" . $segmentName . "</option>";
							}
						}
					} else
					{
						$data["list"] .= "<option value='" . $list["list_id"] . "'>" . $list["list_name"] . "</option>";
					}
				}
			}
		}

		$data["userid"] = (empty($setting["newsmanuserid"])) ? "" : $setting["newsmanuserid"];
		$data["apikey"] = (empty($setting["newsmanapikey"])) ? "" : $setting["newsmanapikey"];

		$_allowAPI = "";

		if(!empty($setting["newsmanallowAPI"]) && $setting["newsmanallowAPI"] == "on"){
			$_allowAPI = "checked";
		}

		$data["allowAPI"] = $_allowAPI;

		if($error)
			$data["message"] .= PHP_EOL . "An error occurred, credentials might not be valid.";

		$this->isOauth($data);
        
		$htmlOutput = $this->load->view('extension/module/newsman', $data);
		$this->response->setOutput($htmlOutput);
	}

	public function isOauth(&$data, $checkOnlyIsOauth = false){
		$this->load->model('setting/setting');

		$redirUri = urlencode("https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
		$redirUri = str_replace("amp%3B", "", $redirUri);
		$data["oauthUrl"] = "https://newsman.app/admin/oauth/authorize?response_type=code&client_id=nzmplugin&nzmplugin=Opencart&scope=api&redirect_uri=" . $redirUri;

		//oauth processing

		$error = "";
		$dataLists = array();
		$data["oauthStep"] = 1;
		$viewState = array();

		if(!empty($_GET["error"])){
			switch($error){
				case "access_denied":
					$error = "Access is denied";
					break;
				case "missing_lists":
					$error = "There are no lists in your NewsMAN account";
					break;
			}
		}else if(!empty($_GET["code"])){

			$authUrl = "https://newsman.app/admin/oauth/token";

			$code = $_GET["code"];

			$redirect = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

			$body = array(
				"grant_type" => "authorization_code",
				"code" => $code,
				"client_id" => "nzmplugin",
				"redirect_uri" => $redirect
			);

			$ch = curl_init($authUrl);

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

			$response = curl_exec($ch);

			if (curl_errno($ch)) {
				$error .= 'cURL error: ' . curl_error($ch);
			}

			curl_close($ch);

			if ($response !== false) {

				$response = json_decode($response);

				$data["creds"] = json_encode(array(
					"newsman_userid" => $response->user_id,
					"newsman_apikey" => $response->access_token
					)
				);

				foreach($response->lists_data as $list => $l){
					$dataLists[] = array(
						"id" => $l->list_id,
						"name" => $l->name
					);
				}	

				$data["dataLists"] = $dataLists;

				$data["oauthStep"] = 2;
			} else {
				$error .= "Error sending cURL request.";
			}  
		}

		if(!empty($_POST["oauthstep2"]) && $_POST['oauthstep2'] == 'Y')
		{
			if(empty($_POST["newsman_list"]) || $_POST["newsman_list"] == 0)
			{
				$step = 1;
			}
			else
			{
				$creds = stripslashes($_POST["creds"]);
				$creds = html_entity_decode($creds);
				$creds = json_decode($creds, true);

				$client = new Newsman_Client($creds["newsman_userid"], $creds["newsman_apikey"]);

				$ret = $client->remarketing->getSettings($_POST["newsman_list"]);

				$remarketingId = $ret["site_id"] . "-" . $ret["list_id"] . "-" . $ret["form_id"] . "-" . $ret["control_list_hash"];

				//set feed
				$url = "https://" . $_SERVER['SERVER_NAME'] . "/index.php?route=extension/module/newsman&newsman=products.json&nzmhash=" . $creds["newsman_apikey"];		

				try{
					$ret = $client->feeds->setFeedOnList($_POST["newsman_list"], $url, $_SERVER['SERVER_NAME'], "NewsMAN");	
				}
				catch(Exception $ex)
				{			
					//the feed already exists
				}

				$settings = $this->model_setting_setting->getSetting('newsman');
				$settings['newsmanlistid'] = $_POST["newsman_list"];
				$settings['newsmanapikey'] = $creds["newsman_apikey"];
				$settings['newsmanuserid'] = $creds["newsman_userid"];

				$this->model_setting_setting->editSetting('newsman', $settings);

				$settings = [
					"analytics_newsmanremarketing" . '_register' => "newsmanremarketing",
					"analytics_newsmanremarketing" . '_trackingid' => $remarketingId,
					'analytics_newsmanremarketing' . '_status' => 1
				];

				$this->model_setting_setting->editSetting("analytics_newsmanremarketing", $settings);
			}
		}

		$settings = $this->model_setting_setting->getSetting('newsman');

		if(empty($settings['newsmanapikey']))
		{
			$data["isOauth"] = true;
		}
		else{
			$data["isOauth"] = false;
		}
	}

	public function validate()
	{
	}

	public static function safeForCsv($str)
	{
		return '"' . str_replace('"', '""', $str) . '"';
	}

	public function _importData(&$data, $list, $client, $segments = null)
	{
        $csv = '"email","firstname","lastname","source"' . PHP_EOL;

        $source = self::safeForCsv("opencart 3 customer subscribers newsman plugin");
        foreach ($data as $_dat) {
            $csv .= sprintf(
                "%s,%s,%s,%s",
                self::safeForCsv($_dat["email"]),
                self::safeForCsv($_dat["firstname"]),
                self::safeForCsv($_dat["lastname"]),
                $source
            );
            $csv .= PHP_EOL;
		}
		
		$ret = null;
		try
		{
			$ret = $client->import->csv($list, $segments, $csv);
			if ($ret == "")
			{
				throw new Exception("Import failed");
			}
		} catch (Exception $e)
		{
		}
		$data = array();
	}

	public function install()
	{
		$this->load->model('setting/setting');

		$setting['module_newsman_status'] = 1;

		$this->model_setting_setting->editSetting('module_newsman', $setting);
	}

	public function uninstall()
	{
		$this->load->model('setting/setting');
		$this->model_setting_setting->deleteSetting('newsman');
	}

	function curlGet($url) {
		$ch = curl_init();
	
		curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);       
	
		$data = curl_exec($ch);
		curl_close($ch);
	
		return $data;
	}
}

?>
