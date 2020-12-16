Notes for the Advanced Report Card

SQL needed elements
1- function get_schooldata -- takes school id or Userschool() function
2- View scheduleStudentReportCard -- Compresensive view of scheduled (not dropped) student classes with subject, period, teacher and standards/skills


Web Server Optional
1- turn on Opcache in Apache for speed


System To Do
1- You need to add a School Custom field called -- School District
	a. Populate that with School District title or the large title desired for report card.
	b. the application will find the custom field called School District to be used with the School.

2- We schedule a CLASS called Attendance so we can get a HOME ROOM teacher. That is on the template but you dont have to keep it on any new template. just use the Grid Replace to remove it or do not include it in your templates.

3 - One of the tricks we did was to create a COURSE called SUBJECT SKILLS for each
					       subject so that they could be pulled easily... We did not give them a PERIOD but did
					       give them a MARKED designation 

4- Modify your templates in the includes directory. 
         a. Called header.html -- holds the top first page header and the back page Comment Codes
         b. maincourseblock.html is a Table Header it has %SKILL% which you can replace with the Subject the student is Schedules for. This appends to the %COURSEMARKS% inside the header.html
         c. marksrow.html is just a Row with cells that get a find and replace of %T#% (where # is 1, 2, 3, etc.). This will append to the Maincourseblock.html.




Things to To
1- Put that Home Rooom Teacher Class name in the ini file. It is currently hard coded in the query as Attendance.