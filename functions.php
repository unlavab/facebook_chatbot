<?php

include 'db.ini.php';



function storeMessage($sentence, $senderId) {

	include 'db.ini.php';



	$respondSql= "SELECT * FROM messageretreat WHERE message = :respondId";

	$respondStmt = $pdo->prepare($respondSql);

	$respondStmt->bindParam(':respondId', $sentence, PDO::PARAM_INT);

	$respondStmt->execute();

	$respondRows = $respondStmt->fetchAll();

	$respondRowInt = count($respondRows);

	if ($respondRowInt == 0) {

		$sql = "INSERT INTO messageretreat (sender,

				message) VALUES (

				:firstValue, 

				:secondValue)";

											 

		$stmt = $pdo->prepare($sql);

		$request = mb_strtolower($sentence);

		$stmt->bindParam(':firstValue', $senderId, PDO::PARAM_STR);	

		$stmt->bindParam(':secondValue', $request, PDO::PARAM_STR);	



		if ($stmt->execute()) {

			return "bi suraltsaj avlaa";

		}

	} else {

		return "bi bodoj baina";

	}

}





function convertLatin($word) {

	$word = preg_replace('/[^\p{L}\p{N}\s]/u', '', $word);

	if (strlen($word) == strlen(utf8_decode($word))) {

		$latin = ['a'=>'а','b'=>'б','d'=>'д','e'=>'э','f'=>'ф','g'=>'г','j'=>'ж','l'=>'л','m'=>'м','n'=>'н','o'=>'о','p'=>'п','q'=>'к','r'=>'р','u'=>'у','v'=>'в','w'=>'в','x'=>'х','y'=>'у','z'=>'з'];

		$letters = str_split($word);

		$unicode = [];

		$after = 0;

		$before = 0;

		foreach ($letters as $letter) {

			$after++;

			$before++;

			switch ($letter) {

				case 'c':

					if ($letters[$after] == 'h') {

						array_push($unicode, 'ч');

					} else {

						array_push($unicode, 'c');

					}	

				break;



				case 'k':

					if ($letters[$after] == 'h') {

						array_push($unicode, 'х');

					} else {

						array_push($unicode, 'к');

					}	

				break;



				case 'i':

					if ($letters[$before] == 'a') {

						array_push($unicode, 'й');

					} elseif ($letters[$before] == 'u') {

						array_push($unicode, 'й');

					} elseif ($letters[$before] == 'o') {

						array_push($unicode, 'й');

					} elseif ($letters[$before] == 'y') {

						array_push($unicode, 'й');

					} elseif ($letters[$before] == 'i') {

						array_push($unicode, 'й');

					} elseif ($letters[$before] == 'e') {

						array_push($unicode, 'й');

					} else {

						array_push($unicode, 'и');

					}	

				break;



				case 's':

					if ($letters[$after] == 'h') {

						array_push($unicode, 'ш');

					} elseif ($letters[$before] == 't') {

						

					} else {

						array_push($unicode, 'с');

					}	

				break;



				case 't':

					if ($letters[$after] == 's') {

						array_push($unicode, 'ц');

					} else {

						array_push($unicode, 'т');

					}	

				break;



				case 'h':

					if ($letters[$before] == 'c') {

						

					} elseif ($letters[$before] == 'k') {

						

					} elseif ($letters[$before] == 's') {

						

					} else {

						array_push($unicode, 'х');

					}

				break;

				

				default:

					array_push($unicode, $latin[$letter]);

					break;

			}

			$before--;

		}

		return implode("",$unicode);

	} else {

		return $word;

	}

}



function converSlang($word) {

	include 'db.ini.php';

	$sql= "SELECT * FROM spellcheck WHERE latin = '".$word."' LIMIT 1";

	$stmt = $pdo->query($sql); 

	$row = $stmt->fetch(PDO::FETCH_ASSOC);

	if (isset($row['unicode'])) {

		return $row['unicode'];

	} else {

		return $word;

	}

}



function suggestWord($word) {

	include 'db.ini.php';

	$n = mb_strlen($word) - 1;

	for ($i=$n; $i >=0  ; $i--) {

		$similarWord = mb_substr($word, 0, $i)."%";

		$sql= "SELECT * FROM spellcheck WHERE unicode LIKE :sentenceWord LIMIT 1";

		$stmt = $pdo->prepare($sql);

		$stmt->bindParam(':sentenceWord', $similarWord, PDO::PARAM_STR);

		$stmt->execute();

		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		if($row['unicode']) {

			return "%".$row['unicode'];

			break;

		}

	}

}



function spellChecker($word) {

	include 'db.ini.php';

	$unicode = convertLatin($word);

	$converted = converSlang($unicode);

	$sql= "SELECT * FROM spellcheck WHERE unicode = :sentenceWord";

	$stmt = $pdo->prepare($sql);

	$stmt->bindParam(':sentenceWord', $converted, PDO::PARAM_STR);

	$stmt->execute();

	$total = $stmt->rowCount();

	if ($total == 0) {

		return suggestWord($converted);

	} else {

		return $converted;

	}

}





function splitWords($sentence)

{

	$sentenceRaw = explode(" ", mb_strtolower($sentence));

	$fixedSentence = [];

	foreach ($sentenceRaw as $sentenceSingleWord) {

		$final = spellChecker($sentenceSingleWord);

		array_push($fixedSentence, $final);

	}



	return $fixedSentence;



}



function answerQuestion($sentence, $senderId)

{

	include 'db.ini.php';

	$sql= "SELECT * FROM messagerespond WHERE message = '".$sentence."' LIMIT 1";

	$stmt = $pdo->query($sql); 

	$row = $stmt->fetch(PDO::FETCH_ASSOC);

	$rowCount = count($row);

	if ($rowCount == 0) {

		return false;

	} else {

		return $row['respond'];

	}

}



function respondMessage($message, $senderId)

{

	include 'db.ini.php';



	$sentence = implode(" ", splitWords($message));

    if(answerQuestion($sentence, $senderId)) {

    	$respond = answerQuestion($sentence, $senderId);
    	$status = 1;

    } else {

    	$respond = storeMessage($sentence, $senderId);
    	$status = 0;

    }

    $sql = "INSERT INTO messages (sender, message, created_at, updated_at) VALUES (:firstValue, :secondValue, :thirdValue, :fourthValue)";

	$stmt = $pdo->prepare($sql);

	$currentDate = date("Y/m/d h:i:sa");

	$stmt->bindParam(':firstValue', $senderId, PDO::PARAM_STR);	

	$stmt->bindParam(':secondValue', $sentence, PDO::PARAM_STR);	

	$stmt->bindParam(':thirdValue', $currentDate, PDO::PARAM_STR);	

	$stmt->bindParam(':fourthValue', $status, PDO::PARAM_STR);

	if ($stmt->execute()) {
		$answer = [ 'text' => $respond ];
	}

	return $answer;

}

function relyMessage($message, $senderId)

{

	$sentence = implode(" ", splitWords($message));

    if(answerQuestion($sentence, $senderId)) {

    	$respond = answerQuestion($sentence, $senderId);

    } else {

    	$respond = storeMessage($sentence, $senderId);

    }

	return $respond;

}


?>