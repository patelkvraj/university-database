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
	 section_id		varchar(20), 
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
	 course_id		varchar(8),
	 section_id		varchar(20), 
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
	 course_id		varchar(8),
	 section_id		varchar(20), 
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
	 course_id		varchar(8),
	 section_id		varchar(20), 
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
	 course_id		varchar(8),
	 section_id		varchar(20), 
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

-- table for course deadlines/events
CREATE TABLE course_event (
    event_id INT AUTO_INCREMENT,
    course_id VARCHAR(20),
    section_id VARCHAR(20),
    semester VARCHAR(6),
    year NUMERIC(4,0),
    event_title VARCHAR(100) NOT NULL,
    event_description TEXT,
    event_date DATE NOT NULL,
    event_type VARCHAR(20), -- 'exam', 'assignment', 'project', etc.
    PRIMARY KEY (event_id),
    FOREIGN KEY (course_id, section_id, semester, year) 
        REFERENCES section(course_id, section_id, semester, year)
        ON DELETE CASCADE
);

-- table for student todos (both course-related and personal)
CREATE TABLE student_todo (
    todo_id INT AUTO_INCREMENT,
    student_id VARCHAR(10),
    event_id INT,
    todo_title VARCHAR(100),
    todo_description TEXT,
    due_date DATE,
    is_completed BOOLEAN DEFAULT 0,
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (todo_id),
    FOREIGN KEY (student_id) REFERENCES student(student_id)
        ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES course_event(event_id)
        ON DELETE SET NULL
);

CREATE TABLE rate (
	rate_id INT AUTO_INCREMENT PRIMARY KEY,
	student_id VARCHAR(10),
	course_id VARCHAR(20),
	rate REAL
);

-- account
insert into account (email, password, type) values 
-- admin
('admin@uml.edu', '123456', 'admin'),

-- instructors
('dbadams@cs.uml.edu', '123456', 'instructor'),
('slin@cs.uml.edu', '123456', 'instructor'),
('Yelena_Rykalova@uml.edu', '123456', 'instructor'),
('Johannes_Weis@uml.edu', '123456', 'instructor'),
('Charles_Wilkes@uml.edu', '123456', 'instructor'),

('sarah_johnson@uml.edu', '123456', 'instructor'),
('michael_chen@uml.edu', '123456', 'instructor'),
('linda_patel@uml.edu', '123456', 'instructor'),
('robert_thompson@uml.edu', '123456', 'instructor'),
('emily_nguyen@uml.edu', '123456', 'instructor'),

('james_oconnor@uml.edu', '123456', 'instructor'),
('priya_desai@uml.edu', '123456', 'instructor'),
('kevin_zhang@uml.edu', '123456', 'instructor'),
('maria_torres@uml.edu', '123456', 'instructor'),
('daniel_kim@uml.edu', '123456', 'instructor'),

('amanda_lee@uml.edu', '123456', 'instructor'),
('jonathan_rivera@uml.edu', '123456', 'instructor'),
('catherine_brooks@uml.edu', '123456', 'instructor'),
('elijah_bennett@uml.edu', '123456', 'instructor'),
('naomi_sinclair@uml.edu', '123456', 'instructor'),

('allison_grant@uml.edu', '123456', 'instructor'),
('marcus_lee@uml.edu', '123456', 'instructor'),
('sofia_martinez@uml.edu', '123456', 'instructor'),
('william_adams@uml.edu', '123456', 'instructor'),
('grace_turner@uml.edu', '123456', 'instructor'),

-- students
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
('henry.walker@student.uml.edu', 'password123', 'student'),
('mia.hall@student.uml.edu', 'password123', 'student'),
('alexander.allen@student.uml.edu', 'password123', 'student'),
('harper.young@student.uml.edu', 'password123', 'student'),
('daniel.king@student.uml.edu', 'password123', 'student'),
('abigail.wright@student.uml.edu', 'password123', 'student'),
('sebastian.scott@student.uml.edu', 'password123', 'student'),
('evelyn.green@student.uml.edu', 'password123', 'student'),
('jack.baker@student.uml.edu', 'password123', 'student'),
('ella.adams@student.uml.edu', 'password123', 'student'),
('logan.nelson@student.uml.edu', 'password123', 'student'),
('grace.hill@student.uml.edu', 'password123', 'student'),
('lucas.ramirez@student.uml.edu', 'password123', 'student'),
('chloe.campbell@student.uml.edu', 'password123', 'student'),
('mason.mitchell@student.uml.edu', 'password123', 'student'),
('lily.roberts@student.uml.edu', 'password123', 'student'),
('ethan.carter@student.uml.edu', 'password123', 'student'),
('aria.phillips@student.uml.edu', 'password123', 'student'),
('logan.evans@student.uml.edu', 'password123', 'student'),
('scarlett.turner@student.uml.edu', 'password123', 'student'),
('elijah.torres@student.uml.edu', 'password123', 'student'),
('zoe.parker@student.uml.edu', 'password123', 'student'),
('aiden.collins@student.uml.edu', 'password123', 'student'),
('nora.edwards@student.uml.edu', 'password123', 'student'),
('jayden.stewart@student.uml.edu', 'password123', 'student'),
('hazel.sanchez@student.uml.edu', 'password123', 'student'),
('matthew.morris@student.uml.edu', 'password123', 'student'),
('victoria.rogers@student.uml.edu', 'password123', 'student'),
('lucas.reed@student.uml.edu', 'password123', 'student'),
('penelope.cook@student.uml.edu', 'password123', 'student'),
('david.morgan@student.uml.edu', 'password123', 'student'),
('riley.bell@student.uml.edu', 'password123', 'student'),
('joseph.bailey@student.uml.edu', 'password123', 'student'),
('layla.cooper@student.uml.edu', 'password123', 'student'),
('samuel.richardson@student.uml.edu', 'password123', 'student');

-- department
insert into department (dept_name, location) values ('Miner School of Computer & Information Sciences', 'Dandeneau Hall, 1 University Avenue, Lowell, MA 01854');
insert into department (dept_name, location) values ('Manning School of Business','Pulichino Tong Business Center, 72 University Ave, Lowell, MA 01854');
insert into department (dept_name, location) values ('Francis College of Engineering', 'Perry Hall, 1 University Ave, Lowell, MA 01854');
insert into department (dept_name, location) values ('College of Fine Arts, Humanities and Social Sciences', 'Dugan Halll 106 883 Broadway Street Lowell, MA 01854');
insert into department (dept_name, location) values ('Zuckerberg College of Health Sciences', 'Manning Building, 113 Wilder St Suite 400 Lowell, MA 01854');

