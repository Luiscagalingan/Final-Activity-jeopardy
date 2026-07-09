-- Web Feud: Jeopardy Elimination + CTF Resolution
-- Import this file in phpMyAdmin, or run: mysql -u root -p < schema.sql

DROP DATABASE IF EXISTS web_feud_ctf;
CREATE DATABASE web_feud_ctf CHARACTER SET utf8mb4;
USE web_feud_ctf;

-- ---------------------------------------------------------
-- Teams competing in the game
-- ---------------------------------------------------------
CREATE TABLE teams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    score INT NOT NULL DEFAULT 0,
    status ENUM('active','eliminated','finalist','winner') NOT NULL DEFAULT 'active',
    display_order INT NOT NULL DEFAULT 0
);

-- ---------------------------------------------------------
-- Player names mapped to a team for the player login flow
-- ---------------------------------------------------------
CREATE TABLE team_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    team_id INT NOT NULL,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    UNIQUE KEY uq_team_member_name (full_name)
);

-- ---------------------------------------------------------
-- Categories for the Elimination round board
-- ---------------------------------------------------------
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    round_type ENUM('elimination','final') NOT NULL DEFAULT 'elimination',
    display_order INT NOT NULL DEFAULT 0
);

-- ---------------------------------------------------------
-- Questions belonging to a category (Jeopardy point values)
-- ---------------------------------------------------------
CREATE TABLE questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    points INT NOT NULL,
    question TEXT NOT NULL,
    answer TEXT NOT NULL,
    is_used TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- ---------------------------------------------------------
-- CTF resolution challenges (used to break a tie between finalists)
-- Flags are stored hashed (sha256) so they never appear in plain text
-- in the database or in page source sent to the Main Board.
-- ---------------------------------------------------------
CREATE TABLE ctf_challenges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    prompt TEXT NOT NULL,
    flag_hash VARCHAR(64) NOT NULL,
    hint TEXT,
    duration_seconds INT NOT NULL DEFAULT 180,
    is_used TINYINT(1) NOT NULL DEFAULT 0
);

-- ---------------------------------------------------------
-- Single-row table holding the entire live game state.
-- The Main Board and Host Dashboard both poll this table.
-- ---------------------------------------------------------
CREATE TABLE game_state (
    id INT PRIMARY KEY DEFAULT 1,
    phase ENUM('lobby','elimination','final_wager','final_question','final_reveal','ctf','finished')
        NOT NULL DEFAULT 'lobby',
    current_question_id INT NULL,
    question_visible TINYINT(1) NOT NULL DEFAULT 0,
    answer_visible TINYINT(1) NOT NULL DEFAULT 0,
    active_ctf_id INT NULL,
    ctf_start_time DATETIME NULL,
    ctf_prompt_visible TINYINT(1) NOT NULL DEFAULT 0,
    ctf_winner_team_id INT NULL,
    winner_team_id INT NULL,
    message VARCHAR(255) NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
INSERT INTO game_state (id, phase) VALUES (1, 'lobby');

-- ---------------------------------------------------------
-- Secret wagers for the Last 2 Standing Final Jeopardy question
-- ---------------------------------------------------------
CREATE TABLE final_wagers (
    team_id INT PRIMARY KEY,
    wager INT NOT NULL DEFAULT 0,
    answered_correct TINYINT(1) NULL,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE
);

-- ---------------------------------------------------------
-- Flag submissions during the CTF resolution stage
-- ---------------------------------------------------------
CREATE TABLE flag_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ctf_id INT NOT NULL,
    team_id INT NOT NULL,
    is_correct TINYINT(1) NOT NULL,
    submitted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ctf_id) REFERENCES ctf_challenges(id) ON DELETE CASCADE,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE
);

-- ===========================================================
-- SEED DATA
-- ===========================================================

INSERT INTO teams (name, display_order) VALUES
('ABCFN', 1),
('Bacon', 2),
('RAAHR', 3),
('Cen is Sored', 4),
('MATCHALAP', 5),
('SCUBRAZIL', 6);

INSERT INTO team_members (full_name, team_id)
SELECT 'Abalos, Kathleen Anne R', id FROM teams WHERE name = 'ABCFN';
INSERT INTO team_members (full_name, team_id)
SELECT 'Bumacod, Najil J', id FROM teams WHERE name = 'ABCFN';
INSERT INTO team_members (full_name, team_id)
SELECT 'Cainglet, Jan Alain S', id FROM teams WHERE name = 'ABCFN';
INSERT INTO team_members (full_name, team_id)
SELECT 'Faustino, Justine Angelo B', id FROM teams WHERE name = 'ABCFN';
INSERT INTO team_members (full_name, team_id)
SELECT 'Napay, Dhone Bert T', id FROM teams WHERE name = 'ABCFN';

