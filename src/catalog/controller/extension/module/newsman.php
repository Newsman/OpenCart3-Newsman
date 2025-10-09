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

        //cron
        if (!empty($cron)) {

            if(empty($setting["newsmanuserid"]) || empty($setting["newsmanapikey"]) || empty($setting["newsmantype"]))
            {
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode("403"));
                return;
            }

            $this->restCallParams = str_replace("{{userid}}", $setting["newsmanuserid"], $this->restCallParams);
            $this->restCallParams = str_replace("{{apikey}}", $setting["newsmanapikey"], $this->restCallParams);
            $this->restCallParams = str_replace("{{method}}", "import.csv.json", $this->restCallParams);

            $client = new Newsman_Client($setting["newsmanuserid"], $setting["newsmanapikey"]);
         
            $csvcustomers = $this->getCustomers();

            $csvdata = $this->getOrders();

            if (empty($csvdata)) {
                $data["message"] .= PHP_EOL . "No data present in your store";
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode($data));
                return;
            }

            $segments = null;

            if (array_key_exists("newsmansegment", $setting)) {
                if ($setting["newsmansegment"] != "1" && $setting["newsmansegment"] != null) {
                    $segments = array($setting["newsmansegment"]);
                }
            }

            //Import

            if ($setting["newsmantype"] == "customers") {
                
                //Customers who ordered
                
                $batchSize = 9000;

                $customers_to_import = array();

                foreach ($csvdata as $item) {
                    $customers_to_import[] = array(
                        "email" => $item["email"],
                        "firstname" => $item["firstname"]
                    );

                    if ((count($customers_to_import) % $batchSize) == 0) {
                        $this->_importData($customers_to_import, $setting["newsmanlistid"], $client, $segments);
                    }
                }

                if (count($customers_to_import) > 0) {
                    $this->_importData($customers_to_import, $setting["newsmanlistid"], $client, $segments);
                }

                unset($customers_to_import);
                
                //Customers who ordered

            } else {
                    $batchSize = 9000;

                    //Customers table

                    $customers_to_import = array();

                    foreach ($csvcustomers as $item) {
                        if ($item["newsletter"] == 0) {
                            continue;
                        }

                        $customers_to_import[] = array(
                            "email" => $item["email"],
                            "firstname" => $item["firstname"]
                        );

                        if ((count($customers_to_import) % $batchSize) == 0) {
                            $this->_importData($customers_to_import, $setting["newsmanlistid"], $segments, $client);
                        }
                    }

                    if (count($customers_to_import) > 0) {
                        $this->_importData($customers_to_import, $setting["newsmanlistid"], $segments, $client);
                    }

                    unset($customers_to_import);
                    
                    //Customers table

                    //Subscribers table
                    
                    try{
                    
                    $csvdata = $this->getSubscribers();

                    if (empty($csvdata)) {
                        $data["message"] .= PHP_EOL . "No subscribers in your store";
                        $this->response->addHeader('Content-Type: application/json');
                        $this->response->setOutput(json_encode($data["message"]));
                        return;
                    }

                    $batchSize = 9000;

                    $customers_to_import = array();

                    foreach ($csvdata as $item) {
                        $customers_to_import[] = array(
                            "email" => $item["email"]
                        );

                        if ((count($customers_to_import) % $batchSize) == 0) {
                            $this->_importDatas($customers_to_import, $setting["newsmanlistid"], $client, $segments);
                        }
                    }

                    if (count($customers_to_import) > 0) {
                        $this->_importDatas($customers_to_import, $setting["newsmanlistid"], $client, $segments);
                    }

                    unset($customers_to_import);
                    
                    }
                    catch(Exception $e)
                    {
                        echo "\nMissing " . DB_PREFIX . "newsletter table, continuing import without issues";
                    }
                    
                    //Subscribers table
                    
                    //OC journal framework table
                    
                    try{
                    
                    $csvdata = $this->getSubscribersOcJournal();

                    if (empty($csvdata)) {
                        $data["message"] .= PHP_EOL . "No subscribers in your store";
                        $this->response->addHeader('Content-Type: application/json');
                        $this->response->setOutput(json_encode($data["message"]));
                        return;
                    }

                    $batchSize = 9000;

                    $customers_to_import = array();

                    foreach ($csvdata as $item) {
                        $customers_to_import[] = array(
                            "email" => $item["email"]
                        );

                        if ((count($customers_to_import) % $batchSize) == 0) {
                            $this->_importDatas($customers_to_import, $setting["newsmanlistid"], $segments, $client);
                        }
                    }

                    if (count($customers_to_import) > 0) {
                        $this->_importDatas($customers_to_import, $setting["newsmanlistid"], $segments, $client);
                    }

                    unset($customers_to_import);
                    
                    }
                    catch(Exception $e)
                    {
                        echo "\nMissing oc_journal3_newsletter table, continuing import without issues";
                    }
                    
                    //OC journal framework table
            }
            //Import

            echo "Cron successfully done";
        }
        //cron
        //webhooks
        elseif(!empty($_GET["webhook"]) && $_GET["webhook"] == true)
        {
            $var = file_get_contents('php://input');

            $newsman_events = urldecode($var);
            $newsman_events = str_replace("newsman_events=", "", $newsman_events);
            $newsman_events = json_decode($newsman_events, true);

            foreach($newsman_events as $event)
            {
                if($event['type'] == "spam" || $event['type'] == "unsub")
                {
                    $sql = "UPDATE  " . DB_PREFIX . "customer SET `newsletter`='0' WHERE `email`='" . $event["data"]["email"] . "'";

                    $query = $this->db->query($sql);
                }
            }
        }
        elseif(!empty($_GET["newsman"]) && $_GET["newsman"] == "getCart.json")
        {
            $this->getCart();
        }
        else {

            //fetch
            if(!empty($_GET["newsman"]))
            {
                if(empty($setting["newsmanapiallow"]) || $setting["newsmanapiallow"] != "on")
                {
                    $this->response->addHeader('Content-Type: application/json');
                    $this->response->setOutput(json_encode("403"));
                    return;
                }

                $this->newsmanFetchData($setting["newsmanapikey"]);
            }
        }

        return $this->load->view('extension/module/newsman', $data);
    }

    public function getCart(){
        $prod = array();
        $cart = $this->cart->getProducts();
        
        foreach ( $cart as $cart_item_key => $cart_item ) {

            $prod[] = array(
                "id" => $cart_item['product_id'],
                "name" => $cart_item["name"],
                "price" => $cart_item["price"],
                "quantity" => $cart_item['quantity']
            );
                                    
         }
         
         header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
         header("Cache-Control: post-check=0, pre-check=0", false);
         header("Pragma: no-cache");
         header('Content-Type:application/json');
         echo json_encode($prod, JSON_PRETTY_PRINT);
         exit;
    }

    public function newsmanFetchData($_apikey)
    {
        $apikey = (empty($_GET["nzmhash"])) ? "" : $_GET["nzmhash"];
        $authorizationHeader = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';
        if (strpos($authorizationHeader, 'Bearer') !== false) {
            $apikey = trim(str_replace('Bearer', '', $authorizationHeader));
        }
        if(empty($apikey))
        {
            $apikey = empty($_POST['nzmhash']) ? '' : $_POST['nzmhash'];
        }        
        $newsman = (empty($_GET["newsman"])) ? "" : $_GET["newsman"];
        if(empty($newsman))
        {
            $newsman = empty($_POST['newsman']) ? '' : $_POST['newsman'];
        }        
        $productId = (empty($_GET["product_id"])) ? "" : $_GET["product_id"];
        $orderId = (empty($_GET["order_id"])) ? "" : $_GET["order_id"];
        $start = (!empty($_GET["start"]) && $_GET["start"] >= 0) ? $_GET["start"] : 1;
        $limit = (empty($_GET["limits"])) ? 1000 : $_GET["limits"];
        $imgWH = (empty($_GET["imgWH"])) ? "-500x500" : "-" . $_GET["imgWH"];
    
        if (!empty($newsman) && !empty($apikey)) {
            $currApiKey = $_apikey;

            if ($apikey != $currApiKey) {
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode("403"));
                return;
            }

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
                                $image = str_replace("." . $image, $imgWH . '.' . $image, $prod["image"]);
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

                    $products = $this->getProducts(array("start" => $start, "limit" => $limit, "sort" => "p.product_id"));
                    
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
                            $this->load->model('tool/image');
                            $image = $this->model_tool_image->resize($prod['image'], 500, 500);
                        }

                        $productsJson[] = array(
                            "id" => $prod["product_id"],
                            "name" => $prod["name"],
                            "stock_quantity" => $prod["quantity"],
                            "price" => $price,
                            "price_old" => $priceOld,
                            "image_url" => $image,
                            "url" => $this->url->link('product/product', 'product_id=' . $prod["product_id"])
                        );
                    }

                    $this->response->addHeader('Content-Type: application/json');
                    $this->response->setOutput(json_encode($productsJson, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
                    return;

                    break;

                case "customers.json":

                    $wp_cust = $this->getCustomers(array("start" => $start, "limit" => $limit));
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

                    $wp_subscribers = $this->getCustomers(array("start" => $start, "limit" => $limit, "filter_newsletter" => 1));
                    $subs = array();

                    foreach ($wp_subscribers as $users) {
                        $subs[] = array(
                            "email" => $users["email"],
                            "firstname" =>$users["name"],
                            "lastname" => ""
                        );
                    }

                    $this->response->addHeader('Content-Type: application/json');
                    $this->response->setOutput(json_encode($subs, JSON_PRETTY_PRINT));
                    return;

                    break;
                case "version.json":
                    $version = array(
                    "version" => "Opencart 2.3.x"
                    );

                    $this->response->addHeader('Content-Type: application/json');
                            $this->response->setOutput(json_encode($version, JSON_PRETTY_PRINT));
                    return;
            
                    break;

                    case "coupons.json":

                        try {
                            $discountType = !isset($this->request->get['type']) ? -1 : (int)$this->request->get['type'];
                            $value = !isset($this->request->get['value']) ? -1 : (int)$this->request->get['value'];
                            $batch_size = !isset($this->request->get['batch_size']) ? 1 : (int)$this->request->get['batch_size'];
                            $prefix = !isset($this->request->get['prefix']) ? "" : $this->request->get['prefix'];
                            $expire_date = isset($this->request->get['expire_date']) ? $this->request->get['expire_date'] : null;
                            $min_amount = !isset($this->request->get['min_amount']) ? -1 : (float)$this->request->get['min_amount'];
                            $currency = isset($this->request->get['currency']) ? $this->request->get['currency'] : "";

                			if(empty($discountType))
                			{
                			    $discountType = empty($_POST['type']) ? '' : $_POST['type'];
                			}			    
                			if(empty($value))
                			{
                			    $value = empty($_POST['value']) ? '' : $_POST['value'];
                			}			    
                			if(empty($batch_size))
                			{
                			    $batch_size = empty($_POST['batch_size']) ? '' : $_POST['batch_size'];
                			}			    
                			if(empty($prefix))
                			{
                			    $prefix = empty($_POST['prefix']) ? '' : $_POST['prefix'];
                			}			    
                			if(empty($expire_date))
                			{
                			    $expire_date = empty($_POST['expire_date']) ? '' : $_POST['expire_date'];
                			}			    
                			if(empty($min_amount))
                			{
                			    $min_amount = empty($_POST['min_amount']) ? '' : $_POST['min_amount'];
                			}			    
                			if(empty($currency))
                			{
                			    $currency = empty($_POST['currency']) ? '' : $_POST['currency'];
                			}                            
    
                            if ($discountType == -1) {
                                $this->response->setOutput(json_encode(array(
                                    "status" => 0,
                                    "msg" => "Missing type param"
                                )));
                                return;
                            }
                            if ($value == -1) {
                                $this->response->setOutput(json_encode(array(
                                    "status" => 0,
                                    "msg" => "Missing value param"
                                )));
                                return;
                            }
    
                            $couponsList = array();
    
                            for ($int = 0; $int < $batch_size; $int++) {
                                $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                                $coupon_code = '';
    
                                do {
                                    $coupon_code = '';
                                    for ($i = 0; $i < 8; $i++) {
                                        $coupon_code .= $characters[rand(0, strlen($characters) - 1)];
                                    }
                                    $full_coupon_code = $prefix . $coupon_code;
                                    $existing_coupon = $this->db->query("SELECT coupon_id FROM " . DB_PREFIX . "coupon WHERE code = '" . $this->db->escape($full_coupon_code) . "'");
                                } while ($existing_coupon->num_rows > 0);
    
                                $coupon_data = array(
                                    'name' => 'Generated Coupon ' . $full_coupon_code,
                                    'code' => $full_coupon_code,
                                    'discount' => $value,
                                    'type' => ($discountType == 1) ? 'P' : 'F',
                                    'total' => ($min_amount != -1) ? $min_amount : 0,
                                    'logged' => 0,
                                    'shipping' => 0,
                                    'date_start' => date('Y-m-d'),
                                    'date_end' => ($expire_date != null) ? date('Y-m-d', strtotime($expire_date)) : '9999-12-31',
                                    'uses_total' => 1,
                                    'uses_customer' => 1,
                                    'status' => 1
                                );
    
                                $this->db->query("INSERT INTO " . DB_PREFIX . "coupon SET " .
                                    "name = '" . $this->db->escape($coupon_data['name']) . "', " .
                                    "code = '" . $this->db->escape($coupon_data['code']) . "', " .
                                    "discount = '" . (float)$coupon_data['discount'] . "', " .
                                    "type = '" . $this->db->escape($coupon_data['type']) . "', " .
                                    "total = '" . (float)$coupon_data['total'] . "', " .
                                    "logged = '" . (int)$coupon_data['logged'] . "', " .
                                    "shipping = '" . (int)$coupon_data['shipping'] . "', " .
                                    "date_start = '" . $this->db->escape($coupon_data['date_start']) . "', " .
                                    "date_end = '" . $this->db->escape($coupon_data['date_end']) . "', " .
                                    "uses_total = '" . (int)$coupon_data['uses_total'] . "', " .
                                    "uses_customer = '" . (int)$coupon_data['uses_customer'] . "', " .
                                    "status = '" . (int)$coupon_data['status'] . "'");
    
                                $couponsList[] = $full_coupon_code;
                            }
    
                            $this->response->setOutput(json_encode(array(
                                "status" => 1,
                                "codes" => $couponsList
                            )));
                        } catch (Exception $exception) {
                            $this->response->setOutput(json_encode(array(
                                "status" => 0,
                                "msg" => $exception->getMessage()
                            )));
                        }                    
    
                        break;

            }
        } else {
           //allow
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

    public function getSubscribers()
    {
        $sql = "SELECT * FROM " . DB_PREFIX . "newsletter";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getSubscribersOcJournal()
    {
        $sql = "SELECT * FROM oc_journal3_newsletter";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function _importDatas(&$data, $list, $client, $segments = null)
    {
        $csv = '"email","source"' . PHP_EOL;

        $source = self::safeForCsv("opencart 2.3 subscribers newsman plugin");
        foreach ($data as $_dat) {
            $csv .= sprintf(
                "%s,%s",
                self::safeForCsv($_dat["email"]),
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

    public function _importData(&$data, $list, $client, $segments = null)
    {
        $csv = '"email","fullname","source"' . PHP_EOL;

        $source = self::safeForCsv("opencart 2.3 customers with newsletter newsman plugin");
        foreach ($data as $_dat) {
            $csv .= sprintf(
                "%s,%s,%s",
                self::safeForCsv($_dat["email"]),
                self::safeForCsv($_dat["firstname"]),
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
    
    public function getProducts($data = array())
    {
        $sql = "SELECT p.product_id, (SELECT AVG(rating) AS total FROM " . DB_PREFIX . "review r1 WHERE r1.product_id = p.product_id AND r1.status = '1' GROUP BY r1.product_id) AS rating, (SELECT price FROM " . DB_PREFIX . "product_discount pd2 WHERE pd2.product_id = p.product_id AND pd2.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND pd2.quantity = '1' AND ((pd2.date_start = '0000-00-00' OR pd2.date_start < NOW()) AND (pd2.date_end = '0000-00-00' OR pd2.date_end > NOW())) ORDER BY pd2.priority ASC, pd2.price ASC LIMIT 1) AS discount, (SELECT price FROM " . DB_PREFIX . "product_special ps WHERE ps.product_id = p.product_id AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND ((ps.date_start = '0000-00-00' OR ps.date_start < NOW()) AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW())) ORDER BY ps.priority ASC, ps.price ASC LIMIT 1) AS special";

        if (!empty($data['filter_category_id'])) {
            if (!empty($data['filter_sub_category'])) {
                $sql .= " FROM " . DB_PREFIX . "category_path cp LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (cp.category_id = p2c.category_id)";
            } else {
                $sql .= " FROM " . DB_PREFIX . "product_to_category p2c";
            }

            if (!empty($data['filter_filter'])) {
                $sql .= " LEFT JOIN " . DB_PREFIX . "product_filter pf ON (p2c.product_id = pf.product_id) LEFT JOIN " . DB_PREFIX . "product p ON (pf.product_id = p.product_id)";
            } else {
                $sql .= " LEFT JOIN " . DB_PREFIX . "product p ON (p2c.product_id = p.product_id)";
            }
        } else {
            $sql .= " FROM " . DB_PREFIX . "product p";
        }

        $sql .= " LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.status = '1'  AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'";

        if (!empty($data['filter_category_id'])) {
            if (!empty($data['filter_sub_category'])) {
                $sql .= " AND cp.path_id = '" . (int)$data['filter_category_id'] . "'";
            } else {
                $sql .= " AND p2c.category_id = '" . (int)$data['filter_category_id'] . "'";
            }

            if (!empty($data['filter_filter'])) {
                $implode = array();

                $filters = explode(',', $data['filter_filter']);

                foreach ($filters as $filter_id) {
                    $implode[] = (int)$filter_id;
                }

                $sql .= " AND pf.filter_id IN (" . implode(',', $implode) . ")";
            }
        }

        if (!empty($data['filter_name']) || !empty($data['filter_tag'])) {
            $sql .= " AND (";

            if (!empty($data['filter_name'])) {
                $implode = array();

                $words = explode(' ', trim(preg_replace('/\s+/', ' ', $data['filter_name'])));

                foreach ($words as $word) {
                    $implode[] = "pd.name LIKE '%" . $this->db->escape($word) . "%'";
                }

                if ($implode) {
                    $sql .= " " . implode(" AND ", $implode) . "";
                }

                if (!empty($data['filter_description'])) {
                    $sql .= " OR pd.description LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
                }
            }

            if (!empty($data['filter_name']) && !empty($data['filter_tag'])) {
                $sql .= " OR ";
            }

            if (!empty($data['filter_tag'])) {
                $implode = array();

                $words = explode(' ', trim(preg_replace('/\s+/', ' ', $data['filter_tag'])));

                foreach ($words as $word) {
                    $implode[] = "pd.tag LIKE '%" . $this->db->escape($word) . "%'";
                }

                if ($implode) {
                    $sql .= " " . implode(" AND ", $implode) . "";
                }
            }

            if (!empty($data['filter_name'])) {
                $sql .= " OR LCASE(p.model) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
                $sql .= " OR LCASE(p.sku) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
                $sql .= " OR LCASE(p.upc) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
                $sql .= " OR LCASE(p.ean) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
                $sql .= " OR LCASE(p.jan) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
                $sql .= " OR LCASE(p.isbn) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
                $sql .= " OR LCASE(p.mpn) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
            }

            $sql .= ")";
        }

        if (!empty($data['filter_manufacturer_id'])) {
            $sql .= " AND p.manufacturer_id = '" . (int)$data['filter_manufacturer_id'] . "'";
        }

        if (isset($data['favi']) && $data['favi']==true) {
            $sql .= " AND p.favi = '1'";
        }

        //$sql .= " GROUP BY p.product_id";

        $sort_data = array(
            
            'p.product_id',
           
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            /*if ($data['sort'] == 'pd.name' || $data['sort'] == 'p.model') {
                $sql .= " ORDER BY LCASE(" . $data['sort'] . ")";
            } elseif ($data['sort'] == 'p.price') {
                $sql .= " ORDER BY (CASE WHEN special IS NOT NULL THEN special WHEN discount IS NOT NULL THEN discount ELSE p.price END)";
            } else {*/
                $sql .= " ORDER BY " . $data['sort'];
            //}
        } else {
            $sql .= " ORDER BY p.sort_order";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            //$sql .= " DESC, LCASE(pd.name) DESC";
        } else {
            //$sql .= " ASC, LCASE(pd.name) ASC";
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

        $product_data = array();
       
        $query = $this->db->query($sql);
        
        foreach ($query->rows as $result) {
            $product_data[$result['product_id']] = $this->getProduct($result['product_id']);
        }

        return $product_data;
    }
    
    public function getProduct($product_id)
    {
        $query = $this->db->query("SELECT DISTINCT *, pd.name AS name, p.image, m.name AS manufacturer, (SELECT price FROM " . DB_PREFIX . "product_discount pd2 WHERE pd2.product_id = p.product_id AND pd2.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND pd2.quantity = '1' AND ((pd2.date_start = '0000-00-00' OR pd2.date_start < NOW()) AND (pd2.date_end = '0000-00-00' OR pd2.date_end > NOW())) ORDER BY pd2.priority ASC, pd2.price ASC LIMIT 1) AS discount, (SELECT price FROM " . DB_PREFIX . "product_special ps WHERE ps.product_id = p.product_id AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND ((ps.date_start = '0000-00-00' OR ps.date_start < NOW()) AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW())) ORDER BY ps.priority ASC, ps.price ASC LIMIT 1) AS special, (SELECT points FROM " . DB_PREFIX . "product_reward pr WHERE pr.product_id = p.product_id AND pr.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "') AS reward, (SELECT ss.name FROM " . DB_PREFIX . "stock_status ss WHERE ss.stock_status_id = p.stock_status_id AND ss.language_id = '" . (int)$this->config->get('config_language_id') . "') AS stock_status, (SELECT wcd.unit FROM " . DB_PREFIX . "weight_class_description wcd WHERE p.weight_class_id = wcd.weight_class_id AND wcd.language_id = '" . (int)$this->config->get('config_language_id') . "') AS weight_class, (SELECT lcd.unit FROM " . DB_PREFIX . "length_class_description lcd WHERE p.length_class_id = lcd.length_class_id AND lcd.language_id = '" . (int)$this->config->get('config_language_id') . "') AS length_class, (SELECT AVG(rating) AS total FROM " . DB_PREFIX . "review r1 WHERE r1.product_id = p.product_id AND r1.status = '1' GROUP BY r1.product_id) AS rating, (SELECT COUNT(*) AS total FROM " . DB_PREFIX . "review r2 WHERE r2.product_id = p.product_id AND r2.status = '1' GROUP BY r2.product_id) AS reviews, p.sort_order FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) LEFT JOIN " . DB_PREFIX . "manufacturer m ON (p.manufacturer_id = m.manufacturer_id) WHERE p.product_id = '" . (int)$product_id . "' AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.status = '1'  AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'");

        if ($query->num_rows) {
            return array(
                'product_id'       => $query->row['product_id'],
                'name'             => $query->row['name'],
                'description'      => $query->row['description'],
                'meta_title'       => $query->row['meta_title'],
                'meta_description' => $query->row['meta_description'],
                'meta_keyword'     => $query->row['meta_keyword'],
                'tag'              => $query->row['tag'],
                'model'            => $query->row['model'],
                'sku'              => $query->row['sku'],
                'upc'              => $query->row['upc'],
                'ean'              => $query->row['ean'],
                'jan'              => $query->row['jan'],
                'isbn'             => $query->row['isbn'],
                'mpn'              => $query->row['mpn'],
                'location'         => $query->row['location'],
                'quantity'         => $query->row['quantity'],
                'stock_status'     => $query->row['stock_status'],
                'image'            => $query->row['image'],
                'manufacturer_id'  => $query->row['manufacturer_id'],
                'manufacturer'     => $query->row['manufacturer'],
                'price'            => ($query->row['discount'] ? $query->row['discount'] : $query->row['price']),
                'special'          => $query->row['special'],
                'reward'           => $query->row['reward'],
                'points'           => $query->row['points'],
                'tax_class_id'     => $query->row['tax_class_id'],
                'date_available'   => $query->row['date_available'],
                'weight'           => $query->row['weight'],
                'weight_class_id'  => $query->row['weight_class_id'],
                'length'           => $query->row['length'],
                'width'            => $query->row['width'],
                'height'           => $query->row['height'],
                'length_class_id'  => $query->row['length_class_id'],
                'subtract'         => $query->row['subtract'],
                'rating'           => $query->row['rating'],
                'reviews'          => $query->row['reviews'] ? $query->row['reviews'] : 0,
                'minimum'          => $query->row['minimum'],
                'sort_order'       => $query->row['sort_order'],
                'status'           => $query->row['status'],
                'date_added'       => $query->row['date_added'],
                'date_modified'    => $query->row['date_modified'],
                'viewed'           => $query->row['viewed']
            );
        } else {
            return false;
        }
    }

    //order hooks
    public function eventAddOrderHistory($route,$data) {
      
        $this->load->model('setting/setting');

        $setting = $this->model_setting_setting->getSetting('newsman');
        
        if(empty($setting["newsmanuserid"]) || empty($setting["newsmanlistid"]))
            return;

        $userId = $setting["newsmanuserid"];
        $apiKey = $setting["newsmanapikey"];
        $list = $setting["newsmanlistid"];

        $status = $this->getOrderStatus($data[1]);
            
        $url = "https://ssl.newsman.app/api/1.2/rest/" . $userId . "/" . $apiKey . "/remarketing.setPurchaseStatus.json?list_id=" . $list . "&order_id=" . $data[0] . "&status=" . $status;
     
        $cURLConnection = curl_init();
        curl_setopt($cURLConnection, CURLOPT_URL, $url);
        curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
        $ret = curl_exec($cURLConnection);
        curl_close($cURLConnection);

    }

    public function getOrderStatus($id){
        $status = "";

        switch($id)
        {
            case 7:
                $status = "Canceled";
            break;
            case 9:
                $status = "Canceled Reversal";
            break;
            case 13:
                $status = "Chargeback";
            break;
            case 5:
                $status = "Complete";
            break;
            case 8:
                $status = "Denied";
            break;
            case 14:
                $status = "Expired";
            break;
            case 10:
                $status = "Failed";
            break;
            case 1:
                $status = "Pending";
            break;
            case 15:
                $status = "Processed";
            break;
            case 2:
                $status = "Processing";
            break;
            case 11:
                $status = "Refunded";
            break;
            case 12:
                $status = "Reversed";
            break;
            case 2:
                $status = "Processing";
            break;
            case 3:
                $status = "Shipped";
            break;
            case 16:
                $status = "Voided";
            break;
            default:
                $status = "New";
            break;
        }

        return $status;
    }

}?>
