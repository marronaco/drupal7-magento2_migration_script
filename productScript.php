<?php
/*
 * Script to Create Products Programmetically - Magento 2
 * */
error_reporting(E_ALL);
ini_set('display_errors', 1);	
?>
<?php
//Including Bootstrap 
use Magento\Framework\App\Bootstrap;
include('../app/bootstrap.php');
$mage_bootstrap = Bootstrap::create(BP, $_SERVER);
//Object Manager to perform Operation with Drupal
$objectManager = $mage_bootstrap->getObjectManager();

$state = $objectManager->get('Magento\Framework\App\State');
$state->setAreaCode('frontend');
/*
 *  Drupal connection
 * Update dbname, username and password a/c to your Drupal Site DB
 * 
 * */
$connection = $objectManager->create(
		'\Magento\Framework\App\ResourceConnection\ConnectionFactory')->create(array(
		'host' => 'localhost',
		'dbname' => 'ABCD',
		'username' => 'root',
		'password' => 'ABCD@123345',
		'active' => '1',    
		));
/*
 * Magento Connection
 * */	
$resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
$connection_magento = $resource->getConnection();
	
//Selctiong all Products from uc_products table of Drupal		
$sql_product="SELECT * FROM `uc_products`"; 
$result_product=$connection->fetchAll($sql_product);
//Creating a new table to maintain relation between Drupal and Magento Product
$sql = "CREATE TABLE IF NOT EXISTS product_relation (drupal_nid INT , magento_product_id INT)";			
$connection_magento->query($sql);

