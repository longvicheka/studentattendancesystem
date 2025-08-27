CREATE TABLE `tblstudent` (
  `Id` int(11) NOT NULL,
  `studentId` int(11) NOT NULL,
  `firstName` varchar(50) NOT NULL,
  `lastName` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `DOB` date NOT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `academicYear` varchar(10) NOT NULL,
  `phoneNumber` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `major_id` tinyint(4) NOT NULL,
  `enrollmentDate` date NOT NULL,
  `graduationDate` date DEFAULT NULL,
  `createdAt` datetime DEFAULT current_timestamp(),
  `createdBy` varchar(50) NOT NULL,
  `modifiedAt` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `modifiedBy` varchar(50) DEFAULT NULL,
  `isActive` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `tblstudent` (`Id`, `studentId`, `firstName`, `lastName`, `email`, `DOB`, `gender`, `academicYear`, `phoneNumber`, `address`, `password`, `major_id`, `enrollmentDate`, `graduationDate`, `createdAt`, `createdBy`, `modifiedAt`, `modifiedBy`, `isActive`) VALUES
(1, 2202001, 'Vicheka', 'Long', 'vicheka.long@gmail.com', '2002-01-15', NULL, '4', '012345678', 'Phnom Penh', '$2y$10$Rwa3tuEkKaWhl8g3XnExZuhsbISOJ.jaaxomKzNjcDaqpFISvZdbS', 2, '2021-02-01', NULL, '2025-08-15 03:56:46', 'admin', '2025-08-27 02:01:56', NULL, 1),
(2, 2202002, 'David', 'Kheang', 'david.kheang@gmail.com', '2001-03-20', NULL, '4', '012345679', 'Phnom Penh', '$2y$10$4Dxdw1dC3uMVs4gONWn9P.Dd2SUNXbWixGFvquFRhKEDJeQc7ZkoO', 2, '2021-02-01', NULL, '2025-08-15 03:56:46', 'admin', '2025-08-27 02:01:56', NULL, 1),
(3, 2202003, 'Soveha', 'Vat', 'soveha.vat@gmail.com', '2002-06-12', NULL, '4', '012345680', 'Phnom Penh', '$2y$10$Gf2h1zu2Sb7JsWHMBJl86.V.czLwFTxmAsNZ88etEDq9qhIrFraK.', 2, '2021-02-01', NULL, '2025-08-15 03:56:46', 'admin', '2025-08-27 02:01:56', NULL, 1),
(4, 2202004, 'Yav', 'Sok', 'yav.sok@gmail.com', '2003-02-08', NULL, '4', '012345681', 'Phnom Penh', '$2y$10$QGZCZ9DIEus0GdvKBXvQ2eXejzaQzEJbuzqLDhEZarnedpFc/8MeG', 3, '2021-02-01', NULL, '2025-08-15 03:56:46', 'admin', '2025-08-27 02:01:56', NULL, 1),
(5, 2202005, 'Sreynit', 'Lay', 'sreynit.lay@gmail.com', '2002-11-30', NULL, '4', '012345682', 'Phnom Penh', '$2y$10$Xr2pXBheWkIaoVz8KmLZEun5NcPARoi.XPfDWh5t6lScLhhDqufUm', 3, '2021-02-01', NULL, '2025-08-15 03:56:46', 'admin', '2025-08-27 02:01:56', NULL, 1),
(6, 2202006, 'Kanika', 'Seng', 'kanika.seng@gmail.com', '2001-08-21', NULL, '4', '012345683', 'Phnom Penh', '$2y$10$0bqD4gYtw53gmHsQxESDqeVshuDMxxc4yp8SK7i/a6f7NWWQookJG', 3, '2021-02-01', NULL, '2025-08-15 03:56:46', 'admin', '2025-08-27 02:01:56', NULL, 1),
(7, 2202007, 'Thavary', 'Vathna', 'thavary.vathna@gmail.com', '2002-04-25', NULL, '4', '012345684', 'Phnom Penh', '$2y$10$CXJXWtAFKJcNkPss8EFAv.XGsNV9fWrq4H8rAalrfOIbJ.PCFB6x.', 1, '2021-02-01', NULL, '2025-08-15 03:56:46', 'admin', '2025-08-27 02:01:56', NULL, 1),
(8, 2202008, 'Kirilundi', 'Eav', 'kirilundi.eav@gmail.com', '2002-09-19', NULL, '4', '012345685', 'Phnom Penh', '$2y$10$iFpY.MTxyKV2Ahz59YsaEejHj8hwujHxC.cVP9rhr3Z4TOsrgdh3a', 4, '2021-02-01', NULL, '2025-08-15 03:56:46', 'admin', '2025-08-27 02:01:56', NULL, 1),
(9, 2202009, 'Sophea', 'Chan', 'sophea.chan@gmail.com', '2003-05-14', NULL, '4', '012345686', 'Phnom Penh', '$2y$10$Vlr6jROBMC7vMMTRYjX9POfc4znpqvaqasKkAtAbQoQ6iGesshkz6', 4, '2021-02-01', NULL, '2025-08-15 03:56:46', 'admin', '2025-08-27 02:01:56', NULL, 1),
(10, 2202010, 'Chhayheng', 'Koung', 'chhayheng.koung@gmail.com', '2002-12-01', NULL, '4', '012345687', 'Phnom Penh', '$2y$10$oXBBEZeUnGHxQejPn84TX.c2Q8JAF/aEb6QKueCA6B3PwZioz7Fum', 1, '2021-02-01', NULL, '2025-08-15 03:56:46', 'admin', '2025-08-27 02:01:56', NULL, 1),
(11, 2202011, 'Rithychey', 'Hongsoth', 'rithychey.hongsoth@gmail.com', '2001-10-17', NULL, '4', '012345688', 'Phnom Penh', '$2y$10$argm1cnRKieqM97.XK314eOM1ijgv/jdv4alLj8tfg88PCQ8jM1eK', 4, '2021-02-01', NULL, '2025-08-15 03:56:46', 'admin', '2025-08-27 02:01:56', NULL, 1);

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

CREATE TABLE tblabsentrequest (
    id INT AUTO_INCREMENT PRIMARY KEY,
    studentId VARCHAR(11) NOT NULL,
    studentName VARCHAR(50) NOT NULL,
    requestDate DATE NOT NULL,
    academicYear INT NOT NULL,
    startDate DATE NOT NULL,
    endDate DATE NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    adminResponse TEXT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    approvedBy INT NULL
);

CREATE TABLE tblmajor (
    major_id INT AUTO_INCREMENT PRIMARY KEY,
    major_code VARCHAR(10) NOT NULL UNIQUE,
    major_name VARCHAR(100) NOT NULL,

    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    createdBy VARCHAR(50) NOT NULL,
    modifiedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    modifiedBy VARCHAR(50) NULL,
    isDeleted BINARY DEFAULT 0
);

INSERT INTO tblmajor (major_code, major_name, createdBy) VALUES
('ACC', 'Accounting', 'admin'),
('BIS', 'Business Information System', 'admin'),
('BM', 'Business Management', 'admin'),
('H&T', 'Hospitality & Tourism', 'admin'),
('B&F', 'Banking & Finance', 'admin'),
('LSC', 'Logistics & Supply Chain Management', 'admin');