-- instructor
-- CS school
insert into instructor (instructor_id, instructor_name, title, dept_name, email) values 
('1', 'David Adams', 'Teaching Professor', 'Miner School of Computer & Information Sciences','dbadams@cs.uml.edu'),
('2', 'Sirong Lin', 'Associate Teaching Professor', 'Miner School of Computer & Information Sciences','slin@cs.uml.edu'),
('3', 'Yelena Rykalova', 'Associate Teaching Professor', 'Miner School of Computer & Information Sciences', 'Yelena_Rykalova@uml.edu'),
('4', 'Johannes Weis', 'Assistant Teaching Professor', 'Miner School of Computer & Information Sciences','Johannes_Weis@uml.edu'),
('5', 'Tom Wilkes', 'Assistant Teaching Professor', 'Miner School of Computer & Information Sciences','Charles_Wilkes@uml.edu');
-- business school
insert into instructor (instructor_id, instructor_name, title, dept_name, email) values
('6', 'Sarah Johnson', 'Teaching Professor', 'Manning School of Business', 'sarah_johnson@uml.edu'),
('7', 'Michael Chen', 'Associate Teaching Professor', 'Manning School of Business', 'michael_chen@uml.edu'),
('8', 'Linda Patel', 'Associate Teaching Professor', 'Manning School of Business', 'linda_patel@uml.edu'),
('9', 'Robert Thompson', 'Assistant Teaching Professor', 'Manning School of Business', 'robert_thompson@uml.edu'),
('10', 'Emily Nguyen', 'Assistant Teaching Professor', 'Manning School of Business', 'emily_nguyen@uml.edu');
-- engineering school
insert into instructor (instructor_id, instructor_name, title, dept_name, email) values
('11', 'James O’Connor', 'Teaching Professor', 'Francis College of Engineering', 'james_oconnor@uml.edu'),
('12', 'Priya Desai', 'Associate Teaching Professor', 'Francis College of Engineering', 'priya_desai@uml.edu'),
('13', 'Kevin Zhang', 'Associate Teaching Professor', 'Francis College of Engineering', 'kevin_zhang@uml.edu'),
('14', 'Maria Torres', 'Assistant Teaching Professor', 'Francis College of Engineering', 'maria_torres@uml.edu'),
('15', 'Daniel Kim', 'Assistant Teaching Professor', 'Francis College of Engineering', 'daniel_kim@uml.edu');
-- fine arts school
insert into instructor (instructor_id, instructor_name, title, dept_name, email) values
('16', 'Amanda Lee', 'Teaching Professor', 'College of Fine Arts, Humanities and Social Sciences', 'amanda_lee@uml.edu'),
('17', 'Jonathan Rivera', 'Associate Teaching Professor', 'College of Fine Arts, Humanities and Social Sciences', 'jonathan_rivera@uml.edu'),
('18', 'Catherine Brooks', 'Associate Teaching Professor', 'College of Fine Arts, Humanities and Social Sciences', 'catherine_brooks@uml.edu'),
('19', 'Elijah Bennett', 'Assistant Teaching Professor', 'College of Fine Arts, Humanities and Social Sciences', 'elijah_bennett@uml.edu'),
('20', 'Naomi Sinclair', 'Assistant Teaching Professor', 'College of Fine Arts, Humanities and Social Sciences', 'naomi_sinclair@uml.edu');
-- health science school
INSERT INTO instructor (instructor_id, instructor_name, title, dept_name, email) VALUES
('21', 'Allison Grant', 'Teaching Professor', 'Zuckerberg College of Health Sciences', 'allison_grant@uml.edu'),
('22', 'Marcus Lee', 'Associate Teaching Professor', 'Zuckerberg College of Health Sciences', 'marcus_lee@uml.edu'),
('23', 'Sofia Martinez', 'Associate Teaching Professor', 'Zuckerberg College of Health Sciences', 'sofia_martinez@uml.edu'),
('24', 'William Adams', 'Assistant Teaching Professor', 'Zuckerberg College of Health Sciences', 'william_adams@uml.edu'),
('25', 'Grace Turner', 'Assistant Teaching Professor', 'Zuckerberg College of Health Sciences', 'grace_turner@uml.edu');


-- students
-- undergrad
INSERT INTO student (student_id, name, email, dept_name) VALUES
('UG001', 'John Smith', 'john.smith@student.uml.edu', 'Miner School of Computer & Information Sciences'),
('UG002', 'Emma Johnson', 'emma.johnson@student.uml.edu', 'Miner School of Computer & Information Sciences'),
('UG003', 'Michael Brown', 'michael.brown@student.uml.edu', 'Miner School of Computer & Information Sciences'),
('UG004', 'Sophia Davis', 'sophia.davis@student.uml.edu', 'Miner School of Computer & Information Sciences'),
('UG005', 'James Wilson', 'james.wilson@student.uml.edu', 'Miner School of Computer & Information Sciences'),
('UG006', 'Hazel Sanchez', 'hazel.sanchez@student.uml.edu', 'College of Fine Arts, Humanities and Social Sciences'),
('UG007', 'Matthew Morris', 'matthew.morris@student.uml.edu', 'Zuckerberg College of Health Sciences'),
('UG008', 'Victoria Rogers', 'victoria.rogers@student.uml.edu', 'Manning School of Business'),
('UG009', 'Lucas Reed', 'lucas.reed@student.uml.edu', 'Francis College of Engineering'),
('UG0010', 'Penelope Cook', 'penelope.cook@student.uml.edu', 'College of Fine Arts, Humanities and Social Sciences'),
('UG0011', 'David Morgan', 'david.morgan@student.uml.edu', 'Zuckerberg College of Health Sciences'),
('UG0012', 'Riley Bell', 'riley.bell@student.uml.edu', 'Manning School of Business'),
('UG0013', 'Joseph Bailey', 'joseph.bailey@student.uml.edu', 'Francis College of Engineering'),
('UG0014', 'Layla Cooper', 'layla.cooper@student.uml.edu', 'College of Fine Arts, Humanities and Social Sciences'),
('UG0015', 'Samuel Richardson', 'samuel.richardson@student.uml.edu', 'Zuckerberg College of Health Sciences'),
('UG0016', 'Charles Wilkes', 'charles_wilkes@student.uml.edu', 'Francis College of Engineering');
-- master
INSERT INTO student (student_id, name, email, dept_name) VALUES
('MS001', 'Olivia Martinez', 'olivia.martinez@student.uml.edu', 'Miner School of Computer & Information Sciences'),
('MS002', 'William Taylor', 'william.taylor@student.uml.edu', 'Miner School of Computer & Information Sciences'),
('MS003', 'Ava Anderson', 'ava.anderson@student.uml.edu', 'Miner School of Computer & Information Sciences'),
('MS004', 'Noah Thomas', 'noah.thomas@student.uml.edu', 'Miner School of Computer & Information Sciences'),
('MS005', 'Isabella Jackson', 'isabella.jackson@student.uml.edu', 'Miner School of Computer & Information Sciences'),
('MS006', 'Chloe Campbell', 'chloe.campbell@student.uml.edu', 'College of Fine Arts, Humanities and Social Sciences'),
('MS007', 'Mason Mitchell', 'mason.mitchell@student.uml.edu', 'Zuckerberg College of Health Sciences'),
('MS008', 'Lily Roberts', 'lily.roberts@student.uml.edu', 'Manning School of Business'),
('MS009', 'Ethan Carter', 'ethan.carter@student.uml.edu', 'Francis College of Engineering'),
('MS0010', 'Aria Phillips', 'aria.phillips@student.uml.edu', 'College of Fine Arts, Humanities and Social Sciences'),
('MS0011', 'Logan Evans', 'logan.evans@student.uml.edu', 'Zuckerberg College of Health Sciences'),
('MS0012', 'Scarlett Turner', 'scarlett.turner@student.uml.edu', 'Manning School of Business'),
('MS0013', 'Elijah Torres', 'elijah.torres@student.uml.edu', 'Francis College of Engineering'),
('MS0014', 'Zoe Parker', 'zoe.parker@student.uml.edu', 'College of Fine Arts, Humanities and Social Sciences'),
('MS0015', 'Aiden Collins', 'aiden.collins@student.uml.edu', 'Manning School of Business'),
('MS0016', 'Nora Edwards', 'nora.edwards@student.uml.edu', 'Zuckerberg College of Health Sciences'),
('MS0017', 'Jayden Stewart', 'jayden.stewart@student.uml.edu', 'Francis College of Engineering');
-- PHD
INSERT INTO student (student_id, name, email, dept_name) VALUES
('PH001', 'Liam White', 'liam.white@student.uml.edu', 'Miner School of Computer & Information Sciences'),
('PH002', 'Charlotte Harris', 'charlotte.harris@student.uml.edu', 'Miner School of Computer & Information Sciences'),
('PH003', 'Benjamin Clark', 'benjamin.clark@student.uml.edu', 'Miner School of Computer & Information Sciences'),
('PH004', 'Amelia Lewis', 'amelia.lewis@student.uml.edu', 'Miner School of Computer & Information Sciences'),
('PH005', 'Henry Walker', 'henry.walker@student.uml.edu', 'Miner School of Computer & Information Sciences'),
('PH006', 'Mia Hall', 'mia.hall@student.uml.edu', 'Zuckerberg College of Health Sciences'),
('PH007', 'Alexander Allen', 'alexander.allen@student.uml.edu', 'Francis College of Engineering'),
('PH008', 'Harper Young', 'harper.young@student.uml.edu', 'Manning School of Business'),
('PH009', 'Daniel King', 'daniel.king@student.uml.edu', 'College of Fine Arts, Humanities and Social Sciences'),
('PH0010', 'Abigail Wright', 'abigail.wright@student.uml.edu', 'Francis College of Engineering'),
('PH0011', 'Sebastian Scott', 'sebastian.scott@student.uml.edu', 'Zuckerberg College of Health Sciences'),
('PH0012', 'Evelyn Green', 'evelyn.green@student.uml.edu', 'Manning School of Business'),
('PH0013', 'Jack Baker', 'jack.baker@student.uml.edu', 'Francis College of Engineering'),
('PH0014', 'Ella Adams', 'ella.adams@student.uml.edu', 'College of Fine Arts, Humanities and Social Sciences'),
('PH0015', 'Logan Nelson', 'logan.nelson@student.uml.edu', 'Zuckerberg College of Health Sciences'),
('PH0016', 'Grace Hill', 'grace.hill@student.uml.edu', 'Manning School of Business'),
('PH0017', 'Lucas Ramirez', 'lucas.ramirez@student.uml.edu', 'Francis College of Engineering');

