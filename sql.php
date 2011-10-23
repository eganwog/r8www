<?php

require_once('sql_config.php');

class r8DB {
	private $conn;
	
	function __construct() {
		$this->conn = $this->connect();
	}

	function __destruct() {
		$this->closeConnection();
	}

	public function getLeaderboardData() {
		$query = "select s.initials, s.longname, s.score, r.createdAt, (select date_add(date(sr.createdAt),interval -1 day) from Scores ss join Runs sr on sr.id = ss.runId where ss.initials = s.initials  and ss.score = s.score order by sr.id limit 1) as 'setAt' from Scores s join Runs r on r.id=s.runId where s.runId = (select id from Runs order by id desc limit 1)";
		$sqlResult = $this->query($query);
		return $sqlResult;
	}

	public function getStatsData() {
		$query = "select s.lsc, s.msc, s.rsc, s.tc, s.eme, s.ptm, s.mp, s.cp from Stats s join Runs r on r.id= s.runId where s.runId = (select id from Runs order by id desc limit 1)";
		$sqlResult = $this->query($query);
		return $sqlResult;		
	}
	
	public function createRun() {
		$escName = $this->conn->real_escape_string($name);
		$escDesc = $this->conn->real_escape_string($desc);

		$query = "INSERT INTO Runs (createdAt) values (NOW())";
		$this->query($query);

		// Retrieve ID of newly inserted row and pass back to client.
		$query = "SELECT LAST_INSERT_ID() AS 'id'";
		$result = $this->query($query);

		$row = $result->fetch_assoc(); 
		$id = intval($row["id"]);
		
		return $id;
	}

	public function saveStats($runId, $stats) {
		$escRunId = $this->conn->real_escape_string($runId);
		/* array(8) { */
		/* 	["left slot coins"]=> */
		/* 	int(4) */
		/* 	["middle slot coins"]=> */
		/* 	int(0) */
		/* 	["right slot coins"]=> */
		/* 	int(0) */
		/* 	["total coins"]=> */
		/* 	int(4) */
		/* 	["extra men earned"]=> */
		/* 	int(12) */
		/* 	["play time in minutes"]=> */
		/* 	int(6) */
		/* 	["men played"]=> */
		/* 	int(24) */
		/* 	["credits played"]=> */
		/* 	int(4) */
		/* 	} */
		$query = "INSERT INTO Stats (lsc, msc, rsc, tc, eme, ptm, mp, cp, runId) VALUES ({$stats['left slot coins']}, {$stats['middle slot coins']}, {$stats['right slot coins']}, {$stats['total coins']}, {$stats['extra men earned']}, {$stats['play time in minutes']}, {$stats['men played']}, {$stats['credits played']}, $runId)";
		$result = $this->query($query);
		
		return $result;		
	}

	public function saveScores($runId, $scores) {
		$escRunId = $this->conn->real_escape_string($runId);

		foreach ($scores as $score) {
			$query = "INSERT INTO Scores (initials, longname, score, runId) VALUES ('{$score['name']}', '{$score['longname']}', '{$score['score']}', $runId)";
			$result = $this->query($query);
		}
		
		return $result;		
	}

	private function query($query) {	
		if (!$this->conn) {
			return false;
		}

		$result = $this->conn->query($query);
		if (empty($result)) {
			$errorStr = "R8: Failed to run query: $query, error: " . $this->conn->error;
			error_log($errorStr);
			throw new Exception($errorStr);
		}		
		
		return $result;		
	}

	private function connect() {
		$conn = mysqli_init();
		if (!$conn->real_connect(R8_DB_HOST, R8_DB_USER, R8_DB_PASS, R8_DB_NAME, 3306)) {
			$errorStr = "R8: Failed to connect to T8 DB, err no: " . mysqli_connect_errno();
			error_log($errorStr);
			$conn = false;
			throw new Exception($errorStr);
		}
		
		return $conn;
	}
	
	private function closeConnection() {
		if (!empty($this->conn)) {
			try {
				$this->conn->close();
			}
			catch (Exception $e) {
				error_log("R8: Failed to close connection, error: " . $e->getMessage());
			}
		}
	}
}