USE final_project;


SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE TABLE football_teams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    team_name VARCHAR(255) NOT NULL,
    stadium VARCHAR(255) NOT NULL,
    logo VARCHAR(255) NULL
);

CREATE TABLE football_matches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    team_id INT NOT NULL,
    opponent_team_id INT NOT NULL,
    match_score VARCHAR(50) NOT NULL,
    stadium VARCHAR(255) NOT NULL,
    match_date DATE NOT NULL,
    FOREIGN KEY (team_id) REFERENCES football_teams(id),
    FOREIGN KEY (opponent_team_id) REFERENCES football_teams(id)
);


CREATE TABLE users (
  id int(11) NOT NULL AUTO_INCREMENT,
  username varchar(100) NOT NULL UNIQUE,
  password varchar(255) NOT NULL,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
