<?php
$title='GIS Map';
require __DIR__.'/partials/header.php';
?>
<h1>GIS Land Bank Map</h1>
<section class="panel">
<div class="filter">
<select id="map-type" onchange="refreshMap()">
<option value="ALL">All</option>
<option value="DEPOSIT">Deposit</option>
<option value="WITHDRAW">Withdraw</option>
</select>
<select id="map-status" onchange="refreshMap()">
<option value="ALL">All Status</option>
<option value="PENDING_SUBCITY">Pending Sub-City</option>
<option value="PENDING_CITY">Pending City</option>
<option value="APPROVED">Approved</option>
<option value="REJECTED">Rejected</option>
<option value="RETURNED">Returned</option>
</select>
<button class="btn" onclick="locateMe()">My Location</button>
</div>
<div id="full-map" class="map tall"></div>
</section>
<script>window.addEventListener('load',()=>loadMap('full-map','full'));</script>
<?php require __DIR__.'/partials/footer.php'; ?>
