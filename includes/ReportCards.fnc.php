<?php
/**
 * Report Cards functions
 */
 


if ( ! function_exists( 'ReportCardsGenerate' ) )
{
	/**
	 * Report Cards generation
	 *
	 * @todo Divide in smaller functions
	 *
	 * @example $report_cards = ReportCardsGenerate( $_REQUEST['st_arr'], $_REQUEST['mp_arr'] );
	 *
	 * @since 4.0 Define your custom function in your addon module or plugin.
	 * @since 4.5 Add Report Cards PDF header action hook.
	 * @since 5.0 Add GPA or Total row.
	 * @since 5.0 Add Min. and Max. Grades.
	 *
	 * @uses _makeTeacher() see below
	 *
	 * @param  array         $student_array Students IDs
	 * @param  array         $mp_array      Marking Periods IDs
	 * @return boolean|array False if No Students or Report Cards associative array (key = $student_id)
	 */
	function ReportCardsGenerate( $student_array, $mp_array )
	{
		global $_ROSARIO,
			$count_lines;


		require_once 'modules/CustomReportCard/includes/Grades.fnc.php';

/* get information from the reportcard.ini settings to include specific information */
		$reportcardParameters = include('modules/CustomReportCard/includes/reportcard.ini');
		

		$reportcardFormat = file_get_contents($_REQUEST['main_template']);//file_get_contents('modules/CustomReportCard/includes/header.html');
		$mainCourseGrid = file_get_contents($_REQUEST['course_template']);
		$attendanceCourseGrid = file_get_contents($_REQUEST['attendance_template']);
		$AttendancemarkRowsGrid = file_get_contents('modules/CustomReportCard/includes/Attendance.DEFAULTMARKROW.html');
		$marksRowsGrid = file_get_contents($_REQUEST['markrows_template']);
		//$marksRowsGrid = file_get_contents('modules/CustomReportCard/includes/marksrows.html');
		$firstHonorsCertificate = file_get_contents('modules/CustomReportCard/includes/middleSchool.HONORS.html');
		$secondHonorsCertificate = file_get_contents('modules/CustomReportCard/includes/middleSchool.SECONDHONORS.html');


/* You forgot to select a student and period */
		if ( empty( $student_array )
			|| empty( $mp_array ) )
		{
			return false;
		}



		/* Desired Marking Periods */
		$mp_list = "'" . implode( "','", $mp_array ) . "'";
		/* does not work if you need to do this for each PERIOD and report each Period */
		$last_mp = end( $mp_array ); //Last Marking Period

		/* Desired Students */
		$st_list = "'" . implode( "','", $student_array ) . "'";

		$extra = GetReportCardsExtra( $mp_list, $st_list );

		$student_RET = GetStuList( $extra );

		if ( empty( $student_RET ) )
		{
			return false;
		}


		// Comments. REMOVED COMMENTS

// Did we want Period Absenses   REMOVED

//if ( isset( $_REQUEST['elements']['comments'] )   REMOVED


		//Get the Principal... Need it once and repeat the print for each report card.

           /* This draws on the stored function get_schooldata(). It takes the school id as input.
           it will return 1 Record    */
           $schoolDataFields = DBGet("Select * from get_schooldata('" . UserSchool() . "')"); 
           $principal = $schoolDataFields[1]['PRINCIPAL'];
           //Find the custom field number for school district
           $schoolCustomDistrict = DBGet("select id from school_fields where title ='School District'");
           $schoolCustomDistrict = 'CUSTOM_' . $schoolCustomDistrict[1]['ID'];
           $schoolDistrict = $schoolDataFields[1][$schoolCustomDistrict];
         

           // Get School District... It is on the previous record, we just dont know the field.

			
//===================================================================================================
		// Report Cards array.
		$report_cards = array(); //open an empty array.

		//Tries to look at each student and course with period assigned and greaded.


		foreach ( (array) $student_RET as $student_id => $course_periods )
		{
			$studentHonors = 0;
			// Start buffer.
			ob_start();
//print('B -- Memory usage ' . round(memory_get_usage()/1048576,2) .' MG<br>');

//print("<pre>".print_r($_REQUEST['st_arr'],true)."</pre>");
			$comments_arr = array();

			$comments_arr_key = ! empty( $all_commentsA_RET );

			//ensure variable arrays were destroyed
			unset( $grades_RET );
			unset($subjectMain);
          	unset($subjectSkillsStandards);

			$i = 0;

			/*
print("<pre>".print_r($_REQUEST["absentType"],true)."</pre>");
//print("<pre>".print_r($_REQUEST,true)."</pre>");	
print("<pre>".print_r($_REQUEST['ytdAbsent'],true)."</pre>");
	print("<pre>".print_r($_REQUEST["presentType"],true)."</pre>");
print("<pre>".print_r($_REQUEST['ytdPresent'],true)."</pre>");	
print("<pre>".print_r($_REQUEST['ytdTardy'],true)."</pre>");	
//print("<pre>".print_r($mp_array,true)."</pre>");		

*/

//print("<pre>".print_r($course_periods,true)."</pre>");
          
          	$homeRoomInfo = '';
          	$homeRoomTeacher = '';
          	$reportCardHeader ='';
          	//Determine if they want 3 Sections: Grouping, Subject & Subject Skills.
          	$tiers = $_REQUEST['elements']['tiers'];
          	
			/* Get the Scheduled Classes by Each Student from the Specialized View. This will not 
			   have grades in it but has all other information */
			  $homeRoomInfo = DBGet("select * from \"studentScheduleReportCard\" where title = 'Attendance' AND student_id = " .$student_id . " AND SYEAR = " .UserSyear());


			  $homeRoomTeacher = $homeRoomInfo[1]['TEACHERSALUTATION'] . ' ' .  $homeRoomInfo[1]['TEACHERLASTNAME'];



			  //We need to modify the view used based on Tiered (3 levels vs Subject & Skill Only)
			  if($tiers == 'Y'){


			  	$subjectGroup = DBGet("select Distinct grouping, student_id, reportcardorder, 
				  	concat(teachersalutation, ' ',teacherfirstname, ' ', teacherlastname) as subjectTeacher,
				  	course_period_id, school_id, credit_hours, syear from \"tieredStudentScheduleReportCard\" where Upper(subject) <> 'ATTENDANCE' AND student_id = " .$student_id . " AND SYEAR = " .UserSyear() . " And Upper(subject) Not Like '%SKILLS%' Order by reportcardorder");

			  	$subjectMain = DBGet("select Distinct grouping, subject, student_id, reportcardorder, 
				  	concat(teachersalutation, ' ',teacherfirstname, ' ', teacherlastname) as subjectTeacher,
				  	course_period_id, school_id, credit_hours, syear from \"tieredStudentScheduleReportCard\" where Upper(subject) <> 'ATTENDANCE' AND student_id = " .$student_id . " AND SYEAR = " .UserSyear() . " And Upper(subject) Not Like '%SKILLS%' Order by reportcardorder");

				  $subjectMainLastCourse = count($subjectMain);

				  //Get the Subjects Period Information (Has the Course in it as well for tiers)
				 $subjectSkillsStandards = DBGet("select grouping, subject, subjectskill, student_id, reportcardorder, 
				  	concat(teachersalutation, ' ',teacherfirstname, ' ', teacherlastname) as subjectTeacher,
				  	course_period_id, marking_period_id, syear, school_id 
				  	from \"tieredStudentScheduleReportCard\" where Upper(subject) <> 'ATTENDANCE' AND student_id = " .$student_id . " AND SYEAR = " .UserSyear() . " And Upper(subject) Like '%SKILLS%' Order by grouping, subject,subjectskill");

			  }
			  else{
			  //Get the Subject
				  $subjectMain = DBGet("select Distinct title, subject, student_id, reportcardorder, 
				  	concat(teachersalutation, ' ',teacherfirstname, ' ', teacherlastname) as subjectTeacher,
				  	course_period_id, school_id, credit_hours, syear from \"studentScheduleReportCard\" where title <> 'Attendance' AND student_id = " .$student_id . " AND SYEAR = " .UserSyear() . " And Upper(Title) Not Like '%SKILLS%' Order by reportcardorder");

				  $subjectMainLastCourse = count($subjectMain);


				//Get the Subjects Period Information (Has the Course in it as well for tiers)
				 $subjectSkillsStandards = DBGet("select title, subject, subjectskill, student_id, reportcardorder, 
				  	concat(teachersalutation, ' ',teacherfirstname, ' ', teacherlastname) as subjectTeacher,
				  	course_period_id, marking_period_id, syear, school_id 
				  	from \"studentScheduleReportCard\" where title <> 'Attendance' AND student_id = " .$student_id . " AND SYEAR = " .UserSyear() . " And Upper(Title) Like '%SKILLS%' Order by subjectskill");
				}
		   
			// Course Periods.
			/* This is where we cycle through other Attendance Codes to see what we want to 
				   include or not include
				 */
			if($_REQUEST['elements']['ytd_absences'] ==='Y' || 
				    $_REQUEST['elements']['mp_absences'] ==='Y' ){
				 //Populate Attendace Template with Absenses
				   //The Grid has a place to replace with actual rows of data after the header is set
				$tardyData ='';
				$attendanceData = '';
				$YTDFlag = ''; //What are we doing MP, YTD or Both

				$YTDFlag = 'MTD' . $_REQUEST['elements']['mp_absences'];
				$YTDFlag .= '_YTD' . $_REQUEST['elements']['ytd_absences'];


				$attendanceGrid = GridReplacement('%SUBJECT%',$attendanceCourseGrid, 'Attendance');
				
				
				/* How are we doing Absent.. In summary or in Detail */
				if(strtoupper($_REQUEST['absentType']) == 'ABSENTSUMMARY'){
					// We are doing 1 line of Absent without Details
					//print("<pre>".print_r($_REQUEST["absentType"],true)."</pre>");
					
					$attendanceData .= GridSkillReplacement($AttendancemarkRowsGrid, 'ABSENT');

					$attendanceData = PopulateAbsentPresent($attendanceCodeID, $mp_array,$attendanceData,$st_list,$student_id,'ABSENT','SUMMARY',$YTDFlag);
								
				}else{
					//Absent Type is broken out by subcategory
					//print("<pre>".print_r($_REQUEST['ytdAbsent'],true)."</pre>");
					foreach((array)$_REQUEST['ytdAbsent'] as $attendanceCode){
						
						//Turn the attendance Code into a TITLE The codes are ID and Title seperated by _
						$attendanceTitle = explode("_",$attendanceCode);
						$attendanceCodeID = $attendanceTitle[0];

						$attendanceData .= GridSkillReplacement($AttendancemarkRowsGrid, $attendanceTitle[1]);

						$attendanceData = PopulateAbsentPresent($attendanceCodeID, $mp_array,$attendanceData,$st_list,$student_id,'ABSENT',$attendanceTitle[1],$YTDFlag);
						
					}
				}// End Absent Type and IF


				/* How are we doing Present summary or in Detail */
				if(strtoupper($_REQUEST['presentType']) == 'PRESENTSUMMARY'){
					// We are doing 1 line of Absent without Details
			
					$attendanceData .= GridSkillReplacement($AttendancemarkRowsGrid, 'PRESENT');


					$attendanceData = PopulateAbsentPresent($attendanceCodeID, $mp_array,$attendanceData,$st_list,$student_id,'PRESENT','SUMMARY',$YTDFlag);
		
				}else{
					//Absent Type is broken out by subcategory
					foreach((array)$_REQUEST['ytdPresent'] as $attendanceCode){

					//Turn the attendance Code into a TITLE The codes are ID and Title seperated by _
						$attendanceTitle = explode("_",$attendanceCode);
						$attendanceCodeID = $attendanceTitle[0];

						$attendanceData .= GridSkillReplacement($AttendancemarkRowsGrid, $attendanceTitle[1]);

						$attendanceData = PopulateAbsentPresent($attendanceCodeID, $mp_array,$attendanceData,$st_list,$student_id,'PRESENT',$attendanceTitle[1],$YTDFlag);				
					}
				}//End Request for PResent

				//TARDY or other Codes of Defected Days not impacting Present ex. Tardy, Cut Class

				foreach((array)$_REQUEST['ytdTardy'] as $attendanceCode){
					//Turn the attendance Code into a TITLE The codes are ID and Title seperated by _
						$attendanceTitle = explode("_",$attendanceCode);
						$attendanceCodeID = $attendanceTitle[0];

						$attendanceData .= GridSkillReplacement($AttendancemarkRowsGrid, $attendanceTitle[1]);

						$attendanceData = PopulateAbsentPresent($attendanceCodeID, $mp_array,$attendanceData,$st_list,$student_id,'PRESENT',$attendanceTitle[1],$YTDFlag);
				}
			}//End Include Attendance Code IF
//print('C -- Memory usage ' . round(memory_get_usage()/1048576,2) .' MG <br>');

			//Get Students Grades per Course
			foreach ( (array) $course_periods as $course_period_id => $mps )
			{
				$i++;

				$grades_RET[$i]['COURSE_TITLE'] = $mps[key( $mps )][1]['COURSE_TITLE'];
				$grades_RET[$i]['COURSE_PERIOD_ID'] = $course_period_id;
				$grades_RET[$i]['TEACHER_ID'] = GetTeacher( $mps[key( $mps )][1]['TEACHER_ID'] );

//foreach ( (array) $mp_array as $mp ) REMOVED

				
			} //end for course Periods
//if ( ! empty( $_REQUEST['elements']['gpa_or_total'] ) ) REMOVED
//print('D -- Memory usage ' . round(memory_get_usage()/1048576,2) .' MG <br> the value of i is ' . $i . '<br>' );
//asort( $comments_arr, SORT_NUMERIC ); REMOVVED
// Student Info.
			$extra2['WHERE'] = " AND s.STUDENT_ID='" . $student_id . "'";

			// SELECT s.* Custom Fields for Substitutions.
			$extra2['SELECT'] = ",s.*";

			if ( empty( $_REQUEST['_search_all_schools'] ) )
			{
				// School Title.
				$extra2['SELECT'] .= ",(SELECT sch.TITLE FROM SCHOOLS sch
					WHERE ssm.SCHOOL_ID=sch.ID
					AND sch.SYEAR='" . UserSyear() . "') AS SCHOOL_TITLE";
			}

			$student = GetStuList( $extra2 );

			$student = $student[1];

			// Mailing Labels.
//This is just poorly named. The addresses is used to loop the Report Cards for each student.
//could have used student array.
			if ( isset( $_REQUEST['mailing_labels'] )
				&& $_REQUEST['mailing_labels'] === 'Y' )
			{
				if ( ! empty( $addresses_RET[$student_id] ) )
				{
					$addresses = $addresses_RET[$student_id];
				}
				else
				{
					$addresses = array( 0 => array( 1 => array(
						'STUDENT_ID' => $student_id,
						'ADDRESS_ID' => '0',
						'MAILING_LABEL' => '<BR /><BR />',
					) ) );
				}
			}
			else
			{
				$addresses = array( 0 => array() );
			}


//print('e -- Memory usage ' . round(memory_get_usage()/1048576,2) .' MG<br>');


/* I could have used a template in the next step but I am doing 1 time so I think I will just
design a really nice HTML and do standard Substitution over targeted Tags */
			foreach ( (array) $addresses as $address )
			{
				unset( $_ROSARIO['DrawHeader'] );

				if ( isset( $_REQUEST['mailing_labels'] )
					&& $_REQUEST['mailing_labels'] === 'Y' )
				{
					//echo '<BR /><BR /><BR />';
				}

				// FJ add school logo.
				$logo_pic = 'assets/school_logo_' . UserSchool() . '.jpg';

				$picwidth = 120;

				if ( file_exists( $logo_pic ) )
				{
					$reportCardHeader = str_replace('%Logo%','<img src="' . $logo_pic . '" width="100px" height="130px"  />',$reportcardFormat);
				}
// For the benefit of PDF TOC
echo '<bookmark content="' . $student['FULL_NAME'] . '"/>';
				// Headers.
				 $reportCardHeader = GridReplacement('%StudentName%',$reportCardHeader,$student['FULL_NAME']);
				 $reportCardHeader = GridReplacement('%Grade%',$reportCardHeader,$student['GRADE_ID']);
				 $reportCardHeader = GridReplacement('%SchoolName%',$reportCardHeader,$student['SCHOOL_TITLE']);

				 $syear = FormatSyear( UserSyear(), Config( 'SCHOOL_SYEAR_OVER_2_YEARS' ) );
				 $reportCardHeader = GridReplacement('%SchoolYear%',$reportCardHeader,$syear);	

				 //This is pulled out before the Loop
				 $reportCardHeader = GridReplacement('%Principal%',$reportCardHeader,$principal);
				 $reportCardHeader = GridReplacement('%SchoolDistrict%',$reportCardHeader,$schoolDistrict);
				 $reportCardHeader = GridReplacement('%Teacher%',$reportCardHeader,$homeRoomTeacher);

				 /* This next step only sets up the GRID Layout with Subject and Skills/Standards.. It will not populate the actual Grades. They will be matched up in a later step */
				    //TTD: Adjust these to input
		
				    $startCourse = 0;
				    //Needs to be lesser of $_REQUEST['courses_firstpage'] or $subjectMainLastCourse

				    $endCourse = min($_REQUEST['courses_firstpage'],$subjectMainLastCourse);
				$subjectGrid = courseTables($subjectMain,$subjectSkillsStandards,$mainCourseGrid,$marksRowsGrid,$mp_array,$startCourse, $endCourse,$studentHonors,$reportCardHeader,$tiers);
				$reportCardHeader = GridReplacement('%PAGE1_CLASSES%',$reportCardHeader,$subjectGrid);
					

				//Populate a Second Page Grid
				   $startCourse = $_REQUEST['courses_firstpage'];
				    $endCourse = $subjectMainLastCourse;
				if($subjectMainLastCourse > $_REQUEST['courses_firstpage']){
					$subjectGrid = courseTables($subjectMain,$subjectSkillsStandards,$mainCourseGrid,$marksRowsGrid,$mp_array,$startCourse, $endCourse,$studentHonors,$reportCardHeader,$tiers);
					$reportCardHeader = GridReplacement('%PAGE2_CLASSES%',$reportCardHeader,$subjectGrid);
				}

				$attendanceGrid = GridReplacement('%COURSEMARKS%',$attendanceGrid,$attendanceData);
				// Change the last column color
				$attendanceGrid = GridReplacement('#B8B8B8;',$attendanceGrid ,'white');

				$reportCardHeader = GridReplacement('%ATTENDANCEGRID%',$reportCardHeader,$attendanceGrid);

				//How do I get a General Average?
				// 1- Only Certain classes qualify for Averagem
				// Attendance Format Grid Should Suff
				if($_REQUEST['elements']['general_average'] === 'Y'){
					$generalAverageGrid = GridReplacement('%SUBJECT%',$attendanceCourseGrid, 'General Average');
					$generalAverage = studentGeneralAverage($student_id,$mp_array,$marksRowsGrid);
					$generalAverageGrid = GridReplacement('%COURSEMARKS%',$generalAverageGrid,$generalAverage);
					$generalAverageGrid = GridReplacement('%SKILL%',$generalAverageGrid,' ');
					$generalAverageGrid = GridReplacement('Year Total',$generalAverageGrid,'Year Average');
					$generalAverageGrid = GridReplacement('/%T(.*?)%/',$generalAverageGrid ,'   ',2);
					// Change the last column color
					$generalAverageGrid = GridReplacement('#B8B8B8;',$generalAverageGrid ,'white');
					//$generalAverageGrid  = GridReplacement('/%YT(.*?)%/',$generalAverageGrid ,'   ',2);


					$reportCardHeader = GridReplacement('%GENERAL_AVERAGE%',$reportCardHeader,$generalAverageGrid);
				
				}else{
					$reportCardHeader = GridReplacement('%GENERAL_AVERAGE%',$reportCardHeader,'');
				}

				//We need to add footnotes if there are certain things that happen
				//1- Add a footnote if we use Percentages and not Letters about major grades.
				if($_REQUEST['elements']['percents'] === 'Y'){
					$footnote1 = '* Major Academic Courses have numerical grades';
					$reportCardHeader = GridReplacement('%FOOTNOTE1%',$reportCardHeader,$footnote1);
				}else{
					//Wipe out the footer holder
					$reportCardHeader = GridReplacement('%FOOTNOTE1%',$reportCardHeader,'');
				}




				//echo $attendanceGrid;
				//check honors
				if($_REQUEST['elements']['honors_certificate'] === 'Y' && $mp = $last_mp){
					if($studentHonors >= 100){
							//No HOnors
						$reportCardHeader = GridReplacement('%HONORS%',$reportCardHeader,'');
						$reportCardHeader = GridReplacement('%HONORSCERTIFICATE%',$reportCardHeader,'');
					}elseif($studentHonors >= 1){
						//Second Honors
						$secondhonors = '<center><img src="modules/CustomReportCard/img/secondhonors.png" style="height:30px;"></center>';
						$reportCardHeader = GridReplacement('%HONORS%',$reportCardHeader,$secondhonors);

						$personalizes = GridReplacement('%STUDENTNAME%',$secondHonorsCertificate, $student['FULL_NAME']);
						$personalizes = GridReplacement('%GRADE%',$personalizes, $student['GRADE_ID']);
						$personalizes = GridReplacement('%YEAR%',$personalizes, $syear);
						$personalizes = GridReplacement('%MARKINGPERIOD%',$personalizes, GetMP( $mp, 'TITLE' ));
						$reportCardHeader = GridReplacement('%HONORSCERTIFICATE%',$reportCardHeader,$personalizes);
						
					}else{
						//First Honors
						$firsthonors = '<center><img src="modules/CustomReportCard/img/firsthonors.png" style="height:30px;"></center>';
						$reportCardHeader = GridReplacement('%HONORS%',$reportCardHeader,$firsthonors);

						$personalizes = GridReplacement('%STUDENTNAME%',$firstHonorsCertificate, $student['FULL_NAME']);
						$personalizes = GridReplacement('%GRADE%',$personalizes, $student['GRADE_ID']);
						$personalizes = GridReplacement('%YEAR%',$personalizes, $syear);
						$personalizes = GridReplacement('%MARKINGPERIOD%',$personalizes, GetMP( $mp, 'TITLE' ));
						$reportCardHeader = GridReplacement('%HONORSCERTIFICATE%',$reportCardHeader,$personalizes);
					}
				}else{
						$reportCardHeader = GridReplacement('%HONORS%',$reportCardHeader,'');
						$reportCardHeader = GridReplacement('%HONORSCERTIFICATE%',$reportCardHeader,'');
				}


				echo $reportCardHeader;

//print('F -- Memory usage '. round(memory_get_usage()/1048576,2) .' MG <br>');
				// @since 4.5 Add Report Cards PDF header action hook.
				do_action( 'CustomReportCard/includes/ReportCards.fnc.php|pdf_header', $student_id );

				// Comments.
//if ( isset( $_REQUEST['elements']['comments'] )   REMOVED

			}

			// Add buffer to Report Cards array.
			//$report_cards[$student_id] = ob_get_clean();
			//echo 'Should be the last printed item';
			$report_cards[$student_id] = ob_get_clean();
			//$report_cards[$student_id] = ob_get_contents();
			//ob_end_clean();
		}


		return $report_cards;
	}
}

