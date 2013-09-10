<?php 
	$this->extend('default');
	$this->Html->script('/data_import/js/import.js', array('inline' => false));
	$this->Html->css('/data_import/css/import.css', null, array('inline' => false));
?>
<?php echo $this->Html->link('&larr; Back to Import Index', array(
	'plugin' => 'data_import',
	'controller' => 'import',
	'action' => 'index'
), array('escape' => false)); ?>
<br />
<a href="/data_categories">Manage Data Categories</a><br />

<div id="data_import">
	
	<?php /* Begin header */ ?>
		<input type="hidden" id="import_flag_auto" value="<?php echo (isset($auto) && $auto) ? 1 : 0; ?>" />
		<input type="hidden" id="import_file" value="<?php echo isset($file) ? $file : ''; ?>" />
		
		<table id="import_header">
			<tbody>
				<tr>
					<th>Color code</th>
					<td class="color_code">
						<span class="success">Imported data</span>
						<span class="error">Errors</span>
						<span class="notification">No action taken</span>
					</td>
				</tr>
				<tr>
					<th>Mode</th>
					<td>
						<table class="modes">
							<thead>
								<tr>
									<td>Safety <a href="#" id="modes_info_toggler1"><img src="/data_import/img/question-small.png" /></a></td>
									<td>Overwriting <a href="#" id="modes_info_toggler2"><img src="/data_import/img/question-small.png" /></a></td>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td><?php echo ($safety) ? '<img src="/data_import/img/lock.png" title="On" alt="On" /> On' : '<img src="/data_import/img/lock-unlock.png"  title="Off" alt="Off" /> Off'; ?></td>
									<td><?php echo (isset($overwrite_data) && $overwrite_data) ? '<img src="/data_import/img/overwrite_on.png" title="Enabled" alt="Enabled"/> Enabled' : '<img src="/data_import/img/overwrite_off.png" title="Disabled" alt="Disabled" /> Disabled'; ?></td>
								</tr>
							</tbody>
						</table>
						<div id="modes_info" style="display: none;">
							<strong>Safety</strong> prevents anything to be written to or deleted from the database.
							<br />
							When <strong>Overwriting</strong> is enabled, the database will be updated even if an entry currently exists for a given datum.
							If Overwriting is disabled and the import file has a different value from an existing database entry, a warning will be shown.  
							<br />
							Safety and Overwriting are enabled and disabled in this import file's corresponding method in /app/models/import.php.
						</div>
					</td>
				</tr>
				<tr>
					<th>File</th>
					<td>
						<a href="#" id="import_directory_toggler">Directory...</a><span id="import_directory" style="display: none;"><?php echo $directory; ?></span>\<?php echo $filename; ?>
					</td>
				</tr>
				<tr class="progress">
					<th>
						Progress
						<br />
						<div id="progress_percent"></div>
					</th>
					<td>
						<table>
							<tr>
								<td style="width: 0;" id="progress_bar">&nbsp;</td>
								<td>&nbsp;</td>
							</tr>
						</table>
						<span id="time_left"></span>
					</td>
				</tr>
				<tr>
					<th>Actions</th>
					<td class="actions">
						<div id="auto_toggle">
							<a href="#" id="auto_toggler_1" <?php if ($auto): ?>class="checked"<?php endif; ?>>Auto</a>
							<a href="#" id="auto_toggler_0" <?php if (! $auto): ?>class="checked"<?php endif; ?>>Manual</a>
						</div>
						
						<div id="buttons_init" class="action_buttons">
							<a href="?page=0" id="button_begin">
								<img src="/data_import/img/control_next.png" title="Begin" />
								<span>Begin</span>
							</a>
						</div>
						<div id="buttons_post_init" class="action_buttons" style="display: none;">
							<a href="?page=0" id="button_restart">
								<img src="/data_import/img/control_auto_restart.png" title="Restart from beginning" />
								<span>Restart</span>
							</a>
							<a href="?page=<?php echo ($page - 1); ?>" id="button_prev">
								<img src="/data_import/img/control_prev.png" title="Previous" />
								<span>Previous</span>
							</a>
							<a href="?page=<?php echo $page; ?>" id="button_rerun">
								<img src="/data_import/img/control_restart.png" title="Re-run this page" />
								<span>Re-run page</span>
							</a>
							<a href="?page=<?php echo ($page + 1); ?>" id="button_next">
								<img src="/data_import/img/control_next.png" title="Next" />
								<span>Next</span>
							</a>
							<a href="?auto=0&page=<?php echo $page; ?>" id="button_pause">
								<img src="/data_import/img/control_pause.png" title="Pause" />
								<span>Pause</span>
							</a>
						</div>
						
						<div style="clear: both; padding-top: 5px;">
							<input type="text" id="import_page" value="<?php echo isset($page) ? $page : ''; ?>" style="width: 50px;"/>
							<button id="run_specified_page">Run page</button>
						</div>
					</td>
				</tr>
			</tbody>
		</table>
		<?php // $this->Js->buffer("setupImportHeader();"); ?>
	<?php /* End header */ ?>
	
	<div id="load_import_results">
		<table class="processing_import">
			<tbody>
				<?php echo $this->fetch('content'); ?>
			</tbody>
		</table>
	</div>
</div>