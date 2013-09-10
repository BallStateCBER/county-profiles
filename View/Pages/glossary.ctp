<h1 class="page_title">
	Glossary
</h1>

<?php
/* This file outputs a simple definition if given a valid term through $term,
 * and otherwise outputs an alphabetized list of all terms. */
$glossary = array(
	'Output' => 'Total domestic or regional production activities plus values of intermediate inputs and imported inputs',
	'Employment' => 'Full-time and part-time employment of paid employees, self-employed, and unpaid workers in family businesses',
	'Total value-added' => 'Total domestic or regional production activities which excludes values of intermediate inputs and imported inputs. In other words, it is the total payments made by industry to workers, interest, profits and indirect business taxes. Other common names for total value-added are Gross Domestic Product (GDP) and Gross Regional Product (GRP).',
	'Unemployment rate' => 'The unemployed total includes all persons who did not have a job during the reference period, were currently available for a job, and were looking for work or were waiting to be called back to a job from which they had been laid off, whether or not they were eligible for unemployment insurance. The unemployment rate is the number of unemployed divided by the number of labor force times 100. The Bureau of Labor Statistics (BLS) collects the unemployment data from the household survey. Explanatory notes and estimates of error concerning employment and earnings can be found at http://www.bls.gov/cps/eetech_intro.pdf.',
	'Wages' => 'A compensation received by a worker in exchange for their labor. This data do not include other benefits and earnings by workers.',
	'Transfer payments' => 'A government transfer of financial aid (welfare), social security, and government subsidies for certain groups of society for a purpose of income redistribution.',
	'Household income' => 'The annual pre-tax money receipts of all residents over the age of 15 in a household, which include wages and salaries, and other earnings such as the unemployment insurance, disability, child support. The residents of the household do not have to be related to the householder for their earnings to be considered part of the household\'s income.',
	'Property tax rate' => 'A tax on the assessed value of property. The tax rate multiplied by the assessed value owned by a taxpayer is what the taxpayer owes to the government; the tax rate multiplied by the total assessed value of the government is the total tax levy. The state also collects a very small part of the property tax, at a rate of one cent per $100 assessed value. The property tax is administered on the state level by the Indiana Department of Local Government Finance, and on the local level by the county and township assessors, the county auditor and the county treasurer.',
	'SPTRC rate' => 'State property tax replacement credit. The replacement factor determines how much money the tax bill is lowered by fund transfers from the State of Indiana. First, the gross rate is calculated based on the budgeted levy of the district and the prior year\'s total assessed value of property. Then, the net rate is derived by multiplying the gross rate by 1 minus the state property tax replacement factor. However, the net rate is not the final rate paid because final bills are based on updated assessed values not available when these rates are computed. The source agency for the rates is the Local Government Finance Department of the State of Indiana. For more detailed explanation see http://www.ibrc.indiana.edu/lakegov/LCGFS_Final_Report.pdf#page=24.',
	'Worker\'s compensation' => 'A form of insurance that provides compensation medical care for employees who are injured in the course of employment, in exchange for mandatory relinquishment of the employee\'s right to sue his or her employer for the tort of negligence. While plans differ between jurisdictions, provision can be made for weekly payments in place of wages (functioning in this case as a form of disability insurance), compensation for economic loss (past and future), reimbursement or payment of medical and like expenses (functioning in this case as a form of health insurance), and benefits payable to the dependents of workers killed during employment (functioning in this case as a form of life insurance). General damages for pain and suffering and punitive damages for employer negligence are generally not available in worker compensation plans.',
	'TANF' => 'TANF is the name given to the traditional "welfare" payments under the 1996 Welfare Reform Act. This act imposed participation requirements (schooling or work) and time limits and also required participants to sign personal responsibility statements on drug and alcohol use. The nation saw a significant drop in participants after 1996, but expenditures did not drop as dramatically since those who remained on the rolls required the most intensive public services. Food Stamps serve as a supplementary nutrition program and were largely unchanged because of the act. Earned Income Tax Credit (EITC) is a "negative income tax" that pays low-wage workers for remaining in the labor force.',
);
ksort($glossary);
?>
<dl class="glossary">
	<?php foreach ($glossary as $term => $definition): ?>
		<dt>
			<?php echo $term; ?>
		</dt>
		<dd>
			<?php echo $this->Text->autoLink($definition); ?>
		</dd>
	<?php endforeach; ?>
</dl>