# WHAT IS THIS?
------
This is a report card customization to Rosario SIS version 5.5
We created this as we need different specialized formats for Middle School, Elementary, Kindergarten and PreK all from the same system. We also needed the Course Major Standards on the
report Card. (Some Report Cards include 120+ course lines.)

It allows customization of format, sending email of report card to multiple people simultaneously (we use GMAIL)
If you follow the setups below you can produce 1 report card every .45 seconds.
NOTE: due to high format or our report cards, we open the PHP during this module to 1 Gig, however you can use it with less memory depending on the complexity of your PDF formats.

* This is a continual work in progress but currently used in Production by clients. This has been tested on Ubuntu 18

## ABOUT US
------
Doc STEM is the **winningest STEM Professor** in the world. Professionally, he contributed to the creation of graphics, is a world reknown robotics engineer, winner of the US Presidential Award in Science Teaching, and retired CEO of the world famous FinTech -- yeah, the real one.

Our organization supports shool technology, networks, programs, etc. We also run the STEMXTRA.org outsource for school STEM programs. Reach out to us if you need support.

## CLIENT ACKNOWLEDGEMENT
------
Acknowledge: **Immaculate Conception Academy, Douglassville PA**. ICA is the foremost technical PreK - 8 school in the US. (2020 World Robotic winners, home of the US Tarc Rocket Team, Winner of US Congressional Coding Challenge, Winner PA STEM...)

REPORTS MENU
![Report Menu](reportsMenu.PNG)
EMAIL REPORTS MENU
![EMAIL Report Menu](emailMenu.PNG)
REPORT CARD
![Report Card Page 1](page1Report.PNG)
![Report Card Page 2](reportpage2.PNG)
HONORS CERTIFICATE
![Honors Certificate](Certificate.PNG)

### INSTALL
-------
Copy the `CustomReportCards/` folder (if named `CustomReportCard-master`, rename it) and its content inside the `modules/` folder of RosarioSIS.

Go to _School Setup > School Configuration > Modules_ and click "Activate".

Requires RosarioSIS 5.5+  -- Say Hey to Francois

This module installs mPDF for pdf printing options.
This module uses PHPMailer to send emails.
(On client systems we modify the SendMail.php base function to speed the process...That information is too technical for this post.)


### Things To Be Aware Of
------
**THIS IS A WORK IN PROGRESS  -- Additional Professional Polish is still needed**
This application is in production for the groups that needed it. You will need to modify if you download to fit your needs.

1. This module makes use of SQL Views to simplify future changes. Their is an additional SQL View package to support JasperServer called ICA_SIS_VIEWS.
2. *This module does a modification to standard Rosario, it expands the COURSE TITLE to 60 Characters!*
3. This module installs Postgres SQL Functions to do many calculations and make updates / modifications simpler
4. The more complex your report card layout, the longer the report does take to generate. This is a direct function of HTML to pdf conversion.

### SQL needed / added elements
------
1. function get_schooldata -- takes school id or Userschool() function
2. View scheduleStudentReportCard -- Compresensive view of scheduled (not dropped) student classes with subject, period, teacher and standards/skills
3. function get_principal gets the princial name
4. function general_average calculates the general average of a student based on courses with a Credit of 1 and numeric grade value.
5. View activeStudents -- provides a list of students that are active, currently enrolled.


### Web Server Optional
-------
1. turn on Opcache in Apache for speed


### Understanding the HTML Templates
-----
The HTML templates are broken into 3 pieces
1. A = Main format and layout
2. B = General Table layout for a Course Header
3. C = The rows that come under a Course

The HTML Templates use a simple find and replace method. They look for Key works set off in '%'. Example %SCHOOL% is a keyword that is targeted for replace.


## System To Do For You
-----
1. To Print the School District
    1. You need to add a School Custom field called -- School District
	2. Populate that with School District title or the large title desired for report card.
	3. the application will find the custom field called School District to be used with the School.

2. We schedule a School CLASS called Attendance so we can get a HOME ROOM teacher. 
That is on the template but you dont have to keep it on any new template. just use the Grid Replace to remove it or do not include it in your templates.

3. One of the tricks we did was to create a COURSE called SUBJECT SKILLS for each subject so that they could be pulled easily... We did not give them a PERIOD but did give them a MARKED designation 

4. Modify your templates in the includes directory. 
	1. Called header.html -- holds the top first page header and the back page Comment Codes
	2. maincourseblock.html is a Table Header it has %SKILL% which you can replace with the Subject the student is Scheduled for. This appends to the %COURSEMARKS% inside the header.html
	3. marksrow.html is just a Row with cells that get a find and replace of %T#% (where # is 1, 2, 3, etc.). This will append to the Maincourseblock.html.
You need to modify the TEMPLATES to fit the format of your schools Report Cards.

5. If you provide HONORS Certificates, you need to create your own. Use the current ones to understand how they work. (See files middleSchool.HONORS.html)
++ Our requirements are 92+ and Pass on all courses for First Honors. 85+ and Pass on all courses for Second Honors.
I would love to see some other ones.

6. This pulls the School Logo into the Report Card template if it exists

7. This needs a directory called pdfreports that is used for debugging of pdf files. We do not use the SIS system for front facing of our clients. The SIS for us is administrative and kept secure from Students and Parents.

8. Our grades come from Canvas LMS. See our SIS Canvas LMS two way migrations (Posting later)

### Program: Things To Do -- Further Development
------
1. Modify the COURSETABLE function to allow variable Targeted Courses to be placed where ever on the template
2. Add a Configuration table to house; icons associated with Marks, Updated Certificates, rules for Honors
3. Perfect Attendance Certificate
4. Find a way to update the module without Activate / Deactivate Delete.

