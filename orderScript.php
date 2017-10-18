<?php
/*
* Order Migration Script -Magento 2
* Using Mofluid API
* */
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Including Bootstrap

useMagentoFrameworkAppBootstrap;
include ('../app/bootstrap.php');

$mage_bootstrap = Bootstrap::create(BP, $_SERVER);

// Getting Instance of Object Manager

$objectManager = $mage_bootstrap->getObjectManager();

// Log File to maitain any sort of Exceptions

$log_file_order = 'order_log.txt';
$log_order_prod = 'order_product.txt';
$state = $objectManager->get('Magento\Framework\App\State');
$state->setAreaCode('frontend');

// Dynamic Range for Script to run from Browser

$limit = $_GET['limit'];
$offset = $_GET['offset'];
/*
* Drupal Connection
* Update dbname, Username, Password a/c to Drupal Database
* */
$connection = $objectManager->create('\Magento\Framework\App\ResourceConnection\ConnectionFactory')->create(array(
	'host' => 'localhost',
	'dbname' => 'ABCDE',
	'username' => 'root',
	'password' => 'abc@123',
	'active' => '1',
));
/*
* Magento Object Connection
* */
$resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
$connection_magento = $resource->getConnection();

// Connection Object

$context = $objectManager->get('\Magento\Framework\App\Helper\Context');
$scopeConfig = $context->getScopeConfig();
$storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
$productFactory = $objectManager->get('\Magento\Catalog\Model\ProductFactory');
$productRepository = $objectManager->get('\Magento\Catalog\Model\ProductRepository');
$quoteManagement = $objectManager->get('\Magento\Quote\Model\QuoteManagement');
$customerFactory = $objectManager->get('\Magento\Customer\Model\CustomerFactory');
$customerRepository = $objectManager->get('\Magento\Customer\Api\CustomerRepositoryInterface');
$orderService = $objectManager->get('\Magento\Sales\Model\Service\OrderService');
$cartRepositoryInterface = $objectManager->get('\Magento\Quote\Api\CartRepositoryInterface');
$cartManagementInterface = $objectManager->get('\Magento\Quote\Api\CartManagementInterface');
$shippingRate = $objectManager->get('\Magento\Quote\Model\Quote\Address\Rate');
$region = $objectManager->get('\Magento\Directory\Model\Region');

// Mofluid Api Relation Object

$helper = $objectManager->create('Mofluid\Mofluidapi2\Helper\Data');

// Loading Order related data from Drupal

$sql_uc_orders = "SELECT * FROM `uc_orders` ORDER BY `order_id` ASC limit {$limit} offset {$offset}";
$result_uc_orders = $connection->fetchAll($sql_uc_orders); // uc_orders
$c = 1;

