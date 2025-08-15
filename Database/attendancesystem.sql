CREATE TABLE `tblstudent` (
  `Id` INT AUTO_INCREMENT PRIMARY KEY,
  `userId` INT UNIQUE NOT NULL,
  `firstName` VARCHAR(50) NOT NULL,
  `lastName` VARCHAR(50) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `DOB` DATE NOT NULL,
  `academicYear` VARCHAR(10) NOT NULL,
  `phoneNumber` VARCHAR(20),
  `address` TEXT,
  `password` VARCHAR(255) NOT NULL,
  `createdAt` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `isActive` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `tblstudent` 
(`userId`, `firstName`, `lastName`, `email`, `DOB`, `academicYear`, `phoneNumber`, `address`, `password`) 
VALUES
(22020001, 'Vicheka', 'Long', 'vicheka.long@gmail.com', '2002-01-15', '2024', '012345678', 'Phnom Penh', SHA2('vicheka@2025', 256)),
(22020002, 'David', 'Kheang', 'david.kheang@gmail.com', '2001-03-20', '2024', '012345679', 'Phnom Penh', SHA2('david@2025', 256)),
(22020003, 'Soveha', 'Vat', 'soveha.vat@gmail.com', '2002-06-12', '2024', '012345680', 'Phnom Penh', SHA2('veha@2025', 256)),
(22020004, 'Yav', 'Sok', 'yav.sok@gmail.com', '2003-02-08', '2024', '012345681', 'Phnom Penh', SHA2('yav@2025', 256)),
(22020005, 'Sreynit', 'Lay', 'sreynit.lay@gmail.com', '2002-11-30', '2024', '012345682', 'Phnom Penh', SHA2('sreynit@2025', 256)),
(22020006, 'Kanika', 'Seng', 'kanika.seng@gmail.com', '2001-08-21', '2024', '012345683', 'Phnom Penh', SHA2('kanika@2025', 256)),
(22020007, 'Thavary', 'Vathna', 'thavary.vathna@gmail.com', '2002-04-25', '2024', '012345684', 'Phnom Penh', SHA2('thavary@2025', 256)),
(22020008, 'Kirilundi', 'Eav', 'kirilundi.eav@gmail.com', '2002-09-19', '2024', '012345685', 'Phnom Penh', SHA2('lundi@2025', 256)),
(22020009, 'Sophea', 'Chan', 'sophea.chan@gmail.com', '2003-05-14', '2024', '012345686', 'Phnom Penh', SHA2('sophea@2025', 256)),
(22020010, 'Chhayheng', 'Koung', 'chhayheng.koung@gmail.com', '2002-12-01', '2024', '012345687', 'Phnom Penh', SHA2('chhayheng@2025', 256)),
(22020011, 'Rithychey', 'Hongsoth', 'rithychey.hongsoth@gmail.com', '2001-10-17', '2024', '012345688', 'Phnom Penh', SHA2('rithychey@2025', 256));

CREATE TABLE tblattendance (
  attendanceId INT PRIMARY KEY AUTO_INCREMENT,
  sessionId INT(11) NOT NULL,
  studentId INT(11) NOT NULL,
  attendanceStatus ENUM('present', 'absent') DEFAULT 'present',
  markedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
  markedBy VARCHAR(50) DEFAULT 'admin',
  remarks TEXT,
  modifiedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  modifiedBy VARCHAR(50) DEFAULT 'admin'
);


INSERT INTO tblAttendance (sessionId, studentId, attendanceStatus, markedAt, markedBy, remarks, modifiedAt, modifiedBy) VALUES
(1, 2202001, 'Absent', '2025-07-01 08:00:00', 'admin', 'Sick', '2025-07-01 08:00:00', 'admin'),
(1, 2202002, 'Late', '2025-07-01 08:00:00', 'admin', '', '2025-07-01 08:00:00', 'admin'),
(1, 2202003, 'Excused', '2025-07-01 08:00:00', 'admin', 'Sick', '2025-07-01 08:00:00', 'admin'),
(1, 2202004, 'Present', '2025-07-01 08:00:00', 'admin', '', '2025-07-01 08:00:00', 'admin'),
(1, 2202005, 'Late', '2025-07-01 08:00:00', 'admin', '', '2025-07-01 08:00:00', 'admin'),
(1, 2202001, 'Present', '2025-07-02 08:00:00', 'admin', '', '2025-07-02 08:00:00', 'admin'),
(1, 2202002, 'Absent', '2025-07-02 08:00:00', 'admin', 'Medical certificate', '2025-07-02 08:00:00', 'admin'),
(1, 2202003, 'Absent', '2025-07-02 08:00:00', 'admin', 'Family reason', '2025-07-02 08:00:00', 'admin'),
(1, 2202004, 'Late', '2025-07-02 08:00:00', 'admin', '', '2025-07-02 08:00:00', 'admin'),
(1, 2202005, 'Absent', '2025-07-02 08:00:00', 'admin', 'Family reason', '2025-07-02 08:00:00', 'admin'),
(1, 2202001, 'Excused', '2025-07-03 08:00:00', 'admin', 'Sick', '2025-07-03 08:00:00', 'admin'),
(1, 2202002, 'Absent', '2025-07-03 08:00:00', 'admin', 'Sick', '2025-07-03 08:00:00', 'admin'),
(1, 2202003, 'Absent', '2025-07-03 08:00:00', 'admin', 'Medical certificate', '2025-07-03 08:00:00', 'admin'),
(1, 2202004, 'Late', '2025-07-03 08:00:00', 'admin', '', '2025-07-03 08:00:00', 'admin'),
(1, 2202005, 'Present', '2025-07-03 08:00:00', 'admin', '', '2025-07-03 08:00:00', 'admin'),
(1, 2202001, 'Present', '2025-07-04 08:00:00', 'admin', '', '2025-07-04 08:00:00', 'admin'),
(1, 2202002, 'Excused', '2025-07-04 08:00:00', 'admin', 'Emergency', '2025-07-04 08:00:00', 'admin'),
(1, 2202003, 'Present', '2025-07-04 08:00:00', 'admin', '', '2025-07-04 08:00:00', 'admin'),
(1, 2202004, 'Late', '2025-07-04 08:00:00', 'admin', '', '2025-07-04 08:00:00', 'admin'),
(1, 2202005, 'Present', '2025-07-04 08:00:00', 'admin', '', '2025-07-04 08:00:00', 'admin'),
(1, 2202001, 'Present', '2025-07-07 08:00:00', 'admin', '', '2025-07-07 08:00:00', 'admin'),
(1, 2202002, 'Absent', '2025-07-07 08:00:00', 'admin', 'Family reason', '2025-07-07 08:00:00', 'admin'),
(1, 2202003, 'Excused', '2025-07-07 08:00:00', 'admin', 'Sick', '2025-07-07 08:00:00', 'admin'),
(1, 2202004, 'Late', '2025-07-07 08:00:00', 'admin', '', '2025-07-07 08:00:00', 'admin'),
(1, 2202005, 'Late', '2025-07-07 08:00:00', 'admin', '', '2025-07-07 08:00:00', 'admin');

CREATE TABLE tblsubject (
    subjectId INT PRIMARY KEY AUTO_INCREMENT,
    subjectCode VARCHAR(10) NOT NULL UNIQUE,
    subjectName VARCHAR(100) NOT NULL,
    scheduledDay INT,
    credits INT NOT NULL DEFAULT 3,
    department VARCHAR(50),
    description TEXT,
    isActive BIT DEFAULT 1,
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    modifiedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO tblsubject (subjectCode, subjectName, scheduledDay, credits, department, description) VALUES
('ISB', 'Information Systems in Business', '3,5', 3, 'Business Information Systems', 'Understanding how information systems are conducted in Business'),
('RM', 'Research Methods', '2,4', 4, 'Research Methodology', 'Research strategies and methods'),
('CRM', 'Customer Relationship Management', '3,5', 4, 'Business Administration', 'Managing customer relationships and data'),
('TAX', 'Taxation', '1,3', 4, 'Accounting & Finance', 'Understanding taxation principles and practices');

CREATE TABLE tblstudentsubject (
    Id INT PRIMARY KEY AUTO_INCREMENT,
    studentId VARCHAR(10) NOT NULL,          
    subjectCode VARCHAR(10) NOT NULL,        
    subjectName VARCHAR(100),                
    credits INT DEFAULT 3,                  
    term VARCHAR(20) NOT NULL,             
    academicYear VARCHAR(9) NOT NULL,       
    grade VARCHAR(5) NULL,                
    status VARCHAR(20) DEFAULT 'Enrolled',  
    enrollmentDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    completionDate DATETIME NULL,
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    modifiedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO tblattendance (studentId, subjectCode, sessionId, attendanceStatus, markedAt)
SELECT 
    ss.studentId,
    ss.subjectCode,
    sessions.sessionId,
    'present' AS attendanceStatus,
    NOW() AS markedAt
FROM tblstudentsubject ss
INNER JOIN tblstudent s ON ss.studentId = s.studentId
INNER JOIN tblsubject sub ON ss.subjectCode = sub.subjectCode
CROSS JOIN (
    SELECT 1 AS sessionId 
    UNION ALL SELECT 2 
    UNION ALL SELECT 3
) AS sessions
WHERE s.isActive = 1 
    AND sub.isActive = 1 
    AND ss.subjectCode = 'CRM'
    AND NOT EXISTS (
        -- Prevent duplicate records for the same date
        SELECT 1 FROM tblattendance a 
        WHERE a.studentId = ss.studentId 
            AND a.subjectCode = ss.subjectCode 
            AND a.sessionId = sessions.sessionId 
            AND DATE(a.markedAt) = CURDATE()
    );

DELETE a1 FROM tblattendance a1
INNER JOIN tblattendance a2 
WHERE a1.attendanceId < a2.attendanceId  -- Keep the record with higher ID (usually more recent)
    AND a1.studentId = a2.studentId
    AND a1.subjectCode = a2.subjectCode
    AND a1.sessionId = a2.sessionId
    AND DATE(a1.markedAt) = DATE(a2.markedAt)
    AND a1.subjectCode = 'CRM'
    AND DATE(a1.markedAt) = CURDATE();