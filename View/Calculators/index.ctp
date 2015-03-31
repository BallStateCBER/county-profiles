<form method="get" id="calc_input_form">
	<div id="calc_input_container">
		<h2>Economic Impact Calculator</h2>
		<p>Enter Company Information...</p>
		<table>
			<tr>
				<th>County</th>
				<td>
					<select name="county_id" id="calc_county">
						<option value="" id="calc_county_leading_choice">Select a county...</option>
						<?php foreach ($sidebar['counties'] as $slug => $name): ?>
					 		<option value="<?php echo $slug; ?>"><?php echo $name; ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>

			<tr>
				<th>Industrial classification</th>
				<td>
					<select name="industry_id" id="calc_industry_id">
						<option value="" id="calc_industry_id_leading_choice">Select an industry...</option>
						<?php foreach ($naicsIndustries as $industry_id => $industry_name): ?>
					 		<option value="<?php echo $industry_id; ?>"><?php echo $industry_name; ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>

			<tr>
				<th>Choose input method</th>
				<td>
					<select name="option" id="calc_input_options">
						<option value="" id="calc_input_option_leading_choice">Choose one...</option>
						<option value="production">Annual Production</option>
						<option value="employees">Number of Employees</option>
					</select>
				</td>
			</tr>

			<tr id="option_a_input" style="display: none;">
				<th>Annual production (sales, in dollars):</th>
				<td>
					<input type="text" name="annual_production" id="calc_annual_production" />
				</td>
			</tr>

			<tr id="option_b_input" style="display: none;">
				<th>
					<img src="/data_center/img/icons/question.png" id="calc_employees_help_toggler" class="calc_help_toggler" />
					Annual number of employees (not FTEs):
					<div id="calc_employees_help" class="calc_help_text" style="display: none;">
						FTE: Full-time equivalents<br />
						This number can be a combination of both full-time and part-time employees.
					</div>
				</th>
				<td>
					<input type="text" name="employees" id="calc_employees" />
				</td>
			</tr>

			<tr>
				<th></th>
				<td id="calculate_button_container">
					<input type="submit" id="calculate_button" value="Calculate Impact" />
						<img src="/data_center/img/loading_small.gif" id="calc_loading" style="display: none;" />
				</td>
			</tr>
		</table>
	</div>
</form>

<div id="calc_output_container"></div>

<br class="clear" />

<?php $this->Html->script('calculator_data.js', array('inline' => false)); ?>
<?php $this->Html->script('calculator.js', array('inline' => false)); ?>
<?php $this->Js->buffer("
	setupCalculator();
"); ?>