foreach($result_uc_orders as $value_uc_order)
	{
	$sql_admin_comment = "SELECT * FROM `uc_order_admin_comments` WHERE order_id={$value_uc_order['order_id']}"; //Order Admin Comment
	$result_admin_comment = $connection->fetchAll($sql_admin_comment);
	$sql_order_comment = "SELECT * FROM `uc_order_comments` WHERE order_id={$value_uc_order['order_id']}"; //Order Comment
	$result_order_comment = $connection->fetchAll($sql_order_comment);
	$sql_order_log = "SELECT * FROM `uc_order_log` WHERE `order_id` ={$value_uc_order['order_id']}"; //Order Log
	$result_order_log = $connection->fetchAll($sql_order_log);
	$sql_order_products = "SELECT * FROM `uc_order_products` WHERE `order_id` = {$value_uc_order['order_id']}"; //Products details
	$result_order_products = $connection->fetchAll($sql_order_products);
	$sql_order_quote = "SELECT * FROM `uc_order_quotes` WHERE `order_id` = {$value_uc_order['order_id']}";
	$result_order_quote = $connection->fetchAll($sql_order_quote);
	$sql_order_transaction = "SELECT * FROM `uc_payment_paypal_ipn` WHERE `order_id` = {$value_uc_order['order_id']}"; //transaction Id
	$result_order_transaction = $connection->fetchAll($sql_order_transaction);

	// ----------------------------magento product ---------------------------//

	$result_prod_magento = array();
	foreach($result_order_products as $order_products)
		{
		$sql_prod_magento = "SELECT * FROM `catalog_product_entity` WHERE `sku` = '{$order_products['model']}'";
		$result_prod_magento = $connection_magento->fetchAll($sql_prod_magento);
		}

	/*Validating If Order Product Exist in Magento 2*/
	if (count($result_prod_magento, 1) == 0)
		{
		file_put_contents($log_order_prod, $order_products['model'] . '--->' . $value_uc_order['order_id'] . "\n", FILE_APPEND);
		continue;
		}

	// Converting in Base64 for Mofluid API

	$myJSON_product = json_encode($result_prod_magento);
	$decodedtext_product = base64_encode($myJSON_product);

	// Updating country and region code of Drupal with corresponding Magento Code

	$sql_country_id = "SELECT country_iso_code_2 FROM `uc_countries` WHERE `country_id` ={$value_uc_order['delivery_country']}";
	$result_country_id = $connection->fetchAll($sql_country_id);
	$sql_country_billing_id = "SELECT country_iso_code_2 FROM `uc_countries` WHERE `country_id` ={$value_uc_order['billing_country']}";
	$result_country_billing_id = $connection->fetchAll($sql_country_billing_id);

	// Unix Time Conversion

	$time_created = date("Y-m-d h:i:s A T", $value_uc_order['created']);
	$time_updated = date("Y-m-d h:i:s A T", $value_uc_order['modified']);

	//-------------------Shipping & Billing Addresss---------------------------//
	  $orderData_address=[		
		'shipping_address' =>[
             'firstname'    => "{$value_uc_order['delivery_first_name']}", 
             'lastname'     => "{$value_uc_order['delivery_last_name']}",
             'email' =>"{$value_uc_order['primary_email']}",
             'street' => "{$value_uc_order['delivery_street1']}".''."{$value_uc_order['delivery_street2']}",
             'city' => "{$value_uc_order['delivery_city']}",
             'country_id' => "{$result_country_id[0]['country_iso_code_2']}",
             'region' => "{$value_uc_order['delivery_zone']}",
             'postcode' => "{$value_uc_order['delivery_postal_code']}",
             'telephone' => "{$value_uc_order['delivery_phone']}",
             'fax' => null,
             'company' =>"{$value_uc_order['delivery_company']}",
             'save_in_address_book' => 1
         ],
         'billing_address' =>[
		'firstname'    => "{$value_uc_order['delivery_first_name']}", 
             'lastname'     => "{$value_uc_order['delivery_last_name']}",
             'email' =>"{$value_uc_order['primary_email']}",
             'street' => "{$value_uc_order['delivery_street1']}".''."{$value_uc_order['delivery_street2']}",
             'city' => "{$value_uc_order['delivery_city']}",
             'country_id' => "{$result_country_billing_id[0]['country_iso_code_2']}",
             'region' => "{$value_uc_order['delivery_zone']}",
             'postcode' => "{$value_uc_order['delivery_postal_code']}",
             'telephone' => "{$value_uc_order['delivery_phone']}",
             'fax' => null,
             'company' =>"{$value_uc_order['delivery_company']}",
             'save_in_address_book' => 1
             ],
         ];
	// Converting in Base64 for Mofluid API

	$myJSON_address = json_encode($orderData_address);
	$decodedtext_address = base64_encode($myJSON_address);

	// Creating Array of Transaction ID

	$trans_id = array();
	$trans_mc_gross = array();
	if (count($result_order_transaction, 1) >= 1)
		{
		$i = 'a';
		foreach($result_order_transaction as $order_trans)
			{
			$trans_id[$i] = $order_trans['txn_id'];
			$trans_mc_gross[$i] = $order_trans['mc_gross'];
			$i++;
			}
		}
	  else
		{
		$trans_id = array(
			'a' => 123456789
		);
		$trans_mc_gross = array(
			'a' => 0
		);
		}

	// Converting in Base64 for Mofluid API

	$encoded_trans_id = json_encode($trans_id, true);
	$encoded_trans_mc_gross = json_encode($trans_mc_gross, true);

	// --------------Calling Placeorder function of Mofluid---------------------//

	if ($value_uc_order['order_status'] == 'completed')
		{
		$value_uc_order['order_status'] = 'complete';
		}

	$sql_user_magento = "SELECT customer_id from user_relation WHERE drupal_customer_id={$value_uc_order['uid']}";
	$result_user_magento = $connection_magento->fetchAll($sql_user_magento);
	if (count($result_user_magento, 1) == 0)
		{
		/*
		* GUEST Order Creation
		* */
		file_put_contents($log_file_order, $value_uc_order['order_id'] . '--->' . $value_uc_order['uid'] . '--->' . 'guest' . "\n", FILE_APPEND);
		try
			{
			/*
			* CURL request to hit Mofluid API to create Order
			* */
			$ch = curl_init();
			$curlConfig = array(
				CURLOPT_URL => 'http://<YOUR URL>/mofluidapi2?callback=&',
				CURLOPT_POST => true,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_POSTFIELDS => array(
					'store' => 1,
					'currency' => 'USD',
					'service' => 'placeorder',
					'customerid' => 0,
					'products' => $decodedtext_product,
					'address' => $decodedtext_address,
					'is_create_quote' => 1,
					'shipmethod' => 'freeshipping_freeshipping',
					'paymentmethod' => 'cashondelivery',
					'couponCode' => 'null',
					'transactionid' => $encoded_trans_id,
					'order_status' => $value_uc_order['order_status'],
					'trans_gross' => $encoded_trans_mc_gross
				)
			);
			curl_setopt_array($ch, $curlConfig);
			$result = curl_exec($ch);
			$index = json_decode($result, true);
			}

		catch(Exception $e)
			{
			echo 'Message:' . $e->getMessage();
			}
		}
	  else
		{
		/*
		* Registered Customer Order Creation
		* */
		try
			{
			/*
			* CURL request to hit Mofluid API to create Order
			* */
			$ch = curl_init();
			$curlConfig = array(
				CURLOPT_URL => 'http://<YOUR URL>/mofluidapi2?callback=&',
				CURLOPT_POST => true,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_POSTFIELDS => array(
					'store' => 1,
					'currency' => 'USD',
					'service' => 'placeorder',
					'customerid' => $result_user_magento[0]['customer_id'],
					'products' => $decodedtext_product,
					'address' => $decodedtext_address,
					'is_create_quote' => 1,
					'shipmethod' => 'freeshipping_freeshipping',
					'paymentmethod' => 'cashondelivery',
					'couponCode' => 'null',
					'transactionid' => $encoded_trans_id,
					'order_status' => $value_uc_order['order_status'],
					'trans_gross' => $encoded_trans_mc_gross
				)
			);
			curl_setopt_array($ch, $curlConfig);
			$result = curl_exec($ch);
			$index = json_decode($result, true);
			}

		catch(Exception $e)
			{
			echo 'Message:' . $e->getMessage();
			}
		}

	// -------------------Order Relation Table Maintain------------------------------------//

	$sql_relation = "INSERT INTO `order_relation`(`drupal_order_id`, `magento_order_id`) VALUES ({$value_uc_order['order_id']}, {$index['a']})";
	$connection_magento->query($sql_relation);

	// ---------------------Update for transaction Data------------------------------------//

	if ($trans_id['a'] != 123456789)
		{
		foreach($result_order_transaction as $order_trans)
			{
			$order_trans['received'] = date("Y-m-d h:i:s A T", $order_trans['received']);
			$sql_update_trans_time = "UPDATE `sales_payment_transaction` SET `created_at`='{$order_trans['received']}', `txn_type`='{$order_trans['txn_type']}', `receiver_email`='{$order_trans['receiver_email']}', `payer_email`='{$order_trans['payer_email']}' WHERE txn_id ='{$order_trans['txn_id']}' AND order_id = {$index['a']}";
			$connection_magento->query($sql_update_trans_time);
			}
		}
	  else
		{
		$sql_update_trans = "DELETE FROM `sales_payment_transaction` WHERE txn_id ='123456789' AND order_id = {$index['a']} ";
		$connection_magento->query($sql_update_trans);
		}

	// ----------------Update of Invoice Date---------------------------------------//

	$sql_invoice = "UPDATE `sales_invoice_grid` SET `created_at`='{$time_updated}', `updated_at`='{$time_updated}', `order_created_at`='{$time_updated}' WHERE order_id={$index['a']}";
	$connection_magento->query($sql_invoice);

	// -----------------------Order Creation Time Updation------------------------//

	$sql = "UPDATE `sales_order` SET `created_at`='{$time_created}', `updated_at`='{$time_updated}', `shipping_method`='freeshipping_freeshipping' WHERE entity_id={$index['a']}";
	$connection_magento->query($sql);
	$sql_order_grid = "UPDATE `sales_order_grid` SET `created_at`='{$time_created}', `updated_at`='{$time_updated}' WHERE entity_id={$index['a']}";
	$connection_magento->query($sql_order_grid);

	// ----------------Order Related Comments-----------------//

	foreach($result_admin_comment as $admin_comment)
		{
		$admin_comment['created'] = date("Y-m-d h:i:s A T", $admin_comment['created']);
		createComment($admin_comment['order_id'], $admin_comment['message'], null, $admin_comment['created'], "By_admin", $index['a']);
		}

	foreach($result_order_comment as $order_comment)
		{
		$order_comment['created'] = date("Y-m-d h:i:s A T", $order_comment['created']);
		createComment($order_comment['order_id'], $order_comment['message'], null, $order_comment['created'], "order_comment", $index['a']);
		}

	foreach($result_order_log as $order_log)
		{
		$split = explode(" ", $order_log['changes']);
		$order_log['changes'] = $split[count($split) - 1];
		$order_log['created'] = date("Y-m-d h:i:s A T", $order_log['created']);
		createComment($order_log['order_id'], $order_log['changes'], null, $order_log['created'], "order_log", $index['a']);
		}

	print_r("Order Created");
	}

// ----------Function to CreateComment-----------------------------//

function createComment($order_no, $comment, $status, $created, $entity, $ind)
	{
	$objectManager = MagentoFrameworkAppObjectManager::getInstance();
	$resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
	$connection_magento = $resource->getConnection();
	$order = $objectManager->create('\Magento\Sales\Model\Order')->load($ind);
	$order->addStatusHistoryComment(strip_tags($comment))->setIsVisibleOnFront(true)->setIsCustomerNotified(false)->setStatus(strip_tags($status))->setCreatedAt($created)->setShowswhatentityhistoryisbindto($entity)->save();
	}

?>
