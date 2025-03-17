create table account
	(email		varchar(50),
	 password	varchar(20) not null,
	 type		varchar(20),
	 primary key(email)
	);


create table department
	(dept_name	varchar(100), 
	 location	varchar(100), 
	 primary key (dept_name)
	);

create table instructor
	(instructor_id		varchar(10),
	 instructor_name	varchar(50) not null,
	 title 			varchar(30),
	 dept_name		varchar(100), 
	 email			varchar(50) not null,
	 primary key (instructor_id)
	);


create table student
	(student_id		varchar(10), 
	 name			varchar(20) not null, 
	 email			varchar(50) not null,
	 dept_name		varchar(100), 
	 primary key (student_id),
	 foreign key (dept_name) references department (dept_name)
		on delete set null
	);

create table PhD
	(student_id			varchar(10), 
	 qualifier			varchar(30), 
	 proposal_defence_date		date,
	 dissertation_defence_date	date, 
	 primary key (student_id),
	 foreign key (student_id) references student (student_id)
		on delete cascade
	);

create table master
	(student_id		varchar(10), 
	 total_credits		int,	
	 primary key (student_id),
	 foreign key (student_id) references student (student_id)
		on delete cascade
	);

create table undergraduate
	(student_id		varchar(10), 
	 total_credits		int,
	 class_standing		varchar(10)
		check (class_standing in ('Freshman', 'Sophomore', 'Junior', 'Senior')), 	
	 primary key (student_id),
	 foreign key (student_id) references student (student_id)
		on delete cascade
	);

create table classroom
	(classroom_id 		varchar(8),
	 building		varchar(15) not null,
	 room_number		varchar(7) not null,
	 capacity		numeric(4,0),
	 primary key (classroom_id)
	);

create table time_slot
	(time_slot_id		varchar(8),
	 day			varchar(10) not null,
	 start_time		time not null,
	 end_time		time not null,
	 primary key (time_slot_id)
	);

create table course
	(course_id		varchar(20), 
	 course_name		varchar(50) not null, 
	 credits		numeric(2,0) check (credits > 0),
	 primary key (course_id)
	);

create table section
	(course_id		varchar(20),
	 section_id		varchar(10), 
	 semester		varchar(6)
			check (semester in ('Fall', 'Winter', 'Spring', 'Summer')), 
	 year			numeric(4,0) check (year > 1990 and year < 2100), 
	 instructor_id		varchar(10),
	 classroom_id   	varchar(8),
	 time_slot_id		varchar(8),	
	 primary key (course_id, section_id, semester, year),
	 foreign key (course_id) references course (course_id)
		on delete cascade,
	 foreign key (instructor_id) references instructor (instructor_id)
		on delete set null,
	 foreign key (time_slot_id) references time_slot(time_slot_id)
		on delete set null
	);

create table prereq
	(course_id		varchar(20), 
	 prereq_id		varchar(20) not null,
	 primary key (course_id, prereq_id),
	 foreign key (course_id) references course (course_id)
		on delete cascade,
	 foreign key (prereq_id) references course (course_id)
	);

create table advise
	(instructor_id		varchar(8),
	 student_id		varchar(10),
	 start_date		date not null,
	 end_date		date,
	 primary key (instructor_id, student_id),
	 foreign key (instructor_id) references instructor (instructor_id)
		on delete  cascade,
	 foreign key (student_id) references PhD (student_id)
		on delete cascade
);

create table TA
	(student_id		varchar(10),
	 course_id		varchar(20),
	 section_id		varchar(10), 
	 semester		varchar(6),
	 year			numeric(4,0),
	 primary key (student_id, course_id, section_id, semester, year),
	 foreign key (student_id) references PhD (student_id)
		on delete cascade,
	 foreign key (course_id, section_id, semester, year) references 
	     section (course_id, section_id, semester, year)
		on delete cascade
);

create table masterGrader
	(student_id		varchar(10),
	 course_id		varchar(20),
	 section_id		varchar(10), 
	 semester		varchar(6),
	 year			numeric(4,0),
	 primary key (student_id, course_id, section_id, semester, year),
	 foreign key (student_id) references master (student_id)
		on delete cascade,
	 foreign key (course_id, section_id, semester, year) references 
	     section (course_id, section_id, semester, year)
		on delete cascade
);

create table undergraduateGrader
	(student_id		varchar(10),
	 course_id		varchar(20),
	 section_id		varchar(10), 
	 semester		varchar(6),
	 year			numeric(4,0),
	 primary key (student_id, course_id, section_id, semester, year),
	 foreign key (student_id) references undergraduate (student_id)
		on delete cascade,
	 foreign key (course_id, section_id, semester, year) references 
	     section (course_id, section_id, semester, year)
		on delete cascade
);