if ( ! function_exists( 'GetReportCardsExtra' ) )
{
	/**
	 * Get $extra var for Report Cards.
	 * To be used by GetStuList().
	 *
	 * @since 5.7.4 Define your custom function in your addon module or plugin.
	 * @example $extra = GetReportCardsExtra( $mp_array, $student_array );
	 *
	 * @param  array $mp_list MPs list.
	 * @param  array $st_list Students list.
	 * @return array $extra
	 */
	function GetReportCardsExtra( $mp_list, $st_list )
	{
		// Student List Extra.
		$extra['WHERE'] = " AND s.STUDENT_ID IN ( " . $st_list . ")";

		// Student Details. TODO test if ReportCards needs GRADE_ID!!
		$extra['SELECT_ONLY'] = "DISTINCT s.FIRST_NAME,s.LAST_NAME,s.STUDENT_ID,ssm.SCHOOL_ID";

		$extra['SELECT_ONLY'] .= ",sg1.GRADE_LETTER as GRADE_TITLE,sg1.GRADE_PERCENT,WEIGHTED_GP,GP_SCALE,
			sg1.COMMENT as COMMENT_TITLE,sg1.STUDENT_ID,sg1.COURSE_PERIOD_ID,sg1.MARKING_PERIOD_ID,
			sg1.COURSE_TITLE as COURSE_TITLE,rc_cp.TEACHER_ID,rc_cp.CREDITS,sp.SORT_ORDER";

		if ( isset( $_REQUEST['elements']['period_absences'] )
			&& $_REQUEST['elements']['period_absences'] === 'Y' )
		{
			// Period-by-period absences.
			$extra['SELECT_ONLY'] .= ",rc_cp.DOES_ATTENDANCE,
				(SELECT count(*) FROM ATTENDANCE_PERIOD ap,ATTENDANCE_CODES ac
					WHERE ac.ID=ap.ATTENDANCE_CODE
					AND ac.STATE_CODE='A'
					AND ap.COURSE_PERIOD_ID=sg1.COURSE_PERIOD_ID
					AND ap.STUDENT_ID=ssm.STUDENT_ID) AS YTD_ABSENCES,
				(SELECT count(*) FROM ATTENDANCE_PERIOD ap,ATTENDANCE_CODES ac
					WHERE ac.ID=ap.ATTENDANCE_CODE
					AND ac.STATE_CODE='A'
					AND ap.COURSE_PERIOD_ID=sg1.COURSE_PERIOD_ID
					AND sg1.MARKING_PERIOD_ID=cast(ap.MARKING_PERIOD_ID as text)
					AND ap.STUDENT_ID=ssm.STUDENT_ID) AS MP_ABSENCES";
		}

		// FJ multiple school periods for a course period.
		//$extra['FROM'] .= ",STUDENT_REPORT_CARD_GRADES sg1,ATTENDANCE_CODES ac,COURSE_PERIODS rc_cp,SCHOOL_PERIODS sp";
		$extra['FROM'] = ",STUDENT_REPORT_CARD_GRADES sg1,ATTENDANCE_CODES ac,COURSE_PERIODS rc_cp,
			SCHOOL_PERIODS sp,COURSE_PERIOD_SCHOOL_PERIODS cpsp";

		/*$extra['WHERE'] .= " AND sg1.MARKING_PERIOD_ID IN (".$mp_list.")
		AND rc_cp.COURSE_PERIOD_ID=sg1.COURSE_PERIOD_ID AND sg1.STUDENT_ID=ssm.STUDENT_ID AND sp.PERIOD_ID=rc_cp.PERIOD_ID";*/
		$extra['WHERE'] .= " AND sg1.MARKING_PERIOD_ID IN (" . $mp_list . ")
						AND rc_cp.COURSE_PERIOD_ID=sg1.COURSE_PERIOD_ID
						AND sg1.STUDENT_ID=ssm.STUDENT_ID
						AND sp.PERIOD_ID=cpsp.PERIOD_ID
						AND rc_cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID";

		$extra['ORDER'] = ",sg1.COURSE_TITLE,sp.SORT_ORDER,ac.TITLE";

		$extra['group'] = array( 'STUDENT_ID', 'COURSE_PERIOD_ID', 'MARKING_PERIOD_ID' );

		// Parent: associated students.
		$extra['ASSOCIATED'] = User( 'STAFF_ID' );

		return $extra;
	}
}

