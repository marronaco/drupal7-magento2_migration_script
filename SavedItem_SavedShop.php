<?php
/*
 * Seller SavedItems and SavedShop - Magento 2
 * */
error_reporting(E_ALL);
ini_set('display_errors', 1);	
?>
<?php
use Magento\Framework\App\Bootstrap;
//Including Bootstrap
include('../app/bootstrap.php');
$mage_bootstrap = Bootstrap::create(BP, $_SERVER);
//Getting Instance of Object Manager
$objectManager = $mage_bootstrap->getObjectManager();
		//Dynamic Range for Script to run from Browser
		$limit = $_GET['limit'];
		$offset = $_GET['offset'];
		// Log File to maitain any sort of Exceptions
		$log_file="log_update.txt";
		
		$response = $objectManager->get('\Magento\Framework\App\Response\Http');
		$state = $objectManager->get('\Magento\Framework\App\State');
		$state->setAreaCode('frontend');		
		
		/* 
		 * Drupal Connection 
		 * Update dbname, Username, Password a/c to Drupal Database
		 * */
		$connection = $objectManager->create(
		'\Magento\Framework\App\ResourceConnection\ConnectionFactory')->create(array(
		'host' => 'localhost',
		'dbname' => 'ABCDE',
		'username' => 'root',
		'password' => 'abc@123',
		'active' => '1',    
		));		
		//Getting users data from Drupal a/c to limit
		$sql = "Select * FROM users limit {$limit} offset {$offset}";  
		$result =$connection->fetchAll($sql);	
		/*
		 * Magento Object Connection
		 * */
		$resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
		$connection_magento = $resource->getConnection();
		/*
		 * Customer Relation Table to maintain Relation between Migrated Data 
		 * */
		$sql = "CREATE TABLE IF NOT EXISTS user_relation_saved_update (customer_id INT , drupal_customer_id INT, user_type VARCHAR(20), is_seller INT)";			
		$connection_magento->query($sql);
		
		$storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
		
		//Customer Object
		$customerFactory = $objectManager->get('\Magento\Customer\Model\CustomerFactory');
		$websiteId = $storeManager->getWebsite()->getWebsiteId();
		$store = $storeManager->getStore();  // Get Store ID
		$storeId = $store->getStoreId();
		//address of Customer	Object
		$addresss = $objectManager->get('\Magento\Customer\Model\AddressFactory');
		//New admin User. Object
		$userFactory= $objectManager->get('\Magento\User\Model\UserFactory');
		//Marketplace			
		$model = $objectManager->create('Webkul\Marketplace\Model\Seller'); 
        $status = $objectManager->get('Webkul\Marketplace\Helper\Data')->getIsPartnerApproval() ? 0 : 1; 
		//Wishlist
		$wishlistRepository= $objectManager->get('\Magento\Wishlist\Model\WishlistFactory');
		$productRepository= $objectManager->get('\Magento\Catalog\Api\ProductRepositoryInterface');
				
		$result_data = array();
		$k = 0; $i = 0;
		foreach($result as $value){
			$roles = "select * from users_roles where uid = {$value['uid']}";  //Getting data from user_roles of Drupal
			$role_result =$connection->fetchAll($roles);
			$role_arr = array();
			foreach($role_result as $re){
				$role_arr[] = $re['rid'];
			}
			$result_data[$k] = $value;
			$result_data[$k]['rids'] = implode(',',$role_arr);
			$k++;
			}
		foreach($result_data as $value_data){

			$ins = unserialize($value_data['data']);
			//Unix Time Conversion
			$time = date("Y-m-d h:i:s A T",$value_data['created']);
			
			$sql3="SELECT email FROM customer_entity";
			$result3 =$connection_magento->fetchAll($sql3);
			$var= array();		
			foreach($result3 as $res){
				$var[] = $res['email'];				
			}
		
			//Create Customer and Address of Customer
			if(in_array(2,explode(',',$value_data['rids'])) || in_array(4,explode(',',$value_data['rids'])) ||in_array(5,explode(',',$value_data['rids'])) || in_array(6,explode(',',$value_data['rids']))) {			
													
					//UPDATION SCRIPT
					if(in_array($value_data['mail'],$var)){
					
						$sql_marketplace_load="Select seller_id FROM marketplace_userdata";
						$var_market=$connection_magento->fetchAll($sql_marketplace_load);
						
						$_shoppath="";
						if(array_key_exists('shop', $ins)){
							$_shoppath=$ins['shop']->title;
						}				
						//Saved Shops by Seller
						$sql_saved_shop="SELECT DISTINCT n.nid, n.title, n.uid, f.last FROM node n INNER JOIN favorite_nodes f ON n.nid = f.nid LEFT JOIN content_type_item cti ON cti.field_shop_id_value = n.nid LEFT JOIN uc_product_stock ups ON ups.nid = cti.nid WHERE n.type = 'shop' AND f.uid = '{$value_data['uid']}'";
						$result_saved_shop=$connection->fetchAll($sql_saved_shop);
						//Update Shop_likes
						if(isset($result_saved_shop[0]['nid'])){
							foreach($result_saved_shop as $value_saved_shop){
								$sql_uid_magento="SELECT DISTINCT customer_id from user_relation WHERE drupal_customer_id='{$value_saved_shop['uid']}'";
								$result_uid_magento=$connection_magento->fetchAll($sql_uid_magento);
								$sql_shop_magento="SELECT DISTINCT seller_id from marketplace_userdata WHERE shop_url='{$value_saved_shop['title']}'";
								$result_shop_magento=$connection_magento->fetchAll($sql_shop_magento);
	
								if(count($result_uid_magento, 1)!= 0 && count($result_shop_magento, 1)!= 0){
									$sql_shop_like_update="INSERT INTO shop_likes (shop_id, customer_id) values({$result_shop_magento[0]['seller_id']}, {$result_uid_magento[0]['customer_id']})";	
									$connection_magento->query($sql_shop_like_update); 
								} 
								printf("UPdate Saved Shop");
							}	
						}						
						//Saved Items by Seller
						$sql_saved_item="SELECT DISTINCT n.nid, n.title, n.uid, f.last FROM node n INNER JOIN favorite_nodes f ON n.nid = f.nid LEFT JOIN content_type_item cti ON cti.field_shop_id_value = n.nid LEFT JOIN uc_product_stock ups ON ups.nid = cti.nid WHERE n.type = 'item' AND f.uid = {$value_data['uid']}";
						$result_saved_item=$connection->fetchAll($sql_saved_item);
						
						if(isset($result_saved_item[0]['nid'])){
							foreach($result_saved_item as $value_saved_item){
								$sql_sku="SELECT model FROM `uc_products` WHERE `nid` = '{$value_saved_item['nid']}'";
								$result_sku=$connection->fetchAll($sql_sku);
								$sql_magento_nid="SELECT entity_id FROM `catalog_product_entity` WHERE `sku`='{$result_sku[0]['model']}'";
								$result_magento_nid=$connection_magento->fetchAll($sql_magento_nid);
								
								try {
									$product = $productRepository->getById($result_magento_nid[0]['entity_id']);
									} catch (NoSuchEntityException $e) {
													$product = null;
											}
								$wishlist = $wishlistRepository->create()->loadByCustomerId($value_data['uid'], true);
								$wishlist->addNewItem($product);
								$wishlist->save();
								printf("saved items");
							}
						}		
						
						
				}
			
			printf("-----------------------------------------------------------------------------------------------------------"); 
			}		
	}
      