create table take
	(student_id		varchar(10), 
	 course_id		varchar(20),
	 section_id		varchar(10), 
	 semester		varchar(6),
	 year			numeric(4,0),
	 grade		    	varchar(2)
		check (grade in ('A+', 'A', 'A-','B+', 'B', 'B-','C+', 'C', 'C-','D+', 'D', 'D-','F')), 
	 primary key (student_id, course_id, section_id, semester, year),
	 foreign key (course_id, section_id, semester, year) references 
	     section (course_id, section_id, semester, year)
		on delete cascade,
	 foreign key (student_id) references student (student_id)
		on delete cascade
	);

-- account
insert into account (email, password, type) values ('admin@uml.edu', '123456', 'admin');
insert into account (email, password, type) values ('dbadams@cs.uml.edu', '123456', 'instructor');
insert into account (email, password, type) values ('slin@cs.uml.edu', '123456', 'instructor');
insert into account (email, password, type) values ('Yelena_Rykalova@uml.edu', '123456', 'instructor');
insert into account (email, password, type) values ('Johannes_Weis@uml.edu', '123456', 'instructor');
insert into account (email, password, type) values ('Charles_Wilkes@uml.edu', '123456', 'instructor');

INSERT INTO account (email, password, type) VALUES
('john.smith@student.uml.edu', 'password123', 'student'),
('emma.johnson@student.uml.edu', 'password123', 'student'),
('michael.brown@student.uml.edu', 'password123', 'student'),
('sophia.davis@student.uml.edu', 'password123', 'student'),
('james.wilson@student.uml.edu', 'password123', 'student'),
('olivia.martinez@student.uml.edu', 'password123', 'student'),
('william.taylor@student.uml.edu', 'password123', 'student'),
('ava.anderson@student.uml.edu', 'password123', 'student'),
('noah.thomas@student.uml.edu', 'password123', 'student'),
('isabella.jackson@student.uml.edu', 'password123', 'student'),
('liam.white@student.uml.edu', 'password123', 'student'),
('charlotte.harris@student.uml.edu', 'password123', 'student'),
('benjamin.clark@student.uml.edu', 'password123', 'student'),
('amelia.lewis@student.uml.edu', 'password123', 'student'),
('henry.walker@student.uml.edu', 'password123', 'student');

-- department
insert into department (dept_name, location) values ('Miner School of Computer & Information Sciences', 'Dandeneau Hall, 1 University Avenue, Lowell, MA 01854');

-- instructor
insert into instructor (instructor_id, instructor_name, title, dept_name, email) values ('1', 'David Adams', 'Teaching Professor', 'Miner School of Computer & Information Sciences','dbadams@cs.uml.edu');
insert into instructor (instructor_id, instructor_name, title, dept_name, email) values ('2', 'Sirong Lin', 'Associate Teaching Professor', 'Miner School of Computer & Information Sciences','slin@cs.uml.edu');
insert into instructor (instructor_id, instructor_name, title, dept_name, email) values ('3', 'Yelena Rykalova', 'Associate Teaching Professor', 'Miner School of Computer & Information Sciences', 'Yelena_Rykalova@uml.edu');
insert into instructor (instructor_id, instructor_name, title, dept_name, email) values ('4', 'Johannes Weis', 'Assistant Teaching Professor', 'Miner School of Computer & Information Sciences','Johannes_Weis@uml.edu');
insert into instructor (instructor_id, instructor_name, title, dept_name, email) values ('5', 'Tom Wilkes', 'Assistant Teaching Professor', 'Miner School of Computer & Information Sciences','Charles_Wilkes@uml.edu');

-- student
INSERT INTO student (student_id, name, email, dept_name) VALUES
('UG001', 'John Smith', 'john.smith@student.uml.edu', 'Miner School of Computer & Information Sciences'),
('UG002', 'Emma Johnson', 'emma.johnson@student.uml.edu', 'Miner School of Computer & Information Sciences'),
('UG003', 'Michael Brown', 'michael.brown@student.uml.edu', 'Miner School of Computer & Information Sciences'),
('UG004', 'Sophia Davis', 'sophia.davis@student.uml.edu', 'Miner School of Computer & Information Sciences'),
('UG005', 'James Wilson', 'james.wilson@student.uml.edu', 'Miner School of Computer & Information Sciences');

