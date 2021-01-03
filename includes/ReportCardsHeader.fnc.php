<?php
/**
 * Report Cards Form
 */
if ( ! function_exists( 'ReportCardsIncludeForm' ) )
{
	/**
	 * Get Include on Report Card form
	 *
	 * @todo Use Inputs.php functions.
	 *
	 * @example $extra['extra_header_left'] = ReportCardsIncludeForm();
	 *
	 * @since 4.0 Define your custom function in your addon module or plugin.
	 * @since 5.0 Add GPA or Total row.
	 * @since 5.0 Add Min. and Max. Grades.
	 *
	 * @global $extra Get $extra['search'] for Mailing Labels Widget
	 *
	 * @uses _getOtherAttendanceCodes()
	 *
	 * @param  string  $include_on_title Form title (optional). Defaults to 'Include on Report Card'.
	 * @param  boolean $mailing_labels   Include Mailing Labels widget (optional). Defaults to true.
	 * @return string  Include on Report Card form
	 */
	function ReportCardsIncludeForm( $include_on_title = 'Include on Report Card', $mailing_labels = true )
	{
		global $extra,
			$_ROSARIO;


			$templateDirectory = 'modules/CustomReportCard/includes/';

       //Setting the request to 0 allows for PRESENT TO be calculated on the Report
		$other_attendance_codes = _getOtherAttendanceCodes(0);

		if ( $include_on_title === 'Include on Report Card' )
		{
			$include_on_title = _( 'Include on Report Card' );
		}

		//echo $templateDirectory;
		// Open table.
		$return .= '<table class="width-100p"><tr><td colspan="4"><b>' . $include_on_title .
			'</b></td></tr><tr><td colspan="3"><table class="cellpadding-5"><tr class="st">';

			$return .= '<tr><td colspan="4">' . FormatInputTitle( _( 'Choose Data Presentation' ), '', false, '' ) .'<hr /></td></tr>';
		// Teacher.
		$return .= '<td><label><input type="checkbox" name="elements[teacher]" value="Y" checked /> ' .
		_( 'Include Home Room Teacher Name' ) . '</label></td>';

		// Comments.
		$return .= '<td><label><input type="checkbox" name="elements[comments]" value="Y" checked /> ' .
		_( 'Include Subject Comments' ) . '</label></td>';

		

		// Percents.
		$return .= '<td colspan="2"><label><input type="checkbox" name="elements[percents]" value="Y" checked> ' .
		_( 'Percents Instead of Letter Grade (Use Grades 3 - 8)' ) . '</label></td>';

		

		$return .= '</tr><tr class="st">';
// Percents.
		$return .= '<td><label><input type="checkbox" name="elements[general_average]" value="Y" checked> ' .
		_( 'Include General Average (Use Grades 3- 8)' ) . '</label></td>';

		// Tier on Report Card.
		$return .= '<td colspan="3"><label><input type="checkbox" name="elements[tiers]" value="Y"> ' .
		_( 'Include Subject, Course & Period (Kindergarten)' ) . '</label></td>';
		
$return .= '</tr><tr class="st">';
		// Add Min. and Max. Grades.
		/*$return .= '<td><label><input type="checkbox" name="elements[minmax_grades]" value="Y"> ' .
		_( 'Min. and Max. Grades' ) . '</label></td>'; */

		
		// Credits.
	/*	$return .= '<td><label><input type="checkbox" name="elements[credits]" value="Y"> ' .
		_( 'Credits' ) . '</label></td>'; */

		//$return .= '</tr><tr class="st">';

		$return .= '<tr><td colspan="4"><br>' . FormatInputTitle( _( 'Attendance Information To Include' ), '', false, '' ) .'<hr /></td></tr>';


		$return .= '<tr>';
		// Year-to-date Daily Absences.
		$return .= '<td><label><input type="checkbox" name="elements[ytd_absences]" value="Y" checked /> ' .
		_( 'Include Attendance Codes YTD' ) . '</label>';

		$return .= '<BR>';

		$return .= '<label><input type="checkbox" name="elements[mp_absences]" value="Y"' .
		( GetMP( UserMP(), 'SORT_ORDER' ) != 1 ? ' checked' : '' ) . ' /> ' .
		_( 'Include Attendance Codes Per Marking Period' ) . '</label></td>';

$return .= '<td colspan="3">';

		// Other Attendance Year-to-date.
$bob= '';
$return .= '<table><tr>';
		$return .= '<td style="vertical-align: top;"><B>ABSENT</B><br>
		<input type="radio" id="absentSummary" name="absentType" value="absentSummary" checked>
		<label for="absentSummary">Summary</label><br><br>
		<input type="radio" id="absentSubcategory" name="absentType" value="absentDetail">
		<label for="absentSubcategory">SubCategory</label><br>';

		foreach ( (array) $other_attendance_codes as $code )
		{
			
			if($code[1]['STATE_CODE'] != 'P' ){
			$bob .= '<input type="checkbox" name="ytdAbsent[]" value="' . $code[1]['ID'] . 
			'_' . $code[1]['TITLE'] . '">' . $code[1]['TITLE'] . '<br>';
			}
			
		}

$return .= $bob;
$bob= '';

		$return .= '</td>';
		$return .= '<td style="vertical-align: top;">';


		$return .= '<B>PRESENT</B><br>
		<input type="radio" id="summary" name="presentType" value="presentSummary">
		<label for="presentSummary">Summary</label><br><br>
		<input type="radio" id="subcategory" name="presentType" value="presentDetail" checked>
		<label for="presentSubcategory">SubCategory</label><br>';

		foreach ( (array) $other_attendance_codes as $code )
		{
			//Tardy is a nightmare and recorded wrong in the database.
			// No time to rewrite the Day Defects of Tardy, Left Early , etc. that are
			// Day defects but not impacting to present or not.
			
			if($code[1]['STATE_CODE'] == 'P' && $code[1]['TITLE'] != 'Tardy' ){
				if(strtoupper($code[1]['TITLE']) == 'PRESENT'){
				$titleTmp = $code[1]['TITLE'] . ' + Tardy';
			}else{
				$titleTmp = $code[1]['TITLE'];
			}
				$bob .= '<input type="checkbox" name="ytdPresent[]" value="' . $code[1]['ID'] . 
				'_' . $code[1]['TITLE'] . '" checked>' . $titleTmp . '<br>';
			}
			
		}


		
$return .= $bob;
$bob= '';

$return .= '</td>';
		$return .= '<td style="vertical-align: top;">';

		$return .= '<b>Other Included Details</b><br>';
		foreach ( (array) $other_attendance_codes as $code )
		{
			//Tardy is a nightmare and recorded wrong in the database.
			// No time to rewrite the Day Defects of Tardy, Left Early , etc. that are
			// Day defects but not impacting to present or not.
			
			if($code[1]['TITLE'] == 'Tardy' ){
				$titleTmp = $code[1]['TITLE'];
			
				$bob .= '<input type="checkbox" name="ytdTardy[]" value="' . $code[1]['ID'] . 
				'_' . $code[1]['TITLE'] . '" checked>' . $titleTmp . '<br>';
			}
			
		}
		
$return .= $bob;
$bob= '';
$return .= '</td></tr></table>';

$return .= '</td></tr>';
		
		

		$return .= '<tr><td colspan="4"><br>' . FormatInputTitle( _( 'Course Data Presentation' ), '', false, '' ) .'<hr /></td></tr>';
//Should we include a grid that has All periods even if they have not happened.
	$return .= '<tr><td colspan="4"><label><input type="checkbox" name="elements[all_periods]" value="Y" checked /> ' . _( 'Include All Marking Periods In Grid Headers' ) . '</label></td>';

	$return .= '</tr>';

	$return .= '<tr><td><label><input type="checkbox" name="elements[subject_teacher]" value="Y" /> ' . _( 'Include Subject Teacher Name with Subject' ) . '</label></td>';

	$return .= '<td colspan="3"><label>' . _( '   How Many Subjects Fit on First Page (Use 4 for Middle School):' ) . '</label><select name="courses_firstpage" id="courses_firstpage">';

	for ($i = 0; $i <11; $i++ )
		{
			$return .= '<option value="'  . $i . '">' . $i . '</option>';
		}

		$return .= '</select></td>';

		$return .= '</tr><tr>';

		$return .= '<td><label>' . _( '   Pick a Main Layout Format (ext .MAIN) :' ). '</label>';
		$return .= '<select name="main_template" id="main_template">';

			$templates = findTemplates($templateDirectory,'MAIN.html');

					foreach($templates as $file) {
						$basefile = basename($file);
					    $return .= '<option value="'  . $file . '">' . $basefile . '</option>';
					}

		$return .= '</select></td>';

		$return .= '<td><label>' . _( '   Pick a Course Format (.COURSE):' ). '</label>';
		$return .= '<select name="course_template" id="course_template">';

			$templates = findTemplates($templateDirectory,'COURSE.html');

					foreach($templates as $file) {
						$basefile = basename($file);
					    $return .= '<option value="'  . $file . '">' . $basefile . '</option>';
					}

		$return .= '</select></td>';


		$return .= '<td><label>' . _( '   Pick an Marking Row Format (.MARKROWS):' ). '</label>';
		$return .= '<select name="markrows_template" id="markrows_template">';

			$templates = findTemplates($templateDirectory,'MARKROW.html');

					foreach($templates as $file) {
						$basefile = basename($file);
					    $return .= '<option value="'  . $file . '">' . $basefile . '</option>';
					}

		$return .= '</select></td>';

		$return .= '<td><label>' . _( '   Pick an Attendance Format (.ATTENDANCE):' ). '</label>';
		$return .= '<select name="attendance_template" id="attendance_template">';

			$templates = findTemplates($templateDirectory,'ATTENDANCE.html');

					foreach($templates as $file) {
						$basefile = basename($file);
					    $return .= '<option value="'  . $file . '">' . $basefile . '</option>';
					}

		$return .= '</select></td>';

		$return .= '</tr><tr class="st">';

		// Do we want an honors certiicate 
		$return .= '<tr><td><label><input type="checkbox" name="elements[honors_certificate]" value="Y" checked/> ' . _( 'Include Honors Certificate -- Middle School Only' ) . '</label></td>';


		//Which honors certificate format
		$return .= '<td><label>' . _( '   Pick HONORS Certificate (.HONORS):' ). '</label>';
				$return .= '<select name="honors_template" id="honors_template">';

					$templates = findTemplates($templateDirectory,'HONORS.html');

							foreach($templates as $file) {
								$basefile = basename($file);
							    $return .= '<option value="'  . $file . '">' . $basefile . '</option>';
							}

		$return .= '</select></td>';




		$return .= '</tr><tr class="st">';


		if ( $_REQUEST['modname'] !== 'CustomReportCard/FinalGrades.php' )
		{
			$return .= '</tr><tr class="st">';

			// Add GPA or Total row.
			$gpa_or_total_options = array(
				'gpa' => _( 'GPA' ),
				'total' => _( 'Total' ),
			);

			if ( User( 'PROFILE' ) !== 'admin' )
			{
				$_ROSARIO['allow_edit'] = true;
			}


/* Not doing CLASS RANK
			$return .= '<td>' . RadioInput( '', 'elements[gpa_or_total]', _( 'Last row' ), $gpa_or_total_options ) . '</td>';
		
*/
		}
		$return .= '</tr></table></td></tr>';

		

		// Get the title instead of the short marking period name.
		$mps_RET = DBGet( "SELECT PARENT_ID,MARKING_PERIOD_ID,SHORT_NAME,TITLE
			FROM SCHOOL_MARKING_PERIODS
			WHERE MP='QTR'
			AND SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'
			ORDER BY SORT_ORDER", array(), array( 'PARENT_ID' ) );

		// Marking Periods.
		$return .= '<tr class="st"><td colspan="3"><hr /><table class="cellpadding-5">';

		foreach ( (array) $mps_RET as $sem => $quarters )
		{
			$return .= '<tr class="st">';

			foreach ( (array) $quarters as $qtr )
			{
				$pro = GetChildrenMP( 'PRO', $qtr['MARKING_PERIOD_ID'] );

				if ( $pro )
				{
					$pros = explode( ',', str_replace( "'", '', $pro ) );

					foreach ( (array) $pros as $pro )
					{
						if ( GetMP( $pro, 'DOES_GRADES' ) === 'Y' )
						{
							$return .= '<td><label>
								<input type="checkbox" name="mp_arr[]" value="' . $pro . '" /> ' .
							GetMP( $pro, 'TITLE' ) . '</label></td>';
						}
					}
				}


                       /* Defaulted all periods to checked */
				$return .= '<td><label>
					<input type="checkbox" name="mp_arr[]" value="' . $qtr['MARKING_PERIOD_ID'] . '" /> ' .
					$qtr['TITLE'] . '</label></td>';
			}

			if ( GetMP( $sem, 'DOES_GRADES' ) === 'Y' )
			{
				$return .= '<td><label>
					<input type="checkbox" name="mp_arr[]" value="' . $sem . '" /> ' .
				GetMP( $sem, 'TITLE' ) . '</label></td>';
			}

			$return .= '</tr>';
		}

		if ( $sem )
		{
			$fy = GetParentMP( 'FY', $sem );

			$return .= '<tr>';

			if ( GetMP( $fy, 'DOES_GRADES' ) === 'Y' )
			{
				$return .= '<td><label>
					<input type="checkbox" name="mp_arr[]" value="' . $fy . '" /> ' .
				GetMP( $fy, 'TITLE' ) . '</label></td>';
			}

			$return .= '</tr>';
		}

		$return .= '</table>' .
			FormatInputTitle( _( 'Select All Marking Periods Included "As Of Report Card"' ), '', false, '' ) .
			'<hr /></td></tr>';

		if ( $mailing_labels )
		{
			// Mailing Labels.
			Widgets( 'mailing_labels' );
		}

		if ( ! empty( $extra['search'] ) )
		{
			$return .= '<tr><td><table>' . $extra['search'] . '</table></td></tr>';
		}

		$extra['search'] = '';

		$return .= '</table>';




  $return .= $html;



		return $return;
	}
}







