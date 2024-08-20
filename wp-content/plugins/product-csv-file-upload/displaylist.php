<?php
global $wpdb;
// Table name
$tablename = $wpdb->prefix . "consult_address";
$cntSQL     = "SELECT * FROM {$tablename}";
$allData     = $wpdb->get_results($cntSQL, OBJECT);
if (!empty($allData)) { ?>
   <!-- Record List -->
   <div class="container-fluid">
      <div class="row header" style="text-align:center;color:green">
         <h3>Consultant's List</h3>
      </div>
      <table id="example" class="table table-striped table-bordered table-responsive" style="width:100%">
         <thead>
            <tr>
            <tr>
               <th>Part Number</th>
               <th>Free Stock</th>
               <th>Reatil Price</th>
               <th>NPP</th>
            </tr>
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
   </div>
<?php
} ?>
<script type="text/javascript">
   $(document).ready(function() {
      $('#example').DataTable({
         "autoWidth": true,
         "scrollX": true
      });
   });
</script>