/**
*
*
**/
function studentGeneralAverage(&$student_id,&$markingPeriods,$gridFormat){
	// pull together the students grade average
	$i = 0;
	$averageByPeriod = array();

	foreach((array) $markingPeriods as $mp){
		$getAverageData = DBGet("Select sum(grade_percent) as total, count(grade_percent) as dn" .
		        ' From "studentGeneralAverage" ' .
		        " Where student_id ='" . $student_id . "'" .
		        " AND marking_period_id = '" . $mp ."'");

		//$averageByPeriod[$i] = $getAverageData['TOTAL'] / $getAverageData['DENOM'];
		$averageByPeriod[$i] = round(round($getAverageData[1]['TOTAL']) / $getAverageData[1]['DN']);


//In our world under 69 is an F and no Mark at all so
						if($averageByPeriod[$i] < 69 ){
							$averageByPeriod[$i] = '<p style="color:red;">F</p>';
						}elseif($averageByPeriod[$i] >= 92){
							$averageByPeriod[$i] = '<B>' . $averageByPeriod[$i] .'</B>';
						}else{
							$averageByPeriod[$i] = $averageByPeriod[$i];
						}

		$gridFormat = GridReplacement('%T' . $i .'%',$gridFormat,$averageByPeriod[$i]);	

		$i++;

		//From$getAverage you need to run through the records summing up grade_percent
		
	}

	unset($averageByPeriod);
	return $gridFormat;
}

