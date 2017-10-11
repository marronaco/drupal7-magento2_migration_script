<?php
/*
 * code to create category programmatically - magento 2
 * */
use \Magento\Framework\App\Bootstrap;
// include magento bootstrap file
include('../app/bootstrap.php');
$mage_bootstrap = Bootstrap::create(BP, $_SERVER);
//Getting Instance of Object Manager
$object_Manager = $mage_bootstrap->getObjectManager(); 
/*
 * Creating Required Objects
 * */  
$site_url = \Magento\Framework\App\ObjectManager::getInstance();
$storeManager = $site_url->get('\Magento\Store\Model\StoreManagerInterface');
$mediaurl= $storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
$state = $object_Manager->get('\Magento\Framework\App\State');
$state->setAreaCode('frontend');
//Get Website ID
$websiteId = $storeManager->getWebsite()->getWebsiteId();
echo 'websiteId: '.$websiteId." ";

//Get Store ID 
$mage_store = $storeManager->getStore();
$store_Id = $mage_store->getStoreId();
echo 'storeId: '.$store_Id." ";

// Get Root Category ID
$root_node_id = $mage_store->getRootCategoryId();
echo 'rootNodeId: '.$root_node_id." ";
//Get Root Category
$root_cat_id = $object_Manager->get('Magento\Catalog\Model\Category');
$cat_info = $root_cat_id->load($root_node_id);
/*
 *  Drupal connection
 * Update dbname, username and password a/c to your Drupal Site DB
 * 
 * */
$connection = $object_Manager->create(
		'\Magento\Framework\App\ResourceConnection\ConnectionFactory')->create(array(
		'host' => 'localhost',
		'dbname' => 'ABCD',
		'username' => 'root',
		'password' => 'admin123',
		'active' => '1',    
		));
/*
 * Magento Connection
 * */
$resource =$object_Manager->get('Magento\Framework\App\ResourceConnection');
$connection_magento = $resource->getConnection();		

//Query for Data from Drupal Database
/*
 * Update it according to your Drupal Databse Category Detail Containing Table
 * */		
$sql_term_data="SELECT *, (SELECT parent from term_hierarchy WHERE term_hierarchy.tid=term_data.tid) AS Parent FROM `term_data` WHERE term_data.vid=4 ORDER BY `Parent` ASC";
$result =$connection->fetchAll($sql_term_data);

$categorys_data = array();
$categorys_parent = array();
$categorys_tid= array();
//Fetching Category Names from Drupal 
foreach($result as $value){   
	$categorys_data[]=$value['name'];
	$categorys_parent[]=$value['Parent'];
	$categorys_tid[]=$value['tid'];
	}
$i=0;	
foreach($categorys_data as $cat_val)
{	
	//catagory name
	$cat_name = ucfirst($cat_val);
	$site_url = strtolower($cat_val);	
	$clean_url = trim(preg_replace('/ +/', '', preg_replace('/[^A-Za-z0-9 ]/', '', urldecode(html_entity_decode(strip_tags($site_url))))));
	//Category Factory Object to add Category in Magento 2
	$category_factory=$object_Manager->get('\Magento\Catalog\Model\CategoryFactory');
	// Add a new sub category under root category
	$category_obj = $category_factory->create();
	print_r($categorys_parent[$i]);
	if($categorys_parent[$i]==0){	//Category
		$category_obj->setName($cat_name);
		
		$category_obj->setIsActive(true);
		$category_obj->setUrlKey($clean_url);
		
		$category_obj->setData('description', 'description');
		$category_obj->setParentId($root_cat_id->getId());
		// add store id 
		$category_obj->setStoreId($store_Id);
		$category_obj->setPath($root_cat_id->getPath());
		// save category
		$category_obj->save();
		/*
		 * Create a table to maintain Relation between Drupal and Magento 2 Category
		 * */
		$sql2 = "INSERT INTO category_relation (entity_id, drupal_category_tid)
					values({$category_obj->getEntityId()}, {$categorys_tid[$i]})";			
		$connection_magento->query($sql2);
		}
		
		else{  //Sub-Category(Child Category)
			//Getting updated Parent Category Id for Sub-category Id from Magento  	
			$sql3="Select entity_id from category_relation where drupal_category_tid={$categorys_parent[$i]}";
			$result_magento =$connection_magento->fetchAll($sql3);
				
			$category_obj->setName($cat_name);
			
			$category_obj->setIsActive(true);
			$category_obj->setUrlKey($clean_url);
			
			$category_obj->setData('description', 'description');		
			$category_obj->setParentId($result_magento[0]['entity_id']);	
			
			// add store id 		
			$category_obj->setStoreId($store_Id);		
			// save category	
			$category_obj->save();
			/*
			 * Create a table to maintain Relation between Drupal and Magento 2 Category
			 * */
			$sql2 = "INSERT INTO category_relation (entity_id, drupal_category_tid)
						values({$category_obj->getEntityId()}, {$categorys_tid[$i]})";			
			$connection_magento->query($sql2);
			echo "Successfully Created";
		}
	$i++;
}


// code for checking already existing category programmatically - magento 2 if you Needed

//~ $parent_cat_id = \Magento\Catalog\Model\Category::TREE_ROOT_ID;

//~ $parent_category = $this->_objectManager->create('Magento\Catalog\Model\Category')->load($parent_cat_id);
                                      
//~ $category_obj = $this->_objectManager->create('Magento\Catalog\Model\Category');

//~ // Check category exist or not
//~ $cate_data = $category_obj->getCollection()
            //~ ->addAttributeToFilter('name','CATE_NAME')
            //~ ->getFirstItem();
//~ 
//~ if(!isset($cate_data->getId())) 
//~ {
    //~ $category_obj->setPath($parent_category->getPath())
        //~ ->setParentId($parent_cat_id)
        //~ ->setName('CATE_NAME')
        //~ ->setIsActive(true);
    //~ $category_obj->save();
//~ }
