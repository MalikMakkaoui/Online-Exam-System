<?php

$servername = 'sql1.njit.edu';
$username = 'mm693';
$password = 'Stepbrothers1!';
$dbname = 'mm693';
$role;
$SID;

#$array = array();
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn ->connect_error){
	die("Connection failed: " . $conn->connect_error);
}


$data = json_decode(file_get_contents('php://input'), true);


$sql = "";
$result = "";
$array = array();
if (isset($data['password'])){
	

	$sql = "SELECT * FROM Person";
	$result = $conn->query($sql);
	while($row = $result->fetch_assoc()){
		
		if($row['username'] == $data['username'] && $row['password'] == md5($data['password'])){
			$role = $row['role'];
			$SID = $row['userid'];
			break;
			}

		else{
			unset($role);
			unset($SID);
		}

	}
	$login = array('role' => $role, 'userid' => $SID);
	echo json_encode($login);
	

}


else{

	if ($data['Action'] == 'CreateQ'){

		#$TestCases = json_encode($data['TestCases']);
		#$Outputs = json_encode($data['TestCaseOutputs']);
		
		$TestCases = json_encode($data['TestCases'], JSON_HEX_APOS);
		$Outputs = json_encode($data['TestCaseOutputs'], JSON_HEX_APOS);
		$TestCases = json_encode($TestCases, JSON_HEX_APOS);
		$Outputs = json_encode($Outputs, JSON_HEX_APOS);
		#echo json_decode($TestCases);
		#echo json_decode($Outputs);
		$sql="INSERT INTO Qbank(QID) SELECT MAX(QID)+1 FROM Qbank;";

		$sql .= "UPDATE Qbank 
				SET Topic='{$data['Topic']}', Difficulty='{$data['Difficulty']}', Question='{$data['Question']}', FunctionName='{$data['FunctionName']}', TestCases={$TestCases}, TestCaseOutputs={$Outputs}, Constraints='{$data['Constraint']}'
				WHERE Topic IS NULL";


		$conn->multi_query($sql);
		

	}
	#WORKING
	else if ($data['Action'] == 'CreateT'){

		$sql = "CREATE TABLE {$data['TestName']} ( QID int, Topic varchar(50), Difficulty varchar(20), Question varchar(255), FunctionName varchar(50), TestCases varchar(255), Qpoints int, TestCaseOutputs varchar(255), Constraints varchar(20));";

		#$sql .= "INSERT INTO Test(TestName) VALUES('{$data['TestName']}')";
		#changing Test TAble format
		# CHANGING ORIGINAL

		$sql .= "ALTER TABLE Test ADD {$data['TestName']} varchar(50) ";

		$conn->multi_query($sql);
		

		echo $data['TestName'];

	}
	#WORKS
	else if ($data['Action'] == 'AddQ'){

		$array = array_keys($data['QIDs']);
		$arrayQpoints = array_values($data['QIDs']);

		for ($i = 0; $i < count($data['QIDs']); $i++){
		$sql .= "INSERT INTO {$data['TestName']}(QID, Topic, Difficulty, Question, FunctionName, TestCases, TestCaseOutputs,Constraints)
		SELECT QID, Topic, Difficulty, Question, FunctionName, TestCases,TestCaseOutputs,Constraints FROM Qbank
		WHERE QID = {$array[$i]};";
		
		$sql .= "UPDATE {$data['TestName']} SET Qpoints = {$arrayQpoints[$i]} WHERE QID = {$array[$i]};";
		
	}

	$conn->multi_query($sql);

	}
	#WORKING Loads all quesiton bank questions
	else if ($data['Action'] == 'LoadT'){



		$array = array();
		$array2 = array();
		$TotalArray = array();

		if($data['Keyword'] != ""){
			$sql = "SELECT * FROM Qbank WHERE Question LIKE '%{$data['Keyword']}%'";
			if($data['Difficulty'] != ""){
				$sql .= "AND Difficulty='{$data['Difficulty']}'";
				if($data['Topic'] != ""){
					$sql .= "AND Topic='{$data['Topic']}'";
					if($data['Constraint'] != ""){
						$sql .= "AND Constraints='{$data['Constraint']}'";
					}
				}
			}	
		}
		
		else if($data['Difficulty'] != ""){
			$sql = "SELECT * FROM Qbank WHERE Difficulty='{$data['Difficulty']}'";
			if($data['Topic'] != ""){
				$sql .= "AND Topic='{$data['Topic']}'";
				if($data['Constraint'] != ""){
					$sql .= "AND Constraints='{$data['Constraint']}'";
				}
			}
		}

		else if($data['Topic'] != ""){
			$sql = "SELECT * FROM Qbank WHERE Topic='{$data['Topic']}'";
			if($data['Constraint'] != ""){
				$sql .= "AND Constraints='{$data['Constraint']}'";
			}
		}
		else if($data['Constraint'] != ""){
			$sql = "SELECT * FROM Qbank WHERE Constraints='{$data['Constraint']}'";
		}
		else{
			$sql = "SELECT * FROM Qbank";
		}
		
		
		
		$result = $conn->query($sql);
	
		while($row = $result->fetch_assoc()){
				
			foreach ($row as $value){
				if (in_array($row['Question'], $array)){
					continue;
				}
				array_push($array, $row['Question']);
				array_push($array2, $row['QID']);

			}
		}
		array_push($TotalArray, $array);
		array_push($TotalArray, $array2);
		echo json_encode($TotalArray);



		
	}
	#WORKS
	else if($data['Action'] == 'TakeT'){

		$array = array();

		$sql = "DROP TABLE IF EXISTS {$data['SID']}{$data['TestName']};";

		$sql .= "CREATE TABLE {$data['SID']}{$data['TestName']} ( QID int, Topic varchar(50), Difficulty varchar(20), Question varchar(255), FunctionName varchar(50), TestCases varchar(255), FunctionStudent varchar(50), OutputStudent varchar (50),Corrections varchar(500), Comments varchar(255), SAnswer varchar(255), TestCaseOutputs varchar(255), Constraints varchar(20), Qpoints int, Score int, CorrectionsBool varchar(255), CorrectionsPoints varchar(255), CorrectionsPointsEarned varchar(255));";

		#$sql .= "INSERT INTO Students(SID) VALUES('{$data['SID']}')";
		# changing original

		#$sql .= "REPLACE INTO Test({$data['TestName']}) VALUES('{$data['SID']}');";
		$sql .= "DELETE FROM Test WHERE {$data['TestName']} = '{$data['SID']}';";
		$sql .= "INSERT INTO Test({$data['TestName']}) VALUES('{$data['SID']}');";
		#$sql .= "UPDATE Test Set {$data['TestName']} = '{$data['SID']}' WHERE {$data['TestName']};";
		

		$conn->multi_query($sql);
		
	}

	else if($data['Action'] == 'PopulateT'){


		$array = array();
		$array2 = array();
		$array3 = array();
		$TotalArray = array();
		

		$sql = "SELECT * FROM {$data['TestName']}";

		
		$result = $conn->query($sql);
		
		while($row = $result->fetch_assoc()){
			#echo '2';
			foreach($row as $value){
				if (in_array($row['Question'], $array) || in_array($row['QID'], $array2)){
					continue;
				}
				
				array_push($array2, $row['QID']);
				array_push($array, $row['Question']);
				array_push($array3, $row['Qpoints']);
			}
		}
		array_push($TotalArray,$array2);
		array_push($TotalArray,$array);
		array_push($TotalArray, $array3);
		echo json_encode($TotalArray);


	}

	#NOT BEING USED

	else if ($data['Action'] == 'ReviewT'){
		$array = array();

		$sql = "SELECT * FROM {$data['SID']}";
		#$sql =  "SELECT CONCAT(QID, ' ', Topic, ' ', Difficulty, ' ', Question,' ', OutputStudent,' ', Qpoints) AS Question FROM {$data['TestName']}";
		$result = $conn->query($sql);
		while($row = $result->fetch_assoc()){

			foreach ($row as $value) {
				#array_push($array, $row['QID'], $row['Topic'], $row['Difficulty'] $row['Question'], $row['OutputStudent'], $row['Qpoints']);
				array_push($array, $row['Question']);
			}

		}
		echo json_encode($array);
	}

	else if ($data['Action'] == 'UpdateT'){

		$CorrectionsPointsEarned = json_encode($data['CorrectionsPointsEarned']);

		$sql = "UPDATE {$data['SID']}{$data['TestName']}
				SET Comments = '{$data['Comments']}', CorrectionsPointsEarned='{$CorrectionsPointsEarned}', Score={$data['Score']}
				WHERE QID = {$data['QID']}";
		$conn->query($sql);
		
	}
	

#WORKING
	else if ($data['Action'] == 'ShowT')
	{


		$array = array();
		
		#$sql = "SELECT * FROM Test";
		#CHANGING ORIGINAL
		$sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='Test'";


		$result = $conn->query($sql);
		while($row = $result->fetch_assoc()){

			foreach ($row as $value) {
				if($row['COLUMN_NAME'] == 'name'){
					continue;
				}
				
				array_push($array, $row['COLUMN_NAME']);
			}

		}
		echo json_encode($array);


		
	}


	else if ($data['Action'] == 'SubmitT'){

		

		$sql = "INSERT INTO {$data['SID']}{$data['TestName']} (QID, Topic, Difficulty, Question, FunctionName, TestCases, TestCaseOutputs,Constraints,Qpoints)
		SELECT QID, Topic, Difficulty, Question, FunctionName,TestCases, TestCaseOutputs,Constraints, Qpoints 
			FROM {$data['TestName']}
			WHERE QID = {$data['QIDs']};";
		

		
		
		$sql .= "UPDATE {$data['SID']}{$data['TestName']} SET SAnswer = '{$data['Answers']}' WHERE QID = {$data['QIDs']}";

		
		

		$conn->multi_query($sql);


	}
	else if ($data['Action'] == 'AutoGrade'){


		#$sql = "SELECT QID, FunctionName, Qpoints, SAnswer
		#		FROM {$data['SID']}";

		$sql = "SELECT * FROM {$data['SID']}{$data['TestName']}";

		$result = $conn->query($sql);
		
		while($row = $result->fetch_assoc()){
			
		
			foreach($row as $value){
				

				$autograde = array("QIDs"=>"{$row['QID']}", "FunctionName"=>$row['FunctionName'], "TestCases"=>json_decode($row['TestCases']), "TestCaseOutputs" => json_decode($row['TestCaseOutputs']), "Qpoints" =>"{$row['Qpoints']}", "Constraint"=>$row['Constraints']);
			}
		}

		echo json_encode($autograde, JSON_HEX_APOS);



	}

	else if ($data['Action'] == 'GetScore'){

		$qid = $data['QIDs'];
		$Corrections = json_encode($data['Corrections'], JSON_HEX_APOS);
		$Corrections = json_encode($Corrections, JSON_HEX_APOS);
		$CorrectionsBool = json_encode($data['CorrectionsBool']);
		$CorrectionsPoints = json_encode($data['CorrectionsPoints']);
		
		
		#$sql = "UPDATE {$data['SID']}{$data['TestName']} SET Score={$data['Score']}, Corrections='{$Corrections}', CorrectionsBool='{$CorrectionsBool}', CorrectionsPoints='{$CorrectionsPoints}' WHERE QID = {$qid}";

		$sql = "UPDATE {$data['SID']}{$data['TestName']} SET Score={$data['Score']}, Corrections='{$Corrections}', CorrectionsBool='{$CorrectionsBool}', CorrectionsPoints='{$CorrectionsPoints}' WHERE QID = {$qid}";

		$conn->query($sql);
	}



	else if ($data['Action'] == 'ShowS')
	{
		$array = array();
		
		#$sql = "SELECT * FROM Students";
		#CHANGING ORIGINAL BECUASE OF NEW TEST TABLE FORMAT
		$sql = "SELECT {$data['TestName']} FROM Test";


		$result = $conn->query($sql);
		while($row = $result->fetch_assoc()){

			foreach ($row as $value) {

				if(!isset($row["{$data['TestName']}"])){
					continue;
				}
				array_push($array, $row["{$data['TestName']}"]);
			}

		}
		echo json_encode($array);
		
	}

	else if ($data['Action'] == 'SendAnswer'){

		
		

		$sql = "SELECT * FROM {$data['SID']}{$data['TestName']}
				WHERE QID = {$data['QID']}";

		$result = $conn->query($sql);
		while($row = $result->fetch_assoc()){


			$array = array("Comments"=>$row['Comments'], "Corrections"=>$row['Corrections'], "SAnswer"=>$row['SAnswer'], "Score" => "{$row['Score']}", "Qpoints" =>"{$row['Qpoints']}", "QID"=>"{$row['QID']}", "Topic" => $row['Topic'], "Difficulty" => $row['Difficulty'], "Question" => $row['Question'], "FunctionName" => $row['FunctionName'], "TestCases"=>json_decode($row['TestCases']), "TestCaseOutputs" => json_decode($row['TestCaseOutputs']), "CorrectionsBool"=>$row['CorrectionsBool'], "CorrectionsPoints" => $row['CorrectionsPoints'], "CorrectionsPointsEarned" => $row['CorrectionsPointsEarned']);
			
		}

		
		echo json_encode($array);

		
	}

	else if ($data['Action'] == 'TotalScore'){


		$array = array();
		$array2 = array();
		$TotalArray = array();
		

		$sql = "SELECT * FROM {$data['SID']}{$data['TestName']}";

		
		$result = $conn->query($sql);
		
		while($row = $result->fetch_assoc()){
			
			array_push($array2, $row['Score']);
			array_push($array, $row['Qpoints']);
			
		}
		array_push($TotalArray,$array2);
		array_push($TotalArray,$array);
		echo json_encode($TotalArray);


	}

}

$conn->close();

?>