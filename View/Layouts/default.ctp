<?php 
	$this->extend('DataCenter.default');
	$this->assign('sidebar', $this->element('sidebar'));
	$this->Html->script('script', array('inline' => false));
?>

<?php $this->start('subsite_title'); ?>
	<h1 id="subsite_title" class="max_width">
		<img src="/img/header.jpg" alt="County Profiles" />
	</h1>
<?php $this->end(); ?>

<div id="content">
	<?php echo $this->fetch('content'); ?>
</div>