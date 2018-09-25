<?php

require_once($_SERVER['DOCUMENT_ROOT'] . "/library/Newsman/Client.php");

//Admin Controller
class ControllerExtensionModuleNewsman extends Controller
{
	private $error = array();

	private $restCall = "https://ssl.newsman.app/api/1.2/rest/{{userid}}/{{apikey}}/{{method}}";
	private $restCallParams = "https://ssl.newsman.app/api/1.2/rest/{{userid}}/{{apikey}}/{{method}}{{params}}";

	public
	function index()
	{
		$this->document->addStyle('./view/stylesheet/newsman.css');

		$this->editModule();
	}

	protected
	function editModule()
	{
		$this->load->model('setting/setting');

		$setting = $this->model_setting_setting->getSetting('newsman');

		$data = array();

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		$data["button_save"] = "Save / Import";

		if (isset($_POST["newsmanSubmit"]))
		{
			$this->restCall = str_replace("{{userid}}", $_POST["userid"], $this->restCall);
			$this->restCall = str_replace("{{apikey}}", $_POST["apikey"], $this->restCall);
			$this->restCall = str_replace("{{method}}", "list.all.json", $this->restCall);

			$settings = $setting;
			$settings["newsmanuserid"] = $_POST["userid"];
			$settings["newsmanapikey"] = $_POST["apikey"];

			$this->model_setting_setting->editSetting('newsman', $settings);

			$_data = json_decode(file_get_contents($this->restCall), true);

			$data["list"] = "";

			foreach ($_data as $list)
			{
				$data["list"] .= "<option value='" . $list["list_id"] . "'>" . $list["list_name"] . "</option>";
			}

			$data["message"] = "Credentials are valid";
		}

		if (isset($_POST["newsmanSubmitSaveList"]))
		{
			$settings = $setting;
			$settings["newsmanlistid"] = $_POST["list"];

			$this->model_setting_setting->editSetting('newsman', $settings);

			$data["message"] = "List is saved";
		}

		//List Import
		if (isset($_POST["newsmanSubmitList"]))
		{
			$this->restCallParams = str_replace("{{userid}}", $setting["newsmanuserid"], $this->restCallParams);
			$this->restCallParams = str_replace("{{apikey}}", $setting["newsmanapikey"], $this->restCallParams);
			$this->restCallParams = str_replace("{{method}}", "import.csv.json", $this->restCallParams);

			$client = new Newsman_Client($setting["newsmanuserid"], $setting["newsmanapikey"]);

			$csvdata = array();

			$this->load->model('customer/customer');
			$csvdata = $this->model_customer_customer->getCustomers();

			$csv = "email,name,source" . PHP_EOL;
			foreach ($csvdata as $row)
			{
				if($row["newsletter"] == "1" && $row["status"] == "1")
				{
					$csv .= $row['email'] . ","
						. $row['firstname'] . " " . $row["lastname"] . ","
						. "opencart3 newsman plugin"
						. PHP_EOL;
				}
			}

			$ret = $client->import->csv($setting["newsmanlistid"], array(), $csv);

			/*$this->restCallParams = str_replace("{{params}}", "?list_id=" . $_POST["list"] . "&segments=" . "&csv_data=" . $csv, $this->restCallParams);
die($this->restCallParams);
			$_data = json_decode(file_get_contents($this->restCallParams), true);
			*/

			$data["message"] = "Imported successfully";
		}
		//List Import

		$setting = $this->model_setting_setting->getSetting('newsman');

		if (!empty($setting["newsmanuserid"]) && !empty($setting["newsmanapikey"]))
		{
			$this->restCall = str_replace("{{userid}}", $setting["newsmanuserid"], $this->restCall);
			$this->restCall = str_replace("{{apikey}}", $setting["newsmanapikey"], $this->restCall);
			$this->restCall = str_replace("{{method}}", "list.all.json", $this->restCall);

			$_data = json_decode(file_get_contents($this->restCall), true);

			$data["list"] = "";

			foreach ($_data as $list)
			{
				if(!empty($setting["newsmanlistid"]) && $setting["newsmanlistid"] == $list["list_id"])
				{
					$data["list"] .= "<option selected value='" . $list["list_id"] . "'>" . $list["list_name"] . "</option>";
				}
				else{
					$data["list"] .= "<option value='" . $list["list_id"] . "'>" . $list["list_name"] . "</option>";
				}
			}

			$data["newsmanuserid"] = $setting["newsmanuserid"];
			$data["newsmanapikey"] = $setting["newsmanapikey"];
		}

		$htmlOutput = $this->load->view('extension/module/newsman', $data);
		$this->response->setOutput($htmlOutput);
	}

	public
	function validate()
	{
	}

	protected
	function install()
	{
		$this->load->model('setting/setting');
		$this->model_setting_setting->editSetting('newsman', ['newsman_status' => 1]);
	}

	protected
	function uninstall()
	{
		$this->load->model('setting/setting');
		$this->model_setting_setting->deleteSetting('newsman');
	}
}

?>
