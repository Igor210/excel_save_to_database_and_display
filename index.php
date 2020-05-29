<!DOCTYPE html>
<html lang="en">
   <head>
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<title>Report</title>
		<link rel="stylesheet" href="assets/css/bootstrap.min.css" >    
		<link rel="stylesheet" type="text/css" href="assets/css/custom.css" >
		<script type="text/javascript" src="assets/js/jquery-3.3.1.js"></script>
  		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
		</script>
	</head>

	<?php
		ini_set('display_errors', 1);
		ini_set('memory_limit', '107374182413654987');
		ini_set('max_execution_time', 7200);
		require_once __DIR__ . '/db_con.php';
		require_once __DIR__ . '/library/simplexlsx.class.php';
		require_once __DIR__ . '/library/SimpleCSV.php';
		require_once __DIR__ . '/library/SimpleXLS.php';

		global $conn;

		init();

		if( isset($_POST['action']) && $_POST['action'] == "inserted" ){
			insert_action();
		}

		function insert_action(){
			if (isset($_FILES['file']) && isset($_POST['tablename'])) {
				
				$path = $_FILES['file']['name'];
				$ext = pathinfo($path, PATHINFO_EXTENSION);
				// $target_dir = "";
				// $target_file = $target_dir . basename($_FILES["file"]["name"]);
				// move_uploaded_file($_FILES["file"]["tmp_name"], $target_file);

				if ( strtolower($ext) == 'csv' && $csv = SimpleCSV::import( $_FILES["file"]["tmp_name"] ) ) {
					$totalData = $csv;
					drawTable($totalData);
				}

				if ( strtolower($ext) == 'xls' && $xls = SimpleXLS::parse( $_FILES["file"]["tmp_name"] ) ) {
					$totalData = $xls->rows();
					drawTable($totalData);
				} else {
					echo SimpleXLS::parseError();
				}

				if ( strtolower($ext) == 'xlsx' && $xlsx = SimpleXLSX::parse( $_FILES["file"]["tmp_name"] ) ) {
					$totalData = $xlsx->rows();
					drawTable($totalData);
				} else {
					echo SimpleXLSX::parse_error();
				}
			}
		}

		function drawTable($totalData){
			global $url;
			global $conn;
			global $DB_name;

			$realAry = []; //column array
			$forChkRepeatColumnAry = [];
			foreach ($totalData[0] as $key => $value) {
				if(!empty($value)){
					if( isset($forChkRepeatColumnAry[$value]) ){
						$realAry[$key] = $value." (1)";
						$forChkRepeatColumnAry[$value." (1)"] = $key;
					} else {
						$realAry[$key] = $value;
						$forChkRepeatColumnAry[$value] = $key;
					}
				}
			}
			$cols = count($realAry);
			
			$tblname = $_POST['tablename'];

			global $field;
			$field = "(";
			$tbldata = "";
			
			foreach ($realAry as $key => $value) {
				if($key == ($cols-1)){						
					$field.= "`". $value . "`)" ;
					$tbldata.= "`" . ($conn->real_escape_string(trim($value,"'"))) . "`" . " VARCHAR(250) NOT NULL" ;
					
				}else{
					$field.= "`". $value . "`," ;
					$tbldata.= "`" . ($conn->real_escape_string(trim($value,"'"))) . "`" . " VARCHAR(250) NOT NULL," ;
				}
			}

			$sql = "SELECT * FROM information_schema.tables WHERE table_schema = '".$DB_name."' AND table_name = '".$tblname."' LIMIT 1";
			$rst = $conn->query($sql);

			if( isset($rst->num_rows) && $rst->num_rows == 0 ){
				createTable($tblname, $tbldata);
				// save tablename in tablelist
				$tb_insert_sql = "INSERT INTO tablelist (`file_name`, `table_name`)
										VALUES ('".$_POST['file_name']."', '".$_POST['tablename']."')";
				$conn->query($tb_insert_sql);
			}

			foreach ( $totalData as $k => $r ) {

				if ($k == 0) {
					
				}else{
					global $field;
					$sql="INSERT INTO `" . $tblname . "` ". $field ." VALUES (";
					for ( $i = 0; $i < $cols; $i ++ ) {
						if(!empty($totalData[0][$i])){
							$sql .= "'" . ( isset($r[ $i ]) ? ($conn->real_escape_string($r[ $i ])) : "" ) ."'";
							if($i < $cols -1)
								$sql.=  "," ;
						}
					}
					$sql .= ");";

					$conn->query($sql);
				}
			}
			echo '<div class="alert alert-success">
			  <strong>Success!</strong> Data saved successfully...
			</div>';
		}

		// Making table list
		function tablelist(){
			global $conn;
			global $DB_name;
			$sql = "SELECT * FROM information_schema.tables WHERE table_schema = '".$DB_name."' AND table_name = `tablelist` LIMIT 1";
			$rst = $conn->query($sql);
			if( empty($rst) ){
				$tbldata = "`file_name` VARCHAR(250) NOT NULL, `table_name` VARCHAR(250) NOT NULL";
				createTable('tablelist', $tbldata);
			}
		}

		function createTable($tblname, $tbldata){
			global $conn;
			$sql = "DROP TABLE IF EXISTS " . $tblname . ";";
			$conn->query($sql);
			$sql = "CREATE TABLE `".$conn->real_escape_string($tblname)."` ( iid INT(19) UNSIGNED AUTO_INCREMENT PRIMARY KEY, ".$tbldata.")  ENGINE=MyISAM CHARACTER SET latin1;";
			return $conn->query($sql);
		}

		function init() {
			// tablelist();
		}

	    $sql = "SELECT * FROM `tablelist` ORDER BY iid DESC";
	    $result = mysqli_query($conn,$sql);
	    $tablenameAry = [];
	    if( isset($result->num_rows) && $result->num_rows >0 ){
			while($row = mysqli_fetch_array($result))
			{
			    $tablenameAry[] = $row;
			}
	    }

		// for selecting ---
		$tableName = '';

		if ( isset( $_GET['tblName'] ) ) {
			$tableName = $_GET['tblName'];
		} else if ( isset( $_POST['tablename'] ) ) {
			$tableName = $_POST['tablename'];
		} else {
			if( count($tablenameAry) > 0 ){
				$tableName = $tablenameAry[0]['table_name'];
			}
		}
	?>

	<body>
	<div class="loading_cover row" style="display: none;">
		<div class="loading"></div>
	</div>
	<div class="container">
		<div class="row mt-100">
			<form method="get" id="gettabledata_form">
				<div class="pull-left">
					<select class="form-control" id="tblNameSel" name="tblName">
						<?php 
						foreach ($tablenameAry as $key => $value) {
							echo '<option '. (($tableName == $value['table_name']) ? "selected='selected'" : "") .' value="'. $value['table_name'] .'">'. $value['file_name'] .'</option>';
						}
						 ?>
				  	</select>
				</div>
			</form>
			<form method="post" id="save_form" enctype="multipart/form-data">
				<div class="pull-left ml-30">
					<button type="button" class="btn btn-primary" id="import"> &nbsp; Import <span class="glyphicon glyphicon-open"></span>&nbsp; </button>
				</div>
				<div class="pull-left ml-30">
					<button type="button" class="btn btn-warning" id="table_del"> Delete table <span class="glyphicon glyphicon-floppy-remove"></span> </button>
				</div>
				<div class="pull-left ml-30">
					<button type="button" class="btn btn-danger" id="deleted" style="display: none;"> Delete Item(s) <span class="glyphicon glyphicon-trash"></span> </button>
				</div>
				<input type="file" class="form-control" id="fileinput" name="file" style="display: none" />
				<input type="hidden" name="action" value="inserted">
				<input type="hidden" name="tablename" value="">
				<input type="hidden" name="file_name" value="">
			</form>
			<form method="get" id="search_form">
				<input type="hidden" name="tblName" value="<?php echo $tableName; ?>" />
				<input type="hidden" name="pageno" value="1" />
				<button type="submit" class="btn btn-default pull-right"><span class="glyphicon glyphicon-search"></span></button>
				<div class="pull-right">
					<input type="text" class="form-control" name="search" placeholder="Search..." value="<?php if(isset($_GET['search'])) echo $_GET['search']; ?>" />
				</div>
			</form>
		</div>
		<div class="row mt-20 table_section">
			<table id="example" class="table table-striped table-bordered table-hover">
				<?php 
					$tableHtm = '';
					$pagenationui = '';

					$sql_getcolumn = "SELECT *
						FROM INFORMATION_SCHEMA.COLUMNS
						WHERE TABLE_NAME='".$tableName."'";
					$colResult = $conn->query($sql_getcolumn);

					if (isset($_GET['pageno'])) {
			            $pageno = $_GET['pageno'];
			        } else {
			            $pageno = 1;
			        }
			        $no_of_records_per_page = 15;
			        $offset = ($pageno-1) * $no_of_records_per_page;

			        $total_pages_sql = "SELECT COUNT(*) FROM `".$tableName."`";
			        $result = mysqli_query($conn,$total_pages_sql);
			        if (isset($result->num_rows) && $result->num_rows > 0) {
				        $total_rows = mysqli_fetch_array($result)[0];
				        $total_pages = ceil($total_rows / $no_of_records_per_page);
				    }

					// thead start-----------------------------
					$tableHtm .= '<thead></tr>';

					$columnAry = [];
					if ( isset($colResult->num_rows) && $colResult->num_rows > 0) {
						$tableHtm .= '<th></th>';
					  	while($columnrow = mysqli_fetch_assoc($colResult)) {
						  	if( $columnrow['COLUMN_NAME'] != 'iid' ){
						  		$tableHtm .= '<th>'. $columnrow['COLUMN_NAME'] .'</th>';
						  		$columnAry[] = $columnrow['COLUMN_NAME'];
						  	}
					  	}
					  	// $tableHtm .= '<th width="40px"><a href="javascript:;"><span class="glyphicon glyphicon-edit"></span></a></th>';
					}
					$tableHtm .= '</tr></thead>';
					// thead end-----------------------------


			        $where = '';
			        if( isset($_GET['search']) && !empty($_GET['search']) ){
						if (count($columnAry) > 0) {
							foreach ($columnAry as $key => $columnrow) {
						  		if( $key == 0 ){
						  			$where .= " WHERE `".$columnrow."` LIKE '%".$_GET['search']."%' "; 
						  		}else {
						  			$where .= " OR `".$columnrow."` LIKE '%".$_GET['search']."%' "; 
						  		}
							}
						}
			        }
			        $sql = "SELECT * FROM `".$tableName ."`". $where ." LIMIT ".$offset.", ".$no_of_records_per_page."";
			        $res_data = mysqli_query($conn,$sql);


					if (isset($res_data->num_rows) && $res_data->num_rows > 0) {
						$kkk = 0;
					  	while($row = mysqli_fetch_assoc($res_data)) {
						  	$tableHtm .= '<tr rowid="'. $row['iid'] .'">';
							if (count($columnAry) > 0) {
								$tableHtm .= '<td><input type="checkbox" class="rowid" value="'.$row['iid'].'"></td>';
								foreach ($columnAry as $key => $columnrow) {
							  		$tableHtm .= '<td>'. $row[ $columnrow ] .'</td>';
								}
								// $tableHtm .= '<th><a href="javascript:;" title="edit" class="edited" rowid="'. $row['iid'] .'"><span class="glyphicon glyphicon-edit"></span></a></th>';
							}
							$kkk++;
						    $tableHtm .= '</tr>';
					  	}

				  		$st_nxt_cla = ''; 
				  		$st_pre_cla = ''; 
				  		$pagenum = '';
					  	if($pageno <= 1){ 
					  		$st_pre_cla = 'disabled'; 
					  		$st_pre_link = '#'; 
					  	} else { 
					  		$st_pre_link = "?pageno=".($pageno - 1); }
					  	if($pageno >= $total_pages){ 
					  		$st_nxt_cla = 'disabled'; 
					  		$st_nxt_link = '#'; 
					  	} else { 
					  		$st_nxt_link = "?pageno=".($pageno + 1); }

				  		$pagenum .= '<li>
				  				<a href="javascript:;" id="pageid_li"><input type="number" name="pageno" value="'. (isset($_GET['pageno'])?$_GET['pageno']:1) .'" /> of '. $total_pages .'</a>
				  			</li>';
				  		
					  	$pagenationui='<ul class="pagination">
					  			        <li><a href="?pageno=1">First</a></li>
					  			        <li class="'.$st_pre_cla.'">
					  			            <a href="'.$st_pre_link.'">Prev</a>
					  			        </li>'. $pagenum .'
					  			        <li class="'.$st_nxt_cla.'">
					  			            <a href="'.$st_nxt_link.'">Next</a>
					  			        </li>
					  			        <li><a href="?pageno='.$total_pages.'">Last</a></li>
					  			    </ul>';
					} else {
					  $tableHtm .= '<div style="width:100%; text-align:center;">No results</div>';
					}
					echo $tableHtm;
				 ?>
			</table>
		</div>
		<div class="pagenation_section pull-right">
			<form method="get">
				<?php echo $pagenationui; ?>
			</form>
		</div>
	</div>
	<button type="button" id="openmodal" class="btn btn-info btn-lg" data-toggle="modal" data-target="#editmodal" style="display: none">Open Modal</button>
	<!-- Modal -->
	<div id="editmodal" class="modal fade" role="dialog">
	  <div class="modal-dialog">
	    <div class="modal-content">
	    	<div class="modal-header">
	          <button type="button" class="close" data-dismiss="modal">&times;</button>
	          <h4 class="modal-title">Edit item</h4>
	        </div>
    	  	<div class="modal-body" id="edit_item_body">
	      	</div>
	        <div class="modal-footer">
	          <button type="button" class="btn btn-primary" id="edited_item_save">Save</button>
	          <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
	        </div>
	    </div>
	  </div>
	</div>

	<script type="text/javascript">
		var rids = [];
		var tb_name = '<?php echo $tableName; ?>';

		$("#import").click(function() {
			$("#fileinput").trigger("click");
		} );

		$('#fileinput').on('change', function(e) {
			$('.loading_cover').show();
			
			var filename = (e.target.files)[0].name;
			var d = new Date();
			var randomTableName = d.getFullYear()+d.getMonth()+d.getDate()+"_"+d.getHours()+d.getMinutes()+"_"+randomStr(10, filename.trim());

			$('input[name="tablename"]').val(randomTableName);
			$('input[name="file_name"]').val(filename);

		  	$("#save_form").submit();
		});

		$("#tblNameSel").change(function (){
			$('#gettabledata_form').submit();
		});

		$(".rowid").click(function() {
			var chkedAry = $(".rowid:checked");
			if( chkedAry.length > 0 ){
				$("#deleted").show();
			} else {
				$("#deleted").hide();
			}
			for( var i = 0; i < chkedAry.length; i++ ) {
				var rid = $(chkedAry[i]).val();
				rids[i] = rid;
			}
		});

		$("#deleted").click(function(){
			var currentUrl = $(location).attr("href");
			$.post( "controller.php", 
				{ 
					'state': 'deleted',
					'rids': rids,
					'tb_name': tb_name
				}, function (rst){
					if(rst == "successful"){
						alert('Success! Item(s) deleted successfully..');
						location.reload(true);
					}
				} );
		});

		$("table td").dblclick(function (){
			var rid = $(this).parent('tr').attr("rowid");

			$.post( "controller.php", 
				{ 
					'state': 'getItemData',
					'rid': rid,
					'tb_name': tb_name
				}, function (rst){
					var data = JSON.parse(rst);
					var htmlDom = '';
					$.each(data, function(key, value) {
					  	// console.log(key, value);
					  	if( key == "iid" ) {
					  		htmlDom += '<input type="hidden" class="form-control edited_input" name="'+ key +'" value='+ value +'>';
					  	} else if ( key != "iid" ) {
						  	htmlDom += '<div class="row" style="margin-bottom:15px;">\
						      	<div class="col-sm-4 text-right">\
						      		<label>'+ key +':</label>\
						      	</div>\
						      	<div class="col-sm-6">\
						      		<input type="text" class="form-control edited_input" name="'+ key +'" value='+ value +'>\
						      	</div>\
						    </div>';
					  	}
					});
					$("#edit_item_body").html(htmlDom);

					$("#openmodal").trigger("click");
				} );
		});

		$("#edited_item_save").click(function() {
			var editedItemAry = {};
			$('.edited_input').each(function (index, item){
				var key = $(item).attr('name');
				var value = $("input[name='"+key+"']").val();
				editedItemAry[key] = value;
			}).promise().done(function () { 
				// alert('Success! Item(s) edited successfully..');
				// location.reload(true);
			});

			$.post( "controller.php", 
				{ 
					'state': 'editedSave',
					'editedData': JSON.stringify(editedItemAry),
					'tb_name': tb_name
				}, function (rst){
					if(rst == "successful"){
						alert('Success! Item(s) edited successfully..');
						location.reload(true);
					}
				} );
		});

		$("#table_del").click(function (){
			var excelname = $('#tblNameSel').children("option:selected").text();
			var tb_name = $('#tblNameSel').children("option:selected").val();
			if( !confirm("Do You Want To Remove \"" + excelname.trim() + "\"") ) return;
			$.post( "controller.php", 
				{ 
					'state': 'table_del',
					'tb_name': tb_name
				}, function (rst){
					if(rst == "successful"){
						alert('Success! Table deleted successfully..');
						documentUrl = document.URL.split('?')[0];
						window.location.href = documentUrl;
					}
				} );
		});

		function randomStr(len, arr) { 
            var ans = ''; 
            for (var i = len; i > 0; i--) { 
                ans +=  
                  arr[Math.floor(Math.random() * arr.length)]; 
            } 
            return ans; 
        } 

        setTimeout(function() {
        	$('.alert').hide("500");
        }, 3500);

	 </script>
	 
	</body>
</html>