<?php $this->extend('DataCenter.default'); ?>

<?php $this->start('subsite_title'); ?>
	<h1 id="subsite_title" class="max_width">
		<img src="/img/header.jpg" alt="County Profiles" />
	</h1>
<?php $this->end(); ?>

<?php $this->assign('sidebar', $this->element('sidebar')); ?>

<div id="content">
	<?php echo $this->fetch('content'); ?>
</div>