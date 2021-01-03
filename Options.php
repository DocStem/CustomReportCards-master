<?php
/**
 * Custom Report Card Options 
 * Optional
 * - Setup Templates for Options to be used when producing Report Cards
 *
 * @package CustomReportCard module
 */


	$optionsCSS = file_get_contents('modules/CustomReportCard/includes/options.css');
	

DrawHeader( ProgramTitle() ); // Display main header with Module icon and Program title.

if ( $_REQUEST['modfunc'] === 'update'
	&& AllowEdit() ) // AllowEdit must be verified before inserting, updating, deleting data.
{
	if ( isset( $_POST['values'] ) )
	{
		// Verify value is numeric.
		if ( empty( $_REQUEST['values']['CustomReportCard_CONFIG'] )
			|| is_numeric( $_REQUEST['values']['CustomReportCard_CONFIG'] ) )
		{
			ProgramConfig( 'CustomReportCard', 'CustomReportCard_CONFIG', $_REQUEST['values']['CustomReportCard_CONFIG'] );

			// Add note.
			$note[] = button( 'check' ) . '&nbsp;' .
				dgettext( 'CustomReportCard', 'The configuration value has been modified.' );
		}
		else // If no value or value not numeric.
		{
			// Add error message.
			$error[] = _( 'Please enter valid Numeric data.' );
		}
	}

	// Unset modfunc & values & redirect URL.
	RedirectURL( array( 'modfunc', 'values' ) );
}



//-----------------------------------------------------------------------------------------------------------
// Display Setup value form.
if ( empty( $_REQUEST['modfunc'] ) )
{
	// Display note if any.
	echo ErrorMessage( $note, 'note' );

	// Display errors if any.
	echo ErrorMessage( $error, 'error' );

	if(isset($_POST['button1'])) { 
		//template name needs to be Unique

		$templateFields = DBGet( 'SELECT * FROM "customReportCardLayout" a
			Where a."templateName" = \'' . $_POST['templateName'] . '\'');
		
		if($_POST['templateName'] && !$templateFields){

				try{
				DBQuery('Insert INTO "customReportCardLayout" ("templateName")
					     VALUES(\'' . $_POST['templateName'] .'\')');
				$success = "Form for template " . $_POST['templateName'] . ' created. Remember to populate assumptions!';
				}catch(Exception $e){
				$error = 'That will not work ' . $e->getMessage();
		           
				}
			echo $success . $error; 

		}else{
			if($templateName){
				$templateExists = 'That name already exists!';
			}
			echo "Specify a unique name." . $templateExists;
		}
    } 

    if(isset($_POST['button2'])) { 
    	//Update the Template Fields
            echo "This is Button2 that is selected"; 
            //var_dump($_POST['templateName']);
        // put out the POST data.

            foreach ( (array) $_POST['templateName'] as $records )
			{
				foreach ( (array) $records as $column => $value )
				{

				$bob .= DBEscapeIdentifier( $column ) . "='" . $value . "'<br>";
				}
			}

			echo $bob;
/*
			$sql = 'UPDATE "customReportCardLayout" SET ';

			foreach ( (array) $columns as $column => $value )
			{
				$sql .= DBEscapeIdentifier( $column ) . "='" . $value . "',";
			}

			$sql = mb_substr( $sql, 0, -1 ) . " WHERE ID='" . $id . "'";

			DBQuery( $sql );
*/
    } 

	// Form used to send the updated Config to be processed by the same script (see at the top).
	echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] .
		'&modfunc=update" method="POST">';

	// Display secondary header with Save button (aligned right).
DrawHeader( '','<input type="submit" name="button2" class="myButton" value="Save" />');
	echo '<br />';

//Bring in CSS to make the Tabs visible
echo $optionsCSS;


echo 'Name: <input type="text" name="templateName"><input type="submit" name="button1" class="myButton1" value="New Template" />';
	$templateFields = array();
	$templateFields = DBGet( 'SELECT * FROM "customReportCardLayout"' );


  $tabs = '<ul class="tab">';
	
	if($templateFields){
		
		$aa = 0;
		foreach((array) $templateFields as $fields){
			$aa +=1;
			
			//echo '<pre>' . print_r($fields,true) . '</pre>';
			$tabs .= '<li><a href="#" class="tablinks" onclick="openCity(event, \'' . $fields['TEMPLATENAME'] . '\')"';

			if($aa == 1){
				$tabs .= ' id="defaultOpen"';
				
			}
				$tabs .= '>' . $fields['TEMPLATENAME'] . '</li>';

			$template .= '<div id="' . $fields['TEMPLATENAME'] . '" class="tabcontent"><table>';

			foreach((array) $fields as $field => $fieldValue ){
				if($field == 'ID' || $field == 'SCHOOLID'){
					//Hide these fields but needed in the RePost as a WHERE clause
					$template .= '<tr style="text-align:left;display:none;"><td><b>' . str_replace('_',' ',$field) . '</b></td>';
					$template .= '<td><input type="text" name="templateName[' . $aa . ']['. $field . ']" value="' . $fieldValue . '">  </td></tr>'; 
				}else{
					$template .= '<tr style="text-align:left;"><td><b>' . str_replace('_',' ',$field) . '</b></td>';
					$template .= '<td><input type="text" name="templateName[' . $aa . '][' . $field . ']" value="' . $fieldValue . '">  </td></tr>'; 
				}
				
			}
			$template .= '</table></div>';
			
		}// There are templates
	}//There are records to see
		
		$tabs .= '</ul>';

		echo $tabs;
// Display the program config options.

		//Output any information regarding the fields populated or not
		echo $template;
	
	// Close PopTable.
	PopTable( 'footer' );
    
	echo '</form>';


// Used to display tab information
echo '
<script>

function openCity(evt, cityName) {
    // Declare all variables
    var i, tabcontent, tablinks;

    // Get all elements with class="tabcontent" and hide them
    tabcontent = document.getElementsByClassName("tabcontent");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }

    // Get all elements with class="tablinks" and remove the class "active"
    tablinks = document.getElementsByClassName("tablinks");
    for (i = 0; i < tabcontent.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }

    // Show the current tab, and add an "active" class to the link that opened the tab
    document.getElementById(cityName).style.display = "block";
    evt.currentTarget.className += " active";
}
</script>
';

}
