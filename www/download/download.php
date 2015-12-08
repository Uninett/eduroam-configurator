<?php
$bootstrapStyle = $device->isRedirect() ? 'btn btn-warning' : 'btn btn-success';
$catStyle = $device->isRedirect() ? 'cat-btn-redirect' : 'cat-btn-download';

$title = 'eduroam ' . $idp->getDisplay();
if ($canListProfiles || $profile->getDisplay() != $idp->getDisplay()) {
	$title .= ' ' . $profile->getDisplay();
}
$title .= ' for ' . $device->getDisplay();
require dirname(__DIR__) . implode(DIRECTORY_SEPARATOR, ['', 'style', 'header.php']);
?>

<ol class="breadcrumb">
	<li><a href="../idps/?c=<?= o($idp->getCountry()) ?>">eduroam</a></li>
<?php if ($canListProfiles) { ?>
<?php if (!$profile->isRedirect()) { ?>
	<li><a href="../profiles/?idp=<?= o($idp->getEntityID()) ?>"><?= o($idp->getDisplay()) ?></a></li>
<?php } ?>
<?php } elseif ($idp->getDisplay() != $profile->getDisplay()) { ?>
	<li><?= o($idp->getDisplay()) ?></li>
<?php } if (!$profile->isRedirect()) { ?>
	<li><a href="<?= o(makeQueryString(['os' => ''])) ?>"><?= o($profile->getDisplay()) ?></a></li>
	<li class="active"><?= o($device->getDisplay()) ?></li>
<?php } else { ?>
	<li class="active"><?= o($idp->getDisplay()) ?></li>
<?php } ?>
</ol>

<div class="container">
<div class="row">

<main class="col-xs-12 col-sm-8 col-md-9 col-lg-9">
<h2><?= o($profile->getDisplay()) ?>
<?php if ($profile->getDisplay() != $idp->getDisplay()) { ?>

<small><?= o($idp->getDisplay()) ?></small>
<?php } ?>
</h2>
<?php if ($device->getEapCustomText()) { ?>
<p class="alert bg-info cat-eap-custom-text"><?= o($device->getEapCustomText()); ?></p>
<?php } ?>
<?php if ($device->getMessage()) { ?>
<p class="alert bg-warning cat-message"><?= $device->getMessage(); ?></p>
<?php } ?>

<p class="cat-download">Download your eduroam profile<br>
<a class="<?= o($bootstrapStyle) ?> <?= o($catStyle) ?>" href="<?= o($device->getDownloadLink()) ?>"><big><big>
<strong><?= o($profile->getDisplay()) ?></strong><br>
<small><small><small class="cat-device-id"><?= o($device->getDisplay()) ?></small></small></small>
</big></big></a>
</p>
<?php if ($device->isRedirect()) { ?>
<p class="alert bg-warning cat-redirect-text">
You will be redirected to <a href="<?= o($device->getRedirect()) ?>"><?= o($device->getRedirect()) ?></a>
</p>
<?php } ?>

<?php if ($device->getDeviceCustomtext()) { ?>
<p><?= o($device->getDeviceCustomtext()); ?></p>
<?php } ?>

<?php if (!$profile->isRedirect() || $canListProfiles) { ?>
<ul class="cat-alt-download">
<?php if (!$profile->isRedirect()) { ?>
<li><a href="<?= o(makeQueryString(['os' => ''])) ?>">Different operating system</a></li>
<?php } if ($canListProfiles) { ?>
<li><a href="../profiles/?idp=<?= o($idp->getEntityID()) ?>">Different profile</a></li>
<?php } ?>
</ul>
<?php } ?>

<?php if (!$profile->isRedirect()) { ?>
<hr>
<h3><?= o($device->getDisplay()) ?> Instructions</h3>
<?php if ($device->getDeviceInfo()) { ?>
<?= $device->getDeviceInfo(); ?>
<?php } ?>
<?php } ?>
</main>

<div class="col-xs-12 col-sm-4 col-md-3 col-lg-3">
<hr class="visible-xs">
<?php include 'support.php'; ?>
</div>

</div>
</div>

<?php require dirname(__DIR__) . implode(DIRECTORY_SEPARATOR, ['', 'style', 'footer.php']); ?>