-- course stuff
-- phd
INSERT INTO PhD (student_id, qualifier, proposal_defence_date, dissertation_defence_date) VALUES
('PH001', 'Passed', '2023-05-15', NULL),
('PH002', 'Passed', '2022-11-10', NULL),
('PH003', 'Scheduled', NULL, NULL),
('PH004', 'Passed', '2023-09-20', NULL),
('PH005', 'Not Started', NULL, NULL),
('PH006', 'Passed', '2023-06-12', NULL),
('PH007', 'Scheduled', NULL, NULL),
('PH008', 'Not Started', NULL, NULL),
('PH009', 'Passed', '2022-10-05', NULL),
('PH0010', 'Passed', '2023-04-18', '2024-12-10'),
('PH0011', 'Scheduled', '2024-09-20', NULL),
('PH0012', 'Not Started', NULL, NULL),
('PH0013', 'Passed', '2023-08-30', NULL),
('PH0014', 'Passed', '2022-11-22', '2024-06-01'),
('PH0015', 'Scheduled', NULL, NULL),
('PH0016', 'Passed', '2023-03-14', NULL),
('PH0017', 'Passed', '2022-09-10', '2024-04-25');

-- master
INSERT INTO master (student_id, total_credits) VALUES
('MS001', 18),
('MS002', 24),
('MS003', 12),
('MS004', 30),
('MS005', 6),
('MS006', 18),
('MS007', 24),
('MS008', 12),
('MS009', 30),
('MS0010', 6),
('MS0011', 21),
('MS0012', 27),
('MS0013', 15),
('MS0014', 33),
('MS0015', 9),
('MS0016', 36),
('MS0017', 12);

-- undergrad
INSERT INTO undergraduate (student_id, total_credits, class_standing) VALUES
('UG001', 75, 'Junior'),
('UG002', 30, 'Sophomore'),
('UG003', 15, 'Freshman'),
('UG004', 90, 'Senior'),
('UG005', 45, 'Sophomore'),
('UG006', 15, 'Freshman'),
('UG007', 36, 'Sophomore'),
('UG008', 78, 'Junior'),
('UG009', 91, 'Senior'),
('UG0010', 60, 'Junior'),
('UG0011', 27, 'Freshman'),
('UG0012', 45, 'Sophomore'),
('UG0013', 88, 'Junior'),
('UG0014', 33, 'Sophomore'),
('UG0015', 96, 'Senior'),
('UG0016', 51, 'Sophomore');

-- classroom
INSERT INTO classroom (classroom_id, building, room_number, capacity) VALUES
('CL001', 'Dandeneau Hall', '301', 20),
('CL002', 'Dandeneau Hall', '302', 25),
('CL003', 'Dandeneau Hall', '401', 30),
('CL004', 'Olsen Hall', '201', 40),
('CL005', 'Olsen Hall', '202', 35),
('CL006', 'Olney Hall', '105', 30),
('CL007', 'Olney Hall', '210', 28),
('CL008', 'Ball Hall', '310', 22),
('CL009', 'Ball Hall', '415', 26),
('CL010', 'Weed Hall', '102', 18),
('CL011', 'Weed Hall', '203', 24),
('CL012', 'Shah Hall', '305', 32),
('CL013', 'Shah Hall', '406', 30),
('CL014', 'Olsen Hall', '303', 20),
('CL015', 'Olney Hall', '112', 25);

-- time slot
INSERT INTO time_slot (time_slot_id, day, start_time, end_time) VALUES
('TS1', 'MoWeFr', '11:00:00', '11:50:00'),
('TS2', 'MoWeFr', '12:00:00', '12:50:00'),
('TS3', 'MoWeFr', '13:00:00', '13:50:00'),
('TS4', 'TuTh', '11:00:00', '12:15:00'),
('TS5', 'TuTh', '12:30:00', '13:45:00'),
('TS6', 'MoWeFr', '09:00:00', '09:50:00'),
('TS7', 'MoWeFr', '10:00:00', '10:50:00'),
('TS8', 'MoWeFr', '14:00:00', '14:50:00'),
('TS9', 'MoWeFr', '15:00:00', '15:50:00'),
('TS10', 'TuTh', '08:00:00', '09:15:00'),
('TS11', 'TuTh', '09:30:00', '10:45:00'),
('TS12', 'TuTh', '14:00:00', '15:15:00'),
('TS13', 'TuTh', '15:30:00', '16:45:00'),
('TS14', 'MoWeFr', '08:00:00', '08:50:00'),
('TS15', 'MoWeFr', '16:00:00', '16:50:00');

