<?php
// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
  
// include database and object files
include_once '../config/database.php';
include_once '../objects/product.php';
  
// instantiate database and product object
$database = new Database();
$db = $database->getConnection();
  
// initialize object
$product = new Product($db);
  
// read products will be here
// query products
$stmt = $product->read();
$num = $stmt->rowCount();
  
// check if more than 0 record found
if($num>0){
  
    // products array
    $products_arr=array();
    $products_arr["records"]=array();

    // retrieve our table contents
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        // extract row
        // this will make $row['name'] to
        // just $name only
        extract($row);
  
        $product_item=array(
            "num" => $iNum,
            "name" => $iName,
            "cost" => $iCost,
            "image" => $iImage
        );
    
        array_push($products_arr["records"], $product_item);
    }

    $random_products_arr=array();
    $random_products_arr["records"]=array();

    $rand_keys = array_rand($products_arr["records"], 4);

    array_push($random_products_arr, $products_arr["records"][$rand_keys[0]]);
    array_push($random_products_arr, $products_arr["records"][$rand_keys[1]]);
    array_push($random_products_arr, $products_arr["records"][$rand_keys[2]]);
    array_push($random_products_arr, $products_arr["records"][$rand_keys[3]]);
    

    // set response code - 200 OK
    http_response_code(200);
  
    // show products data in json format
    echo json_encode($random_products_arr);
}
else{
  
    // set response code - 404 Not found
    http_response_code(404);
  
    // tell the user no products found
    echo json_encode(
        array("message" => "No products found.")
    );
}