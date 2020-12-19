<?php
ini_set("memory_limit","2048M");
/**
 * Email Report Cards to Parents
 *
 * @package Email Parents module
 */
 
require_once 'modules/CustomReportCards/includes/ReportCards.fnc.php';
require_once 'ProgramFunctions/SendEmail.fnc.php';
// @deprecated since 4.2.
require_once 'modules/Email_Parents/includes/MakeChooseCheckbox.fnc.php';

if ( file_exists( 'ProgramFunctions/Template.fnc.php' ) )
{
	// @since 3.6.
	require_once 'ProgramFunctions/Template.fnc.php';
}
else
{
	// @deprecated.
	require_once 'modules/Email_Parents/includes/Template.fnc.php';
}

if ( file_exists( 'ProgramFunctions/Substitutions.fnc.php' ) )
{
	// @since 4.3.
	require_once 'ProgramFunctions/Substitutions.fnc.php';
}
else
{
	// @deprecated.
	require_once 'modules/Email_Parents/includes/Substitutions.fnc.php';
}

//Clean out the print directory
// Folder path to be flushed 
$folder_path = dirname(__FILE__) . '/pdfreportcards/'; 
   
// List of name of files inside 
// specified folder 
$files = glob($folder_path.'/*');  
   
// Deleting all the files in the list 
foreach($files as $file) { 
   
    if(is_file($file))  
    
        // Delete the given file 
        unlink($file);  
} 



DrawHeader( ProgramTitle() );

