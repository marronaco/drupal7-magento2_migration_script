<?php
/*
 * Create Customer and Admin Programmetically - Magento 2
 * */
error_reporting(E_ALL);
ini_set('display_errors', 1);	
?>
<?php
//Including Bootstrap
use Magento\Framework\App\Bootstrap;
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
		'dbname' => 'ABCD',
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
		$sql = "CREATE TABLE IF NOT EXISTS user_relation_update (customer_id INT , drupal_customer_id INT, user_type VARCHAR(20), is_seller INT)";			
		$connection_magento->query($sql);
		//StoreManager Object
		$storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
		
		//Customer Object
		$customerFactory = $objectManager->get('\Magento\Customer\Model\CustomerFactory');
		$websiteId = $storeManager->getWebsite()->getWebsiteId();
		// Get Store ID
		$store = $storeManager->getStore();  
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
		
		/*
		 * All user Data and their Role in 1 Array
		 * */		
		$result_data = array();
		$k = 0; $i = 0;
		foreach($result as $value){
			//Getting data from user_roles of Drupal
			$roles = "select * from users_roles where uid = {$value['uid']}";  
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
			
			echo $i;
			$i++;
			echo '<br>'.'----------->'.'<br>';
			echo $value_data['mail'].' Role : '.$value_data['rids']." UID: ".$value_data['uid'].'<br>';
							
			$ins = unserialize($value_data['data']);
			//Unix Time Conversion
			$time = date("Y-m-d h:i:s A T",$value_data['created']);
			//Breaking Name from Drupal into Firstname and Lastname			
			$_firstname=$value_data['name']; $_lastname="";
			if(isset($ins['profile_name'])){
				if($ins['profile_name'] != ''){
					if(strpos($ins['profile_name'], " " !== false)){
						$_name = explode(" ",$ins['profile_name']);	
						$_firstname=$_name[0];
						$_lastname=$_name[1];		 
						} 
				}
			}
			//Existing customer in Magento 
			$sql_customer_email="SELECT email FROM customer_entity";
			$result_customer_email =$connection_magento->fetchAll($sql_customer_email);
			$var= array();		
			foreach($result_customer_email as $res){
				$var[] = $res['email'];				
			}
			//Customer Address Details 
			$sql1="Select * from profile_values where uid={$value_data['uid']}";  
			$result_profile_values =$connection->fetchAll($sql1);
			//Relation Table entry Details
			$u_type=(in_array(3,explode(',',$value_data['rids'])))?"admin":"customer";
			$if_seller=(in_array(4,explode(',',$value_data['rids'])))?1:0;	
			//---------------------------Customer and Seller Creation------------------//
			if(in_array(2,explode(',',$value_data['rids'])) || in_array(4,explode(',',$value_data['rids'])) ||in_array(5,explode(',',$value_data['rids'])) || in_array(6,explode(',',$value_data['rids']))) {	
				//Customer Object		
				$customer = $customerFactory->create();
					//--------customer address Details----------------------------------//
					$_country=" ";$_postcode=" "; $_city="NA";$_street="NA";$_shopname="NA";$_gender=0;$_state="";$_description="";$_dob=null;$_telephone="";						
						foreach($result_profile_values as $data){						
						if($data['fid']== 9 && $data['value']){	
							$_country=$data['value'];
						}
						if($data['fid']== 8 && $data['value']){
							$_postcode=$data['value'];
						}
						if($data['fid']== 6 && $data['value']){
							$_city=$data['value'];
							$_city = str_replace("'","",$_city);
						}
						if($data['fid']== 5 && $data['value']){
							$_street=$data['value'];
							$_street = str_replace("'","",$_street);
						}
						if($data['fid']== 7 && $data['value']){
							$_state=$data['value'];
							$_state = str_replace("'","",$_state);
						}
						if($data['fid']== 4 && $data['value']){	
							$_description=$data['value'];
							$_description=str_replace("'","",$_description);
						}
						if($data['fid']== 2 && $data['value']){
							$dob_data=unserialize($data['value']);
							if($dob_data['year'] == '' && $dob_data['month'] == '' && $dob_data['day'] == ''){
								printf("im here");
								$_dob=null; }
							elseif($dob_data['year']=='' && $dob_data['month'] !='')
								$_dob='1970'.'-'.$dob_data['month'].'-'.$dob_data['day'];
							else{
								$_dob=$dob_data['year'].'-'.$dob_data['month'].'-'.$dob_data['day']; }
						}
						if($data['fid']==1 && $data['value']){
							if($data['value'] == "male"){
								$_gender=1;
								}							
						}									
					}					
					$_returnpolicy="";
					//Checking If Customer with Given email already exist
					if(!in_array($value_data['mail'],$var))
					{														
						//Customer Creation	
						$customer->setWebsiteId($websiteId);
						$customer->setEmail($value_data['mail']);
						$customer->setGender($_gender);
						$customer->setIsActive($value_data['status']);
						$customer->setFirstname($value_data['name']);
						$customer->setLastname(".");
						$customer->setPassword($value_data['pass']);
						if(in_array(4,explode(',',$value_data['rids']))){
							$customer->setGroupId(4);
						}
						$customer->save();
						echo 'Create customer successfully'.$customer->getId();		
						echo"<br>";				
										
						//Create address of Customer
						$address = $addresss->create();
						$address->setCustomerId($customer->getId())
								->setFirstname($value_data['name'])
								->setLastname(".")
								->setCountryId($_country)
								->setPostcode($_postcode)
								->setCity($_city)
								->setTelephone(1234567890)
								->setFax(0)
								->setStreet($_street)
								->setCompany($_shopname)
								->setIsDefaultBilling(1)
								->setIsDefaultShipping(1)
								->setSaveInAddressBook(1);
						$address->save();
									
						//Marketplace_controller_list											
						if(in_array(4,explode(',',$value_data['rids']))){	
							$model->setData('is_seller',1);  
							$model->setData('shop_url', $_shoppath);
							$model->setData('location',$_city); 
							$model->setGroupId(4);  
							$model->setData('seller_id', $customer->getEntityId());
							$model->setData('logo_pic',$value_data['picture']);                
							if ($status == 0) {                        
								$model->setAdminNotification(1);                   
							}                    
							$model->save();																		   				
						}
						
						if(!in_array(6,explode(',',$value_data['rids']))){
							$sql2 = "INSERT INTO user_relation (customer_id, drupal_customer_id, user_type, is_seller) 
							values({$customer->getEntityId()},{$value_data['uid']},'{$u_type}',{$if_seller})";			
							$connection_magento->query($sql2);
						}					
					
						//Return Policies
						$sql_policies="SELECT return_and_exchange_policies from vespoes_policies WHERE uid='{$value_data['uid']}'";
						$result_policies=$connection->fetchAll($sql_policies);
						if(isset($result_policies[0]['return_and_exchange_policies'])){
							$_returnpolicy=$result_policies[0]['return_and_exchange_policies'];
							$_returnpolicy = str_replace("'","",$_returnpolicy);
							}
							
						//Banner Image 
						$sql_banner_fid="SELECT field_banner_fid from content_type_shop WHERE field_owner_uid='{$value_data['uid']}'";
						$result_banner_fid=$connection->fetchAll($sql_banner_fid); 	
						$banner_img=null;
						if(isset($result_banner_fid[0]['field_banner_fid'])){				
							$sql_banner="SELECT filename from files WHERE fid='{$result_banner_fid[0]['field_banner_fid']}' && uid='{$value_data['uid']}'";
							$result_banner=$connection->fetchAll($sql_banner);
							if(isset($result_banner[0])) 
							$banner_img=$result_banner[0]['filename'];
						}	
																			
						//Update in Customer Details
						$sql_customer_update="UPDATE customer_entity SET 
														is_active='{$value_data['status']}',
														lastname='{$_lastname}',
														firstname='{$_firstname}',
														created_at='{$time}',
														updated_at='{$time}',
														password_hash='{$value_data['pass']}',
														dob='{$_dob}',
														gender='{$_gender}' WHERE entity_id='{$customer->getId()}'";
						$connection_magento->query($sql_customer_update);
						printf("Customer Details UPdated");
						//Update Customer Address Details
						$sql_customer_address_update="UPDATE customer_address_entity SET
																created_at='{$time}',
																updated_at='{$time}',
																city='{$_city}',
																telephone='{$_telephone}',	
																country_id='{$_country}',
																firstname='{$_firstname}',
																lastname='{$_lastname}',
																postcode='{$_postcode}',
																street='{$_street}' WHERE parent_id='{$customer->getId()}'";
						$connection_magento->query($sql_customer_address_update);	
						printf("Customer Address UPdated");	
						//Marketplace userdata 'Seller_id'
						$sql_marketplace_load="Select seller_id FROM marketplace_userdata";
						$var_market=$connection_magento->fetchAll($sql_marketplace_load);						
						$_shoppath="";
						if(array_key_exists('shop', $ins)){
							$_shoppath=$ins['shop']->title;
						}													
						//Update in Seller Details						
						foreach($var_market as $data){ 
							if($customer->getId() == $data['seller_id']){
								$_locationSeller=null;
								if($_city != "NA"){
									$_locationSeller=$_city;
								}	 
								$sql_marketplace_update = "UPDATE marketplace_userdata SET 
																	logo_pic='{$value_data['picture']}',
																	banner_pic='{$banner_img}',
																	location='{$_locationSeller}',
																	created_at='{$time}',
																	updated_at='{$time}',
																	company_description='{$_description}',
																	return_policy='{$_returnpolicy}',
																	shop_title='{$_shoppath}',
																	shop_url='{$_shoppath}'
																	WHERE seller_id = {$var_id[0]['entity_id']}";
								$connection_magento->query($sql_marketplace_update); 
								printf("Seller Details UPdated");
								}					
							}
						}												 
				}
				//-------------------Admin User Creation------------------------------------//
				//Getting Details of Existing Admin Data
				$sql_user="Select email from admin_user";
				$result_admin=$connection_magento->fetchAll($sql_user);
				$var1= array();
				foreach($result_admin as $res1){
					$var1[] = $res1['email'];				
				}					
				//Create Admin user		
				if(in_array(3,explode(',',$value_data['rids'])) || in_array(6,explode(',',$value_data['rids'])) ){	
					if(!in_array($value_data['mail'],$var1)){
						
						$role=7;     //Update it A/c to Magento Admin Role ID
						if(in_array(3,explode(',',$value_data['rids']))){
							 $role=1;
						}
						$userModel = $userFactory->create();
						$userModel->setUserName($value_data['name']);
						$userModel->setFirstName($value_data['name']);
						$userModel->setLastName(".");					
						$userModel->setEmail($value_data['mail']);					
						$userModel->setPassword($value_data['pass']);
						$userModel->setIsActive($value_data['status']);
						$userModel->setRoleId($role);						
						$userModel->save();					
						try{
							$userModel->save(); 
						} catch (\Exception $ex) {						
							$ex->getMessage();
						}		
						echo 'Create user successfully'.$userModel->getUserId();		
							echo"<br>";		
						$sql2 = "INSERT INTO user_relation (customer_id, drupal_customer_id, user_type, is_seller)
						values({$userModel->getUserId()}, {$value_data['uid']}, '{$u_type}', {$if_seller})";			
						$connection_magento->query($sql2);
					}
				}					
	}
      
