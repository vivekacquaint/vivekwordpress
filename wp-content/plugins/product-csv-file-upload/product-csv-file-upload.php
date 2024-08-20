<?php
/*
Plugin Name:  Product CSV File Upload
Description:  Product CSV  file upload and show the products. 
Version:      1.0
Author:       Synergytop Dev.
*/

// Create a new table

add_action("admin_enqueue_scripts", "register_plugin_style");
add_action("admin_menu", "customplugin_menu");

function register_plugin_style()
{
    if (isset($_GET["page"]) && ($_GET["page"] == "product-csv-file-upload" || $_GET["page"] == "allentries" || $_GET["page"] == "addnewentry")) {
        wp_register_style("my-plugin", plugins_url("product-csv-file-upload/assets/css/plugin.css"));
        wp_enqueue_style("my-plugin");
        wp_register_style("bootstrap-min", plugins_url("product-csv-file-upload/assets/css/bootstrap.min.css"));
        wp_enqueue_style("bootstrap-min");
        wp_register_style("dataTables-min", plugins_url("product-csv-file-upload/assets/css/jquery.dataTables.min.css"));
        wp_enqueue_style("dataTables-min");
        wp_enqueue_script("jquery_min_script", plugin_dir_url(__FILE__) . "assets/js/jquery.min.js", array(), "1.0");
        wp_enqueue_script("bootstrap_min_script", plugin_dir_url(__FILE__) . "assets/js/bootstrap.min.js", array(), "1.0");
        wp_enqueue_script("dataTables_script", plugin_dir_url(__FILE__) . "assets/js/jquery.dataTables.min.js", array(), "1.0");
    }
}


function webroom_add_custom_css_file_to_admin($hook)
{
    wp_enqueue_style('your_custom_css_file', plugins_url('product-csv-file-upload/css/plugin.css'));
}
add_action('admin_enqueue_scripts', 'webroom_add_custom_css_file_to_admin');


// Add menu
function customplugin_menu()
{
    add_menu_page(
        "Lettuce Product Option",
        "MAM Uploads",
        "manage_options",
        "product-csv-file-upload",
        "displayList",
        plugins_url("/product-csv-file-upload/img/icon.png"),
        13
    );
    //add_submenu_page("consultant-csv-file-upload","All Entries", "All entries","manage_options", "allentries", "displayList");
    add_submenu_page(
        "addnewentry",
        "Lettuce Entries",
        "Lettuce Entries",
        "manage_options",
        "product-csv-file-upload",
        "addEntry"
    );
    add_submenu_page(
        "product-csv-file-upload", // Parent menu slug
        "Cron Job",
        "Cron Job",
        "manage_options",
        "cron-job",
        "cronJobPageCallback"
    );
}

function cronJobPageCallback()
{ ?>
    <div class="container">
        <h1>Cron Job Settings</h1>
        <a style="background-color: #ee333c; padding: 10px 15px;color: white;font-size: 20px;text-decoration: none;margin-top: 20px;display: inline-block;" href="https://drbcarspares.co.uk/inventory-system/" target="_blank">Cron Job Start Here</a>
    </div>
<?php
}


add_action("admin_enqueue_scripts", "ds_admin_theme_style");
add_action("login_enqueue_scripts", "ds_admin_theme_style");
function ds_admin_theme_style()
{
    if (!current_user_can("manage_options")) {
        echo "<style>.update-nag, .updated, .error, .is-dismissible, .notice-warning, .e-notice__content { display: none !important; }</style>";
        echo "<style>.notice.e-notice.e-notice--warning.e-notice--dismissible{display:none !important;}</style>";
    }
}

function displayList()
{
    include "displaylist.php";
}

function addEntry()
{
    include "addentry.php";
}

add_action("wp_ajax_submit_log", "submitLog");
add_action("wp_ajax_nopriv_submit_log", "submitLog");
function submitLog()
{
    if (!empty($_POST)) {
        global $wpdb;
        $allData = json_decode(stripslashes($_POST["success_entries"]), true);
        foreach ($allData as $allDataValue) {
            $partNumber = $allDataValue["part_number"];
            $freeStock = $allDataValue["free_stock"];
            $retailPrice = $allDataValue["retail_price"];
            $npp = $allDataValue["npp"];

            $sku = $partNumber;
            $_product_id = wc_get_product_id_by_sku($sku);

            if ($_product_id > 0) {
                $product_extra_price = get_post_meta($_product_id, '_custom_shipping_cost', true);
                // Check if the product extra price is empty or zero, and update it to 4 if it is
                if (empty($product_extra_price) || $product_extra_price == 0) {
                    $product_extra_price = 4;
                    update_post_meta($_product_id, '_custom_shipping_cost', $product_extra_price);
                }

                $new_price = ($retailPrice + $product_extra_price);

                update_post_meta($_product_id, '_price', $new_price);
                update_post_meta($_product_id, '_regular_price', $new_price);

                if ($freeStock == 0) {
                    $out_of_stock_status = 'outofstock';
                    update_post_meta($_product_id, '_stock', $freeStock);
                    update_post_meta($_product_id, '_stock_status', wc_clean($out_of_stock_status));
                } else {
                    $in_stock_status = 'instock';
                    update_post_meta($_product_id, '_stock', $freeStock);
                    update_post_meta($_product_id, '_stock_status', wc_clean($in_stock_status));
                }
            } else {
                printf('Invalid SKU "%s"… Cannot update price.', $sku);
            }
        }
    } else {
        $msg = 0;
        echo json_encode($msg);
    }
    exit();
}