/**
*
* @param marking periods
* get_studentgrade takes schoolid,studentid,schoolyear,courseid,marking period
**/
function courseTables(&$subjectMain,&$subjectSkillsStandards,&$mainCourseGrid,&$marksRowsGrid,&$markingPeriods,$startCourse,$endCourse,&$studentHonors,&$reportCardHeader,$tiers)
{
	$subjectGrid = '';
	// Printing all the keys and values one by one
	$keys = array_keys($subjectMain);
	$skillsData = array();
	$Cnt_SubjectsForGrids = '';
	$last_mp = end($markingPeriods);
	
					
					//Of course we need to to deal with COURSES of a 2nd page if it exists. Bur for now
					//for($i = 0; $i < $_REQUEST['courses_firstpage']; $i++) {
					for($i = $startCourse; $i < $endCourse; $i++) {
						// Check to see if the Teacher should be included next to the subject name
						$tmpSubject =  strtoupper($subjectMain[$keys[$i]]['SUBJECT']);
						$tmpCourse = strtoupper($subjectMain[$keys[$i]]['TITLE']);

						//Add teachers name to subject
						if ( isset( $_REQUEST['elements']['subject_teacher'] ) 
							&& $_REQUEST['elements']['subject_teacher'] === 'Y' ){
							$tmpSubject =  $tmpSubject . ' - ' . $subjectMain[$keys[$i]]['SUBJECTTEACHER'];
						}

						//Make sure the subjectMain query pulls enough data fields to populate the 
						// get_studentgrade function
		
						//put the subject grid aside if the tmpSubject is Christian Values
						if(strtoupper($tmpSubject) == 'CHRISTIAN VALUES'){
							$putAsideSubjectGrid = $subjectGrid;
							$subjectGrid = GridReplacement('%SUBJECT%',$mainCourseGrid, '');
						}else{
							if(strtoupper($subjectMain[$keys[$i]]['GROUPING']) == 'SUCCESSFUL LEARNER'){
							$putAsideSubjectGrid = $subjectGrid;
							$subjectGrid = '';
							}

								if($tiers == 'Y'){
									if(strtoupper($subjectMain[$keys[$i]]['GROUPING']) <> $tmpSubject){
									$subjectGrid = $subjectGrid . GridReplacement('%SUBJECT%',$mainCourseGrid, strtoupper($subjectMain[$keys[$i]]['GROUPING']) .' - ' . trim($tmpSubject));
									}else{
										$subjectGrid = $subjectGrid .  GridReplacement('%SUBJECT%',$mainCourseGrid, strtoupper($subjectMain[$keys[$i]]['GROUPING']));
									}
								}else{
									//This is the One used 98% of the time.
									$subjectGrid = $subjectGrid . GridReplacement('%SUBJECT%',$mainCourseGrid, trim($tmpSubject));
								}

						}

						$ia =0;

						foreach((array) $markingPeriods as $mp){
									$testMark = DBGet("SELECT * From get_studentgrade(" . 
							      	$subjectMain[$keys[$i]]['SCHOOL_ID'] . "," . 
							      	$subjectMain[$keys[$i]]['STUDENT_ID']  . "," .
							      	$subjectMain[$keys[$i]]['SYEAR'] . "," . 
							      	$subjectMain[$keys[$i]]['COURSE_PERIOD_ID'] . ",'" . $mp . "')");			     


							if($_REQUEST['elements']['percents'] == 'Y' && $subjectMain[$keys[$i]]['CREDIT_HOURS'] >= 1){
							
								$reportCardGrade =round(trim($testMark[1]['GRADE_PERCENT']));


								//In our world under 69 is an F and no Mark at all so
								if($reportCardGrade < 69 && $reportCardGrade){
									$reportCardGrade = '<p style="color:red;">F</p>';
								}elseif($reportCardGrade == 0){
									$reportCardGrade = '';  // Just means not populated
								}elseif($reportCardGrade >= 92){
									$reportCardGrade = '<B>' . $reportCardGrade .'</B>';
								}else{
									$reportCardGrade = $reportCardGrade;
								}


								//One more fun thing, does the student have honors?
								// update the variable in the main subroutine student honors which is passed by
								// reference
								if($mp == $last_mp){
									if(round(trim($testMark[1]['GRADE_PERCENT'])) < 92 &&
								        round(trim($testMark[1]['GRADE_PERCENT'])) >= 85){
										//not getting first honors
										$studentHonors += 1;
									}elseif(round(trim($testMark[1]['GRADE_PERCENT'])) < 85){
										// not getting second honors
										$studentHonors += 100;
									}else{}
								}


							}else{

								$reportCardGrade = $testMark[1]['GRADE_LETTER'];

								if($reportCardGrade == 'F' && $reportCardGrade){
									$reportCardGrade = '<p style="color:red;">F</p>';
									//You cannot get honors
								$studentHonors += 100;
								}

							}//end if marks are Percents.


					//There may be one, if not still remove the TEMPLATE Holder
					$subjectGrid = GridReplacement('%T' . $ia . '%',$subjectGrid,$reportCardGrade);
//echo ' The tiers are ' . $tiers;
				
					
								$ia++;
								} // end of for each population of the Marks on Standards / Skills
	//Clean up remaining Unused Periods
							$subjectGrid  = GridReplacement('/%T(.*?)%/',$subjectGrid ,'   ',2);

							$subjectGrid  = GridReplacement('/%YT(.*?)%/',$subjectGrid ,'   ',2);


					     
					     //You can get the COURSE ID FROM THE SUBJECT MAIN and use the same process as Skills/ Standards Replacement for Grades..

					     //Subjects might have Marks and we need the student Marks so we can replace the Template
					    
					   //Find the Standards / Skills that go with this subject
					   /* One of the tricks we did was to create a COURSE called SUBJECT SKILLS for each
					       subject so that they could be pulled easily... We did not give them a PERIOD but did
					       give them a MARKED designation  -- Ran this query once when we did SUBJECT*/
					    //PERIODS
					       $array = $subjectSkillsStandards;

					       //Because of tiers this is an IF
					       if($tiers == 'Y'){
					       	if(strtoupper($subjectMain[$keys[$i]]['GROUPING']) <> $tmpSubject){
					       		$searchFor =  strtoupper($subjectMain[$keys[$i]]['SUBJECT']) . ' SKILLS';

					       		$keyName = 'SUBJECT';
					       		// must be on php 5.3 or better
					       		$filteredArray = 
								array_filter($array, function($element) use($searchFor){
								  return isset($element['SUBJECT']) && strtoupper($element['SUBJECT']) == $searchFor;
								});
					       }else{
					       	//There is no Skill for the GROUPING in the Subject
					       	$searchFor =  strtoupper($subjectMain[$keys[$i]]['GROUPING']);
					       		$keyName = 'GROUPING';
					       		// must be on php 5.3 or better
					       		$filteredArray = 
								array_filter($array, function($element) use($searchFor){
								  return isset($element['GROUPING']) && strtoupper($element['GROUPING']) == $searchFor;
								  });
					       }
					       		

					       }else{

					       		$searchFor = $subjectMain[$keys[$i]]['SUBJECT'];
					       		$keyName = 'SUBJECT';
					       		// must be on php 5.3 or better
					       		$filteredArray = 
								array_filter($array, function($element) use($searchFor){
								  return isset($element['SUBJECT']) && $element['SUBJECT'] == $searchFor;
								});
					       
					       // You need to refilter because of the Tiers
								
							}
							$keysSkills = array_keys($filteredArray);
		/*					

							if($tiers == 'Y'){

								$subjectGrid = GridReplacement('%COURSEMARKS%',$subjectGrid,'');

								$subjectGrid = $subjectGrid . GridReplacement('%SUBJECT%',$mainCourseGrid, trim($filteredArray[$keysSkills[$i]]['SUBJECT']));
								$subjectGrid  = GridReplacement('/%T(.*?)%/',$subjectGrid ,'   ',2);

								$subjectGrid  = GridReplacement('/%YT(.*?)%/',$subjectGrid ,'   ',2);


					     

								$searchFor = $filteredArray[$keysSkills[$i]]['SUBJECT'];
					       		$keyName = 'SUBJECT';
					       		// must be on php 5.3 or better
					       		$filteredArray = 
								array_filter($filteredArray, function($element) use($searchFor){
								  return isset($element['SUBJECT']) && $element['SUBJECT'] == $searchFor;
								});

							}
*/
							$skillsStandardsData = '';
							
							$holdToLastRow = '';
							$keysSkills = array_keys($filteredArray);


//echo '<pre>' . print_r($filteredArray,true) . '</pre>';

							for($iS = 0; $iS < count($filteredArray); $iS++) {
								
								//Filtered Array has the subject's standard/skills
								//replace the standard/skill template variable.
								$skillName = trim($filteredArray[$keysSkills[$iS]]['SUBJECTSKILL']);

								if($tiers == 'Y'){
									//Group, Subject & Skill
									$tierTwo = trim($filteredArray[$keysSkills[$iS]]['SUBJECT']);

									//Remove Skills and see if the tierTwo and Skillname are the same
									$tierTwo = str_replace(' SKILLS', '', strtoupper($tierTwo));

									if(strtoupper($subjectMain[$keys[$i]]['GROUPING']) == $tierTwo ||
											trim($tmpSubject) == $tierTwo){
											$skillsStandardDataOneRow = GridSkillReplacement($marksRowsGrid, $skillName);
										}else{
									
										$skillsStandardDataOneRow = GridSkillReplacement($marksRowsGrid, $tierTwo . ': ' . $skillName);
									
									
									
										}
									
								}else{
									//Only Subject and Skill
								$skillsStandardDataOneRow = GridSkillReplacement($marksRowsGrid, $skillName);

								}
								//Populate the Skills $filteredArray[$keysSkills[$iS]]Row & build up the rows
								// for each marking period taken
							$ii =0;

								foreach((array) $markingPeriods as $mp){
									$testMark = DBGet("SELECT * From get_studentgrade(" . 
							      	$filteredArray[$keysSkills[$iS]]['SCHOOL_ID'] . "," . 
							      	$filteredArray[$keysSkills[$iS]]['STUDENT_ID']  . "," .
							      	$filteredArray[$keysSkills[$iS]]['SYEAR'] . "," . 
							      	$filteredArray[$keysSkills[$iS]]['COURSE_PERIOD_ID'] . ",'" . $mp . "')");
						     
//echo '<pre>' . print_r($testMark,true) . '</pre>' . 'The mp is ' . $mp;							

						     if($testMark[1]['GRADE_LETTER'] == 'Check'){
						     	$letterGrade = '<img src="modules/CustomReportCard/img/checkmark.png" style="width:13px;height:13px;"></img>';
						     }elseif($testMark[1]['GRADE_LETTER'] == '-'){
						     	$letterGrade = '<img src="modules/CustomReportCard/img/redminus.png" style="width:12px;height:12px;"></img>';
						     }elseif($testMark[1]['GRADE_LETTER'] == '+'){
						     	$letterGrade = '<img src="modules/CustomReportCard/img/plus.png" style="width:12px;height:12px;"></img>';
						     }else{
						     	$letterGrade = $testMark[1]['GRADE_LETTER'];
						     }

								$skillsStandardDataOneRow = GridReplacement('%T' . $ii . '%',$skillsStandardDataOneRow ,$letterGrade);
								$ii++;
								} // end of for each population of the Marks on Standards / Skills

							//Clean up remaining Unused Periods
							$skillsStandardDataOneRow  = GridReplacement('/%(.*?)%/',$skillsStandardDataOneRow ,' ',2);


								//Add the Single Row together to make a larger Grid with all skill standards
								if(strToUpper($skillName) == 'EFFORT' || strToUpper($skillName) == 'CONDUCT'){
									$holdToLastRow = $holdToLastRow .  $skillsStandardDataOneRow;

								}else{
									$skillsStandardsData = $skillsStandardsData . $skillsStandardDataOneRow;
								}
						   
								
							
							}
						

							//Add the Hold to last into the Skills Grid
								$skillsStandardsData = $skillsStandardsData . $holdToLastRow;
							//Merge skillstandards with Subject Header.	

							//Special Case is Christian Values target %CHRISITAN_VALUES%
							if(strtoupper($tmpSubject) == 'CHRISTIAN VALUES' ){
								$subjectGrid = GridReplacement('%COURSEMARKS%',$subjectGrid,$skillsStandardsData);

								$reportCardHeader = GridReplacement('%CHRISTIAN_VALUES%',$reportCardHeader,$subjectGrid);

								//return to the previous interuption.
								 $subjectGrid = $putAsideSubjectGrid;
							}elseif(strtoupper($subjectMain[$keys[$i]]['GROUPING']) == 'SUCCESSFUL LEARNER'){
								$subjectGrid = GridReplacement('%COURSEMARKS%',$subjectGrid,$skillsStandardsData);

								$reportCardHeader = GridReplacement('%SUCCESSFUL LEARNER%',$reportCardHeader,$subjectGrid .'%SUCCESSFUL LEARNER%');

								//return to the previous interuption.
								 $subjectGrid = $putAsideSubjectGrid;

							}else{
							$subjectGrid = GridReplacement('%COURSEMARKS%',$subjectGrid,$skillsStandardsData);
							}

					
					}  //end the for loop
				return $subjectGrid;
}




