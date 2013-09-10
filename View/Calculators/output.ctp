<div>
	<?php foreach ($output as $section => $section_info): ?>
		<table>
			<thead>
				<tr>
					<td class="help">
						&nbsp;
					</td>
					<th colspan="2">
						<?php echo $section_info['title']; ?>
					</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($section_info['rows'] as $measure => $measure_info): ?>
					<?php if (! isset($measure_info['value'])) continue; ?>
					<tr>
						<td class="help">
							<?php if (isset($measure_info['help'])): ?>
								<img 
									src="/data_center/img/icons/question.png" 
									class="calc_help_toggler" 
									onmouseover="$('#calc_<?php echo $measure; ?>_<?php echo $section; ?>_help').show();" 
									onmouseout="$('#calc_<?php echo $measure; ?>_<?php echo $section; ?>_help').hide();" 
								/>
							<?php else: ?>
								&nbsp;
							<?php endif; ?>
						</td>
						<th>
							<?php echo $measure_info['name']; ?>
							<?php if (isset($measure_info['help'])): ?>
								<div id="calc_<?php echo $measure; ?>_<?php echo $section; ?>_help" class="calc_help_text" style="display: none;">
									<?php echo $measure_info['help']; ?>
								</div>
							<?php endif; ?>
						</th>
						<td>
							<?php echo $measure_info['value']; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
			<?php if (isset($section_info['footnote'])): ?>
				<tfoot>
					<td>
						&nbsp;
					</td>
					<td>
						<?php echo $section_info['footnote']; ?>
					</td>
				</tfoot>
			<?php endif; ?>
		</table>
	<?php endforeach; ?>

	<table>
		<thead>
			<tr>
				<td class="help">
					<img 
						src="/data_center/img/icons/question.png" 
						class="calc_help_toggler" 
						onmouseover="$('#calc_taximpact_help').show();" 
						onmouseout="$('#calc_taximpact_help').hide();" 
					/>
					<div id="calc_taximpact_help" class="calc_help_text" style="display: none; margin-left: 0;">
						IBT <strong>excludes</strong> corporate profit tax, estate and gift tax, income tax, 
						social security taxes, personal motor vehicle license tax, personal property tax, 
						other personal taxes, and fines and fees
					</div>
				</td>
				<th colspan="3">
					Indirect Business Tax Impact
				</th>
			</tr>
			<tr>
				<td class="help">
					&nbsp;
				</td>
				<td>
					&nbsp;
				</td>
				<th>
					Total
				</th>
				<th>
					Direct
				</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($taxes_order as $tax_type): ?>
				<?php $row = $impact['tax_detail'][$tax_type]; ?>
				<?php if (isset($row['total'])): ?>
					<tr>
						<td class="help">
							<?php if (isset($row['help'])): ?>
								<img 
									src="/data_center/img/icons/question.png" 
									class="calc_help_toggler" 
									onmouseover="$('#calc_<?php echo $tax_type; ?>_taximpact_help').show();" 
									onmouseout="$('#calc_<?php echo $tax_type; ?>_taximpact_help').hide();" 
								/>
							<?php else: ?>
								&nbsp;
							<?php endif; ?>
						</td>
						<th>
							<?php echo $row['name']; ?>
							<?php if (isset($row['help'])): ?>
								<div id="calc_<?php echo $tax_type; ?>_taximpact_help" class="calc_help_text" style="display: none;">
									<?php echo $row['help']; ?>
								</div>
							<?php endif; ?>
						</th>
						<td>
							<?php echo $row['total'] ?>
						</td>
						<td>
							<?php echo $row['direct'] ?>
						</td>
					</tr>
				<?php endif; ?>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>