<?php
//https://makitweb.com/import-csv-file-to-mysql-from-custom-plugin-in-wordpress/

global $wpdb;

$errorEntries = [];
$successEntries = [];

$dbId = [];
$errorCounter = 0;
$successCounter = 0;

// Table name
$tablename = $wpdb->prefix . "update_product_data";

// Import CSV
if (isset($_POST['butimport'])) {
	// File extension
	$extension = pathinfo($_FILES['import_file']['name'], PATHINFO_EXTENSION);
	// If file extension is 'csv'
	if (!empty($_FILES['import_file']['name']) && $extension == 'csv' || $extension == 'CSV') {
		$totalInserted = 0;
		// Open file in read mode
		$csvFile = fopen($_FILES['import_file']['tmp_name'], 'r');
		$csvData = fgetcsv($csvFile); // Skipping header row
		if ($csvData[0] == 'Part Number' && $csvData[1] == 'Free Stock' && $csvData[2] == 'Retail' && $csvData[3] == 'NPP') {
			while (($csvData = fgetcsv($csvFile)) !== FALSE) {
				$csvData = array_map("utf8_encode", $csvData);
				$sku       = $csvData[0];
				$productId = wc_get_product_id_by_sku($sku);

				if ($csvData[0] == '') {
					$reason = "Part number shouldn't be empty";
				} elseif (is_numeric($csvData[0])) {
					$reason = "Part Number should be alphabetic value";
				} elseif ($csvData[1] == '') {
					$reason = "Free stock shouldn't be empty";
				} elseif (!is_numeric($csvData[1])) {
					$reason = "Free stock should be numeric value";
				} elseif ($csvData[2] == '') {
					$reason = "Retails price shouldn't be empty";
				} elseif (!is_numeric($csvData[2])) {
					$reason = "Reatils price should be numeric value";
				} elseif ($csvData[3] == '') {
					$reason = "NPP shouldn't be empty";
				} elseif (!is_numeric($csvData[3])) {
					$reason = "NPP should be numeric value";
				} else {
					$reason = '';
				}

				if ($csvData[0]) {
					if ($productId == 0 || $productId == "") {
						$errorEntries[]  = array(
							'part_number' 	=> trim($csvData[0]),
							'free_stock' 	=> trim($csvData[1]),
							'retail_price' 	=> trim($csvData[2]),
							'npp'			=> trim($csvData[3]),
							'reason'		=> "Product Not Match",
						);
						$errorCounter++;
					} else {
						$successEntries[]  = array(
							'part_number' 	=> trim($csvData[0]),
							'free_stock' 	=> trim($csvData[1]),
							'retail_price' 	=> trim($csvData[2]),
							'npp'			=> trim($csvData[3]),
						);
						$successCounter++;
					}
				}
			}
		} else {
			$msg = "<h4 style='color:red; font-size: 16px;'>Please All field mandatory your csv file by serial line like [Part Number,Free Stock,Retail,NPP]</h4>";
		}
	} else {
		echo "<h3 style='color: red;'>Invalid Extension</h3>";
	}
}
?>

