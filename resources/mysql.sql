CREATE TABLE IF NOT EXISTS player_stats (
  name VARCHAR(255) PRIMARY KEY,
  breaks INT DEFAULT 0,
  places INT DEFAULT 0,
  deaths INT DEFAULT 0,
  kicked INT DEFAULT 0,
  drops INT DEFAULT 0,
  joins INT DEFAULT 0,
  quits INT DEFAULT 0,
  chats INT DEFAULT 0,
  kills INT DEFAULT 0
);
