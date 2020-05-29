<?php 
	require_once __DIR__ . '/db_con.php';
	global $conn;
	global $DB_name;

	if(isset($_POST) && $_POST['state'] == "deleted"){
		$rids = $_POST['rids'];
		foreach ($rids as $key => $value) {
			$sql = 'DELETE FROM `'. $_POST['tb_name'] .'` WHERE iid = '. $value;
			$conn->query($sql);
		}
		exit("successful");
	}

	if(isset($_POST) && $_POST['state'] == "getItemData"){
		$sql = 'SELECT * FROM `'. $_POST['tb_name'] .'` WHERE iid='. $_POST['rid'];
		$result = $conn->query($sql);
		$dataAry = [];
		if (isset($result->num_rows) && $result->num_rows > 0) {
		  	while($row = mysqli_fetch_assoc($result)) {
		  		$dataAry = $row;
		  	}
	  	}
	  	echo json_encode($dataAry);exit();
	}

	if(isset($_POST) && $_POST['state'] == "editedSave"){
		$data = json_decode($_POST['editedData']);
		$k = 1;
		$setfield = '';
		foreach ($data as $key => $value) {
			// echo $key." = ".$value . " || ";
			if($k == 1){
				$setfield .= '`'. $key .'` = "'. $value .'"';
			} else {
				$setfield .= ', `'. $key .'` = "'. $value .'"';
			}
			$k++;
		}
		$sql = 'UPDATE `'.$_POST['tb_name'].'` SET '. $setfield .' WHERE iid='. $data->iid;
		$conn->query($sql);
		exit("successful");
	}

	if(isset($_POST) && $_POST['state'] == "table_del"){
		$droptablesql = 'DROP TABLE `'.$_POST['tb_name'].'`';
		$conn->query($droptablesql);
		$deletetablesql = 'DELETE FROM `tablelist` WHERE table_name = "'. $_POST['tb_name'] .'"';
		$conn->query($deletetablesql);
		exit("successful");
	}