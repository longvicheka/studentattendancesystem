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
(22020001, 'Vicheka', 'Long', 'vicheka.long@example.com', '2002-01-15', '2024', '012345678', 'Phnom Penh', SHA2('22020001@123', 256)),
(22020002, 'David', 'Kheang', 'david.kheang@example.com', '2001-03-20', '2024', '012345679', 'Phnom Penh', SHA2('22020002@123', 256)),
(22020003, 'Soveha', 'Vat', 'soveha.vat@example.com', '2002-06-12', '2024', '012345680', 'Phnom Penh', SHA2('22020003@123', 256)),
(22020004, 'Yav', 'Sok', 'yav.sok@example.com', '2003-02-08', '2024', '012345681', 'Phnom Penh', SHA2('22020004@123', 256)),
(22020005, 'Sreynit', 'Lay', 'sreynit.lay@example.com', '2002-11-30', '2024', '012345682', 'Phnom Penh', SHA2('22020005@123', 256)),
(22020006, 'Kanika', 'Seng', 'kanika.seng@example.com', '2001-08-21', '2024', '012345683', 'Phnom Penh', SHA2('22020006@123', 256)),
(22020007, 'Thavary', 'Vathna', 'thavary.vathna@example.com', '2002-04-25', '2024', '012345684', 'Phnom Penh', SHA2('22020007@123', 256)),
(22020008, 'Kirilundi', 'Eav', 'kirilundi.eav@example.com', '2002-09-19', '2024', '012345685', 'Phnom Penh', SHA2('22020008@123', 256)),
(22020009, 'Sophea', 'Chan', 'sophea.chan@example.com', '2003-05-14', '2024', '012345686', 'Phnom Penh', SHA2('22020009@123', 256)),
(22020010, 'Chhayheng', 'Koung', 'chhayheng.koung@example.com', '2002-12-01', '2024', '012345687', 'Phnom Penh', SHA2('22020010@123', 256)),
(22020011, 'Rithychey', 'Hongsoth', 'rithychey.hongsoth@example.com', '2001-10-17', '2024', '012345688', 'Phnom Penh', SHA2('22020011@123', 256));

CREATE TABLE tblAttendance (
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
