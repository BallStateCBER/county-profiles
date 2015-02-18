<table class="sortable" id="table_<?php echo $table_id ?>">
	<thead>
		<tr class="title">
			<th colspan="<?php echo count($table['columns']); ?>">
				<?php echo nl2br($table['title']); ?>
			</th>
		</tr>
		<?php if (! empty($table['col_groups'])): ?>
			<tr class="column_groups">
				<?php foreach ($table['col_groups'] as $cell): ?>
					<?php if (! $cell): ?>
						<td></td>
					<?php else: ?>
						<th colspan="<?php echo $cell['colspan']; ?>">
							<?php echo $cell['label']; ?>
						</th>
					<?php endif; ?>
				<?php endforeach; ?>
			</tr>
		<?php endif; ?>
		<tr class="sort_header">
			<?php foreach ($table['columns'] as $col_i => $col): ?>
				<th>
					<?php echo $col; ?>
				</th>
			<?php endforeach; ?>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($table['rows'] as $row): ?>
			<tr>
				<?php
					foreach ($row as $i => $cell) {
						if ($i == 0) {
							echo '<th>'.$cell.'</th>';
						} elseif (in_array($i, $table['pos_neg_columns'])) {
							$number_value = (int) str_replace('$', '', $cell);
							if ($number_value > 0) {
								echo '<td class="positive">';
							} elseif ($number_value < 0) {
								echo '<td class="negative">';
							} else {
								echo '<td>';
							}
							echo $cell.'</td>';
						} else {
							echo '<td>'.$cell.'</td>';
						}
					}
				?>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
<?php if ($table['footnote']): ?>
	<div class="footnote">
		<?php echo $table['footnote']; ?>
	</div>
<?php endif; ?>

<?php $this->Js->buffer("
	$('#table_$table_id').tablesorter();
	countyProfiles.setupScrollingTableContainer('$table_id');
"); ?>