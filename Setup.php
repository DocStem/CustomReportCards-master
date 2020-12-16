<?php
/**
 * Setup program
 * Optional
 * - Modify the Config values present in the program_config table
 *
 * @package CustomReportCard module
 */

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

// Display Setup value form.
if ( empty( $_REQUEST['modfunc'] ) )
{
	// Display note if any.
	echo ErrorMessage( $note, 'note' );

	// Display errors if any.
	echo ErrorMessage( $error, 'error' );

	// Form used to send the updated Config to be processed by the same script (see at the top).
	echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] .
		'&modfunc=update" method="POST">';

	// Display secondary header with Save button (aligned right).
	DrawHeader( '', SubmitButton() ); // SubmitButton is diplayed only if AllowEdit.

	echo '<br />';

	// Encapsulate content in PopTable.
	PopTable( 'header', dgettext( 'CustomReportCard', 'CustomReportCard module Setup' ) );

	// Display the program config options.
	echo '<fieldset><legend><b>' . dgettext( 'CustomReportCard', 'CustomReportCard' ) . '</b></legend><table>';

	echo '<tr style="text-align:left;"><td>' .
		TextInput(
			ProgramConfig( 'CustomReportCard', 'CustomReportCard_CONFIG' ),
			'values[CustomReportCard_CONFIG]',
			'<span class="legend-gray" title="' . dgettext( 'CustomReportCard', 'Try to enter a non-numeric value' ) . '">' .
				dgettext( 'CustomReportCard', 'CustomReportCard config value label' ) . ' *</span>',
			'maxlength=2 size=2 min=0'
		) . '</td></tr>';

	echo '</table></fieldset>';

	// Close PopTable.
	PopTable( 'footer' );

	// SubmitButton is diplayed only if AllowEdit.
	echo '<br /><div class="center">' . SubmitButton() . '</div>';

	echo '</form>';
}
