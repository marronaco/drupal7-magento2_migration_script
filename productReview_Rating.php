<?php

use Magento\Framework\App\Bootstrap;
include('../app/bootstrap.php');

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

$state = $objectManager->get('Magento\Framework\App\State');
$state->setAreaCode('frontend');
/*
 * Create Drupal connection 
 * Fetch Product reviews and rating of Product 
 * Update the static data passed with fetch data from Drupal
 * Its not been done here do it yourself 
 * */
$productId=1106;
$customerId=45;
$customerNickName="STACK EXCHANGE";
$reviewTitle="STACK EXCHANGE";
$reviewDetail="STACK EXCHANGE";
$StoreId=1;
$title="STACK EXCHANGE";

$_review = $objectManager->get('Magento\Review\Model\Review')
->setEntityPkValue($productId)
->setStatusId(\Magento\Review\Model\Review::STATUS_PENDING)
->setTitle($reviewTitle)
->setDetail($reviewDetail)
->setStoreId($StoreId)
->setEntityId(1)
->setStores(1)
->setCustomerId($customerId)
->setNickname($customerNickName)
->save();
//~ print_r($_review->getData());
echo "Review Has been saved ";

echo "/////FOR SAVING RATING /////////
     ///////////////////////////////";

/* 
 $_ratingOptions = array(
     1 => array(1 => 1,  2 => 2,  3 => 3,  4 => 4,  5 => 5),   //quality
     2 => array(1 => 6,  2 => 7,  3 => 8,  4 => 9,  5 => 10),  //value
     3 => array(1 => 11, 2 => 12, 3 => 13, 4 => 14, 5 => 15),  //price 
     4 => array(1 => 16, 2 => 17, 3 => 18, 4 => 19, 5 => 20)   //rating
);*/

//Lets Assume User Chooses Rating based on Rating Attributes called(quality,value,price,rating)
$ratingOptions = array(
            '1' => '1',
            '2' => '7',
            '3' => '13',
            '4' => '14',
            '5' => '15'
); 
print_r($ratingOptions);   

foreach ($ratingOptions as $ratingId => $optionIds) 
{     
	print_r($ratingId);
	print_r($optionIds);
       $rating = $objectManager->get('Magento\Review\Model\Rating')
                     ->setRatingId($ratingId)
                     ->setReviewId($_review->getId())
                     ->addOptionVote($optionIds, $productId)->save();
			print_r($rating->getData());			
}
printf("oooooooooooooooooo");
echo  "Latest REVIEW ID ===".$_review->getId()."</br>";     
$_review->aggregate();
echo "Rating has been saved submitted  successfully";

?>