-- course
-- CS
insert into course (course_id, course_name, credits) values 
('COMP1010', 'Computing I', 3),
('COMP1020', 'Computing II', 3),
('COMP2010', 'Computing III', 3),
('COMP2040', 'Computing IV', 3),
('COMP3000', 'Data Structures', 3),
('COMP3010', 'Operating Systems', 3),
('COMP3020', 'Algorithms', 3),
('COMP3050', 'Software Engineering', 3),
('COMP3080', 'Database Systems', 3),
('COMP4040', 'Artificial Intelligence', 3),
-- Manning School of Business
('BUSN1001', 'Introduction to Business', 3),
('BUSN2002', 'Financial Accounting', 3),
('BUSN3003', 'Principles of Marketing', 3),
('BUSN3100', 'Business Ethics', 3),
('BUSN3200', 'Organizational Behavior', 3),
('BUSN3300', 'Business Analytics', 3),
('BUSN3400', 'Operations Management', 3),
('BUSN3500', 'Corporate Finance', 3),
('BUSN3600', 'Strategic Management', 3),
('BUSN3700', 'International Business', 3),
-- Francis College of Engineering
('ENGR1001', 'Introduction to Engineering', 3),
('ENGR2002', 'Statics', 3),
('ENGR2003', 'Dynamics', 3),
('ENGR3004', 'Fluid Mechanics', 3),
('ENGR3005', 'Thermodynamics', 3),
('ENGR3100', 'Electrical Circuits', 3),
('ENGR3200', 'Engineering Materials', 3),
('ENGR3300', 'Control Systems', 3),
('ENGR3400', 'Engineering Design', 3),
('ENGR3500', 'Capstone Engineering Project', 3),
-- College of Fine Arts, Humanities and Social Sciences
('FAHS1001', 'Intro to Psychology', 3),
('FAHS1002', 'Intro to Sociology', 3),
('FAHS2001', 'World History I', 3),
('FAHS2002', 'American Literature', 3),
('FAHS2100', 'Creative Writing', 3),
('FAHS2200', 'Philosophy and Ethics', 3),
('FAHS2300', 'Modern Art History', 3),
('FAHS2400', 'Comparative Politics', 3),
('FAHS2500', 'Public Speaking', 3),
('FAHS2600', 'Media & Society', 3),
-- Zuckerberg College of Health Sciences
('HLTH1001', 'Foundations of Health Science', 3),
('HLTH1100', 'Medical Terminology', 2),
('HLTH2001', 'Nutrition and Wellness', 3),
('HLTH2100', 'Human Anatomy & Physiology I', 3),
('HLTH2200', 'Health Policy', 3),
('HLTH2300', 'Pathophysiology', 3),
('HLTH2400', 'Clinical Microbiology', 3),
('HLTH2500', 'Community Health', 3),
('HLTH2600', 'Health Assessment', 3),
('HLTH2700', 'Epidemiology', 3);

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

INSERT INTO advise (instructor_id, student_id, start_date, end_date) VALUES ('1', 'PH001', '2022-01-15', NULL);
INSERT INTO advise (instructor_id, student_id, start_date, end_date) VALUES ('2', 'PH002', '2022-01-15', NULL);
INSERT INTO advise (instructor_id, student_id, start_date, end_date) VALUES ('3', 'PH003', '2022-01-15', NULL);
INSERT INTO advise (instructor_id, student_id, start_date, end_date) VALUES ('4', 'PH004', '2022-01-15', NULL);
INSERT INTO advise (instructor_id, student_id, start_date, end_date) VALUES ('5', 'PH005', '2022-01-15', NULL);
INSERT INTO advise (instructor_id, student_id, start_date, end_date) VALUES ('6', 'PH006', '2022-01-15', NULL);
INSERT INTO advise (instructor_id, student_id, start_date, end_date) VALUES ('7', 'PH007', '2022-01-15', NULL);
INSERT INTO advise (instructor_id, student_id, start_date, end_date) VALUES ('8', 'PH008', '2022-01-15', NULL);
INSERT INTO advise (instructor_id, student_id, start_date, end_date) VALUES ('9', 'PH009', '2022-01-15', NULL);
INSERT INTO advise (instructor_id, student_id, start_date, end_date) VALUES ('10', 'PH0010', '2022-01-15', NULL);
INSERT INTO advise (instructor_id, student_id, start_date, end_date) VALUES ('11', 'PH0011', '2022-01-15', NULL);
INSERT INTO advise (instructor_id, student_id, start_date, end_date) VALUES ('12', 'PH0012', '2022-01-15', NULL);

INSERT INTO section (course_id, section_id, semester, year, instructor_id, classroom_id, time_slot_id)
VALUES 
('COMP1010', 'Section001', 'Spring', 2025, '1', 'CL001', 'TS1'),
('COMP1010', 'Section002', 'Spring', 2025, '2', 'CL002', 'TS2'),
('COMP1020', 'Section001', 'Spring', 2025, '3', 'CL003', 'TS3'),
('COMP2010', 'Section001', 'Spring', 2025, '4', 'CL004', 'TS4'),
('COMP2040', 'Section001', 'Spring', 2025, '5', 'CL005', 'TS5');

INSERT INTO section (course_id, section_id, semester, year, instructor_id, classroom_id, time_slot_id) 
VALUES 
-- Computing I
('COMP1010', 'Section1010S01', 'Fall', 2021, '1', 'CL001', 'TS1'),
('COMP1010', 'Section1010S02', 'Fall', 2022, '1', 'CL001', 'TS1'),
('COMP1010', 'Section1010S03', 'Spring', 2022, '2', 'CL002', 'TS2'),
('COMP1010', 'Section1010S04', 'Fall', 2023, '2', 'CL002', 'TS2'),
('COMP1010', 'Section1010S05', 'Spring', 2023, '3', 'CL003', 'TS3'),
('COMP1010', 'Section1010S06', 'Fall', 2024, '3', 'CL003', 'TS3'),
('COMP1010', 'Section1010S07', 'Spring', 2024, '4', 'CL004', 'TS4'),
('COMP1010', 'Section1010S10', 'Spring', 2025, '1', 'CL001', 'TS1'),

-- Computing II
('COMP1020', 'Section1020S01', 'Fall', 2021, '2', 'CL002', 'TS2'),
('COMP1020', 'Section1020S02', 'Spring', 2022, '2', 'CL002', 'TS2'),
('COMP1020', 'Section1020S03', 'Fall', 2022, '3', 'CL003', 'TS3'),
('COMP1020', 'Section1020S04', 'Spring', 2023, '3', 'CL003', 'TS3'),
('COMP1020', 'Section1020S05', 'Fall', 2023, '4', 'CL004', 'TS4'),
('COMP1020', 'Section1020S06', 'Spring', 2024, '4', 'CL004', 'TS4'),
('COMP1020', 'Section1020S07', 'Fall', 2024, '5', 'CL005', 'TS5'),
('COMP1020', 'Section1020S10', 'Spring', 2025, '2', 'CL002', 'TS2'),

