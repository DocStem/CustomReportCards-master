<?php
/**
 * English Help texts
 *
 * Texts are organized by:
 * - Module
 * - Profile
 *
 * Please use this file as a model to translate the texts to your language
 * The new resulting Help file should be named after the following convention:
 * Help_[two letters language code].php
 *
 * @author François Jacquet
 *
 * @package CustomReportCard module
 * @subpackage Help
 */


/*$menuOptionsExplained = '1- Data Presentation Group defines the way information is presented.
                          Include Home Room teacher works if you created a Class Attendance and use a variable 
                          %HOMEROOMTEACHER%  in your HTML.'*/



// CustomReportCard ---.
if ( User( 'PROFILE' ) === 'admin' ) :

	


	$help['CustomReportCard/CustomReportCards.php'] = '<p>' . _help( '<i>Custom Report Cards</i> You Create HTHL Templates that can use replacement to populate the report card format.' ) . '</p>
	<p>' . _help( 'I am the inline help for the <code>Setup.php</code> program, you will find me in the <code>Help_en.php</code> file, see you!', 'CustomReportCard' ) . '</p>';

endif;


// Teacher help.
if ( User( 'PROFILE' ) === 'teacher' ) :

	

endif;


// Parent & student help.
if ( User( 'PROFILE' ) === 'parent' ) :



endif;
