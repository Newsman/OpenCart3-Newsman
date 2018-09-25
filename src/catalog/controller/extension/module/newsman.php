<?php
//Catalog Controller

require_once($_SERVER['DOCUMENT_ROOT'] . "/library/Newsman/Client.php");

class ControllerExtensionmoduleNewsman extends Controller
{

	private $restCallParams = "https://ssl.newsman.app/api/1.2/rest/{{userid}}/{{apikey}}/{{method}}{{params}}";

	public function index()
	{
		$data = array();

		$this->load->model('setting/setting');

		$setting = $this->model_setting_setting->getSetting('newsman');

		//List Import
		if ($_GET["cron"] == "true")
		{
			$this->restCallParams = str_replace("{{userid}}", $setting["newsmanuserid"], $this->restCallParams);
			$this->restCallParams = str_replace("{{apikey}}", $setting["newsmanapikey"], $this->restCallParams);
			$this->restCallParams = str_replace("{{method}}", "import.csv.json", $this->restCallParams);

			$client = new Newsman_Client($setting["newsmanuserid"], $setting["newsmanapikey"]);

			$csvdata = array();

			$csvdata = $this->getCustomers();

			$csv = "email,name,source" . PHP_EOL;
			foreach ($csvdata as $row)
			{
				if ($row["newsletter"] == "1" && $row["status"] == "1")
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
		}
		//List Import

		return $this->load->view('extension/module/newsman', $data);
	}

	public function getCustomers($data = array())
	{
		$sql = "SELECT *, CONCAT(c.firstname, ' ', c.lastname) AS name, cgd.name AS customer_group FROM " . DB_PREFIX . "customer c LEFT JOIN " . DB_PREFIX . "customer_group_description cgd ON (c.customer_group_id = cgd.customer_group_id)";

		if (!empty($data['filter_affiliate']))
		{
			$sql .= " LEFT JOIN " . DB_PREFIX . "customer_affiliate ca ON (c.customer_id = ca.customer_id)";
		}

		$sql .= " WHERE cgd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

		$implode = array();

		if (!empty($data['filter_name']))
		{
			$implode[] = "CONCAT(c.firstname, ' ', c.lastname) LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
		}

		if (!empty($data['filter_email']))
		{
			$implode[] = "c.email LIKE '" . $this->db->escape($data['filter_email']) . "%'";
		}

		if (isset($data['filter_newsletter']) && !is_null($data['filter_newsletter']))
		{
			$implode[] = "c.newsletter = '" . (int)$data['filter_newsletter'] . "'";
		}

		if (!empty($data['filter_customer_group_id']))
		{
			$implode[] = "c.customer_group_id = '" . (int)$data['filter_customer_group_id'] . "'";
		}

		if (!empty($data['filter_affiliate']))
		{
			$implode[] = "ca.status = '" . (int)$data['filter_affiliate'] . "'";
		}

		if (!empty($data['filter_ip']))
		{
			$implode[] = "c.customer_id IN (SELECT customer_id FROM " . DB_PREFIX . "customer_ip WHERE ip = '" . $this->db->escape($data['filter_ip']) . "')";
		}

		if (isset($data['filter_status']) && $data['filter_status'] !== '')
		{
			$implode[] = "c.status = '" . (int)$data['filter_status'] . "'";
		}

		if (!empty($data['filter_date_added']))
		{
			$implode[] = "DATE(c.date_added) = DATE('" . $this->db->escape($data['filter_date_added']) . "')";
		}

		if ($implode)
		{
			$sql .= " AND " . implode(" AND ", $implode);
		}

		$sort_data = array(
			'name',
			'c.email',
			'customer_group',
			'c.status',
			'c.ip',
			'c.date_added'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data))
		{
			$sql .= " ORDER BY " . $data['sort'];
		} else
		{
			$sql .= " ORDER BY name";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC'))
		{
			$sql .= " DESC";
		} else
		{
			$sql .= " ASC";
		}

		if (isset($data['start']) || isset($data['limit']))
		{
			if ($data['start'] < 0)
			{
				$data['start'] = 0;
			}

			if ($data['limit'] < 1)
			{
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$query = $this->db->query($sql);

		return $query->rows;
	}

}

?>
