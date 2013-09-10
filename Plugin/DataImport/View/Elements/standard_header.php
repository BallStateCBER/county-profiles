<style>
	body {font-family: Arial;}
	.success {background-color: #AFFFBA; color: green; font-weight: bold;}
	.error {background-color: #FFCBAF; color: red; font-weight: bold;}
	.notification {background-color: #CFE5FF; color: blue; font-weight: bold;}
	.redundant {color: black; font-weight: bold;}
	table.import {border: 1px solid #666; border-collapse: collapse; font-size: 8pt;}
	table.import thead {background-color: black; color: white;}
	table.import td {padding: 3px;}
	.color_code span {border: 1px solid black; margin: 0 5px; padding: 0 5px; width: 140px;}
	div.error_message, p.error_message {background-color: #FFCBAF; background-image: url('/img/cross-circle.png'); background-position: left top; background-repeat: no-repeat; border: 1px dotted #7F0000; color: #7F0000; margin: 10px 0; padding: 5px 18px;}
	div.success_message, p.success_message {background-color: #AFFFBA; background-image: url('/img/tick-circle.png'); background-position: left top; background-repeat: no-repeat; border: 1px dotted #004F00; color: #004F00; margin: 10px 0; padding: 5px 18px;}
	div.notification_message, p.notification_message {background-color: #CFE5FF; background-image: url('/img/information.png'); background-position: left top; background-repeat: no-repeat; border: 1px dotted #00006F; color: #00006F; margin: 10px 0; padding: 5px 18px;}
	#data_import .error_message, #data_import .success_message, #data_import .notification_message {
		margin: auto; width: 300px;
	}
</style>

<div id="data_import">
	<span style="font-size: 12px;">
		<?php echo $file_path; ?>
	</span>
	<table align="center"><tr><td>
		<p>
			Now reading through the import file. <a href="/data/import">[Back to index]</a>
		</p>
		<ul>
			<li>
				Color code:
				<span class="color_code">
					<span class="success">Imported data</span>
					<span class="error">Errors</span>
					<span class="notification">No action taken</span>
				</span>
			</li>
			<li>Safety is <strong><?php print ($safety) ? 'ON' : 'OFF'; ?></strong></li>
			<li>Overwriting is <strong><?php print (isset($overwrite_data) && $overwrite_data) ? 'ENABLED' : 'DISABLED'; ?></strong></li>
			<li>Max rows per page: <strong><?php print $rows_per_page ?></strong></li>
			<li>
				Auto-mode is <strong><?php print ($auto) ? 'ON' : 'OFF'; ?></strong>
				<ul>
					<?php if ($auto): ?>
						<?php if ($page_num != 0): ?>
							<li><a href="?auto=1&page=<?php echo $page_num ?>">Restart this page</a></li>
						<?php endif; ?>
						<li><a href="?auto=1&page=0">Restart from beginning</a></li>
					<?php else: ?>
						<?php if ($page_num === false): ?>
							<li><a href="?auto=1&page=0">Begin <strong>automatic</strong> incremental import</a></li>
							<li><a href="?page=0">Begin <strong>manual</strong> incremental import</a></li>
						<?php else: ?>
							<li>Begin automatic incremental import <a href="?auto=1&page=0">from the beginning</a> or <a href="?auto=1&page=<?php echo ($page_num + 1) ?>">from the next page</a></li>
						<?php endif; ?>
					<?php endif; ?>
				</ul>
			</li>
		</ul>
	</td></tr></table>
	<hr />