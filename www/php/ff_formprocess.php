<?php
header("Access-Control-Allow-Origin: *");

include '../../ffdb.php';

$type = $_POST['type'];

switch($type) {
	
	case 'create-user':
		$stmt = $conn->prepare("SELECT user_email FROM ff_user_list WHERE user_email = :email");
		$stmt->bindParam(":email",$_POST['email']);
		$stmt->execute();
		
		$num = $stmt->rowCount();
		
		if($num == 0) {
			
			$conn->beginTransaction();
			
			$stmt1 = $conn->prepare("INSERT INTO ff_user_list (user_email, user_pass, user_hash) VALUES (:user_email, :user_pass, :user_hash)");
			$stmt1->bindParam(":user_email", $_POST['email']);
			
			$password = $_POST['pass1'];
			$passhash = hash('sha256', $password, false);
			$stmt1->bindParam(":user_pass", $passhash);
			
			$sessionhash = $_POST['email'].uniqid();
			$sesshash = hash('sha256', $sessionhash, false);
			$stmt1->bindParam(":user_hash", $sesshash);
			
			try {
				if($stmt1->execute()) {
					$conn->commit(); 
					$result['success'] = 1;
					$result['hash'] = $sesshash;
				} else {
					$conn->rollBack();
					$result['success'] = 0;
					$result['msg'] = "Account creation unsuccessful. Ensure all fields are appropriately filled.";
				}
				
			} catch(Exception $e) {
				$conn->rollBack();
				$result['success'] = 0;
				$result['msg'] = "Account creation unsuccessful. Ensure all fields are appropriately filled." . $e->getMessage();
			}
			
		} else {
			// User exists so we should return unsuccessful === 0
			$result['success'] = 0;
			$result['msg'] = "Email already used for an account";
		}
		break;
		
		
	case 'login-user':
		
		$password = $_POST['pass1'];
		$passhash = hash('sha256', $password, false);
		
		$stmt = $conn->prepare("SELECT user_id FROM ff_user_list WHERE user_email = :email AND user_pass = :user_pass");
		
		$stmt->bindParam(":user_pass", $passhash);
		$stmt->bindParam(":email", $_POST['email']);
		$stmt->execute();
		
		$num = $stmt->rowCount();
		
		if($num == 0) {
			// Either email or password doesn't match
			$result['success'] = 0;
			$result['msg'] = "Invalid Email or Password!";
		} else {
			// Continue login process
			$sessionhash = $_POST['email'].uniqid();
			$sesshash = hash('sha256', $sessionhash, false);
			
			$res = $stmt->fetchall(PDO::FETCH_ASSOC);
			
			$id = $res[0]['user_id'];
			
			$stmt = $conn->prepare("UPDATE ff_user_list SET user_hash=:user_hash WHERE user_id = :user_id");
			
			$stmt->bindParam(":user_id", $id);
			$stmt->bindParam(":user_hash", $sesshash);
			
			if($stmt->execute()) {
				$result['success'] = 1;
				$result['hash'] = $sesshash;
			} else {
				$result['success'] = 0;
			}
		}
		
		break;
		
		
	case 'change-pass':
	
		$stmt = $conn->prepare("UPDATE ff_user_list SET user_pass = :pass_hash WHERE user_hash = :user_hash");
		$password = $_POST['newPass'];
		$passhash = hash('sha256', $password, false);
		$stmt->bindParam(":pass_hash", $passhash);
		$stmt->bindParam(":user_hash", $_POST['hash']);
		
		if($stmt->execute()) {
			$result['success'] = 1;
		} else {
			$result['success'] = 0;
		}
	
		break;
	
	
	case 'populate-settings-form':
		
		$stmt = $conn->prepare("SELECT user_email FROM ff_user_list WHERE user_hash = :user_hash");
		$stmt->bindParam(":user_hash",$_POST['hash']);
		$stmt->execute();
		
		$result = $stmt->fetch(PDO::FETCH_ASSOC);
		
		if($result) {
			$user_email = $result['user_email']; 
			$result['success'] = 1;
		} else {
			$user_email = null; 
			$result['success'] = 0;
		}
	
		break;
		
		
	case 'save-program':
		$stmt = $conn->prepare("SELECT user_id FROM ff_user_list WHERE user_hash = :hash");
		$stmt->bindParam(":hash", $_POST['hash']);
		$stmt->execute();
		
		$result = $stmt->fetch(PDO::FETCH_ASSOC);
		
		if($result) {
			$user_id = $result['user_id']; 
		} else {
			$result['success'] = 0;
			$result['msg'] = "An unexpected error occurred.";
			break;
		} 
		
		$stmt = $conn->prepare("SELECT program_id FROM ff_programs WHERE user_id = :user_id AND name = :program_name");
		$stmt->bindParam(":user_id", $user_id);
		$stmt->bindParam(":program_name", $_POST['programName']);
		$stmt->execute();
		
		$result = $stmt->fetch(PDO::FETCH_ASSOC);
		$num = $stmt->rowCount();
		
		if($num == 0) {
			$program_exists = false;
		} else {
			$program_exists = true;
			$program_id = $result['program_id'];
		}
		
		
		$program_name = $_POST['programName'];
		$exercises = json_decode($_POST['exercises'], true);

		if(empty($exercises)) {
			$result['success'] = 0;
			$result['msg'] = "No exercises submitted.";
			break;
		}
		
		
		if(!$program_exists) {
			$stmt = $conn->prepare("INSERT INTO ff_programs (user_id, name) VALUES (:user_id, :name)");
			$stmt->bindParam(":user_id", $user_id);
			$stmt->bindParam(":name", $program_name);
			$stmt->execute();
			$program_id = $conn->lastInsertId();
		}
		
		if($program_exists) {
			$stmt = $conn->prepare("DELETE FROM ff_exercises WHERE program_id = :program_id");
			$stmt->bindParam(":program_id", $program_id);
			$stmt->execute();
		}
		
		$order = 1;
		$stmt = $conn->prepare("
			INSERT INTO ff_exercises 
			(program_id, program_order, name, sets, reps, last_weight, rest_time_index, notes)
			VALUES (:program_id, :program_order, :name, :sets, :reps, :last_weight, :rest_time_index, :notes)
		");
		
		foreach($exercises as $ex) {
			$stmt->bindParam(':program_id', $program_id);
			$stmt->bindParam(':program_order', $order);
			$stmt->bindParam(':name', $ex['name']);
			$stmt->bindParam(':sets', $ex['sets']);
			$stmt->bindParam(':reps', $ex['reps']);
			$stmt->bindParam(':last_weight', $ex['weight']);
			$stmt->bindParam(':rest_time_index', $ex['restTime']);
			$stmt->bindParam(':notes', $ex['notes']);
			$stmt->execute();
			$order++;
		}
		
		$result['success'] = 1;
		$result['msg'] = "Program saved successfully!";
		break;
		
	
	case 'get-programs':
		$stmt = $conn->prepare("SELECT user_id FROM ff_user_list WHERE user_hash = :hash");
		$stmt->bindParam(":hash", $_POST['hash']);
		$stmt->execute();
		$result = $stmt->fetch(PDO::FETCH_ASSOC);
		$user_id = $result['user_id'];
		
		$stmt = $conn->prepare("SELECT name FROM ff_programs WHERE user_id = :user_id");
		$stmt->bindParam(":user_id", $user_id);
		$stmt->execute();
		$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		
		$result['programs'] = $res;
		$result['success'] = 1;
		break;
		
	
	case 'get-program-by-name':
		// Get user_id
		$stmt = $conn->prepare("SELECT user_id FROM ff_user_list WHERE user_hash = :hash");
		$stmt->bindParam(":hash", $_POST['hash']);
		$stmt->execute();
		$res = $stmt->fetch(PDO::FETCH_ASSOC);
		$user_id = $res['user_id'];
	
		// Get program_id
		$stmt = $conn->prepare("SELECT program_id FROM ff_programs WHERE user_id = :user_id AND name = :program_name");
		$stmt->bindParam(":user_id", $user_id);
		$stmt->bindParam(":program_name", $_POST['programName']);
		$stmt->execute();
		$program = $stmt->fetch(PDO::FETCH_ASSOC);
		$program_id = $program['program_id'];
	
		// Get exercises
		$stmt = $conn->prepare("SELECT * FROM ff_exercises WHERE program_id = :program_id ORDER BY program_order ASC");
		$stmt->bindParam(":program_id", $program_id);
		$stmt->execute();
		$exercises = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		
		$result['success'] = 1;
		$result['program_id'] = $program_id;
		$result['exercises'] = $exercises;
		
		break;
}
print json_encode($result);
?>