// Send emails.
if ( isset( $_REQUEST['modfunc'] )
	&& $_REQUEST['modfunc'] === 'save'
	&& AllowEdit() )
{
	if ( isset( $_POST['mp_arr'] )
		&& isset( $_POST['student'] ) )
	{
		SaveTemplate( $_REQUEST['inputreportcardsemailtext'] );

		$message = str_replace( "''", "'", $_REQUEST['inputreportcardsemailtext'] );

		$_REQUEST['_ROSARIO_PDF'] = 'true';

		// Generate and get Report Cards HTML.
		$report_cards = ReportCardsGenerate( $_REQUEST['student'], $_REQUEST['mp_arr'] );

		if ( $report_cards )
		{
			$i=0;
			$files =array();

			// PDF
			require_once __DIR__ . '/mpdf/autoload.php';


			$st_list = "'" . implode( "','", $_REQUEST['student'] ) . "'";



			// SELECT Staff details.
			$extra['SELECT'] .= ",(SELECT st.FIRST_NAME||' '||st.LAST_NAME
		FROM STAFF st,STUDENTS_JOIN_USERS sju
		WHERE sju.STAFF_ID=st.STAFF_ID
		AND s.STUDENT_ID=sju.STUDENT_ID
		AND st.EMAIL is Not Null 
		AND st.SYEAR='" . UserSyear() . "' 
		ORDER BY st.FIRST_NAME LIMIT 1) AS A_PARENT_NAME";

	// SELECT Staff details.
	$extra['SELECT'] .= ",(SELECT st.FIRST_NAME||' '||st.LAST_NAME
		FROM STAFF st,STUDENTS_JOIN_USERS sju
		WHERE sju.STAFF_ID=st.STAFF_ID
		AND s.STUDENT_ID=sju.STUDENT_ID
		AND st.EMAIL is Not Null 
		AND st.SYEAR='" . UserSyear() . "' 
		ORDER BY st.FIRST_NAME LIMIT 1 OFFSET 1) AS B_PARENT_NAME";

//	AND st.SYEAR='" . UserSyear() . "' LIMIT 1) AS PARENT_NAME";

	$extra['SELECT'] .= ",(SELECT st.EMAIL
		FROM STAFF st,STUDENTS_JOIN_USERS sju
		WHERE sju.STAFF_ID=st.STAFF_ID
		AND s.STUDENT_ID=sju.STUDENT_ID
		AND st.EMAIL is Not Null 
		AND st.SYEAR='" . UserSyear() . "' 
		ORDER BY st.FIRST_NAME LIMIT 1) AS A_PARENT_EMAIL";

	$extra['SELECT'] .= ",(SELECT st.EMAIL
		FROM STAFF st,STUDENTS_JOIN_USERS sju
		WHERE sju.STAFF_ID=st.STAFF_ID
		AND s.STUDENT_ID=sju.STUDENT_ID
			AND st.EMAIL is Not Null 
		AND st.SYEAR='" . UserSyear() . "' 
		ORDER BY st.FIRST_NAME LIMIT 1 OFFSET 1) AS B_PARENT_EMAIL";

			$extra['WHERE'] = " AND s.STUDENT_ID IN (" . $st_list . ")";

			$student_RET = GetStuList( $extra );

			// echo '<pre>'; var_dump($student_RET); echo '</pre>';

			$error_email_list = array();

				$lastRecord = count($student_RET);


					

			foreach ( (array) $student_RET as $students )
			{


							$defaultConfig = (new Mpdf\Config\ConfigVariables())->getDefaults();
								$fontDirs = $defaultConfig['fontDir'];

								$defaultFontConfig = (new Mpdf\Config\FontVariables())->getDefaults();
								$fontData = $defaultFontConfig['fontdata'];
								$mpdf = new \Mpdf\Mpdf(['orientation' => 'L'],
										['fontdata' => $fontData + [
			        								'engraver' => [
			            								'R' => 'Englisht.ttf',
			        								],
			        								'fancyscript' => [
			            								'R' => 'Promocyja.ttf',
			        								]
			        					]
			   					 			]);
								$mpdf->SetWatermarkText('Immaculate Conception Academy',0.06);
								$mpdf->showWatermarkText = true;
								$mpdf->watermark_font = 'TimesNewRoman';
								$mpdf->SetHTMLFooter('<table width="100%">
			    						<tr>
			        						<td width="33%" style="text-align: right;font-size:9;"><i>Immaculate Conception Academy</i> </td>
			    						</tr>
										</table>');






				$bcc='';
				$count=0;
				$myto = array();
				if($students['A_PARENT_EMAIL']){
					$myto[0] = $students['A_PARENT_EMAIL'];
					//$myto[0] ='gforkin@icaknights.org';
				}
				if($students['B_PARENT_EMAIL'] > '' && $students['A_PARENT_EMAIL'] != $students['B_PARENT_EMAIL']){
					$myto[1] = $students['B_PARENT_EMAIL'];
					//$myto[1] ='gforkin@fintechllc.com';
				}

				/*
				if($students['A_PARENT_EMAIL'] > '' && $students['B_PARENT_EMAIL'] > ''){
					if($students['A_PARENT_EMAIL'] != $students['B_PARENT_EMAIL']){
					  $bcc = $students['A_PARENT_EMAIL'] . ',' . $students['B_PARENT_EMAIL'];
					  $count = 2;
					}else{
						$bcc = $students['A_PARENT_EMAIL'];
						$count=1;
					}
				}elseif($students['A_PARENT_EMAIL'] > '' && ! $students['B_PARENT_EMAIL'] ){
					$bcc = $students['A_PARENT_EMAIL'];
				}elseif(! $students['A_PARENT_EMAIL'] && $students['B_PARENT_EMAIL'] > '' ){
					$bcc = $students['B_PARENT_EMAIL'];
				}

				*/



				//$to =  'gforkin@icaknights.org';/// Take no Chance.  $student['PARENT_EMAIL'];

				$reply_to = $cc = null;

				if ( filter_var( User( 'EMAIL' ), FILTER_VALIDATE_EMAIL ) )
				{
					$reply_to = User( 'NAME' ) . ' <' . User( 'EMAIL' ) . '>';
				}

				$subject = _( 'Report Card' ) .
					' - ' . $students['FULL_NAME'];

				$substitutions = array(
					'A__PARENT_NAME__' => $students['A_PARENT_NAME'],
					'B__PARENT_NAME__' => $students['B_PARENT_NAME'],
					'__FIRST_NAME__' => $students['FIRST_NAME'],
					'__LAST_NAME__' => $students['LAST_NAME'],
					'__SCHOOL_ID__' => SchoolInfo( 'TITLE' ),
				);

				// Substitutions.
				$msg = SubstitutionsTextMake( $substitutions, $message );

				//$msg .= 'This would have been emailed to ' . $bcc;

				//$msg .;= '** This report card booklet is best printed landsape 2-sided.';
				$report_card = '';
				$report_card = $report_cards[ $students['STUDENT_ID'] ];




					if ( $report_card )
					{

						//give pdf a place to go
						//$files[$i] = dirname(__FILE__) . '/pdfreportcards/' . $students['STUDENT_ID'] .'.pdf';

					

						$mpdf->WriteHTML(utf8_encode($report_card));
						//$mpdf->Output($files[$i], 'F');

						$files[$i] = dirname(__FILE__) . '/pdfreportcards/' . $students['FIRST_NAME'] .'_' . $students['LAST_NAME'] . '.pdf';
						$pdf_name = 'Report Card ' . $students['FIRST_NAME'] .'_' . $students['LAST_NAME'] . '.pdf';
						
							$mpdf->Output($files[$i], 'F');
							unset($mpdf);
						//$pdf_file = $mpdf->Output('', 'S');

					

	//error_log('Student report card ' . $pdf_name);
	//error_log('report card ' . $pdf_file);
						// Send Email.
					/*	$result = SendEmail(
							$to,
							$subject,
							$msg,
							$reply_to,
							$bcc,
							$cc,
							array( array( $pdf_file, $pdf_name ) )
						);*/

						for($j = 0; $j < count($myto); $j++){

							$cc='';
							$to=$myto[$j];
							$bcc='';

							$result = SendEmail(
							$to,
							$subject,
							$msg,
							$reply_to,
							$bcc,
							$cc,
							array( array( $files[$i],$pdf_name) )
						);

						

						if ( ! $result )
						{
						//	$error_email_list[] = $student['PARENT_NAME'] .
						//		' (' . $student['PARENT_EMAIL'] . ')';
							$error_email_list[] = $students['A_PARENT_NAME'] . ' OR ' . $students['B_PARENT_NAME'] . ' (' . $students['A_PARENT_EMAIL'] . ' ' . $students['B_PARENT_EMAIL'] .')';
						}
						
					  }
					  // Delete PDF file.
						unlink($files[$i]);
						unset($myto);
					}
				
				$i++;
			}

			if ( ! empty( $error_email_list ) )
			{
				$error_email_list = implode( ', ', $error_email_list );

				$error[] = sprintf(
					dgettext( 'Email_Parents', 'Email not sent to: %s' ),
					$error_email_list
				);
			}

			$note[] = dgettext( 'Email_Parents', 'The report cards email routine is complete.' );
		}
		else
			$error[] = _( 'No Students were found.' );
	}
	// No Users / MP selected.
	else
		$error[] = _( 'You must choose at least one student and one marking period.' );

	unset( $_SESSION['_REQUEST_vars']['modfunc'] );

	unset( $_REQUEST['modfunc'] );
}