INSERT INTO team_members (full_name, team_id)
SELECT 'Arcillas, Aldrin Shane', id FROM teams WHERE name = 'Bacon';
INSERT INTO team_members (full_name, team_id)
SELECT 'Balang, Gabriel', id FROM teams WHERE name = 'Bacon';
INSERT INTO team_members (full_name, team_id)
SELECT 'Cantorna, Rhona', id FROM teams WHERE name = 'Bacon';
INSERT INTO team_members (full_name, team_id)
SELECT 'Neri, Ryan', id FROM teams WHERE name = 'Bacon';
INSERT INTO team_members (full_name, team_id)
SELECT 'Oliveros, Marc Ryane', id FROM teams WHERE name = 'Bacon';

INSERT INTO team_members (full_name, team_id)
SELECT 'Benitez, Richelle Dorothy', id FROM teams WHERE name = 'RAAHR';
INSERT INTO team_members (full_name, team_id)
SELECT 'Loterte, Anthony', id FROM teams WHERE name = 'RAAHR';
INSERT INTO team_members (full_name, team_id)
SELECT 'Nañez, Hanzel Gwen', id FROM teams WHERE name = 'RAAHR';
INSERT INTO team_members (full_name, team_id)
SELECT 'Navarro, Rex', id FROM teams WHERE name = 'RAAHR';
INSERT INTO team_members (full_name, team_id)
SELECT 'Pascua, Avril Lavigne', id FROM teams WHERE name = 'RAAHR';

INSERT INTO team_members (full_name, team_id)
SELECT 'Batara, Stephen Kyle B', id FROM teams WHERE name = 'Cen is Sored';
INSERT INTO team_members (full_name, team_id)
SELECT 'Bayot, Ryza L', id FROM teams WHERE name = 'Cen is Sored';
INSERT INTO team_members (full_name, team_id)
SELECT 'Rosell, Jamie', id FROM teams WHERE name = 'Cen is Sored';
INSERT INTO team_members (full_name, team_id)
SELECT 'Santos, Milan Franco L', id FROM teams WHERE name = 'Cen is Sored';
INSERT INTO team_members (full_name, team_id)
SELECT 'Suarez, Alyssa Mae G', id FROM teams WHERE name = 'Cen is Sored';

INSERT INTO team_members (full_name, team_id)
SELECT 'Mejia, Charles Adrian', id FROM teams WHERE name = 'MATCHALAP';
INSERT INTO team_members (full_name, team_id)
SELECT 'Menciano, Cecille', id FROM teams WHERE name = 'MATCHALAP';
INSERT INTO team_members (full_name, team_id)
SELECT 'Tolento, Jayvelyn', id FROM teams WHERE name = 'MATCHALAP';
INSERT INTO team_members (full_name, team_id)
SELECT 'Velasquez, Leo', id FROM teams WHERE name = 'MATCHALAP';
INSERT INTO team_members (full_name, team_id)
SELECT 'Viterbo, Archie', id FROM teams WHERE name = 'MATCHALAP';

INSERT INTO team_members (full_name, team_id)
SELECT 'Legaspi, Ron Michael', id FROM teams WHERE name = 'SCUBRAZIL';
INSERT INTO team_members (full_name, team_id)
SELECT 'Lola, Kelly Rowland', id FROM teams WHERE name = 'SCUBRAZIL';
INSERT INTO team_members (full_name, team_id)
SELECT 'Manlogon, Prince Emir', id FROM teams WHERE name = 'SCUBRAZIL';
INSERT INTO team_members (full_name, team_id)
SELECT 'Martinez, John Edrian', id FROM teams WHERE name = 'SCUBRAZIL';
INSERT INTO team_members (full_name, team_id)
SELECT 'Junio, Carl AJ', id FROM teams WHERE name = 'SCUBRAZIL';

INSERT INTO categories (name, round_type, display_order) VALUES
('Cybersecurity Fundamentals', 'elimination', 1),
('Identity and Access Security', 'elimination', 2),
('Digital Privacy and Safety Awareness', 'elimination', 3),
('Ethical Hacking and CTF Concepts', 'elimination', 4),
('Security Defense Practices', 'elimination', 5),
('Final Jeopardy', 'final', 6);