foreach($result_product as $value_product){	
	$_count=0;
	$_product = $objectManager->create('Magento\Catalog\Model\Product'); //Product Object call for Magento
	$_product->setSku($value_product['model']);		//Setting Sku of Product 
	
	//DAtA from node Drupal TAble for Product Details
	$sql_node="SELECT * FROM `node` WHERE type='item' AND nid={$value_product['nid']}";
	$result_node= $connection->fetchAll($sql_node);
	 
		if($result_node){
			if($result_node[0]['title']){
				$_product->setName($result_node[0]['title']);	//Setting Name of Product
			}	
			$_product->setTypeId('simple');				//Setting type of Product
			$_product->setAttributeSetId(4);
			//Unix Date Conversion
			$time_created = date("Y-m-d h:i:s A T",$result_node[0]['created']);
			$time_updated = date("Y-m-d h:i:s A T",$result_node[0]['changed']);
			if($result_node[0]['created']){
				$_product->setCreatedAt($time_created); 
				$_product->setUpdatedAt($time_updated); 
				
			}
			$_product->setWebsiteIds(array(1));
			$_product->setVisibility(4);
			
			//DATA from node_revisions table for Descrption of Product	
			$sql_node_revision="SELECT body, teaser from node_revisions WHERE nid={$result_node[0]['nid']}";
			$result_node_revisions=$connection->fetchAll($sql_node_revision);	
				
			$_product->setDescription( $result_node_revisions[0]['body']);
			$_product->setShortDescription($result_node_revisions[0]['teaser']);
		}
	//DAta for Seller Information to identify Product Owner
	$sql_seller="SELECT name from users WHERE uid={$result_node[0]['uid']}";
	$result_seller=$connection->fetchAll($sql_seller);
	//Finding details of respective Seller from Magento
	$customerObj = $objectManager->create('Magento\Customer\Model\Customer')->getCollection()->addFieldToFilter('firstname' , $result_seller[0]['name'])->getData();
	/*
	 * Update Below tables according to your Drupal DB for Payment,Shipping and Policies of Product set by Seller
	 * */
	if($result_node[0]['uid'] != 0){
		$sql_vpid="SELECT vpid from table_policies WHERE uid={$result_node[0]['uid']}";
		$result_vpid=$connection->fetchAll($sql_vpid); 
		
		$sql_shipping="SELECT * from table_policies_shipping WHERE vpid={$result_vpid[0]['vpid']}";
		$result_shipping=$connection->fetchAll($sql_shipping);
		
		$sql_seller_payment="SELECT * from table_policies WHERE uid={$result_node[0]['uid']}";
		$result_seller_payment=$connection->fetchAll($sql_seller_payment);
	}
	
	//DATA from content_type_item				
	$sql_content_type_item= "SELECT * from content_type_item WHERE nid={$value_product['nid']}";
	$result_content_type_item = $connection->fetchAll($sql_content_type_item);	
		$_shopid=null;$_featuredvalue=0;$_sectionorder=null;$_preparingvalue=null;$_shopname='';$_unlistemail=null;
		$_editedvalue=null;$_allordervalue=null;
		if($result_content_type_item[0]['field_shop_id_value']){
			$_shopid=$result_content_type_item[0]['field_shop_id_value'];
			}	
		if($result_content_type_item[0]['field_featured_value']){
			$_featuredvalue=$result_content_type_item[0]['field_featured_value'];
			}
		if($result_content_type_item[0]['field_section_order_value']){
			$_sectionorder=$result_content_type_item[0]['field_section_order_value'];
			}
		if($result_content_type_item[0]['field_preparing_value']){
			$_preparingvalue=$result_content_type_item[0]['field_preparing_value'];
			}
		if($result_content_type_item[0]['field_shop_name_value']){
			$_shopname=$result_content_type_item[0]['field_shop_name_value'];
			}
		if($result_content_type_item[0]['field_unlist_email_sent_value']){
			$_unlistemail=$result_content_type_item[0]['field_unlist_email_sent_value'];
			}
		if($result_content_type_item[0]['field_edited_value']){
			$_editedvalue=$result_content_type_item[0]['field_edited_value'];
			}
		if($result_content_type_item[0]['field_all_order_value']){
			$_allordervalue=$result_content_type_item[0]['field_all_order_value'];
			}
		//----------------Custom Attribute Value-----
		$_product->setShop_Id($_shopid);
		$_product->setShop_Name($_shopname);
		$_product->setFeatured_Value($_featuredvalue);
		$_product->setSection_Order($_sectionorder);
		$_product->setAll_Order($_allordervalue);
		$_product->setPreparing($_preparingvalue);
		$_product->setEdited_Value($_editedvalue);
		$_product->setUnlist_Email_Sent_Value($_unlistemail);
		$_product->setUrlKey($result_node[0]['title'].$value_product['model']); //URL Key
	
	//DATA from term_node 
	$sql_term_node="SELECT * from term_node WHERE nid={$value_product['nid']}";
	$result_term_node = $connection->fetchAll($sql_term_node);
	$_count = 0;
	$i = 0;
	$j = 0;
	foreach($result_term_node as $value_term_node){ 
		
	$sql_term_data="SELECT * from term_data WHERE tid={$value_term_node['tid']}";
	$result_term_data= $connection->fetchAll($sql_term_data); 
	//Skip conditions a/c to refernce from Drupal
	//If you have direct refernce for Material,category and tags of Product
	//Update the condition a/c to your Drupal Data Structure
	if($result_term_data[0]['vid']==2){
		if($_count>=1){
			$_tag=$_tag.','.$result_term_data[0]['name'];
			$_count++;
			}else{ 
				$_tag=$result_term_data[0]['name'];
				$_count++;
				}	}	
	
	if($result_term_data[0]['vid']==3){
			if($i>=1){
				$_material=$_material.','.$result_term_data[0]['name'];
				$i++;
				}else{ 
					$_material=$result_term_data[0]['name'];
					$i++;
					 }	}
	
	if($result_term_data[0]['vid']==4){
		if($j>=1){
			$_category[$j] = $result_term_data[0]['name'];
			$j++;
			}else{ 
				$_category[$j] = $result_term_data[0]['name'];
				$j++;
				 }		
		}	
	}
	
	$_categoryID = array();
	$_product->setTags($_tag);	
	$_product->setMaterials($_material);
	//Setting Category of Respective Product
	for($n=0;$n<$j;$n++){
				
	$category_factory=$objectManager->get('\Magento\Catalog\Model\CategoryFactory')->create();
	$_categoryid = $category_factory->getCollection()->setStore(0)->addFieldToFilter('name' , $_category[$n])->getData();
	
	$_categoryID[]=$_categoryid[0]['entity_id'];
	
	}
	$_product->setCategoryIds($_categoryID);  
	
	$_product->setWeight($value_product['weight']); 
	$_product->setWeight_Unit($value_product['weight_units']);
	$_product->setUnit_of_Dimension($value_product['length_units']);
	$_product->setLength($value_product['length']);
	$_product->setWidth($value_product['width']);
	$_product->setHeight($value_product['height']);
	
	$_product->setpackage_quantity($value_product['pkg_qty']);
	$_product->setDefault_quantity($value_product['default_qty']);
	
	$_product->setPrice($value_product['sell_price']); //price in form 11.22
	
	$_product->setSeller_Commission($value_product['cost']);
	
	$sql_node_counter="SELECT totalcount from node_counter WHERE nid={$value_product['nid']}";
	$result_count=$connection->fetchAll($sql_node_counter);
	
	$_product->setViews($result_count[0]['totalcount']);
	
	$_product->setStoreId(1);
	$_product->setStatus(1);//product status (1 - enabled, 2 - disabled)
	//~ $_product->setTaxClassId($tax); //tax class (0 - none, 1 - default, 2 - taxable, 4 - shipping)              
	$sql_product_stock="SELECT stock, threshold from uc_product_stock WHERE nid={$value_product['nid']}";
	$result_product_stock=$connection->fetchAll($sql_product_stock);
	$_product->setThreshold($result_product_stock[0]['threshold']);
	$_product->setStockData(array(
			'use_config_manage_stock' => 0, //'Use config settings' checkbox
			'manage_stock' => 1, //manage stock
			'min_sale_qty' => 1, //Minimum Qty Allowed in Shopping Cart
			'max_sale_qty' => $result_product_stock[0]['stock'], //Maximum Qty Allowed in Shopping Cart
			'is_in_stock' => 1, //Stock Availability
			'qty' => $result_product_stock[0]['stock'] //qty
			)
		);
	
	$_product->setData('color', " ");
	$_product->setManufacturer($result_seller[0]['name']);
	/*
	 * Updating Product Relation Table for future refernce
	 * Update Query a/c to your Script
	 * */
	if($result_product_magento){
			$sql_product_relation = "INSERT INTO product_relation (drupal_nid, magento_product_id) VALUES ({$value_product['nid']}, {$result_product_magento[0]['entity_id']})";
			$connection_magento->query($sql_product_relation);
	}		
	
	$_product->save();

	$product = $objectManager->create('Magento\Catalog\Model\Product')->load($_product->getId());
	//for image upload
	$sql_image_cache="SELECT field_image_cache_fid from content_field_image_cache WHERE nid={$value_product['nid']}";
	$result_image_cache=$connection->fetchAll($sql_image_cache);
	
	$fileSystem = $objectManager->create('\Magento\Framework\Filesystem');   
	$mediaPath=$fileSystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA)->getAbsolutePath('tmp/catalog/product');
	foreach($result_image_cache as $value_image_cache){
		if($value_image_cache['field_image_cache_fid'] != ''){
			$sql_image_path="SELECT filepath from files WHERE fid={$value_image_cache['field_image_cache_fid']}";
			$result_image_path=$connection->fetchAll($sql_image_path);		
			$filename= $result_image_path[0]['filepath'];	
			$files=explode('/',$filename);
			$filename = end($files);
			if($filename != ''){
				
					 $filepath='/catalog/product/' .str_replace(' ', '', $filename);
					  try{
						  $product->setMediaGallery(array('images' => array(), 'values' => array()))
							->addImageToMediaGallery($filepath, array('image', 'thumbnail', 'small_image'), false, false);
						  $product->save();
					  }catch(Exception $e)
					   {
						echo $e->getMessage();
					   }

					
				}
			}
	}
}		
?>





