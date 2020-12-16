<?php
/**
 * Menu.php file
 * Required
 * - Menu entries for the CustomReportCard module
 * - Add Menu entries to other modules
 *
 * @package CustomReportCard module
 */
 
/**
 * Use dgettext() function instead of _() for Module specific strings translation
 * see locale/README file for more information.
 */
$module_name = dgettext( 'CustomReportCard', 'CustomReportCard' );

// Menu entries for the CustomReportCard module.
$menu['CustomReportCard']['admin'] = array( // Admin menu.
	'title' => dgettext( 'CustomReportCard', 'CustomReportCard' ),
	'default' => 'CustomReportCards/CustomReportCards.php', // Program loaded by default when menu opened.
	'CustomReportCards/CustomReportCards.php' => dgettext( 'CustomReportCard', 'Custom Report Cards' ),
	'CustomReportCards/ReportCardsEmailParents.php' => dgettext( 'CustomReportCard', 'EMail Report Cards' ),
	1 => _( 'Setup' ), // Add sub-menu 1 (only for admins).
	'CustomReportCards/Setup.php' => _( 'Configuration' ), // Reuse existing translations: 'Configuration' exists under School module.
);

