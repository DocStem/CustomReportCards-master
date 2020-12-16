/**
 * Delete SQL
 *
 * Required if install.sql file present
 * - Delete profile exceptions
 * - Delete program config options if any (to every schools)
 * - Delete module specific tables
 * (and their eventual sequences & indexes) if any
 *
 * @package CustomReportCard module
 */

--
-- Delete from profile_exceptions table
--


DELETE FROM profile_exceptions WHERE modname='CustomReportCard/CustomReportCards.php';
DELETE FROM profile_exceptions WHERE modname='CustomReportCard/ReportCardsEmailParents.php';
DELETE FROM profile_exceptions WHERE modname='CustomReportCards/CustomReportCards.php';
DELETE FROM profile_exceptions WHERE modname='CustomReportCards/ReportCardsEmailParents.php';

--
-- Delete options from program_config table
--

DELETE FROM program_config WHERE program='CustomReportCard';
DELETE FROM program_config WHERE program='CustomReportCards';
DELETE FROM program_config WHERE program='ReportCardsEmailParents';