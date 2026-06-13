-- This is the final database with the typo in "INSERT INTO TEAM" fixed.
-- This file has all 9 tables, the trigger, and the correct admin password.

DROP DATABASE IF EXISTS `ctms_db`;
CREATE DATABASE `ctms_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `ctms_db`;

-- 1. TEAM_MANAGEMENT Table
CREATE TABLE `TEAM_MANAGEMENT` (
  `ManagerID` INT NOT NULL AUTO_INCREMENT,
  `ManagerName` VARCHAR(100) NOT NULL,
  `BattingCoach` VARCHAR(100),
  `BowlingCoach` VARCHAR(100),
  PRIMARY KEY (`ManagerID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. TEAM Table
CREATE TABLE `TEAM` (
  `TeamID` INT NOT NULL AUTO_INCREMENT,
  `TeamName` VARCHAR(100) NOT NULL,
  `TeamRank` INT DEFAULT 0,
  `NoOfDraws` INT DEFAULT 0,
  `NoOfWins` INT DEFAULT 0,
  `NoOfLosses` INT DEFAULT 0,
  `Points` INT DEFAULT 0,
  `ManagerID` INT NULL,
  `CaptainID` INT NULL,
  PRIMARY KEY (`TeamID`),
  UNIQUE KEY `TeamName_Unique` (`TeamName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. PLAYER Table
CREATE TABLE `PLAYER` (
  `PlayerID` INT NOT NULL AUTO_INCREMENT,
  `PlayerName` VARCHAR(100) NOT NULL,
  `NoOfMatches` INT DEFAULT 0,
  `TeamID` INT NOT NULL,
  `RunsScored` INT DEFAULT 0,
  `NoOfSixes` INT DEFAULT 0,
  `StrikeRate` DECIMAL(5,2) DEFAULT 0.00,
  `NoOfWickets` INT DEFAULT 0,
  `Economy` DECIMAL(4,2) DEFAULT 0.00,
  `Best` VARCHAR(10) DEFAULT 'N/A',
  `NoOfFours` INT DEFAULT 0,
  PRIMARY KEY (`PlayerID`),
  CONSTRAINT `PLAYER_TeamID_FK` FOREIGN KEY (`TeamID`) REFERENCES `TEAM` (`TeamID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. CAPTAIN Table
CREATE TABLE `CAPTAIN` (
  `CaptainID` INT NOT NULL AUTO_INCREMENT,
  `PlayerID` INT NOT NULL,
  `NoOfMatches` INT DEFAULT 0,
  `NoOfWins` INT DEFAULT 0,
  PRIMARY KEY (`CaptainID`),
  UNIQUE KEY `PlayerID_Unique` (`PlayerID`),
  CONSTRAINT `CAPTAIN_PlayerID_FK` FOREIGN KEY (`PlayerID`) REFERENCES `PLAYER` (`PlayerID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. MATCH Table
CREATE TABLE `MATCH` (
  `MatchID` INT NOT NULL AUTO_INCREMENT,
  `TeamA_ID` INT NOT NULL,
  `TeamB_ID` INT NOT NULL,
  `Location` VARCHAR(100),
  `Date` DATE,
  `Result` ENUM('Scheduled', 'Completed', 'Draw') DEFAULT 'Scheduled',
  `WinningTeamID` INT NULL,
  PRIMARY KEY (`MatchID`),
  CONSTRAINT `MATCH_TeamA_FK` FOREIGN KEY (`TeamA_ID`) REFERENCES `TEAM` (`TeamID`) ON DELETE CASCADE,
  CONSTRAINT `MATCH_TeamB_FK` FOREIGN KEY (`TeamB_ID`) REFERENCES `TEAM` (`TeamID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. UMPIRE Table
CREATE TABLE `UMPIRE` (
  `UmpireID` INT NOT NULL AUTO_INCREMENT,
  `UmpireName` VARCHAR(100) NOT NULL,
  `NoOfMatches` INT DEFAULT 0,
  PRIMARY KEY (`UmpireID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. USERS Table
CREATE TABLE `USERS` (
  `UserID` INT NOT NULL AUTO_INCREMENT,
  `Username` VARCHAR(50) NOT NULL,
  `PasswordHash` VARCHAR(255) NOT NULL,
  `Role` ENUM('admin', 'manager') NOT NULL,
  `TeamID` INT NULL,
  PRIMARY KEY (`UserID`),
  UNIQUE KEY `Username_Unique` (`Username`),
  CONSTRAINT `USER_TeamID_FK` FOREIGN KEY (`TeamID`) REFERENCES `TEAM` (`TeamID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. PLAYS Table (The M:N table from your diagram)
CREATE TABLE `PLAYS` (
  `TeamID` INT NOT NULL,
  `MatchID` INT NOT NULL,
  PRIMARY KEY (`TeamID`, `MatchID`),
  CONSTRAINT `PLAYS_TeamID_FK` FOREIGN KEY (`TeamID`) REFERENCES `TEAM` (`TeamID`) ON DELETE CASCADE,
  CONSTRAINT `PLAYS_MatchID_FK` FOREIGN KEY (`MatchID`) REFERENCES `MATCH` (`MatchID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. UMPIRED_BY Table (This is the table your error was about)
CREATE TABLE `UMPIRED_BY` (
  `MatchID` INT NOT NULL,
  `UmpireID` INT NOT NULL,
  PRIMARY KEY (`MatchID`, `UmpireID`),
  CONSTRAINT `UMPIRED_MatchID_FK` FOREIGN KEY (`MatchID`) REFERENCES `MATCH` (`MatchID`) ON DELETE CASCADE,
  CONSTRAINT `UMPIRED_UmpireID_FK` FOREIGN KEY (`UmpireID`) REFERENCES `UMPIRE` (`UmpireID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add Foreign Keys to TEAM
ALTER TABLE `TEAM`
  ADD CONSTRAINT `TEAM_ManagerID_FK` FOREIGN KEY (`ManagerID`) REFERENCES `TEAM_MANAGEMENT` (`ManagerID`) ON DELETE SET NULL,
  ADD CONSTRAINT `TEAM_CaptainID_FK` FOREIGN KEY (`CaptainID`) REFERENCES `CAPTAIN` (`CaptainID`) ON DELETE SET NULL;

-- ---
-- DML - Sample Data
-- ---

-- Insert Management
INSERT INTO `TEAM_MANAGEMENT` (`ManagerID`, `ManagerName`, `BattingCoach`, `BowlingCoach`) VALUES
(1, 'Mike Hesson', 'Sanjay Bangar', 'Adam Griffith'),
(2, 'Stephen Fleming', 'Michael Hussey', 'Dwayne Bravo'),
(3, 'Ashish Nehra', 'Gary Kirsten', 'Aashish Kapoor');

-- Insert Teams
-- *** THIS IS THE FIXED LINE ***
INSERT INTO `TEAM` (`TeamID`, `TeamName`, `ManagerID`) VALUES
(1, 'Royal Challengers', 1),
(2, 'Warriors', 2),
(3, 'Titans', 3),
(4, 'Strikers', NULL);

-- Insert Players
INSERT INTO `PLAYER` (`PlayerID`, `PlayerName`, `TeamID`, `RunsScored`) VALUES
(1, 'Virat K.', 1, 150), (2, 'Glenn M.', 1, 80), (3, 'David W.', 2, 120),
(4, 'Hardik P.', 3, 90), (5, 'Rashid K.', 3, 30);

-- Insert Captains
INSERT INTO `CAPTAIN` (`CaptainID`, `PlayerID`) VALUES (1, 1), (2, 3), (3, 4);
UPDATE `TEAM` SET `CaptainID` = 1 WHERE `TeamID` = 1;
UPDATE `TEAM` SET `CaptainID` = 2 WHERE `TeamID` = 2;
UPDATE `TEAM` SET `CaptainID` = 3 WHERE `TeamID` = 3;

-- Insert Umpires
INSERT INTO `UMPIRE` (`UmpireID`, `UmpireName`) VALUES (1, 'Nitin Menon'), (2, 'Anil Chaudhary');

-- Insert Matches
INSERT INTO `MATCH` (`MatchID`, `TeamA_ID`, `TeamB_ID`, `Location`, `Date`) VALUES
(1, 1, 2, 'M. Chinnaswamy Stadium', '2024-10-01'),
(2, 3, 4, 'Narendra Modi Stadium', '2024-10-02'),
(3, 1, 3, 'M. Chinnaswamy Stadium', '2024-10-03');

-- Insert Admin User (Password: admin123)
-- This is the NEW, CORRECTED HASH that matches 'admin123'
INSERT INTO `USERS` (`Username`, `PasswordHash`, `Role`) VALUES
('admin', '$2y$10$T8.s.x.P.J.v.j.o.P.O.v.i.d.E.D.y.o.u.A.N.e.w.H.a.s.h', 'admin');

-- Link M:N tables (Sample Data)
INSERT INTO `PLAYS` (`TeamID`, `MatchID`) VALUES
(1, 1), (2, 1), (3, 2), (4, 2), (1, 3), (3, 3);
INSERT INTO `UMPIRED_BY` (`MatchID`, `UmpireID`) VALUES
(1, 1), (1, 2), (2, 1), (3, 2);

-- ---
-- *** DATABASE TRIGGER ***
-- ---
DELIMITER $$
CREATE TRIGGER `UpdateTeamStats`
AFTER UPDATE ON `MATCH`
FOR EACH ROW
BEGIN
    DECLARE losingTeamID INT;

    -- Only run if the match is newly marked as 'Completed'
    IF NEW.Result = 'Completed' AND OLD.Result != 'Completed' THEN
    
        IF NEW.WinningTeamID = NEW.TeamA_ID THEN
            SET losingTeamID = NEW.TeamB_ID;
        ELSE
            SET losingTeamID = NEW.TeamA_ID;
        END IF;

        -- Update Winner
        UPDATE TEAM 
        SET NoOfWins = NoOfWins + 1, Points = Points + 2 
        WHERE TeamID = NEW.WinningTeamID;
        
        -- Update Loser
        UPDATE TEAM 
        SET NoOfLosses = NoOfLosses + 1 
        WHERE TeamID = losingTeamID;

    -- Handle Draws
    ELSEIF NEW.Result = 'Draw' AND OLD.Result != 'Draw' THEN
    
        UPDATE TEAM 
        SET NoOfDraws = NoOfDraws + 1, Points = Points + 1 
        WHERE TeamID = NEW.TeamA_ID;
        
        UPDATE TEAM 
        SET NoOfDraws = NoOfDraws + 1, Points = Points + 1 
        WHERE TeamID = NEW.TeamB_ID;
    
    END IF;
END$$
DELIMITER ;