// Display errors if any.
if ( isset( $error ) )
{
	echo ErrorMessage( $error );
}

// Display notes if any.
if ( isset( $note ) )
{
	echo ErrorMessage( $note, 'note' );
}

// Display Search screen or Student list.
if ( empty( $_REQUEST['modfunc'] )
	|| $_REQUEST['search_modfunc'] === 'list' )
{
	// Open Form & Display Email options.
	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		echo '<form action="' . PreparePHP_SELF(
			$_REQUEST,
			array( 'search_modfunc' ),
			array( 'modfunc' => 'save' )
		) . '" method="POST">';

		$extra['header_right'] = SubmitButton( dgettext( 'Email_Parents', 'Send Report Cards to Selected Parents' ) );

		$extra['extra_header_left'] = '<table class="width-100p">';

		$template = GetTemplate();

		// Email Template Textarea.
		$extra['extra_header_left'] .= '<tr class="st"><td>' .TextAreaInput(
			$template,
			'inputreportcardsemailtext',
			_( 'Report Cards' ) . ' - ' . _( 'Email Text' ),
			'',
			false,
			'text'
		);

		$substitutions = array(
			'A__PARENT_NAME__' => _( 'A Parent Name' ),
			'B__PARENT_NAME__' => _( 'B Parent Name' ),
			'__FIRST_NAME__' => _( 'Student First Name' ),
			'__LAST_NAME__' => _( 'Student Last Name' ),
			'__SCHOOL_ID__' => _( 'School' )
		);

		$extra['extra_header_left'] .= '<tr><td>' . SubstitutionsInput( $substitutions ) . '<hr /></td></tr>';

		$extra['extra_header_left'] .= '</table>';

		// Add Include in Report Cards Log form.
		$extra['extra_header_left'] .= ReportCardsIncludeForm();
	}

	$extra['SELECT'] = ",s.STUDENT_ID AS CHECKBOX";

	// SELECT Staff details.
	$extra['SELECT'] .= ",(SELECT st.FIRST_NAME||' '||st.LAST_NAME
		FROM STAFF st,STUDENTS_JOIN_USERS sju
		WHERE sju.STAFF_ID=st.STAFF_ID
		AND s.STUDENT_ID=sju.STUDENT_ID
		AND st.EMAIL is Not Null 
		AND st.SYEAR='" . UserSyear() . "' 
		ORDER BY st.FIRST_NAME LIMIT 1) AS A_PARENT_NAME";

	// SELECT Staff details.
	$extra['SELECT'] .= ",(SELECT st.FIRST_NAME||' '||st.LAST_NAME
		FROM STAFF st,STUDENTS_JOIN_USERS sju
		WHERE sju.STAFF_ID=st.STAFF_ID
		AND s.STUDENT_ID=sju.STUDENT_ID
		AND st.EMAIL is Not Null 
		AND st.SYEAR='" . UserSyear() . "' 
		ORDER BY st.FIRST_NAME LIMIT 1 OFFSET 1) AS B_PARENT_NAME";