-- Category 1: Cybersecurity Fundamentals
INSERT INTO questions (category_id, points, question, answer) VALUES
(1, 100, 'Name one reason organizations regularly back up their data.', 'To recover from data loss, such as a ransomware attack'),
(1, 200, 'What term describes the practice of protecting systems, networks, and data from digital attacks?', 'Cybersecurity'),
(1, 300, 'What is the term for a weakness in a system that attackers can exploit?', 'A vulnerability'),
(1, 400, 'Which security principle ensures information is only accessible to authorized users?', 'Confidentiality'),
(1, 500, 'Name the security triad often abbreviated as the CIA triad.', 'Confidentiality, Integrity, and Availability');

-- Category 2: Identity and Access Security
INSERT INTO questions (category_id, points, question, answer) VALUES
(2, 100, 'Name a common weak password many people still use.', '"123456" or "password"'),
(2, 200, 'What security method requires two or more verification steps to log in?', 'Multi-factor authentication (MFA)'),
(2, 300, 'What is it called when an attacker guesses many password combinations until one works?', 'A brute-force attack'),
(2, 400, 'What is it called when someone reuses the same password across multiple sites?', 'Password reuse'),
(2, 500, 'Name a tool or method used to securely store and generate passwords.', 'A password manager');

-- Category 3: Digital Privacy and Safety Awareness
INSERT INTO questions (category_id, points, question, answer) VALUES
(3, 100, 'What should you check before clicking a link in an unexpected email?', 'The sender''s address and the actual link URL'),
(3, 200, 'Name one setting you should review regularly on social media accounts.', 'Privacy settings'),
(3, 300, 'What is the term for tricking someone into revealing personal information online?', 'Phishing'),
(3, 400, 'What kind of personal information should you avoid sharing publicly online?', 'Information like your home address or ID numbers'),
(3, 500, 'What does a VPN do to protect your privacy on public networks?', 'It encrypts your internet traffic');

-- Category 4: Ethical Hacking and CTF Concepts
INSERT INTO questions (category_id, points, question, answer) VALUES
(4, 100, 'What do the letters CTF stand for in cybersecurity competitions?', 'Capture The Flag'),
(4, 200, 'What is the term for a hacker who tests systems with permission to find vulnerabilities?', 'An ethical hacker (white-hat hacker)'),
(4, 300, 'Name a popular tool used for scanning networks for open ports.', 'Nmap'),
(4, 400, 'What is the practice called where testers simulate real attacks to find weaknesses?', 'Penetration testing'),
(4, 500, 'What must ethical hackers obtain in writing before testing a system?', 'Authorization / a signed scope of engagement');

-- Category 5: Security Defense Practices
INSERT INTO questions (category_id, points, question, answer) VALUES
(5, 100, 'What should you regularly install to patch security holes in software?', 'Software updates'),
(5, 200, 'Name a type of software that protects against malicious programs.', 'Antivirus software'),
(5, 300, 'What practice limits users to only the access they need to do their job?', 'The principle of least privilege'),
(5, 400, 'What network device filters incoming and outgoing traffic based on rules?', 'A firewall'),
(5, 500, 'What is the process of scrambling data so unauthorized users cannot read it?', 'Encryption');

-- Final Jeopardy (used in the Last 2 Standing round)
INSERT INTO questions (category_id, points, question, answer) VALUES
(6, 0, 'This OWASP Top 10 vulnerability lets attackers inject malicious database commands through unvalidated input fields.', 'SQL Injection');

-- CTF resolution challenges (flags stored as sha256 hashes only)
-- Challenge 1 flag (plain, for reference during setup): FLAG{CIPHER_MASTER}
-- Challenge 2 flag (plain, for reference during setup): FLAG{DECODE_ME_PLEASE}
INSERT INTO ctf_challenges (title, prompt, flag_hash, hint, duration_seconds) VALUES
('Caesar''s Cipher',
 'Decode this message, shifted by 3 letters, then submit it in the exact format shown:\nIODJ{FLSKHU_PDVWHU}',
 '2ce02c120827a28d8e471868577bd2a922e14d8d1218ee4b3c148ee1a6655297',
 'Shift every letter backward by 3 positions in the alphabet.',
 180),
('Encoded Transmission',
 'This message was intercepted in Base64. Decode it and submit the result exactly as shown:\nRkxBR3tERUNPREVfTUVfUExFQVNFfQ==',
 'f01e17d1baf583556eedf9df2666fb8023690df3ff3900913e298c1716991e41',
 'Base64 alphabets only use A-Z, a-z, 0-9, +, / and pad with =.',
 180);
