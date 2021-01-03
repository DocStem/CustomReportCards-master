/**
 * Install SQL
 * Required if the module adds programs to other modules
 * Required if the module has menu entries
 * - Add profile exceptions for the module to appear in the menu
 * - Add program config options if any (to every schools)
 * - Add module specific tables (and their eventual sequences & indexes)
 *   if any: see rosariosis.sql file for CustomReportCards
 *
 * @package CustomReportCard module
 */

/**
 * profile_exceptions Table
 *
 * profile_id:
 * - 0: student
 * - 1: admin
 * - 2: teacher
 * - 3: parent
 * modname: should match the Menu.php entries
 * can_use: 'Y'
 * can_edit: 'Y' or null (generally null for non admins)
 */
--
-- Data for Name: profile_exceptions; Type: TABLE DATA;
--

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 1, 'CustomReportCard/CustomReportCards.php', 'Y', 'Y'
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='CustomReportCard/CustomReportCards.php'
    AND profile_id=1);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 1, 'CustomReportCard/ReportCardsEmailParents.php', 'Y', 'Y'
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='CustomReportCard/ReportCardsEmailParents.php'
    AND profile_id=1);



/**
 * program_config Table
 *
 * syear: school year (school may have various years in DB)
 * school_id: may exists various schools in DB
 * program: convention is module name, for ex.: 'CustomReportCard'
 * title: for ex.: 'CustomReportCard_[your_program_config]'
 * value: string
 */
--
-- Data for Name: program_config; Type: TABLE DATA; Schema: public; Owner: rosariosis
--


INSERT INTO program_config (syear, school_id, program, title, value)
SELECT sch.syear, sch.id, 'CustomReportCard', 'CustomReportCard_CONFIG', '5'
FROM schools sch
WHERE NOT EXISTS (SELECT title
    FROM program_config
    WHERE title='CustomReportCard_CONFIG');

/* Modifications, Table Additions, View Additions, Function Additions needed to support this module

*/
/* In Case of reinstall with new version */
DROP VIEW "studentGeneralAverage" CASCADE;
DROP VIEW "studentScheduleReportCard" CASCADE;
DROP VIEW "active_students" CASCADE;
DROP Function "get_schooldata" CASCADE;
DROP VIEW "get_schooldata" CASCADE;

/* Need more characters for the Skills */




alter table courses alter column short_name TYPE varchar(50);

CREATE OR REPLACE FUNCTION get_principal(
    desiredfield text,
    schoolname text)
    RETURNS schools
    LANGUAGE 'sql'
    COST 100
    VOLATILE PARALLEL UNSAFE
AS $BODY$
select *
                from schools
                where short_name = schoolname;
$BODY$;


CREATE OR REPLACE FUNCTION get_schooldata(
    schoolid integer)
    RETURNS schools
    LANGUAGE 'sql'
    COST 100
    VOLATILE PARALLEL UNSAFE
AS $BODY$
select *
                from schools
                where id = schoolid;
$BODY$;


CREATE OR REPLACE FUNCTION get_studentgrade(
    schoolid integer,
    studentid integer,
    schoolyear integer,
    courseperiodid integer,
    markingperiodid character)
    RETURNS student_report_card_grades
    LANGUAGE 'sql'
    COST 100
    VOLATILE PARALLEL UNSAFE
AS $BODY$
select *
                from student_report_card_grades a
                where a.school_id = schoolid
                AND a.student_id = studentid
                AND a.syear = schoolyear
                AND a.course_period_id = coursePeriodId
                AND a.marking_period_id LIKE markingPeriodID;
$BODY$;

/* End of Additional Functions */

/* Add Views */