-- Computing III
('COMP2010', 'Section2010S01', 'Fall', 2021, '3', 'CL003', 'TS3'),
('COMP2010', 'Section2010S02', 'Spring', 2022, '3', 'CL003', 'TS3'),
('COMP2010', 'Section2010S03', 'Fall', 2023, '4', 'CL004', 'TS4'),
('COMP2010', 'Section2010S04', 'Spring', 2023, '4', 'CL004', 'TS4'),
('COMP2010', 'Section2010S05', 'Fall', 2024, '5', 'CL005', 'TS5'),
('COMP2010', 'Section2010S06', 'Spring', 2024, '5', 'CL005', 'TS5'),
('COMP2010', 'Section2010S10', 'Spring', 2025, '3', 'CL003', 'TS3'),

-- Computing IV
('COMP2040', 'Section2040S01', 'Fall', 2021, '4', 'CL004', 'TS4'),
('COMP2040', 'Section2040S02', 'Spring', 2022, '4', 'CL004', 'TS4'),
('COMP2040', 'Section2040S03', 'Fall', 2022, '5', 'CL005', 'TS5'),
('COMP2040', 'Section2040S04', 'Spring', 2023, '5', 'CL005', 'TS5'),
('COMP2040', 'Section2040S05', 'Fall', 2023, '1', 'CL001', 'TS1'),
('COMP2040', 'Section2040S06', 'Spring', 2024, '1', 'CL001', 'TS1'),
('COMP2040', 'Section2040S10', 'Spring', 2025, '4', 'CL004', 'TS4');

INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('UG001', 'COMP1010', 'Section1010S01', 'Fall', 2021, 'B-');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('UG001', 'COMP1020', 'Section1020S02', 'Spring', 2022, 'C');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('UG001', 'COMP2010', 'Section2010S03', 'Fall', 2023, 'B+');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('UG001', 'COMP2040', 'Section2040S01', 'Fall', 2021, 'C+');

INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('UG002', 'COMP1010', 'Section1010S01', 'Fall', 2021, 'B-');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('UG002', 'COMP1020', 'Section1020S02', 'Spring', 2022, 'B-');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('UG002', 'COMP2010', 'Section2010S03', 'Fall', 2023, 'C+');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('UG002', 'COMP2040', 'Section2040S01', 'Fall', 2021, 'B');

INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('UG003', 'COMP1010', 'Section1010S01', 'Fall', 2021, 'A');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('UG003', 'COMP1020', 'Section1020S02', 'Spring', 2022, 'C+');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('UG003', 'COMP2010', 'Section2010S03', 'Fall', 2023, 'C');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('UG003', 'COMP2040', 'Section2040S01', 'Fall', 2021, 'B');

INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('UG004', 'COMP1010', 'Section1010S01', 'Fall', 2021, 'B-');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('UG004', 'COMP1020', 'Section1020S02', 'Spring', 2022, 'B+');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('UG004', 'COMP2010', 'Section2010S03', 'Fall', 2023, 'A-');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('UG004', 'COMP2040', 'Section2040S01', 'Fall', 2021, 'B-');

INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('UG005', 'COMP1010', 'Section1010S01', 'Fall', 2021, 'A');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('UG005', 'COMP1020', 'Section1020S02', 'Spring', 2022, 'A');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('UG005', 'COMP2010', 'Section2010S03', 'Fall', 2023, 'B+');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('UG005', 'COMP2040', 'Section2040S01', 'Fall', 2021, 'B+');

INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('UG006', 'COMP1010', 'Section1010S01', 'Fall', 2021, 'A');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('UG006', 'COMP1020', 'Section1020S02', 'Spring', 2022, 'B+');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('UG006', 'COMP2010', 'Section2010S03', 'Fall', 2023, 'B-');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('UG006', 'COMP2040', 'Section2040S01', 'Fall', 2021, 'B-');

INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('UG007', 'COMP1010', 'Section1010S01', 'Fall', 2021, 'B-');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('UG007', 'COMP1020', 'Section1020S02', 'Spring', 2022, 'A-');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('UG007', 'COMP2010', 'Section2010S03', 'Fall', 2023, 'A-');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('UG007', 'COMP2040', 'Section2040S01', 'Fall', 2021, 'A');

INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('UG008', 'COMP1010', 'Section1010S01', 'Fall', 2021, 'B+');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('UG008', 'COMP1020', 'Section1020S02', 'Spring', 2022, 'B+');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('UG008', 'COMP2010', 'Section2010S03', 'Fall', 2023, 'A');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('UG008', 'COMP2040', 'Section2040S01', 'Fall', 2021, 'B');

INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('UG009', 'COMP1010', 'Section1010S01', 'Fall', 2021, 'B+');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('UG009', 'COMP1020', 'Section1020S02', 'Spring', 2022, 'C');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('UG009', 'COMP2010', 'Section2010S03', 'Fall', 2023, 'B-');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('UG009', 'COMP2040', 'Section2040S01', 'Fall', 2021, 'A-');

INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('UG0010', 'COMP1010', 'Section1010S01', 'Fall', 2021, 'C');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('UG0010', 'COMP1020', 'Section1020S02', 'Spring', 2022, 'A-');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('UG0010', 'COMP2010', 'Section2010S03', 'Fall', 2023, 'B');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('UG0010', 'COMP2040', 'Section2040S01', 'Fall', 2021, 'C+');

INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('UG0011', 'COMP1010', 'Section1010S01', 'Fall', 2021, 'C+');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('UG0011', 'COMP1020', 'Section1020S02', 'Spring', 2022, 'C+');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('UG0011', 'COMP2010', 'Section2010S03', 'Fall', 2023, 'B+');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('UG0011', 'COMP2040', 'Section2040S01', 'Fall', 2021, 'C');

INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('MS001', 'COMP1010', 'Section1010S01', 'Fall', 2021, 'A');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('MS001', 'COMP1020', 'Section1020S02', 'Spring', 2022, 'A');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('MS001', 'COMP2010', 'Section2010S03', 'Fall', 2023, 'B+');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('MS001', 'COMP2040', 'Section2040S01', 'Fall', 2021, 'A-');

INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('MS002', 'COMP1010', 'Section1010S01', 'Fall', 2021, 'B+');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('MS002', 'COMP1020', 'Section1020S02', 'Spring', 2022, 'B-');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('MS002', 'COMP2010', 'Section2010S03', 'Fall', 2023, 'C');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('MS002', 'COMP2040', 'Section2040S01', 'Fall', 2021, 'A');

INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('MS003', 'COMP1010', 'Section1010S01', 'Fall', 2021, 'A');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('MS003', 'COMP1020', 'Section1020S02', 'Spring', 2022, 'C+');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('MS003', 'COMP2010', 'Section2010S03', 'Fall', 2023, 'B-');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('MS003', 'COMP2040', 'Section2040S01', 'Fall', 2021, 'B');

INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('MS004', 'COMP1010', 'Section1010S01', 'Fall', 2021, 'B-');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('MS004', 'COMP1020', 'Section1020S02', 'Spring', 2022, 'A-');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('MS004', 'COMP2010', 'Section2010S03', 'Fall', 2023, 'B-');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('MS004', 'COMP2040', 'Section2040S01', 'Fall', 2021, 'B-');

INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('MS005', 'COMP1010', 'Section1010S01', 'Fall', 2021, 'C');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('MS005', 'COMP1020', 'Section1020S02', 'Spring', 2022, 'B+');

INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('MS006', 'COMP1010', 'Section1010S01', 'Fall', 2021, 'B+');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('MS006', 'COMP1020', 'Section1020S02', 'Spring', 2022, 'B-');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('MS006', 'COMP2010', 'Section2010S03', 'Fall', 2023, 'C');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('MS006', 'COMP2040', 'Section2040S01', 'Fall', 2021, 'A-');

INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('MS007', 'COMP1010', 'Section1010S01', 'Fall', 2021, 'A');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('MS007', 'COMP1020', 'Section1020S02', 'Spring', 2022, 'C');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('MS007', 'COMP2010', 'Section2010S03', 'Fall', 2023, 'C');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('MS007', 'COMP2040', 'Section2040S01', 'Fall', 2021, 'C');

INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('MS008', 'COMP1010', 'Section1010S01', 'Fall', 2021, 'A-');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('MS008', 'COMP1020', 'Section1020S02', 'Spring', 2022, 'A');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('MS008', 'COMP2010', 'Section2010S03', 'Fall', 2023, 'B+');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('MS008', 'COMP2040', 'Section2040S01', 'Fall', 2021, 'B');

INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('MS009', 'COMP1010', 'Section1010S01', 'Fall', 2021, 'B-');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('MS009', 'COMP1020', 'Section1020S02', 'Spring', 2022, 'C');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('MS009', 'COMP2010', 'Section2010S03', 'Fall', 2023, 'C');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('MS009', 'COMP2040', 'Section2040S01', 'Fall', 2021, 'A-');

INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('MS0010', 'COMP1010', 'Section1010S01', 'Fall', 2021, 'B+');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('MS0010', 'COMP1020', 'Section1020S02', 'Spring', 2022, 'B');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('MS0010', 'COMP2010', 'Section2010S03', 'Fall', 2023, 'C+');

INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('MS0011', 'COMP1010', 'Section1010S01', 'Fall', 2021, 'B');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('MS0011', 'COMP1020', 'Section1020S02', 'Spring', 2022, 'C+');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('MS0011', 'COMP2010', 'Section2010S03', 'Fall', 2023, 'B+');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('MS0011', 'COMP2040', 'Section2040S01', 'Fall', 2021, 'C');

INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('MS0012', 'COMP1010', 'Section1010S01', 'Fall', 2021, 'B-');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('MS0012', 'COMP1020', 'Section1020S02', 'Spring', 2022, 'C+');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('MS0012', 'COMP2010', 'Section2010S03', 'Fall', 2023, 'B');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('MS0012', 'COMP2040', 'Section2040S01', 'Fall', 2021, 'C');

INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('PH001', 'COMP1010', 'Section1010S01', 'Fall', 2021, 'C');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('PH001', 'COMP1020', 'Section1020S02', 'Spring', 2022, 'A');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('PH001', 'COMP2010', 'Section2010S03', 'Fall', 2023, 'B-');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('PH001', 'COMP2040', 'Section2040S01', 'Fall', 2021, 'B');

INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('PH002', 'COMP1010', 'Section1010S01', 'Fall', 2021, 'C');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('PH002', 'COMP1020', 'Section1020S02', 'Spring', 2022, 'C+');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('PH002', 'COMP2010', 'Section2010S03', 'Fall', 2023, 'C+');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('PH002', 'COMP2040', 'Section2040S01', 'Fall', 2021, 'B');

INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('PH003', 'COMP1010', 'Section1010S01', 'Fall', 2021, 'B+');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('PH003', 'COMP1020', 'Section1020S02', 'Spring', 2022, 'B+');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('PH003', 'COMP2010', 'Section2010S03', 'Fall', 2023, 'C+');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('PH003', 'COMP2040', 'Section2040S01', 'Fall', 2021, 'A');

INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('PH004', 'COMP1010', 'Section1010S01', 'Fall', 2021, 'B+');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('PH004', 'COMP1020', 'Section1020S02', 'Spring', 2022, 'B');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('PH004', 'COMP2010', 'Section2010S03', 'Fall', 2023, 'A');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('PH004', 'COMP2040', 'Section2040S01', 'Fall', 2021, 'C+');

INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('PH005', 'COMP1010', 'Section1010S01', 'Fall', 2021, 'B');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('PH005', 'COMP1020', 'Section1020S02', 'Spring', 2022, 'C');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('PH005', 'COMP2010', 'Section2010S03', 'Fall', 2023, 'A-');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('PH005', 'COMP2040', 'Section2040S01', 'Fall', 2021, 'C');

INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('PH006', 'COMP1010', 'Section1010S01', 'Fall', 2021, 'C');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('PH006', 'COMP1020', 'Section1020S02', 'Spring', 2022, 'A');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('PH006', 'COMP2010', 'Section2010S03', 'Fall', 2023, 'B+');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('PH006', 'COMP2040', 'Section2040S01', 'Fall', 2021, 'C+');

INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('PH007', 'COMP1010', 'Section1010S01', 'Fall', 2021, 'A-');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('PH007', 'COMP1020', 'Section1020S02', 'Spring', 2022, 'A');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('PH007', 'COMP2010', 'Section2010S03', 'Fall', 2023, 'B+');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('PH007', 'COMP2040', 'Section2040S01', 'Fall', 2021, 'A');


INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('PH008', 'COMP1010', 'Section1010S01', 'Fall', 2021, 'B-');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('PH008', 'COMP1020', 'Section1020S02', 'Spring', 2022, 'A-');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('PH008', 'COMP2010', 'Section2010S03', 'Fall', 2023, 'B-');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('PH008', 'COMP2040', 'Section2040S01', 'Fall', 2021, 'B-');

INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('PH009', 'COMP1010', 'Section1010S01', 'Fall', 2021, 'C+');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('PH009', 'COMP1020', 'Section1020S02', 'Spring', 2022, 'B+');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('PH009', 'COMP2010', 'Section2010S03', 'Fall', 2023, 'C');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('PH009', 'COMP2040', 'Section2040S01', 'Fall', 2021, 'A');

INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('PH0010', 'COMP1010', 'Section1010S01', 'Fall', 2021, 'B-');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('PH0010', 'COMP1020', 'Section1020S02', 'Spring', 2022, 'B-');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('PH0010', 'COMP2010', 'Section2010S03', 'Fall', 2023, 'A-');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('PH0010', 'COMP2040', 'Section2040S01', 'Fall', 2021, 'C');

INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('PH0011', 'COMP1010', 'Section1010S01', 'Fall', 2021, 'B-');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('PH0011', 'COMP1020', 'Section1020S02', 'Spring', 2022, 'A-');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('PH0011', 'COMP2010', 'Section2010S03', 'Fall', 2023, 'A-');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('PH0011', 'COMP2040', 'Section2040S01', 'Fall', 2021, 'A');

INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('PH0012', 'COMP1010', 'Section1010S01', 'Fall', 2021, 'C');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('PH0012', 'COMP1020', 'Section1020S02', 'Spring', 2022, 'B');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('PH0012', 'COMP2010', 'Section2010S03', 'Fall', 2023, 'B-');
INSERT INTO take (student_id, course_id, section_id, semester, year, grade) VALUES ('PH0012', 'COMP2040', 'Section2040S01', 'Fall', 2021, 'A-');

INSERT INTO take (student_id, course_id, section_id, semester, year) VALUES ('UG001', 'COMP2010', 'Section2010S10', 'Spring', 2025);
INSERT INTO take (student_id, course_id, section_id, semester, year) VALUES ('UG002', 'COMP2010', 'Section2010S10', 'Spring', 2025);
INSERT INTO take (student_id, course_id, section_id, semester, year) VALUES ('UG003', 'COMP1020', 'Section1020S10', 'Spring', 2025);
INSERT INTO take (student_id, course_id, section_id, semester, year) VALUES ('UG004', 'COMP1020', 'Section1020S10', 'Spring', 2025);
INSERT INTO take (student_id, course_id, section_id, semester, year) VALUES ('UG005', 'COMP2010', 'Section2010S10', 'Spring', 2025);
INSERT INTO take (student_id, course_id, section_id, semester, year) VALUES ('UG006', 'COMP1020', 'Section1020S10', 'Spring', 2025);
INSERT INTO take (student_id, course_id, section_id, semester, year) VALUES ('UG007', 'COMP1010', 'Section1010S10', 'Spring', 2025);
INSERT INTO take (student_id, course_id, section_id, semester, year) VALUES ('UG008', 'COMP1020', 'Section1020S10', 'Spring', 2025);
INSERT INTO take (student_id, course_id, section_id, semester, year) VALUES ('UG009', 'COMP1020', 'Section1020S10', 'Spring', 2025);
INSERT INTO take (student_id, course_id, section_id, semester, year) VALUES ('UG0010', 'COMP2010', 'Section2010S10', 'Spring', 2025);
INSERT INTO take (student_id, course_id, section_id, semester, year) VALUES ('UG0011', 'COMP1010', 'Section1010S10', 'Spring', 2025);
INSERT INTO take (student_id, course_id, section_id, semester, year) VALUES ('MS001', 'COMP1010', 'Section1010S10', 'Spring', 2025);
INSERT INTO take (student_id, course_id, section_id, semester, year) VALUES ('MS002', 'COMP1010', 'Section1010S10', 'Spring', 2025);
INSERT INTO take (student_id, course_id, section_id, semester, year) VALUES ('MS003', 'COMP2010', 'Section2010S10', 'Spring', 2025);
INSERT INTO take (student_id, course_id, section_id, semester, year) VALUES ('MS004', 'COMP2040', 'Section2040S10', 'Spring', 2025);
INSERT INTO take (student_id, course_id, section_id, semester, year) VALUES ('MS005', 'COMP2010', 'Section2010S10', 'Spring', 2025);
INSERT INTO take (student_id, course_id, section_id, semester, year) VALUES ('MS006', 'COMP1010', 'Section1010S10', 'Spring', 2025);
INSERT INTO take (student_id, course_id, section_id, semester, year) VALUES ('MS007', 'COMP1010', 'Section1010S10', 'Spring', 2025);
INSERT INTO take (student_id, course_id, section_id, semester, year) VALUES ('MS008', 'COMP1010', 'Section1010S10', 'Spring', 2025);
INSERT INTO take (student_id, course_id, section_id, semester, year) VALUES ('MS009', 'COMP1010', 'Section1010S10', 'Spring', 2025);
INSERT INTO take (student_id, course_id, section_id, semester, year) VALUES ('MS0010', 'COMP2040', 'Section2040S10', 'Spring', 2025);
INSERT INTO take (student_id, course_id, section_id, semester, year) VALUES ('MS0011', 'COMP1010', 'Section1010S10', 'Spring', 2025);
INSERT INTO take (student_id, course_id, section_id, semester, year) VALUES ('MS0012', 'COMP2010', 'Section2010S10', 'Spring', 2025);
INSERT INTO take (student_id, course_id, section_id, semester, year) VALUES ('PH001', 'COMP2010', 'Section2010S10', 'Spring', 2025);
INSERT INTO take (student_id, course_id, section_id, semester, year) VALUES ('PH002', 'COMP2040', 'Section2040S10', 'Spring', 2025);
INSERT INTO take (student_id, course_id, section_id, semester, year) VALUES ('PH003', 'COMP1020', 'Section1020S10', 'Spring', 2025);
INSERT INTO take (student_id, course_id, section_id, semester, year) VALUES ('PH004', 'COMP2010', 'Section2010S10', 'Spring', 2025);
INSERT INTO take (student_id, course_id, section_id, semester, year) VALUES ('PH005', 'COMP1010', 'Section1010S10', 'Spring', 2025);
INSERT INTO take (student_id, course_id, section_id, semester, year) VALUES ('PH006', 'COMP1010', 'Section1010S10', 'Spring', 2025);
INSERT INTO take (student_id, course_id, section_id, semester, year) VALUES ('PH007', 'COMP2040', 'Section2040S10', 'Spring', 2025);
INSERT INTO take (student_id, course_id, section_id, semester, year) VALUES ('PH008', 'COMP2040', 'Section2040S10', 'Spring', 2025);
INSERT INTO take (student_id, course_id, section_id, semester, year) VALUES ('PH009', 'COMP1010', 'Section1010S10', 'Spring', 2025);
INSERT INTO take (student_id, course_id, section_id, semester, year) VALUES ('PH0010', 'COMP2010', 'Section2010S10', 'Spring', 2025);
INSERT INTO take (student_id, course_id, section_id, semester, year) VALUES ('PH0011', 'COMP1020', 'Section1020S10', 'Spring', 2025);
INSERT INTO take (student_id, course_id, section_id, semester, year) VALUES ('PH0012', 'COMP2010', 'Section2010S10', 'Spring', 2025);
INSERT INTO TA (student_id, course_id, section_id, semester, year) VALUES ('PH001', 'COMP2040', 'Section2040S10', 'Spring', 2025);
INSERT INTO TA (student_id, course_id, section_id, semester, year) VALUES ('PH002', 'COMP2040', 'Section2040S10', 'Spring', 2025);
INSERT INTO TA (student_id, course_id, section_id, semester, year) VALUES ('PH003', 'COMP2040', 'Section2040S10', 'Spring', 2025);
INSERT INTO TA (student_id, course_id, section_id, semester, year) VALUES ('PH004', 'COMP2040', 'Section2040S10', 'Spring', 2025);
INSERT INTO TA (student_id, course_id, section_id, semester, year) VALUES ('PH005', 'COMP2040', 'Section2040S10', 'Spring', 2025);
INSERT INTO masterGrader (student_id, course_id, section_id, semester, year) VALUES ('MS001', 'COMP1010', 'Section1010S10', 'Spring', 2025);
INSERT INTO masterGrader (student_id, course_id, section_id, semester, year) VALUES ('MS002', 'COMP1020', 'Section1020S10', 'Spring', 2025);
INSERT INTO masterGrader (student_id, course_id, section_id, semester, year) VALUES ('MS003', 'COMP2010', 'Section2010S10', 'Spring', 2025);
INSERT INTO masterGrader (student_id, course_id, section_id, semester, year) VALUES ('MS004', 'COMP2040', 'Section2040S10', 'Spring', 2025);
INSERT INTO masterGrader (student_id, course_id, section_id, semester, year) VALUES ('MS005', 'COMP1010', 'Section1010S10', 'Spring', 2025);
INSERT INTO undergraduateGrader (student_id, course_id, section_id, semester, year) VALUES ('UG001', 'COMP1010', 'Section1010S10', 'Spring', 2025);
INSERT INTO undergraduateGrader (student_id, course_id, section_id, semester, year) VALUES ('UG002', 'COMP1020', 'Section1020S10', 'Spring', 2025);
INSERT INTO undergraduateGrader (student_id, course_id, section_id, semester, year) VALUES ('UG003', 'COMP2010', 'Section2010S10', 'Spring', 2025);
INSERT INTO undergraduateGrader (student_id, course_id, section_id, semester, year) VALUES ('UG004', 'COMP2040', 'Section2040S10', 'Spring', 2025);
INSERT INTO undergraduateGrader (student_id, course_id, section_id, semester, year) VALUES ('UG005', 'COMP1010', 'Section1010S10', 'Spring', 2025);