INSERT INTO student (student_id, name, email, dept_name) VALUES
('MS001', 'Olivia Martinez', 'olivia.martinez@student.uml.edu', 'Miner School of Computer & Information Sciences'),
('MS002', 'William Taylor', 'william.taylor@student.uml.edu', 'Miner School of Computer & Information Sciences'),
('MS003', 'Ava Anderson', 'ava.anderson@student.uml.edu', 'Miner School of Computer & Information Sciences'),
('MS004', 'Noah Thomas', 'noah.thomas@student.uml.edu', 'Miner School of Computer & Information Sciences'),
('MS005', 'Isabella Jackson', 'isabella.jackson@student.uml.edu', 'Miner School of Computer & Information Sciences');

INSERT INTO student (student_id, name, email, dept_name) VALUES
('PH001', 'Liam White', 'liam.white@student.uml.edu', 'Miner School of Computer & Information Sciences'),
('PH002', 'Charlotte Harris', 'charlotte.harris@student.uml.edu', 'Miner School of Computer & Information Sciences'),
('PH003', 'Benjamin Clark', 'benjamin.clark@student.uml.edu', 'Miner School of Computer & Information Sciences'),
('PH004', 'Amelia Lewis', 'amelia.lewis@student.uml.edu', 'Miner School of Computer & Information Sciences'),
('PH005', 'Henry Walker', 'henry.walker@student.uml.edu', 'Miner School of Computer & Information Sciences');

-- phd
INSERT INTO PhD (student_id, qualifier, proposal_defence_date, dissertation_defence_date) VALUES
('PH001', 'Passed', '2023-05-15', NULL),
('PH002', 'Passed', '2022-11-10', NULL),
('PH003', 'Scheduled', NULL, NULL),
('PH004', 'Passed', '2023-09-20', NULL),
('PH005', 'Not Started', NULL, NULL);

-- master
INSERT INTO master (student_id, total_credits) VALUES
('MS001', 18),
('MS002', 24),
('MS003', 12),
('MS004', 30),
('MS005', 6);

-- undergrad
INSERT INTO undergraduate (student_id, total_credits, class_standing) VALUES
('UG001', 75, 'Junior'),
('UG002', 30, 'Sophomore'),
('UG003', 15, 'Freshman'),
('UG004', 90, 'Senior'),
('UG005', 45, 'Sophomore');

-- classroom
INSERT INTO classroom (classroom_id, building, room_number, capacity) VALUES
('CL001', 'Dandeneau Hall', '301', 20),
('CL002', 'Dandeneau Hall', '302', 25),
('CL003', 'Dandeneau Hall', '401', 30),
('CL004', 'Olsen Hall', '201', 40),
('CL005', 'Olsen Hall', '202', 35);

-- time slot
insert into time_slot (time_slot_id, day, start_time, end_time) values ('TS1', 'MoWeFr', '11:00:00', '11:50:00');
insert into time_slot (time_slot_id, day, start_time, end_time) values ('TS2', 'MoWeFr', '12:00:00', '12:50:00');
insert into time_slot (time_slot_id, day, start_time, end_time) values ('TS3', 'MoWeFr', '13:00:00', '13:50:00');
insert into time_slot (time_slot_id, day, start_time, end_time) values ('TS4', 'TuTh', '11:00:00', '12:15:00');
insert into time_slot (time_slot_id, day, start_time, end_time) values ('TS5', 'TuTh', '12:30:00', '13:45:00');

-- course
insert into course (course_id, course_name, credits) values ('COMP1010', 'Computing I', 3);
insert into course (course_id, course_name, credits) values ('COMP1020', 'Computing II', 3);
insert into course (course_id, course_name, credits) values ('COMP2010', 'Computing III', 3);
insert into course (course_id, course_name, credits) values ('COMP2040', 'Computing IV', 3);

-- section
-- First, create all the sections needed
insert into section (course_id, section_id, semester, year) values ('COMP1010', 'Section101', 'Fall', 2023);
insert into section (course_id, section_id, semester, year) values ('COMP1010', 'Section102', 'Fall', 2023);
insert into section (course_id, section_id, semester, year) values ('COMP1010', 'Section103', 'Fall', 2023);
insert into section (course_id, section_id, semester, year) values ('COMP1010', 'Section104', 'Fall', 2023);
insert into section (course_id, section_id, semester, year) values ('COMP1020', 'Section101', 'Spring', 2023);
insert into section (course_id, section_id, semester, year) values ('COMP1020', 'Section102', 'Spring', 2023);
insert into section (course_id, section_id, semester, year) values ('COMP2010', 'Section101', 'Fall', 2023);
insert into section (course_id, section_id, semester, year) values ('COMP2010', 'Section102', 'Fall', 2023);
insert into section (course_id, section_id, semester, year) values ('COMP2040', 'Section201', 'Spring', 2023);