<!-- Form -->
<div class="container-fluid">
	<div class="row">
		<div class="col-md-12" style="margin-bottom: 10px;">
			<h2 style="font-size: 16px;">Upload CSV File</h2>
			<?php if (isset($msg)) {
				echo $msg;
			} ?>
			<form method='post' class="consultant_name" action='<?= $_SERVER['REQUEST_URI']; ?>' enctype='multipart/form-data'>
				<input type="file" name="import_file">
				<input type="submit" name="butimport" value="Upload">
			</form>
		</div>

		<?php
		if (!empty($successEntries)) { ?>
			<ul class="nav nav-tabs consultant_csv_tab">

				<li class="active succ-tab"><a data-toggle="tab" href="#succ-entry">All Entries(<?php echo $successCounter; ?>)</a></li>
				<li class="err-tab"><a data-toggle="tab" href="#err-entry">Error Entries(<?php echo $errorCounter; ?>)</a></li>
			</ul>

			<div class="tab-content">
				<div class="loader-wrapper" style="display: none;">
					<div class="progress blue">
						<h4>Loading...</h4>
						<div class="progress-value">0%</div>
					</div>
					<!-- <img src="<?php echo plugins_url("/product-csv-file-upload/img/loader.gif"); ?>"> -->
				</div>
				<div id="succ-entry" class="tab-pane fade in active">
					<form action="" method="POST" name="successentries" id="successentries">
						<table id="success_entry_data" class="table table-striped main-table-entry">
							<thead>
								<tr>
									<th>Part Number</th>
									<th>Free Stock</th>
									<th>Retail Price</th>
									<th>NPP</th>
								</tr>
							</thead>
							<tbody>
								<?php
								if (!empty($successEntries)) {
									foreach ($successEntries as $successEntriesValue) { ?>
										<tr>
											<td><?php echo $successEntriesValue['part_number']; ?></td>
											<td><?php echo $successEntriesValue['free_stock']; ?></td>
											<td><?php echo $successEntriesValue['retail_price']; ?></td>
											<td><?php echo $successEntriesValue['npp']; ?></td>
										</tr>
								<?php
									}
								} ?>
							</tbody>
						</table>
						<div class="btn-div " style="text-align: right;">
							<input type="hidden" name="success_entries" id="all_success_entries" value="" data-conv="json">
							<input type="submit" class="btn btn-primary grn-btn confirm-btn" name="confirmbtn" value="Confirm">
							<input type="reset" class="btn btn-primary red-btn" id="reset_btn" name="cancel-btn" value="Cancel" style="margin-left:10px">
						</div>
					</form>
				</div>

				<div id="err-entry" class="tab-pane fade">
					<table id="error_entry_data" class="table table-striped main-table-entry error-entry">
						<thead>
							<tr>
								<th>Part Number</th>
								<th>Free Stock</th>
								<th>Reatil Price</th>
								<th>NPP</th>
								<th>Reason</th>
							</tr>
						</thead>
						<tbody>
							<?php
							if (!empty($errorEntries)) {
								foreach ($errorEntries as $errorEntriesValue) { ?>
									<tr>
										<td><?php echo $errorEntriesValue['part_number']; ?></td>
										<td><?php echo $errorEntriesValue['free_stock']; ?></td>
										<td><?php echo $errorEntriesValue['retail_price']; ?></td>
										<td><?php echo $errorEntriesValue['npp']; ?></td>
										<td><?php echo $errorEntriesValue['reason']; ?></td>
									</tr>
							<?php
								}
							} ?>
						</tbody>
					</table>
					<div class="btn-div " style="text-align: right;">
						<input type="reset" class="btn btn-primary red-btn" id="reset_btn" name="cancel-btn" value="Cancel" style="margin-left:10px">
					</div>
				</div>
			</div>
		<?php
		} else {
			//echo "Exiting File";
		} ?>
	</div>
</div>

<script type="text/javascript">
	async function upload_success_entry(data2, entryValue, counter) {
		var percentage = ((100 / entryValue) * counter);
		percentage = parseInt(percentage);
		await $.ajax({
			type: "POST",
			url: "<?php echo site_url(); ?>/wp-admin/admin-ajax.php",
			data: {
				action: 'submit_log',
				'success_entries': data2
			},
			beforeSend: function() {
				$('.loader-wrapper').show();
				$('.confirm-btn').prop('disabled', true);
			},
			success: function(result) {
				$('.progress-value').html(percentage + '%');
				console.log("Hello", entryValue, counter);
				if (entryValue == counter) {
					$('.loader-wrapper').hide();
					location.reload();
				}
			},
		});
	}
	jQuery(document).ready(function($) {
		var counter = 1;
		var data = '<?php echo $successCounter; ?>';
		var entryValue = data / 200;
		var entryValueRem = data % 200;
		var entryValue = parseInt(entryValue);
		if (entryValueRem != 0) {
			entryValue = parseInt(entryValue) + 1;
		}
		var data2 = [];
		$("#successentries").on("submit", async function(event) {
			event.preventDefault();
			var reset_counter = 0;
			<?php
			foreach (array_chunk($successEntries, 200) as $arrayDataValue) { ?>
				reset_counter++;
				data2 = '<?php echo json_encode($arrayDataValue, JSON_UNESCAPED_UNICODE); ?>';
				// if(reset_counter>=5){
				// 	reset_counter = 0;
				// 	await upload_success_entry(data2,entryValue,counter);
				// }
				// else{
				// 	upload_success_entry(data2,entryValue,counter);
				// }
				await upload_success_entry(data2, entryValue, counter);
				counter++;

			<?php
			} ?>

			// $('.loader').hide();
			// location.reload();
		});
		$("#reset_btn").click(function() {
			location.reload();
		});
	});

	$(document).ready(function() {
		$('#error_entry_data').DataTable({
			"autoWidth": true,
			"bFilter": false,
			"lengthChange": false,
			"scrollX": true
		});
		$('#success_entry_data').DataTable({
			"bFilter": false,
			"autoWidth": true,
			"lengthChange": false,
			"scrollX": true
		});
	});
</script>

<style>
	.tab-content {
		position: relative;
	}
</style>