/**
*
*
**/

function GridSkillReplacement($skillData, $skill){
	//The Skill is Absent
	$populatedSkill = str_replace('%SKILL%',$skill,$skillData);

	return $populatedSkill;
}

/**
* @param subjectHeader string what you are replacing with
* @param mainCourseGrid string html that holds the template needing replace
* @param templageVariable string the variable inside the mainCourseGrid you want to replace
*   what to replace , inside of string that holds the info, what to replace with
**/

function GridReplacement($templateVariable,$mainCourseGrid, $subjectHeader,$string_or_preg = 1)
{

	if($string_or_preg == 1)
	{
		$completeHeaderGrid = str_replace($templateVariable,$subjectHeader,$mainCourseGrid);
		
	}else{
		$completeHeaderGrid = preg_replace($templateVariable, $subjectHeader, $mainCourseGrid);
		
	}
	
	return $completeHeaderGrid;

}


/**
*
*
*  This uses a SQL VIEW 



/**
* Populate Attendance Grid
*
* @param array marking_period
* @param string table row html
*
*  do we need to pass st_list and studend_id
*/

function PopulateAbsentPresent($attendanceCodeID, $mp_array,$populatedRowsGrid,$st_list,$student_id,$absentPresent,$summaryOrDetail,$YTDFlag)
{

	

	$mp_count = 0;
	$absentPresent =strtoupper($absentPresent);

	$mtd = strpos($YTDFlag, 'MTDY');
	$ytd = strpos($YTDFlag, 'YTDY');

			$last_mp = end( $mp_array ); //Last Marking Period
			foreach ( (array) $mp_array as $mp ){

				$mp_data[$mp_count] = getCategoryAttendanceCount($mp, $student_id,$summaryOrDetail, $absentPresent);
				$mp_count++;
			}


			//The Text output is the same process absences, marks ,etc. only difference is the grid it populates
			$totalCodeCount = 0;
			for ($i = 0; $i < count($mp_data); $i++)
			{
				//Clean up the sum where they are all 0
				$codeCount = $mp_data[$i][1]['DAYS'];
				if(!$codeCount || $codeCount == 0){
					$codeCount = '0.0';
				}

				if($mtd !== FALSE){
					$populatedRowsGrid = GridReplacement('%T' . $i .'%',$populatedRowsGrid,$codeCount);	
				}

				$totalCodeCount = $codeCount + $totalCodeCount; //accumulating for Year To Date
			}
	 


		if($ytd !== FALSE){
			//Make sure PHP prints this as 1  decimal place
			$totalCodeCount = number_format((float)$totalCodeCount, 1, '.', '');
			$populatedRowsGrid = GridReplacement('%TOTAL%',$populatedRowsGrid,$totalCodeCount);	
				}

		//Clean Up Periods left
		$populatedRowsGrid = GridReplacement('/%(.*?)%/',$populatedRowsGrid,' ',2);


		return $populatedRowsGrid;
}