//	AND st.SYEAR='" . UserSyear() . "' LIMIT 1) AS PARENT_NAME";

	$extra['SELECT'] .= ",(SELECT st.EMAIL
		FROM STAFF st,STUDENTS_JOIN_USERS sju
		WHERE sju.STAFF_ID=st.STAFF_ID
		AND s.STUDENT_ID=sju.STUDENT_ID
		AND st.EMAIL is Not Null 
		AND st.SYEAR='" . UserSyear() . "' 
		ORDER BY st.FIRST_NAME LIMIT 1) AS A_PARENT_EMAIL";

	$extra['SELECT'] .= ",(SELECT st.EMAIL
		FROM STAFF st,STUDENTS_JOIN_USERS sju
		WHERE sju.STAFF_ID=st.STAFF_ID
		AND s.STUDENT_ID=sju.STUDENT_ID
			AND st.EMAIL is Not Null 
		AND st.SYEAR='" . UserSyear() . "' 
		ORDER BY st.FIRST_NAME LIMIT 1 OFFSET 1) AS B_PARENT_EMAIL";

	// ORDER BY Name.
	$extra['ORDER_BY'] = 'FULL_NAME';

	// Call functions to format Columns.
	$extra['functions'] = array( 'CHECKBOX' => '_makeChooseCheckbox' );

	// Columns Titles.
	$extra['columns_before'] = array(
		'CHECKBOX' => MakeChooseCheckbox( '', '', 'student' ),
	);

	$extra['columns_after'] = array(
		'A_PARENT_NAME' => _( '1st Parent Name' ),
		'A_PARENT_EMAIL' => _( '1st Email' ),
		'B_PARENT_NAME' => _( '2nd Parent Name' ),
		'B_PARENT_EMAIL' => _( '2nd Email' ),
	);

	// No link for Student's name.
	$extra['link'] = array( 'FULL_NAME' => false );

	// Remove Current Student if any.
	$extra['new'] = true;

	// Display Search screen or Search Students.
	Search( 'student_id', $extra );

	// Submit & Close Form.
	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		echo '<br /><div class="center">' .
			SubmitButton( dgettext( 'Email_Parents', 'Send Report Cards to Selected Parents' ) ) . '</div>';
		echo '</form>';
	}
}


/**
 * Make Choose Checkbox
 *
 * Local function
 * DBGet() callback
 *
 * @uses MakChooseCheckbox
 *
 * @param  string $value  STUDENT_ID value.
 * @param  string $column 'CHECKBOX'.
 *
 * @return string Checkbox or empty string if no Email or no Referrals
 */
function _makeChooseCheckbox( $value, $column )
{
	global $THIS_RET;

	// If valid email & has Referrals.
	if ( filter_var( $THIS_RET['A_PARENT_EMAIL'], FILTER_VALIDATE_EMAIL ) )
	{
		return MakeChooseCheckbox( $value, $column );
	}
	else
		return '';
}