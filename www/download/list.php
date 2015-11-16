<?php
$title = 'Eduroam connect utility';
require dirname(__DIR__) . implode(DIRECTORY_SEPARATOR, ['', 'style', 'header.php']);
?>

<ol class="breadcrumb">
	<li><a href="../idps/?c=<?= o($idp->getCountry()) ?>">Eduroam</a></li>
<?php if ($canListProfiles) { ?>
	<li><a href="../profiles/?idp=<?= o($idp->getEntityID()) ?>"><?= o($idp->getDisplay()) ?></a></li>
<?php } else { ?>
	<li><?= o($idp->getDisplay()) ?></li>
<?php } ?>
	<li class="active"><?= o($profile->getDisplay()) ?></li>
</ol>

<!-- <pre><?= o(json_encode($idp->getRaw())); ?></pre> -->
<!-- <pre><?= o(json_encode($profile->getRaw())); ?></pre> -->

<div class="container">
<div class="row">
<div class="col-xs-12 col-sm-12 col-md-push-9 col-md-3 col-lg-push-9 col-lg-3">
<?php include 'support.php'; ?>
</div>

<main class="col-xs-12 col-sm-12 col-md-pull-3 col-md-9 col-lg-pull-3 col-lg-9">
<h2><?= o($profile->getDisplay()) ?>
<?php if ($profile->getDisplay() != $idp->getDisplay()) { ?>

<small><?= o($idp->getDisplay()) ?></small>
<?php } ?>
</h2>
<p>Choose an installer to download</p>

<?php foreach(\Eduroam\Connect\Device::groupDevices($profile->getDevices()) as $group => $devices) { ?>
<h3><?= o($group) ?></h3>
<ul>
<?php foreach($devices as $device) { ?>
<!-- <li><pre><?= o(json_encode($device->getRaw())); ?></pre></li> -->
<li>
<a href="<?= o(makeQueryString(['os' => $device->getDeviceID()])) ?>">
<?= o($device->getDisplay()); ?>
</a>
<?php if ($device->getDeviceCustomText()) { ?>
<small><?= o($device->getDeviceCustomText()) ?></small>
<?php } ?>
</li>
<?php } ?>
</ul>
<?php } ?>
</main>
</div>
</div>

<?php require dirname(__DIR__) . implode(DIRECTORY_SEPARATOR, ['', 'style', 'footer.php']); ?>
