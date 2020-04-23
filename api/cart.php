<?php
	
	include("./config/database.php");	
	$response = new \stdClass();
	$obj = json_decode(file_get_contents('php://input'), true);

	//$response format:
	//['text'] = (string) log of what occured
	//['transmit'] = copy of object recieved
	//['errorlog'] = any error caught
	//['status'] = (boolean) operation successful - true, or failed - false
	$database = new Database();
	$db = $database->mysqliConnection();
	$response->sessiontest = $database->createSession();

	
	if($_SERVER["REQUEST_METHOD"] == "POST")
	{
		if(mysqli_connect_errno($db))
		{
			$repsonse->text = "Error with DB connect";
		}
		else
		{
			$response->transmit = "default";
			$response->text = "Triggered Post. ";
			//$response->foo = $obj['foo'];
			//$response->footype = gettype($obj['foo']);
			$response->transmit = $obj;
			
			switch (json_last_error()) 
			{
				case JSON_ERROR_NONE:
					$response->errorlog = ' - No errors';
				break;
				case JSON_ERROR_DEPTH:
					$response->errorlog = ' - Maximum stack depth exceeded';
				break;
				case JSON_ERROR_STATE_MISMATCH:
					$response->errorlog = ' - Underflow or the modes mismatch';
				break;
				case JSON_ERROR_CTRL_CHAR:
					$response->errorlog = ' - Unexpected control character found';
				break;
				case JSON_ERROR_SYNTAX:
					$response->errorlog = ' - Syntax error, malformed JSON';
				break;
				case JSON_ERROR_UTF8:
					$response->errorlog = ' - Malformed UTF-8 characters, possibly incorrectly encoded';
				break;
				default:
					$response->errorlog = ' - Unknown error';
				break;
			}
			
			switch($obj['foo'])
			{
				
				case "addCart":
					$response->text .= "add item switch";
					add_item($obj);
					break;
				case "getCart":
					$response->text .= "get cart switch";
					get_cart($obj);
					break;
				case "applyDiscount":
					$response->text .= "add discount switch";
					applyDiscount($obj);
					break;
				case "removeItem":
					$response->text .= " remove from cart switch. ";
					remove_item($obj);
					break;
				default:
					$response->text .= "unable to case switch.";
					break;
			}
			
		}
		//$response->text = "not zero";
		echo json_encode($response);
	}
	
	function add_item($transmit)
	{
		global $db, $response;
		$response->text .= "Made it to addItems in cart.php. ";
		$response->sessionSuccess = session_start();
		if (!isset($_SESSION['cart']))
			$_SESSION['cart'] = array();
		
		$found = false;

		for ($i = 0; $i < count($_SESSION['cart']); $i++ )
		{
			if ($_SESSION['cart'][$i]['id'] == $transmit['id'])
			{
				if($_SESSION['cart'][$i]['valid']){
					$response->arraySet = "true";
					$_SESSION['cart'][$i]['quantity'] += $transmit['quantity'];
				}
				else {
					$_SESSION['cart'][$i]['valid'] = true;
					$_SESSION['cart'][$i]['quantity'] = $transmit['quantity'];
				}
				$found = true;
				break;
			}
		}
		if (!$found){
			$response->arraySet = "false";
			$item = array();
			$item['id'] = intval($transmit['id']);
			$item['quantity'] = intval($transmit['quantity']);
			$item['valid'] = true;
			array_push($_SESSION['cart'], $item);
		}
		$response->cart = $_SESSION['cart'];

	}

	function get_cart($transmit)
	{
		global $db, $response;
		$j = 0;
		$response->sessionSuccess = session_start();
		$response->test =isset($_SESSION['cart']);
		$empty = isset($_SESSION['cart']) ? false : true;
		//$response->cart = $_SESSION['cart'];
		if ($empty)
		{
			$response->emptyCart = true;
			return;
		}
		else {
			$response->emptyCart = false;
			$response->items = array(array());
			
			for ($i = 0; $i < count($_SESSION['cart']); $i++ )
			{
				if (!$_SESSION['cart'][$i]['valid'])
				{
					$response->invalid = true;
					continue;
				}

				$response->invalid = false;
				$stmt = $db->prepare("Select * from items i where i.iNum = (?);");
				$stmt->bind_param("i", $_SESSION['cart'][$i]['id']);
				$stmt->execute();
			
				$stmt->bind_result($id, $name, $desc, $cost, $image);
				$stmt->store_result();
			
				if($stmt->num_rows() > 0)
				{
					
					
					
					$stmt->fetch();
					$response->items[$j] = new \stdClass();
					$response->items[$j]->id = $id;
					$response->items[$j]->name = $name;
					$response->items[$j]->desc = $desc;
					$response->items[$j]->cost = $cost;
					$response->items[$j]->image = $image;
					$response->items[$j]->quantity = $_SESSION['cart'][$i]['quantity'];
					$j++;
					
				}
			}

			if ($j == 0)
				$response->emptyCart = true;
			
			return;
		}
	}

	function applyDiscount($transmit){
		global $response, $db;
		$response->sessionSuccess = session_start();
		for ($i = 0; $i < count($_SESSION['cart']); $i++)
		{
			if ($_SESSION['cart'][$i]['id'] == $transmit['itemid'])
			{
				
				$stmt = $db->prepare("Select * from discount d where d.dCode = ? and d.dItem_id = ?;");

				$stmt->bind_param("ss", $transmit['code'], $transmit['itemid']);
				$stmt->execute();
			
				$stmt->bind_result($dNum, $dItem_id, $dFormula_id, $dCode, $dStart, $dEnd);
				$stmt->store_result();
				 
				if($stmt->num_rows() > 0)
				{
					
					$_SESSION['cart'][$i]['discountId'] = $transmit['code'];
					$response->discountApplied = true;
					return;
				}

				$response->discountApplied = false;
				return;
			}
		}

	}

	function remove_item($transmit)
	{
		global $db, $response;
		$response->text .= "Made it to remove_item in cart.php. ";
		$response->sessionSuccess = session_start();
		
		

		for ($i = 0; $i < count($_SESSION['cart']); $i++ )
		{
			if ($_SESSION['cart'][$i]['id'] == $transmit['id'])
			{
				if ($_SESSION['cart'][$i]['valid'])
				{
					$_SESSION['cart'][$i]['quantity'] -= $transmit['quantity'];
					if ($_SESSION['cart'][$i]['quantity'] < 1)
						$_SESSION['cart'][$i]['valid'] = false;
					
				}
				else{
					$response->text .= "error, reached unreachable statement in remove_item of cart.php .";
				}

				break;
			}
		}
		if (!$found){
			$response->text .= "error, failed to find Item in remove_item of cart.php ";
		}
		$response->cart = $_SESSION['cart'];
		
	}
?>