-- COMP1010 Section001 Events
INSERT INTO course_event (course_id, section_id, semester, year, event_title, event_description, event_date, event_type) VALUES
('COMP1010', 'Section001', 'Spring', 2025, 'Midterm Exam', 'Covers chapters 1-5', '2025-03-15', 'exam'),
('COMP1010', 'Section001', 'Spring', 2025, 'Assignment 1', 'Basic programming concepts', '2025-02-10', 'assignment'),
('COMP1010', 'Section001', 'Spring', 2025, 'Assignment 2', 'Control structures and loops', '2025-02-24', 'assignment'),
('COMP1010', 'Section001', 'Spring', 2025, 'Programming Project 1', 'Individual project on algorithms', '2025-04-05', 'project'),
('COMP1010', 'Section001', 'Spring', 2025, 'Final Exam', 'Comprehensive exam', '2025-05-20', 'exam');

-- COMP1010 Section002 Events
INSERT INTO course_event (course_id, section_id, semester, year, event_title, event_description, event_date, event_type) VALUES
('COMP1010', 'Section002', 'Spring', 2025, 'Midterm Exam', 'Covers chapters 1-5', '2025-03-17', 'exam'),
('COMP1010', 'Section002', 'Spring', 2025, 'Assignment 1', 'Basic programming concepts', '2025-02-12', 'assignment'),
('COMP1010', 'Section002', 'Spring', 2025, 'Assignment 2', 'Control structures and loops', '2025-02-26', 'assignment'),
('COMP1010', 'Section002', 'Spring', 2025, 'Programming Project 1', 'Individual project on algorithms', '2025-04-07', 'project'),
('COMP1010', 'Section002', 'Spring', 2025, 'Final Exam', 'Comprehensive exam', '2025-05-22', 'exam');

-- COMP1020 Section001 Events
INSERT INTO course_event (course_id, section_id, semester, year, event_title, event_description, event_date, event_type) VALUES
('COMP1020', 'Section001', 'Spring', 2025, 'Quiz 1', 'Basic OOP concepts', '2025-02-05', 'quiz'),
('COMP1020', 'Section001', 'Spring', 2025, 'Quiz 2', 'Inheritance and polymorphism', '2025-03-05', 'quiz'),
('COMP1020', 'Section001', 'Spring', 2025, 'Midterm Exam', 'Covers all material from weeks 1-7', '2025-03-20', 'exam'),
('COMP1020', 'Section001', 'Spring', 2025, 'Group Project', 'Design and implement a small application', '2025-04-15', 'project'),
('COMP1020', 'Section001', 'Spring', 2025, 'Final Exam', 'Comprehensive with focus on latter half of course', '2025-05-18', 'exam');

-- COMP2010 Section001 Events
INSERT INTO course_event (course_id, section_id, semester, year, event_title, event_description, event_date, event_type) VALUES
('COMP2010', 'Section001', 'Spring', 2025, 'Lab 1', 'Data structures implementation', '2025-02-08', 'lab'),
('COMP2010', 'Section001', 'Spring', 2025, 'Lab 2', 'Searching and sorting algorithms', '2025-02-22', 'lab'),
('COMP2010', 'Section001', 'Spring', 2025, 'Lab 3', 'Trees and graphs', '2025-03-08', 'lab'),
('COMP2010', 'Section001', 'Spring', 2025, 'Midterm', 'Written exam on algorithm analysis', '2025-03-22', 'exam'),
('COMP2010', 'Section001', 'Spring', 2025, 'Final Project', 'Algorithm implementation and analysis', '2025-04-25', 'project'),
('COMP2010', 'Section001', 'Spring', 2025, 'Final Exam', 'Comprehensive exam', '2025-05-15', 'exam');

-- COMP2040 Section001 Events
INSERT INTO course_event (course_id, section_id, semester, year, event_title, event_description, event_date, event_type) VALUES
('COMP2040', 'Section001', 'Spring', 2025, 'Paper 1', 'Research summary on selected topic', '2025-02-20', 'assignment'),
('COMP2040', 'Section001', 'Spring', 2025, 'Presentation 1', 'Group presentations on research areas', '2025-03-10', 'presentation'),
('COMP2040', 'Section001', 'Spring', 2025, 'Midterm Examination', 'In-class written exam', '2025-03-25', 'exam'),
('COMP2040', 'Section001', 'Spring', 2025, 'Paper 2', 'Original research proposal', '2025-04-20', 'assignment'),
('COMP2040', 'Section001', 'Spring', 2025, 'Final Presentation', 'Final project presentation', '2025-05-10', 'presentation'),
('COMP2040', 'Section001', 'Spring', 2025, 'Final Exam', 'Comprehensive written exam', '2025-05-25', 'exam');

-- Insert some sample student todos (personal notes) for demonstration
INSERT INTO student_todo (student_id, todo_title, todo_description, due_date, is_completed) VALUES
('UG001', 'Study Group Meeting', 'Meet with study group for COMP2040', '2025-02-15', 0),
('UG001', 'Library Research', 'Find resources for COMP2040 paper', '2025-02-05', 1),
('UG002', 'Meet with Advisor', 'Discuss course selection for next semester', '2025-03-01', 0),
('UG003', 'Buy Textbook', 'Get textbook for COMP1020', '2025-01-30', 1),
('UG004', 'Internship Application', 'Submit application for summer internship', '2025-02-28', 0);

-- Insert some sample rating to courses for demonstration
INSERT INTO rate(rate_id, student_id, course_id, rate) VALUES(1, 'MS0010', 'COMP1010', 5);
INSERT INTO rate(rate_id, student_id, course_id, rate) VALUES(2, 'MS001', 'COMP1020', 4);
INSERT INTO rate(rate_id, student_id, course_id, rate) VALUES(3, 'MS001', 'COMP2040', 2.5);