/**
 * Make Teacher
 * DBGet callback
 * Local function
 *
 * @deprecated since 3.4.3. Use Teacher ID instead of extracting Teacher name from CP title.
 *
 * @param  string $teacher  Teacher
 * @param  string $column   'TEACHER'
 * @return string Formatted Teacher
 */
function _makeTeacher( $teacher, $column )
{
	return mb_substr( $teacher, mb_strrpos( str_replace( ' - ', ' ^ ', $teacher ), '^' ) + 2 );
}


/**
*
*
* This module uses a SQL view of studentReportCardAttendance to do all the Attendance and
* Attendance subcategory work
* I am not writing one of these for Marking Period and Year Total so there is an if statement on last_mp
*
* @param last_mp is last marking Period
* @param student_id  Student ID number
* @param summaryOrDetail do you want Summary of Absent/Present or the categaories
* @param 
* 
**/
function getCategoryAttendanceCount($last_mp, $student_id,$summaryOrDetail, $attendanceCategory){
	// check the $last_mp to see if it is YearTotal

//print('Summary / Detail ' . $summaryOrDetail . '   Category ' . $attendanceCategory);
	if($last_mp == 'YearTotal'){
		//Change the last_mp flag
		$whereClauseMarkingPeriod = ' ';
	}else{
		$whereClauseMarkingPeriod = ' AND marking_period_id =' . $last_mp;
	}

	//Switch to State Value codes for SUMMARY
	if($summaryOrDetail == 'SUMMARY'){
		if($attendanceCategory == 'ABSENT'){
			$whereClause = " AND (STATE_CODE = 'A' OR STATE_CODE = 'H')";  
			$fieldToUse = "ABSENT";
		}elseif($attendanceCategory == 'PRESENT'){
			$whereClause = " AND (STATE_CODE = 'P' OR STATE_CODE = 'H')";
			$fieldToUse = "PRESENT";
		}else{}//do nothing

	}else{
		//Deal with Details aka Categories of Absent or Present
		   $summaryOrDetail = strtoupper($summaryOrDetail);

			  
			
			$getStateCode = DBGet("SELECT STATE_CODE" .
		            ' From "studentReportCardAttendance"' .
		            " WHERE upper(TITLE) LIKE '%" . $summaryOrDetail . "%'" .
		            " LIMIT 1");
			//Turn State Code to the Column you want.

//print('<br>State Codes ');
//print("<pre>".print_r($getStateCode,true)."</pre>");

			if($getStateCode[1]['STATE_CODE'] == 'A'){
				$fieldToUse = "ABSENT";
				$whereClause = " AND upper(TITLE) LIKE '%" . $summaryOrDetail . "%'";
			}elseif($getStateCode[1]['STATE_CODE'] == 'P'){
				$fieldToUse = "PRESENT";

				if($summaryOrDetail == 'PRESENT'){
					$whereClause = " AND ((upper(TITLE) = '" . $summaryOrDetail . "' OR " .  
				               "upper(TITLE) LIKE '%TARDY%') OR (STATE_CODE Like '%H%'))";
				 }else{
				 	$whereClause = " AND upper(TITLE) LIKE '%" . $summaryOrDetail . "%'";
				 }
				
			}elseif($getStateCode[1]['STATE_CODE'] == 'H'){
				$fieldToUse = "HALFDAY";
				$whereClause = " AND upper(TITLE) LIKE '%" . $summaryOrDetail . "%'";
			}else{
				$fieldToUse = "ABSENT";
				$whereClause = " AND upper(TITLE) LIKE '%" . $summaryOrDetail . "%'";
			}
			

	}

	//Get all our data from the View
	$AttendanceRecords = DBGet('Select sum(' . $fieldToUse . ') as days ' .
						'From "studentReportCardAttendance"' .
						' Where student_id = ' . $student_id . 
						' AND syear =' . UserSyear() .
						$whereClauseMarkingPeriod  . $whereClause);

//print('attendance funciton '. $attendanceCategory . '  where  ' . $whereClause);
//print("<pre>".print_r($AttendanceRecords,true)."</pre>");

	return $AttendanceRecords;

}



/**
 * Other Attendace Codes.
 * Local function.
 *
 * @param includeDefault 1 or other... 1 = Do not Include Present
 * @return array
 */
