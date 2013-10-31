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

<?php $this->start('footer_about'); ?>
	<h3>
		About County Profiles
	</h3>
	<p>
		This site was created through a partnership between <a href="http://www.bsu.edu/bbc">Ball State's 
		Building Better Communities</a> and the Center for Business and Economic Research.
	</p>
	<p>
		The <a href="http://www.cberdata.org/">CBER Data Center</a> is a product of the Center for Business 
		and Economic Research at Ball State University.  CBER's mission is to conduct relevant and timely 
		public policy research on a wide range of economic issues affecting the state and nation.  
		<a href="http://www.bsu.edu/cber">Learn more</a>.
	</p>
<?php $this->end(); ?>

<div id="content">
	<?php echo $this->fetch('content'); ?>
</div>