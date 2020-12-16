<?php
ini_set("memory_limit","2048M");
// Should be included first, in case modfunc is Class Rank Calculate AJAX.
require_once 'modules/CustomReportCard/includes/ClassRank.inc.php';
require_once 'modules/CustomReportCard/includes/ReportCards.fnc.php';

require_once 'ProgramFunctions/MarkDownHTML.fnc.php';
require_once 'ProgramFunctions/Template.fnc.php';
require_once 'ProgramFunctions/Substitutions.fnc.php';




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


switch($_REQUEST['modfunc']){
	case 'save':
		if ( isset( $_REQUEST['mp_arr'] )
				&& isset( $_REQUEST['st_arr'] ) )
			{
				
				//This function i sin the ReportCards.fnc.php
				$report_cards = ReportCardsGenerate( $_REQUEST['st_arr'], $_REQUEST['mp_arr'] );

				/**
				 * Report Cards array hook action.
				 *
				 * @since 4.0
				 */
				do_action( 'CustomReportCard/ReportCards.php|report_cards_html_array' );




				if ( $report_cards ){

					print('<h1>Table of Report Card Contents</h1><br>');
					$i=0;
					$files =array();

					
					// PDF
					require_once __DIR__ . '/mpdf/autoload.php';

					$st_list = "'" . implode( "','", $_REQUEST['st_arr'] ) . "'";

					// SELECT Staff details.
					$extra['SELECT'] .= ",(SELECT st.FIRST_NAME||' '||st.LAST_NAME
						FROM STAFF st,STUDENTS_JOIN_USERS sju
						WHERE sju.STAFF_ID=st.STAFF_ID
						AND s.STUDENT_ID=sju.STUDENT_ID
						AND st.SYEAR='" . UserSyear() . "' LIMIT 1) AS PARENT_NAME";

					$extra['SELECT'] .= ",(SELECT st.EMAIL
						FROM STAFF st,STUDENTS_JOIN_USERS sju
						WHERE sju.STAFF_ID=st.STAFF_ID
						AND s.STUDENT_ID=sju.STUDENT_ID
						AND st.SYEAR='" . UserSyear() . "' LIMIT 1) AS PARENT_EMAIL";

					$extra['WHERE'] = " AND s.STUDENT_ID IN (" . $st_list . ")";

					$student_RET = GetStuList( $extra );
//error_log("test" . print("<pre>".print_r($report_cards,true)."</pre>"));
					$lastRecord = count($student_RET);


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
								$mpdf->SetWatermarkText('Immaculate Conception Academy',0.07);
								$mpdf->showWatermarkText = true;
								$mpdf->watermark_font = 'TimesNewRoman';
								$mpdf->SetHTMLFooter('<table width="100%">
			    						<tr>
			        						<td width="33%" style="text-align: right;font-size:9;"><i>Immaculate Conception Academy</i> </td>
			    						</tr>
										</table>');

					foreach((array) $student_RET as $students){

//error_log("strudents info " . print_r($students));
					$report_card = $report_cards[ $students['STUDENT_ID'] ];
 

							//Options for pdf -O is landscape
								//$replacements = array('orientation' => 'landscape');

								
								
								//$mpdf->Bookmark($students['STUDENT_ID']);


								$files[$i] = dirname(__FILE__) . '/pdfreportcards/' . $students['STUDENT_ID'] .'.pdf';

								if($i == 0){
									$mpdf->WriteHTML($report_card,\Mpdf\HTMLParserMode::DEFAULT_MODE,true,false);
								}elseif($i == $lastRecord){
									$mpdf->WriteHTML($report_card,\Mpdf\HTMLParserMode::DEFAULT_MODE,false,true);
								}else{
									$mpdf->WriteHTML($report_card,\Mpdf\HTMLParserMode::DEFAULT_MODE,true,true);
								}
								
								
							

								//$mpdf->Output($files[$i], \Mpdf\Output\Destination::FILE);
								//$mpdf->Output();
								

								$i++;
				//error_log('Number of people that eat poop ' . $i);
/*
				$hrefLink = 'sis.ica.lan/modules/CustomReportCard/pdfreportcards/' . $students['STUDENT_ID'] .'.pdf';
				$childname = $students['FIRST_NAME'] .' ' . $students['LAST_NAME'] .'<BR>';
				$refLink = '<a href="http://' . $hrefLink .'" target="_blank">' .$childname . '</a>'; 
				print($refLink .'<BR>');
*/
					}// end pdf student loop
					$mpdf->Output();
					unset($mpdf);

//error_log('wht the hell');
					//$outFile = dirname(__FILE__) . '/pdfreportcards/collective.pdf';
					//mergePDFFiles($files, $outFile);
					
					//print(' this is ' . $collective);

					//$handle = PDFStart($replacements);

					//echo $report_cards_html;

					//PDFStop( $handle );
				}
				else
				{
					BackPrompt(
						sprintf(
							_( 'No %s were found. %s' ),
							_( 'Final Grades' ),_( 'Report Cards Not Generated.' )
						)
					);
					//echo 'NOT WORKING';
				}
			}
			else
			{
				BackPrompt( _( 'You must choose at least one student and one marking period.' ) );
			}
	   break;

	default:
	//This follows the Grade Search but before Generation of Report Cards
			DrawHeader( ProgramTitle() );

			if ( $_REQUEST['search_modfunc'] === 'list' )
			{
				echo '<form action="' . PreparePHP_SELF(
					$_REQUEST,
					array( 'search_modfunc' ),
					array( 'modfunc' => 'save', '_ROSARIO_PDF' => 'true' )
				) .	'" method="POST">';

				$extra['header_right'] = Buttons( _( 'Create Report Cards for Selected Students' ) );

				$extra['extra_header_left'] = ReportCardsIncludeForm();

				// @since 4.5 Add Report Cards header action hook.
				do_action( 'CustomReportCard/ReportCards.php|header' );
			}

			$extra['new'] = true;

			$extra['link'] = array( 'FULL_NAME' => false );

			$extra['SELECT'] = ",s.STUDENT_ID AS CHECKBOX";

			$extra['functions'] = array( 'CHECKBOX' => 'MakeChooseCheckbox' );

			$extra['columns_before'] = array(
				'CHECKBOX' => MakeChooseCheckbox( 'Y', '', 'st_arr' )
			);

			$extra['options']['search'] = false;

			// Parent: associated students.
			$extra['ASSOCIATED'] = User( 'STAFF_ID' );


/* Dont need to select 1 course
			Widgets( 'course' );
*/

			Search( 'student_id', $extra );

			if ( $_REQUEST['search_modfunc'] === 'list' )
			{
				echo '<br /><div class="center">' .
					Buttons( _( 'Create Report Cards for Selected Students' ) ) . '</div>';

				echo '</form>';


/* We dont do Class Rank
				// SYear & Semester MPs only, including History MPs.
				$mps_RET = DBGet( "SELECT MARKING_PERIOD_ID
					FROM MARKING_PERIODS
					WHERE SCHOOL_ID='" . UserSchool() . "'
					AND MP_TYPE IN ('semester','year','quarter')
					AND DOES_GRADES='Y'" );


				foreach ( (array) $mps_RET as $mp )
				{
					// @since 4.7 Automatic Class Rank calculation.
					ClassRankMaybeCalculate( $mp['MARKING_PERIOD_ID'] );
				}
*/
			}


	  break;

}