-- Add Spring 2025 sections
insert into section (course_id, section_id, semester, year) values ('COMP1010', 'Section001', 'Spring', 2025);
insert into section (course_id, section_id, semester, year) values ('COMP1010', 'Section002', 'Spring', 2025);
insert into section (course_id, section_id, semester, year) values ('COMP1020', 'Section001', 'Spring', 2025);
insert into section (course_id, section_id, semester, year) values ('COMP2010', 'Section001', 'Spring', 2025);
insert into section (course_id, section_id, semester, year) values ('COMP2040', 'Section001', 'Spring', 2025);

-- Add Spring 2024 sections that are needed for the take table
insert into section (course_id, section_id, semester, year) values ('COMP1020', 'Section101', 'Spring', 2024);
insert into section (course_id, section_id, semester, year) values ('COMP1020', 'Section102', 'Spring', 2024);
insert into section (course_id, section_id, semester, year) values ('COMP2040', 'Section201', 'Spring', 2024);

-- Now update sections with instructors, classrooms, and time slots
UPDATE section SET instructor_id = '1', classroom_id = 'CL001', time_slot_id = 'TS1' WHERE course_id = 'COMP1010' AND section_id = 'Section101' AND semester = 'Fall' AND year = 2023;
UPDATE section SET instructor_id = '1', classroom_id = 'CL002', time_slot_id = 'TS2' WHERE course_id = 'COMP1010' AND section_id = 'Section102' AND semester = 'Fall' AND year = 2023;
UPDATE section SET instructor_id = '2', classroom_id = 'CL003', time_slot_id = 'TS3' WHERE course_id = 'COMP1010' AND section_id = 'Section103' AND semester = 'Fall' AND year = 2023;
UPDATE section SET instructor_id = '2', classroom_id = 'CL004', time_slot_id = 'TS4' WHERE course_id = 'COMP1010' AND section_id = 'Section104' AND semester = 'Fall' AND year = 2023;
UPDATE section SET instructor_id = '3', classroom_id = 'CL005', time_slot_id = 'TS5' WHERE course_id = 'COMP1020' AND section_id = 'Section101' AND semester = 'Spring' AND year = 2023;
UPDATE section SET instructor_id = '3', classroom_id = 'CL001', time_slot_id = 'TS1' WHERE course_id = 'COMP1020' AND section_id = 'Section102' AND semester = 'Spring' AND year = 2023;
UPDATE section SET instructor_id = '4', classroom_id = 'CL002', time_slot_id = 'TS2' WHERE course_id = 'COMP2010' AND section_id = 'Section101' AND semester = 'Fall' AND year = 2023;
UPDATE section SET instructor_id = '4', classroom_id = 'CL003', time_slot_id = 'TS3' WHERE course_id = 'COMP2010' AND section_id = 'Section102' AND semester = 'Fall' AND year = 2023;
UPDATE section SET instructor_id = '5', classroom_id = 'CL004', time_slot_id = 'TS4' WHERE course_id = 'COMP2040' AND section_id = 'Section201' AND semester = 'Spring' AND year = 2023;

-- Update Spring 2025 sections
UPDATE section SET instructor_id = '1', classroom_id = 'CL001', time_slot_id = 'TS1' WHERE course_id = 'COMP1010' AND section_id = 'Section001' AND semester = 'Spring' AND year = 2025;
UPDATE section SET instructor_id = '2', classroom_id = 'CL002', time_slot_id = 'TS2' WHERE course_id = 'COMP1010' AND section_id = 'Section002' AND semester = 'Spring' AND year = 2025;
UPDATE section SET instructor_id = '3', classroom_id = 'CL003', time_slot_id = 'TS3' WHERE course_id = 'COMP1020' AND section_id = 'Section001' AND semester = 'Spring' AND year = 2025;
UPDATE section SET instructor_id = '4', classroom_id = 'CL004', time_slot_id = 'TS4' WHERE course_id = 'COMP2010' AND section_id = 'Section001' AND semester = 'Spring' AND year = 2025;
UPDATE section SET instructor_id = '5', classroom_id = 'CL005', time_slot_id = 'TS5' WHERE course_id = 'COMP2040' AND section_id = 'Section001' AND semester = 'Spring' AND year = 2025;