CREATE OR REPLACE VIEW active_students
 AS
 SELECT a.student_id,
    a.last_name,
    a.first_name,
    a.middle_name,
    a.name_suffix,
    a.username,
    a.password,
    a.last_login,
    a.failed_login,
    a.custom_200000000,
    a.custom_200000001,
    a.custom_200000002,
    a.custom_200000003,
    a.custom_200000004,
    a.custom_200000005,
    a.custom_200000006,
    a.custom_200000007,
    a.custom_200000008,
    a.custom_200000009,
    a.custom_200000010,
    a.custom_200000011,
    a.created_at,
    a.updated_at,
    a.custom_200000013,
    a.custom_200000014,
    a.custom_200000015,
    a.custom_200000016,
    a.custom_200000017,
    a.custom_200000018,
    a.custom_200000019,
    a.custom_200000020,
    a.custom_200000021,
    a.custom_200000022,
    a.custom_200000023,
    a.custom_200000024,
    a.custom_200000025,
    a.custom_200000026,
    a.custom_200000028,
    a.custom_200000029,
    a.custom_200000030,
    a.custom_200000033,
    b.start_date,
    b.end_date,
    b.drop_code
   FROM (students a
     JOIN student_enrollment b ON ((a.student_id = b.student_id)))
  WHERE (b.drop_code IS NULL);

  CREATE OR REPLACE VIEW course_details
 AS
 SELECT cp.school_id,
    cp.syear,
    cp.marking_period_id,
    c.subject_id,
    cp.course_id,
    cp.course_period_id,
    cp.teacher_id,
    c.title AS course_title,
    cp.title AS cp_title,
    cp.grade_scale_id,
    cp.mp,
    cp.credits
   FROM course_periods cp,
    courses c
  WHERE (cp.course_id = c.course_id);

  CREATE OR REPLACE VIEW "studentReportCardAttendance"
 AS
 SELECT a.student_id,
    a.school_date,
    a.marking_period_id,
    a.syear,
    b.attendance_code,
    c.state_code,
    c.title,
    a.state_value,
    ((count(a.state_value) FILTER (WHERE (a.state_value = 1.0)))::numeric + ((count(a.state_value) FILTER (WHERE (a.state_value = 0.5)))::numeric * 0.5)) AS present,
    ((count(a.state_value) FILTER (WHERE (a.state_value = 0.5)))::numeric * 0.5) AS halfday,
    ((count(a.state_value) FILTER (WHERE (a.state_value = 0.0)))::numeric + ((count(a.state_value) FILTER (WHERE (a.state_value = 0.5)))::numeric * 0.5)) AS absent,
    count(a.state_value) AS markingperioddays
   FROM attendance_day a,
    attendance_period b,
    attendance_codes c
  WHERE ((a.student_id = b.student_id) AND (a.school_date = b.school_date) AND (a.marking_period_id = b.marking_period_id) AND (b.attendance_code = c.id) AND (c.table_name = 0))
  GROUP BY a.student_id, a.school_date, a.marking_period_id, a.syear, b.attendance_code, c.title, a.state_value, c.state_code
  ORDER BY a.student_id, a.school_date;


  CREATE OR REPLACE VIEW "studentScheduleReportCard"
 AS
 SELECT a.syear,
    a.school_id,
    a.student_id,
    a.end_date,
    a.course_id,
    a.course_period_id,
    a.marking_period_id,
    b.subject_id,
    b.title,
    b.short_name,
    b.credit_hours,
    c.title AS subjectteacher,
    c.short_name AS subjectskill,
    c.teacher_id,
    f.title AS teachersalutation,
    f.first_name AS teacherfirstname,
    f.last_name AS teacherlastname,
    c.parent_id,
    d.last_name,
    d.first_name,
    e.title AS subject,
    e.sort_order AS reportcardorder
   FROM schedule a,
    courses b,
    course_periods c,
    students d,
    course_subjects e,
    staff f
  WHERE ((a.course_id = b.course_id) AND (a.course_period_id = c.course_period_id) AND (a.student_id = d.student_id) AND (a.end_date IS NULL) AND (b.subject_id = e.subject_id) AND (c.teacher_id = f.staff_id));


CREATE OR REPLACE VIEW enroll_grade
 AS
 SELECT e.id,
    e.syear,
    e.school_id,
    e.student_id,
    e.start_date,
    e.end_date,
    sg.short_name,
    sg.title
   FROM student_enrollment e,
    school_gradelevels sg
  WHERE (e.grade_id = sg.id);

  CREATE OR REPLACE VIEW public."studentGeneralAverage"
 AS
 SELECT a.syear,
    a.course_id,
    a.subject_id,
    a.school_id,
    a.grade_level,
    a.title,
    a.short_name,
    a.rollover_id,
    a.credit_hours,
    a.description,
    a.created_at,
    a.updated_at,
    b.student_id,
    b.last_name,
    b.first_name,
    b.teachersalutation,
    b.teacherfirstname,
    b.teacherlastname,
    c.grade_percent,
    c.course_period_id,
    c.marking_period_id
   FROM courses a,
    "studentScheduleReportCard" b,
    student_report_card_grades c
  WHERE ((a.subject_id = b.subject_id) AND (a.syear = b.syear) AND (b.syear = c.syear) AND (a.school_id = b.school_id) AND (b.student_id = c.student_id) AND (b.course_period_id = c.course_period_id) AND ((a.title)::text = (b.title)::text) AND (a.credit_hours = (1)::numeric));


  

  /* Add New Tables */