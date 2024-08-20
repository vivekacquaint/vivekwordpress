<?php
/*
    Template Name: Inventory System
    Template Post Type: page
*/
?>

<?php get_header(); ?>

<?php

$upload_folder = WP_CONTENT_DIR . '/inbounded/';
$successEntries = [];
$errorEntries = [];

$successCounter = 0;
$errorCounter = 0;

// Check if the folder exists
if (file_exists($upload_folder)) {
    $todayDate = date('Ymd');
    $files = glob($upload_folder . 'WUF-' . $todayDate . '*.CSV');
    $current_date = date('Ymd', current_time('timestamp'));

    if (!empty($files)) {
        global $wpdb;

        for ($i = 0; $i < count($files); $i++) {
            $csvDataValue = array();
            $csvFile = fopen($files[$i], 'r');
            $csvData = fgetcsv($csvFile);
            $file_info = pathinfo($files[$i]);
            $file_extension = $file_info['extension'];

            if ($file_info['extension'] == 'CSV' || $file_info['extension'] == 'csv') {

                while (($csvData = fgetcsv($csvFile)) !== FALSE) {
                    $csvData = array_map("utf8_encode", $csvData);
                    $csvDataValue[] = array(
                        'part_number' => trim($csvData[0]),
                        'free_stock'  => trim($csvData[1]),
                        'retail'      => trim($csvData[2]),
                        'npp'         => trim($csvData[3]),
                    );
                }

                foreach ($csvDataValue as $data) {
                    $sku = $data['part_number'];
                    $product_id = wc_get_product_id_by_sku($sku);
                    $custom_field_value = get_post_meta($product_id, '_custom_shipping_cost', true);
                    if ($product_id > 0) {
                        $successEntries[]  = array(
                            'product_id'    => $product_id,
                            'part_number'   => $data['part_number'],
                            'free_stock'    => $data['free_stock'],
                            'retail_price'  => $data['retail'],
                            'npp'           => $data['npp'],
                        );
                        $successCounter++;
                    } else {
                        $errorEntries[]  = array(
                            'part_number'   => $data['part_number'],
                            'free_stock'    => $data['free_stock'],
                            'retail_price'  => $data['retail'],
                            'npp'           => $data['npp'],
                            'reason'        => "Not Matched",
                        );
                        $errorCounter++;
                    }
                }

                if ($successEntries) {
                    foreach ($successEntries as $allDataValue) {
                        $_product_id = $allDataValue['product_id'];
                        $partNumber = $allDataValue["part_number"];
                        $freeStock = $allDataValue["free_stock"];
                        $retailPrice = $allDataValue["retail_price"];
                        $npp = $allDataValue["npp"];

                        $product_extra_price = get_post_meta($_product_id, '_custom_shipping_cost', true);

                        // Check if the product extra price is empty or zero, and update it to 4 if it is
                        if (empty($product_extra_price) || $product_extra_price == 0) {
                            $product_extra_price = 4;
                            update_post_meta($_product_id, '_custom_shipping_cost', $product_extra_price);
                        }

                        $new_price = ($retailPrice + $product_extra_price);

                        update_post_meta($_product_id, '_price', $new_price);
                        update_post_meta($_product_id, '_regular_price', $new_price);

                        $stock_status = $freeStock == 0 ? 'outofstock' : 'instock';
                        update_post_meta($_product_id, '_stock', $freeStock);
                        update_post_meta($_product_id, '_stock_status', wc_clean($stock_status));
                    }


                    $source_folder = WP_CONTENT_DIR . '/inbounded/';
                    $destination_folder = WP_CONTENT_DIR . '/outbounded/';
                    $file_name_table = 'wp_csv_file_data';

                    $new_file_name = explode("/", $files[$i]);
                    $file_name = end($new_file_name);
                    $source_path = $source_folder . $file_name;
                    $destination_path = $destination_folder . $file_name;

                    $existing_file = $wpdb->get_row("SELECT * FROM $file_name_table WHERE file_name = '$file_name'");

                    if (empty($existing_file)) {
                        $wpdb->insert(
                            $file_name_table,
                            array(
                                'file_name'   => $file_name,
                            )
                        );
                    } else {
                        $wpdb->update(
                            $file_name_table,
                            array(
                                'status'    => '1'
                            ),
                            array('file_name' => $file_name)
                        );
                    }

                    if (file_exists($source_path)) {
                        // Attempt to move the file
                        if (rename($source_path, $destination_path)) {
                            $successMessage = "Data updated successfully.";
                        }
                    } else {
                        $successMessage = 'Source file does not exist.';
                    }
                }
            } else {
                $successMessage = "Invalid File Extension. Only accepted file extension csv";
            }
        }
    } else {
        $successMessage = 'No files found in the "inbounded" folder.';
    }
} else {
    $successMessage = 'The "inbounded" folder does not exist.';
}

?>
<div style="text-align:center;">
    <p><?php echo $successMessage; ?></p>
</div>
<?php

// Send email on success
if ($successCounter > 0) {
    $to = 'vivek.g@dolphinwebsolutions.com'; // Replace with the recipient's email address
    $subject = 'CSV Upload Success';

    $message = '<html>
                <head>
                    <style>
                        table {
                            border-collapse: collapse;
                            width: 100%;
                            margin:10px 0;
                        }
                        th, td {
                            border: 1px solid black;
                            padding: 8px;
                            text-align: left;
                        }
                        th {
                            background-color: #f2f2f2;
                        }
                    </style>
                </head>
                <body>';

    $message = '<table>
                    <thead>
                        <tr>
                            <th colspan="4">Success Entry</th>
                        </tr>
                        <tr>
                            <th>Part Number</th>
                            <th>Retail Price</th>
                            <th>Free Stock</th>
                            <th>Npp</th>
                        </tr>
                    </thead>
                    <tbody>';
    foreach ($successEntries as $entry) {
        $message .= '<tr>
                        <td>' . $entry['part_number'] . '</td>
                        <td>' . $entry['free_stock'] . '</td>
                        <td>' . $entry['retail_price'] . '</td>
                        <td>' . $entry['npp'] . '</td>
                    </tr>';
    }

    $message .= '</tbody>
                </table>';

    $message .= '<table>
                    <thead>
                        <tr>
                            <th colspan="5">Error Entry</th>
                        </tr>
                        <tr>
                            <th>Part Number</th>
                            <th>Retail Price</th>
                            <th>Free Stock</th>
                            <th>Npp</th>
                            <th>Reason</th>
                        </tr>
                    </thead>
                    <tbody>';

    foreach ($errorEntries as $entry) {
        $message .= '<tr>
                        <td>' . $entry['part_number'] . '</td>
                        <td>' . $entry['free_stock'] . '</td>
                        <td>' . $entry['retail_price'] . '</td>
                        <td>' . $entry['npp'] . '</td>
                        <td>' . $entry['reason'] . '</td>
                    </tr>';
    }

    $message .= '</tbody>
                </table>';

    $message .= '</body>
            </html>';

    $headers = 'From: vivek.g@dolphinwebsolutions.com';

    // Use wp_mail() function to send the email
    if (wp_mail($to, $subject, $message, $headers)) {
        echo 'Email sent successfully.';
    } else {
        echo $message;
    }
}

?>

<?php get_footer(); ?>