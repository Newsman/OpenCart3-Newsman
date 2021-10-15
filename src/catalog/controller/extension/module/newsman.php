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

        $cron = (empty($_GET["cron"]) ? "" : $_GET["cron"]);

        //List Import
        if (!empty($cron)) {
            $this->restCallParams = str_replace("{{userid}}", $setting["newsmanuserid"], $this->restCallParams);
            $this->restCallParams = str_replace("{{apikey}}", $setting["newsmanapikey"], $this->restCallParams);
            $this->restCallParams = str_replace("{{method}}", "import.csv.json", $this->restCallParams);

            $client = new Newsman_Client($setting["newsmanuserid"], $setting["newsmanapikey"]);

            $csvdata = $this->getCustomers();

            if (empty($csvdata)) {
                $data["message"] = PHP_EOL . "No customers in your store";
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode($data["message"]));
                return;
            }
            //Import
            $batchSize = 5000;
            $customers_to_import = array();
            $segments = null;
            if ($setting["newsmansegment"] != "1" && $setting["newsmansegment"] != null) {
                $segments = array($setting["newsmansegment"]);
            }
            foreach ($csvdata as $item) {
                $customers_to_import[] = array(
                    "email" => $item["email"],
                    "firstname" => $item["firstname"],
                    "lastname" => $item["lastname"]
                );
                if ((count($customers_to_import) % $batchSize) == 0) {
                    $this->_importData($customers_to_import, $setting["newsmanlistid"], $segments, $client);
                }
            }
            if (count($customers_to_import) > 0) {
               $this->_importData($customers_to_import, $setting["newsmanlistid"], $segments, $client);
            }

            unset($customers_to_import);

            echo "Cron successfully executed";

        }
        else {
            $allowAPI = $setting["newsmanallowAPI"];
            if(empty($allowAPI) || $allowAPI != "on")
            {
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode("403"));           
                return;
            }

            $this->newsmanFetchData($setting["newsmanapikey"]);
        }

        return $this->load->view('extension/module/newsman', $data);
    }

    public function newsmanFetchData($_apikey)
    {
        $apikey = (empty($_GET["apikey"])) ? "" : $_GET["apikey"];
        $newsman = (empty($_GET["newsman"])) ? "" : $_GET["newsman"];
        $start = (!empty($_GET["start"]) && $_GET["start"] >= 0) ? $_GET["start"] : "";
        $limit = (empty($_GET["limit"])) ? "" : $_GET["limit"];
        $startLimit = array();

        if (!empty($newsman) && !empty($apikey)) {
            $apikey = $_GET["apikey"];
            $currApiKey = $_apikey;

            if ($apikey != $currApiKey) {
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode("403"));
                return;
            }

            if(!empty($start) && $start >= 0 && !empty($limit))
                $startLimit = array(
                    "start" => $start,
                    "limit" => $limit
                );

            switch ($_GET["newsman"]) {
                case "orders.json":

                    $ordersObj = array();

                    $this->load->model('catalog/product');
                    $this->load->model('checkout/order');

                    $orders = $this->getOrders(array("start" => $start, "limit" => $limit));                    
                    
                    if(!empty($orderId))
                    {
                        $orders = $this->model_checkout_order->getOrder($orderId);                        
                        $orders = array(
                            $orders
                        );
                    }                    

                    foreach ($orders as $item) {

                        $products = $this->getProductsByOrder($item["order_id"]);
                        $productsJson = array();

                        foreach ($products as $prodOrder) {
                            
                            $prod = $this->model_catalog_product->getProduct($prodOrder["product_id"]);

                            $image = "";

                            if(!empty($prod["image"]))
                            {
                                $image = explode(".", $prod["image"]);
                                $image = $image[1];  
                                $image = str_replace("." . $image, "-500x500" . '.' . $image, $prod["image"]);    
                                $image = 'https://' . $_SERVER['SERVER_NAME'] . '/image/cache/' . $image;                                
                            }

                            $productsJson[] = array(
                                "id" => $prodOrder['product_id'],
                                "name" => $prodOrder['name'],
                                "quantity" => $prodOrder['quantity'],
                                "price" => $prodOrder['price'],
                                "price_old" => (empty($prodOrder["special"]) ? "" : $prodOrder["special"]),
                                "image_url" => $image,
                                "url" => 'https://' . $_SERVER['SERVER_NAME'] . '/index.php?route=product/product&product_id=' . $prodOrder["product_id"]
                            );
                        }

                        $ordersObj[] = array(
                            "order_no" => $item["order_id"],
                            "date" => "",
                            "status" => "",
                            "lastname" => "",
                            "firstname" => $item["firstname"],
                            "email" => $item["email"],
                            "phone" => "",
                            "state" => "",
                            "city" => "",
                            "address" => "",
                            "discount" => "",
                            "discount_code" => "",
                            "shipping" => "",
                            "fees" => 0,
                            "rebates" => 0,
                            "total" => $item["total"],
                            "products" => $productsJson
                        );
                    }

					$this->response->addHeader('Content-Type: application/json');
					$this->response->setOutput(json_encode($ordersObj, JSON_PRETTY_PRINT));
                    return;

                    break;

                case "products.json":

                    $this->load->model('catalog/product');

                    $products = $this->model_catalog_product->getProducts(array("start" => $start, "limit" => $limit));

                    if(!empty($productId))
                    {
                        $products = $this->model_catalog_product->getProduct($productId);
                        $products = array(
                            $products
                        );
                    }

                    $productsJson = array();

                    foreach ($products as $prod) {

                        $image = "";

                        //price old special becomes price
                        $price = (!empty($prod["special"])) ? $prod["special"] : $prod["price"];
                        //price becomes price old
                        $priceOld = (!empty($prod["special"])) ? $prod["price"] : "";

                        if(!empty($prod["image"]))
                        {
                            $image = explode(".", $prod["image"]);
                            $image = $image[1];  
                            $image = str_replace("." . $image, "-500x500" . '.' . $image, $prod["image"]);    
                            $image = 'https://' . $_SERVER['SERVER_NAME'] . '/image/cache/' . $image;                                
                        }

                        $productsJson[] = array(
                            "id" => $prod["product_id"],
                            "name" => $prod["name"],
                            "stock_quantity" => $prod["quantity"],
                            "price" => $price,
                            "price_old" => $priceOld,
                            "image_url" => $image,
                            "url" => 'https://' . $_SERVER['SERVER_NAME'] . '/index.php?route=product/product&product_id=' . $prod["product_id"]
                        );
                    }

					$this->response->addHeader('Content-Type: application/json');
					$this->response->setOutput(json_encode($productsJson, JSON_PRETTY_PRINT));
                    return;

                    break;

                case "customers.json":

                    $wp_cust = $this->getCustomers(
                        $startLimit
                    );
                    $custs = array();

                    foreach ($wp_cust as $users) {
                        $custs[] = array(
                            "email" => $users["email"],
                            "firstname" => $users["name"],
                            "lastname" => ""
                        );
                    }

                    $this->response->addHeader('Content-Type: application/json');
                    $this->response->setOutput(json_encode($custs, JSON_PRETTY_PRINT));
                    return;

                    break;

                case "subscribers.json":

                    if(!empty($startLimit))
                    {
                        $startLimit = array(
                            "start" => $start,
                            "limit" => $limit,
                            "filter_newsletter" => 1
                        );
                    }
                    else{
                        $startLimit = array(
                            "filter_newsletter" => 1
                        );
                    }
            
                    $wp_subscribers = $this->getCustomers($startLimit);
                    $subs = array();

                    foreach ($wp_subscribers as $users) {
                        $subs[] = array(
                            "email" => $users["email"],
                            "firstname" => $users["name"],
                            "lastname" => ""
                        );
                    }

                    $this->response->addHeader('Content-Type: application/json');
                    $this->response->setOutput(json_encode($subs, JSON_PRETTY_PRINT));
                    return;

                    break;
                case "version.json":
                    $version = array(
                    "version" => "Opencart 3"
                    );

                    $this->response->addHeader('Content-Type: application/json');
                            $this->response->setOutput(json_encode($version, JSON_PRETTY_PRINT));
                    return;
            
                    break;

            }
        } else {
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode("403"));
        }
    }

    public function getOrders($data = array())
    {
        $sql = "SELECT o.order_id, o.email, o.firstname, (SELECT os.name FROM " . DB_PREFIX . "order_status os WHERE os.order_status_id = o.order_status_id AND os.language_id = '" . (int)$this->config->get('config_language_id') . "') AS order_status, o.shipping_code, o.total, o.currency_code, o.currency_value, o.date_added, o.date_modified FROM `" . DB_PREFIX . "order` o";

        if (isset($data['filter_order_status'])) {
            $implode = array();

            $order_statuses = explode(',', $data['filter_order_status']);

            foreach ($order_statuses as $order_status_id) {
                $implode[] = "o.order_status_id = '" . (int)$order_status_id . "'";
            }

            if ($implode) {
                $sql .= " WHERE (" . implode(" OR ", $implode) . ")";
            }
        } else {
            $sql .= " WHERE o.order_status_id > '0'";
        }

        if (!empty($data['filter_order_id'])) {
            $sql .= " AND o.order_id = '" . (int)$data['filter_order_id'] . "'";
        }

        if (!empty($data['filter_customer'])) {
            $sql .= " AND CONCAT(o.firstname, ' ', o.lastname) LIKE '%" . $this->db->escape($data['filter_customer']) . "%'";
        }

        if (!empty($data['filter_date_added'])) {
            $sql .= " AND DATE(o.date_added) = DATE('" . $this->db->escape($data['filter_date_added']) . "')";
        }

        if (!empty($data['filter_date_modified'])) {
            $sql .= " AND DATE(o.date_modified) = DATE('" . $this->db->escape($data['filter_date_modified']) . "')";
        }

        if (!empty($data['filter_total'])) {
            $sql .= " AND o.total = '" . (float)$data['filter_total'] . "'";
        }

        $sort_data = array(
            'o.order_id',
            'customer',
            'order_status',
            'o.date_added',
            'o.date_modified',
            'o.total'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY o.order_id";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getProductsByOrder($order_id)
    {
        $order_product_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "'");

        return $order_product_query->rows;
    }

    public function getProducts($startLimit)
    {
        $query;
        if(!empty($startLimit)){
            $query = "SELECT * FROM " . DB_PREFIX . "product LIMIT {$startLimit['limit']} OFFSET {$startLimit['start']}";
        }
        else{
            $query = "SELECT * FROM " . DB_PREFIX . "product";
        }

        $order_product_query = $this->db->query($query);

        return $order_product_query->rows;
    }


    public function getSubscribers()
    {
        $sql = "SELECT * FROM " . DB_PREFIX . "newsletter";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function _importDatas(&$data, $list, $segments = null, $client)
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
        try {
            $ret = $client->import->csv($list, $segments, $csv);

            if ($ret == "") {
                throw new Exception("Import failed");
            }
        } catch (Exception $e) {

        }

        $data = array();
    }

    public static function safeForCsv($str)
    {
        return '"' . str_replace('"', '""', $str) . '"';
    }

    public function _importData(&$data, $list, $segments = null, $client)
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
        try {
            $ret = $client->import->csv($list, $segments, $csv);

            if ($ret == "") {
                throw new Exception("Import failed");
            }
        } catch (Exception $e) {

        }

        $data = array();
    }

    public function getCustomers($data = array())
    {
        $sql = "SELECT *, CONCAT(c.firstname, ' ', c.lastname) AS name, cgd.name AS customer_group FROM " . DB_PREFIX . "customer c LEFT JOIN " . DB_PREFIX . "customer_group_description cgd ON (c.customer_group_id = cgd.customer_group_id)";

        if (!empty($data['filter_affiliate'])) {
            $sql .= " LEFT JOIN " . DB_PREFIX . "customer_affiliate ca ON (c.customer_id = ca.customer_id)";
        }

        $sql .= " WHERE cgd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

        $implode = array();

        if (!empty($data['filter_name'])) {
            $implode[] = "CONCAT(c.firstname, ' ', c.lastname) LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }

        if (!empty($data['filter_email'])) {
            $implode[] = "c.email LIKE '" . $this->db->escape($data['filter_email']) . "%'";
        }

        if (isset($data['filter_newsletter']) && !is_null($data['filter_newsletter'])) {
            $implode[] = "c.newsletter = '" . (int)$data['filter_newsletter'] . "'";
        }

        if (!empty($data['filter_customer_group_id'])) {
            $implode[] = "c.customer_group_id = '" . (int)$data['filter_customer_group_id'] . "'";
        }

        if (!empty($data['filter_affiliate'])) {
            $implode[] = "ca.status = '" . (int)$data['filter_affiliate'] . "'";
        }

        if (!empty($data['filter_ip'])) {
            $implode[] = "c.customer_id IN (SELECT customer_id FROM " . DB_PREFIX . "customer_ip WHERE ip = '" . $this->db->escape($data['filter_ip']) . "')";
        }

        if (isset($data['filter_status']) && $data['filter_status'] !== '') {
            $implode[] = "c.status = '" . (int)$data['filter_status'] . "'";
        }

        if (!empty($data['filter_date_added'])) {
            $implode[] = "DATE(c.date_added) = DATE('" . $this->db->escape($data['filter_date_added']) . "')";
        }

        if ($implode) {
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

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY name";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }

}

?>