-- prereq
INSERT INTO prereq (course_id, prereq_id) VALUES
('COMP1020', 'COMP1010'),
('COMP2010', 'COMP1020'),
('COMP2040', 'COMP2010');

-- advise - for phd students
INSERT INTO advise (instructor_id, student_id, start_date, end_date) VALUES
('1', 'PH001', '2022-01-15', NULL),
('2', 'PH002', '2021-09-01', NULL),
('3', 'PH003', '2023-01-10', NULL),
('4', 'PH004', '2023-05-20', NULL),
('5', 'PH005', '2023-09-05', NULL);

-- take - past courses
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES
-- John Smith's completed courses
('UG001', 'COMP1010', 'Section101', 'Fall', 2023, 'A'),
('UG001', 'COMP1020', 'Section101', 'Spring', 2023, 'A-'),
('UG001', 'COMP2010', 'Section101', 'Fall', 2023, 'B+'),

-- Emma Johnson's completed courses
('UG002', 'COMP1010', 'Section102', 'Fall', 2023, 'B+'),
('UG002', 'COMP1020', 'Section102', 'Spring', 2023, 'B'),

-- Michael Brown's completed courses
('UG003', 'COMP1010', 'Section103', 'Fall', 2023, 'B'),

-- Sophia Davis's completed courses
('UG004', 'COMP1010', 'Section101', 'Fall', 2023, 'A'),
('UG004', 'COMP1020', 'Section101', 'Spring', 2023, 'A'),
('UG004', 'COMP2010', 'Section101', 'Fall', 2023, 'A-'),
('UG004', 'COMP2040', 'Section201', 'Spring', 2023, 'B+'),

-- James Wilson's completed courses
('UG005', 'COMP1010', 'Section102', 'Fall', 2023, 'C+'),
('UG005', 'COMP1020', 'Section102', 'Spring', 2023, 'B-'),

-- Master's students
('MS001', 'COMP2010', 'Section101', 'Fall', 2023, 'A'),
('MS002', 'COMP2040', 'Section201', 'Spring', 2023, 'A-'),
('MS003', 'COMP2010', 'Section102', 'Fall', 2023, 'A-'),
('MS004', 'COMP2040', 'Section201', 'Spring', 2023, 'B+'),

-- PhD students
('PH001', 'COMP2010', 'Section101', 'Fall', 2023, 'A'),
('PH001', 'COMP2040', 'Section201', 'Spring', 2023, 'A'),
('PH002', 'COMP2010', 'Section102', 'Fall', 2023, 'A-'),
('PH002', 'COMP2040', 'Section201', 'Spring', 2023, 'A');

-- take - current courses (Spring 2025)
INSERT INTO take (student_id, course_id, section_id, semester, year) VALUES
-- Undergraduate students
('UG001', 'COMP2040', 'Section001', 'Spring', 2025),
('UG002', 'COMP2010', 'Section001', 'Spring', 2025),
('UG003', 'COMP1020', 'Section001', 'Spring', 2025),
('UG004', 'COMP2040', 'Section001', 'Spring', 2025),
('UG005', 'COMP2010', 'Section001', 'Spring', 2025),

-- Master's students
('MS001', 'COMP2040', 'Section001', 'Spring', 2025),
('MS002', 'COMP2040', 'Section001', 'Spring', 2025),
('MS003', 'COMP2040', 'Section001', 'Spring', 2025),
('MS004', 'COMP2040', 'Section001', 'Spring', 2025),
('MS005', 'COMP2040', 'Section001', 'Spring', 2025),

-- PhD students
('PH003', 'COMP2040', 'Section001', 'Spring', 2025),
('PH004', 'COMP2040', 'Section001', 'Spring', 2025),
('PH005', 'COMP2040', 'Section001', 'Spring', 2025);

-- ta - for sections with more than 10 students
INSERT INTO TA (student_id, course_id, section_id, semester, year) VALUES
('PH001', 'COMP2040', 'Section001', 'Spring', 2025);

-- mastergrader
INSERT INTO masterGrader (student_id, course_id, section_id, semester, year) VALUES
('MS001', 'COMP1010', 'Section001', 'Spring', 2025),
('MS002', 'COMP1010', 'Section002', 'Spring', 2025),
('MS003', 'COMP1020', 'Section001', 'Spring', 2025);

-- undegradgrader
INSERT INTO undergraduateGrader (student_id, course_id, section_id, semester, year) VALUES
('UG004', 'COMP1010', 'Section001', 'Spring', 2025);