function _getOtherAttendanceCodes($includeDefault = 1)
{
	/**
	 * @var mixed
	 */
	static $other_attendance_codes = null;

	if ( ! $other_attendance_codes )
	{
	 if($includeDefault == 1){
	 	$whereClause = "AND (DEFAULT_CODE!='Y' OR DEFAULT_CODE IS NULL)";
	 }
		// Get Other Attendance Codes.
		$other_attendance_codes = DBGet( "SELECT SHORT_NAME,ID,TITLE, STATE_CODE
			FROM ATTENDANCE_CODES
			WHERE SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "' " .
			$whereClause . " 
			AND TABLE_NAME='0'", array(), array( 'ID' ) );
	}

	return $other_attendance_codes;
}


/**
 * Get Report Cards Comments
 *
 * @since 5.0
 *
 * @example $rc_comments_RET = GetReportCardsComments( $st_list, $mp_list );
 *
 * @param  array $st_list Students list.
 * @param  array $mp_list MPs list.
 *
 * @return array $rc_comments_RET
 */
function GetReportCardsComments( $st_list, $mp_list )
{
	// GET THE COMMENTS.
	$extra['WHERE'] = " AND s.STUDENT_ID IN (" . $st_list . ")";

	// Order General Comments first.
	$extra['SELECT_ONLY'] = "s.STUDENT_ID,sc.COURSE_PERIOD_ID,sc.MARKING_PERIOD_ID,
	sc.REPORT_CARD_COMMENT_ID,sc.COMMENT,
	(SELECT SORT_ORDER
		FROM REPORT_CARD_COMMENTS
		WHERE ID=sc.REPORT_CARD_COMMENT_ID) AS SORT_ORDER,
	(SELECT COALESCE(SCALE_ID, 0)
		FROM REPORT_CARD_COMMENTS
		WHERE ID=sc.REPORT_CARD_COMMENT_ID) AS SORT_ORDER2";

	$extra['FROM'] = ",STUDENT_REPORT_CARD_COMMENTS sc";

	// Get the comments of all MPs.
	//$extra['WHERE'] .= " AND sc.STUDENT_ID=s.STUDENT_ID AND sc.MARKING_PERIOD_ID='".$last_mp."'";
	$extra['WHERE'] .= " AND sc.STUDENT_ID=s.STUDENT_ID AND sc.MARKING_PERIOD_ID IN (" . $mp_list . ")";

	$extra['ORDER_BY'] = 'SORT_ORDER,SORT_ORDER2';

	$extra['group'] = array( 'STUDENT_ID', 'COURSE_PERIOD_ID', 'MARKING_PERIOD_ID' );

	// Parent: associated students.
	$extra['ASSOCIATED'] = User( 'STAFF_ID' );

	$rc_comments_RET = GetStuList( $extra );

	//echo '<pre>'; print_r($rc_comments_RET); echo '</pre>'; exit;

	return $rc_comments_RET;
}


/**
 * Get Course Comment Code Scales
 *
 * @example $comment_scales = GetReportCardCommentScales( $student_id, $course_periods_list );
 *
 * @since 5.0
 *
 * @param int    $student_id          Student ID.
 * @param string $course_periods_list Course Periods present on the Student Report Card list. Comma-separated list.
 *
 * @return array Course Comment Code Scales, 1 formatted string per scale.
 */
function GetReportCardCommentScales( $student_id, $course_periods_list )
{
	static $comment_codes_RET = null;

	if ( ! $comment_codes_RET )
	{
		// Limit code scales to the ones in current SYEAR in REPORT_CARD_COMMENTS.
		//$comment_codes_RET = DBGet( "SELECT cc.TITLE,cc.COMMENT,cs.TITLE AS SCALE_TITLE,cs.COMMENT AS SCALE_COMMENT FROM REPORT_CARD_COMMENT_CODES cc, REPORT_CARD_COMMENT_CODE_SCALES cs WHERE cc.SCHOOL_ID='".UserSchool()."' AND cs.ID=cc.SCALE_ID ORDER BY cs.SORT_ORDER,cs.ID,cc.SORT_ORDER,cc.ID" );
		$comment_codes_RET = DBGet( "SELECT cs.ID AS SCALE_ID,cc.TITLE,cc.COMMENT,
			cs.TITLE AS SCALE_TITLE,cs.COMMENT AS SCALE_COMMENT
		FROM REPORT_CARD_COMMENT_CODES cc, REPORT_CARD_COMMENT_CODE_SCALES cs
		WHERE cc.SCHOOL_ID='" . UserSchool() . "'
		AND cs.ID=cc.SCALE_ID
		AND cc.SCALE_ID IN (SELECT DISTINCT c.SCALE_ID
			FROM REPORT_CARD_COMMENTS c
			WHERE c.SYEAR='" . UserSyear() . "'
			AND c.SCHOOL_ID=cc.SCHOOL_ID
			AND c.SCALE_ID IS NOT NULL)
		ORDER BY cc.SORT_ORDER,cc.ID" );
	}

	$student_comment_scales_RET = DBGet( "SELECT cs.ID
	FROM REPORT_CARD_COMMENT_CODE_SCALES cs
	WHERE cs.ID IN
		(SELECT c.SCALE_ID
		FROM REPORT_CARD_COMMENTS c
		WHERE (c.COURSE_ID IN(SELECT COURSE_ID
			FROM SCHEDULE
			WHERE STUDENT_ID='" . $student_id . "'
			AND COURSE_PERIOD_ID IN(" . $course_periods_list . "))
			OR c.COURSE_ID=0)
		AND c.SCHOOL_ID=cs.SCHOOL_ID
		AND c.SYEAR='" . UserSyear() . "')
	AND cs.SCHOOL_ID='" . UserSchool() . "'", array(), array( 'ID' ) );

	$student_comment_scales = array_keys( $student_comment_scales_RET );

	$comments = array();

	$scale_titles = array();

	$scale_title = '';

	foreach ( (array) $comment_codes_RET as $comment )
	{
		// Limit comment scales to the ones used in student's courses.
		if ( ! in_array( $comment['SCALE_ID'], $student_comment_scales ) )
		{
			continue;
		}

		if ( $scale_title != $comment['SCALE_TITLE'] )
		{
			$scale_titles[ $comment['SCALE_ID'] ] = FormatInputTitle(
				$comment['SCALE_TITLE'] . ( ! empty( $comment['SCALE_COMMENT'] ) ?
					', ' . $comment['SCALE_COMMENT'] : '' )
			);
		}

		if ( ! isset( $comments[ $comment['SCALE_ID'] ] ) )
		{
			$comments[ $comment['SCALE_ID'] ] = array();
		}

		$comments[ $comment['SCALE_ID'] ][] = '(' . $comment['TITLE'] . ') ' . $comment['COMMENT'];

		$scale_title = $comment['SCALE_TITLE'];
	}

	$comments_scales = array();

	foreach ( $comments as $scale_id => $comments_array )
	{
		$comment_scales[] = implode( '<br />', $comments_array ) . $scale_titles[ $scale_id ];
	}

	return $comment_scales;
}


/**
 * Get General Comment Codes
 *
 * @example $general_comments = GetReportCardGeneralComments( $student_id, $comments_arr );
 *
 * @since 5.0
 *
 * @param int   $student_id     Student ID.
 * @param array $comments_array Student Comments array, as generated by ReportCardsGenerate().
 *
 * @return string General Comment Codes.
 */
function GetReportCardGeneralComments( $student_id, $comments_array )
{
	static $commentsB_RET = null;

	if ( ! $commentsB_RET )
	{
		$commentsB_RET = DBGet( "SELECT ID,TITLE,SORT_ORDER
		FROM REPORT_CARD_COMMENTS
		WHERE SCHOOL_ID='" . UserSchool() . "'
		AND SYEAR='" . UserSyear() . "'
		AND COURSE_ID IS NULL", array(), array( 'ID' ) );
	}

	$personalizations = _getReportCardCommentPersonalizations( $student_id );

	$commentsB_displayed = array();

	$general_comments = array();

	foreach ( (array) $comments_array as $comment_course_title => $comments )
	{
		foreach ( (array) $comments as $comment => $sort_order )
		{
			if ( empty( $commentsB_RET[$comment] )
				|| in_array( $commentsB_RET[$comment][1]['SORT_ORDER'], $commentsB_displayed ) )
			{
				continue;
			}

			$general_comments[] = $commentsB_RET[$comment][1]['SORT_ORDER'] . ': ' .
			str_replace(
				array_keys( $personalizations ),
				$personalizations,
				$commentsB_RET[$comment][1]['TITLE']
			);

			$commentsB_displayed[] = $commentsB_RET[$comment][1]['SORT_ORDER'];
		}
	}

	$general_comments = implode( '<br />', $general_comments );

	$general_comments .= FormatInputTitle( _( 'General Comments' ) );

	return $general_comments;
}

/**
 * Get Course Specific Comment Code Scales
 *
 * @example $course_specific_comments = GetReportCardCourseSpecificComments( $student_id, $comments_arr );
 *
 * @since 5.0
 *
 * @param int   $student_id     Student ID.
 * @param array $comments_array Student Comments array, as generated by ReportCardsGenerate().
 *
 * @return array Course Specific Comment Code Scales, 1 formatted string per course.
 */
function GetReportCardCourseSpecificComments( $student_id, $comments_array )
{
	static $commentsA_RET = null;

	if ( ! $commentsA_RET )
	{
		// Get color for Course specific categories & get comment scale.
		//$commentsA_RET = DBGet( "SELECT ID,TITLE,SORT_ORDER FROM REPORT_CARD_COMMENTS WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."' AND COURSE_ID IS NOT NULL AND COURSE_ID!='0'",array(),array('ID'));
		$commentsA_RET = DBGet( "SELECT c.ID,c.TITLE,c.SORT_ORDER,cc.COLOR,
			cs.TITLE AS SCALE_TITLE
		FROM REPORT_CARD_COMMENTS c, REPORT_CARD_COMMENT_CATEGORIES cc,
			REPORT_CARD_COMMENT_CODE_SCALES cs
		WHERE c.SCHOOL_ID='" . UserSchool() . "'
		AND c.SYEAR='" . UserSyear() . "'
		AND c.COURSE_ID IS NOT NULL
		AND c.COURSE_ID!='0'
		AND cc.SYEAR=c.SYEAR
		AND cc.SCHOOL_ID=c.SCHOOL_ID
		AND cc.COURSE_ID=c.COURSE_ID
		AND cc.ID=c.CATEGORY_ID
		AND cs.SCHOOL_ID=c.SCHOOL_ID
		AND cs.ID=c.SCALE_ID
		ORDER BY c.SORT_ORDER,c.ID", array(), array( 'ID' ) );
	}

	$personalizations = _getReportCardCommentPersonalizations( $student_id );

	$course_comments = array();

	$course_title = '';

	$i = 0;

	foreach ( (array) $comments_array as $comment_course_title => $comments )
	{
		$course_comments[ $comment_course_title ] = array();

		foreach ( (array) $comments as $comment => $sort_order )
		{
			if ( empty( $commentsA_RET[$comment] ) )
			{
				continue;
			}

			$color = $commentsA_RET[$comment][1]['COLOR'];

			if ( $color )
			{
				$color_html = '<span style="color:' . $color . '">';
			}
			else
			{
				$color_html = '';
			}

			$course_comments[ $comment_course_title ][] = $color_html .
			$commentsA_RET[$comment][1]['SORT_ORDER'] . '. ' .
			str_replace(
				array_keys( $personalizations ),
				$personalizations,
				$commentsA_RET[$comment][1]['TITLE']
			) .
			( $color_html ? '</span>' : '' ) .
			' <small>(' . $commentsA_RET[$comment][1]['SCALE_TITLE'] . ')</small>';
		}

		if ( $course_comments[ $comment_course_title ] )
		{
			$course_comments[ $comment_course_title ] = implode( '<br />', $course_comments[ $comment_course_title ] ) .
				FormatInputTitle( $comment_course_title );
		}
	}

	return $course_comments;
}


/**
 * Get Comment Personalizations
 * Replace ^n with Student first name
 * Replace ^s with Student gender.
 *
 * Local function
 *
 * @example $personalizations = _getReportCardCommentPersonalizations( $student_id );
 *
 * @since 5.0
 *
 * @param  int   $student_id Student ID.
 *
 * @return array Comment Personalizations
 */
function _getReportCardCommentPersonalizations( $student_id )
{
	static $gender_field_type = null;

	if ( ! $gender_field_type )
	{
		$gender_field_type = DBGetOne( "SELECT TYPE
		FROM CUSTOM_FIELDS
		WHERE ID=200000000" );
	}

	$student_RET = DBGet( "SELECT CUSTOM_200000000 AS GENDER,FIRST_NAME
		FROM STUDENTS
		WHERE STUDENT_ID='" . $student_id . "'" );

	// Gender field.
	$gender = 'M';

	if ( $gender_field_type === 'select' )
	{
		if ( mb_substr( $student_RET[1]['GENDER'], 0, 1 ) === 'F' )
		{
			$gender = 'F';
		}
	}

	$personalizations = array(
		'^n' => ( $student_RET[1]['FIRST_NAME'] ),
		'^s' => ( $gender == 'M' ? _( 'his' ) :
			( $gender == 'F' ? _( 'her' ) : _( 'his/her' ) ) ) );

	return $personalizations;
}


/**
 * Get Report Card Min. and Max. Grades
 *
 * @since 5.0
 *
 * @param array $course_periods Course Periods array, with MPs array.
 *
 * @return array Updated $grades_RET.
 */
function GetReportCardMinMaxGrades( $course_periods )
{
	static $min_max_grades = array();

	$mp_list = $cp_list = array();

	foreach ( (array) $course_periods as $course_period_id => $mps )
	{
		$cp_list[] = $course_period_id;

		if ( ! empty( $mp_list ) )
		{
			continue;
		}

		foreach ( (array) $mps as $mp )
		{
			$mp_list[] = $mp[1]['MARKING_PERIOD_ID'];
		}
	}

	$mp_list = "'" . implode( "','", $mp_list ) . "'";

	$cp_list = "'" . implode( "','", $cp_list ) . "'";

	if ( ! isset( $min_max_grades[$cp_list][$mp_list] ) )
	{

		// Get Min. Max. Grades for each CP, and each MP.
		$min_max_grades[$cp_list][$mp_list] = DBGet( "SELECT COURSE_PERIOD_ID,MARKING_PERIOD_ID,
			MIN(GRADE_PERCENT) AS GRADE_MIN,MAX(GRADE_PERCENT) AS GRADE_MAX
			FROM STUDENT_REPORT_CARD_GRADES
			WHERE SYEAR='" . UserSyear() . "'
			AND COURSE_PERIOD_ID IN(" . $cp_list . ")
			AND MARKING_PERIOD_ID IN(" . $mp_list . ")
			GROUP BY COURSE_PERIOD_ID,MARKING_PERIOD_ID", array(), array( 'COURSE_PERIOD_ID', 'MARKING_PERIOD_ID' ) );
	}

	return $min_max_grades[$cp_list][$mp_list];
}


/**
 * Add Report Card Min. and Max. Grades before and after student Grade for each Course & each MP.
 * Update MP columns text: "Min. [MP] Max.".
 *
 * @since 5.0
 *
 * @param array $min_max_grades Min. and Max. Grades.
 * @param array $grades_RET     Student Report Card Grades list array.
 * @param array &$LO_columns    List columns.
 *
 * @return array Updated $grades_RET.
 */
function AddReportCardMinMaxGrades( $min_max_grades, $grades_RET, &$LO_columns )
{
	static $columns_done = false;

	require_once 'ProgramFunctions/_makeLetterGrade.fnc.php';

	$grades_loop = $grades_RET;

	foreach ( (array) $grades_loop as $i => $grade )
	{
		if ( empty( $grade['COURSE_PERIOD_ID'] ) )
		{
			continue;
		}

		$cp_id = $grade['COURSE_PERIOD_ID'];

		$min_max_grades_cp = $min_max_grades[ $cp_id ];

		foreach ( (array) $min_max_grades_cp as $mp_id => $min_max )
		{
			$min_grade = issetVal( $min_max[1]['GRADE_MIN'], '' );
			$max_grade = issetVal( $min_max[1]['GRADE_MAX'], '' );

			$min_grade = _makeLetterGrade( $min_grade / 100, $cp_id );
			$max_grade = _makeLetterGrade( $max_grade / 100, $cp_id );

			$grades_RET[$i][$mp_id] = '<div style="float: left;width: 23%;" class="size-1">' . $min_grade . '</div>
				<div style="float: left;width: 48%;text-align: center;">' . $grades_RET[$i][$mp_id] . '</div>
				<div style="float: left;width: 23%;text-align: right;" class="size-1">' . $max_grade . '</div>';

			if ( $columns_done )
			{
				continue;
			}

			// Note: Total width < 100% so to leave space for triangle sort icon (::after).
			$LO_columns[$mp_id] = '<div style="float: left;width: 23%;" class="size-1">' . _( 'Min.' ) . '</div>
				<div style="float: left;width: 48%;text-align: center;"><b>' . $LO_columns[$mp_id] . '</b></div>
				<div style="float: left;width: 23%;text-align: right;" class="size-1">' . _( 'Max.' ) . '</div>';
		}

		$columns_done = true;
	}

	return $grades_RET;
}



/**
*
*
**/
function findTemplates($templateDirectory,$extension){
	/* Main Layouts must end with .MAIN
	   Course layouts must end with .COURSE
	   Attendance Layouts must end with ATTENDANCE
	   These are layouts only, the application will replace 
	   tags */
	   //$files = glob("/path/to/directory/*.{jpg,gif,png}", GLOB_BRACE);
	   $glob = glob("{$templateDirectory}*.{$extension}", GLOB_BRACE);

	   return $glob;


}




function mergePDFFiles(Array $filenames, $outFile, $title='', $author = '', $subject = '') {

		require_once __DIR__ . '../mpdf/autoload.php';
$mpdf = new \Mpdf\Mpdf(['orientation' => 'L']);


error_log('In the functions ');

        
        $mpdf->SetTitle($title);
        $mpdf->SetAuthor($author);
        $mpdf->SetSubject($subject);
        if ($filenames) {
            $filesTotal = sizeof($filenames);
            $mpdf->SetImportUse();        
            for ($i = 0; $i<count($filenames);$i++) {
                $curFile = $filenames[$i];
                if (file_exists($curFile)){
                    $pageCount = $mpdf->SetSourceFile($curFile);
                    for ($p = 1; $p <= $pageCount; $p++) {
                        $tplId = $mpdf->ImportPage($p);
                        $wh = $mpdf->getTemplateSize($tplId);                
                        if (($p==1)){
                            $mpdf->state = 0;
                            $mpdf->UseTemplate ($tplId);
                        }
                        else {
                            $mpdf->state = 1;
                            $mpdf->AddPage($wh['w']>$wh['h']?'L':'P');
                            $mpdf->UseTemplate($tplId);    
                        }
                    }
                }                    
            }                
        }
        $mpdf->Output();
        unset($mpdf);
    }
