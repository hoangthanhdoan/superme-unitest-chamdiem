<?php

require 'TDScoreUtility.php';

//$testData = require 'randomNumber.php';
// read and parse the json file
// $testData = json_decode(file_get_contents('testdata1_nameface.json'), true);
// // Define parameters for TDScoreUtility
// $column = 3;
// $row = 3;
// $count = 16; // Constructor expects 'count'
// $viewConfig = [];
// $correctScore = 10;


//test 2 

// $testData = json_decode(file_get_contents('testdata2_nameface.json'), true);
// // // Define parameters for TDScoreUtility
// $column = 3;
// $row = 3;
// $count = 9; // Constructor expects 'count'
// $viewConfig = [];
// $correctScore = 3;

//test 1

$testData = json_decode(file_get_contents('test_phuckhao_randomwords.json'), true);
// // Define parameters for TDScoreUtility
$column = 20;
$row = 5;
$count = 400; // Constructor expects 'count'
$viewConfig = [];
$correctScore = 32;


// Instantiate TDScoreUtility
/** @var TDScoreUtility $scoreUtility */
$scoreUtility = new \Modules\Competitions\Services\Scores\TDScoreUtility($column, $row, $count, $viewConfig);

// Calculate the score for random words test
// Pass the entire $testData array which includes the 'items' key
// Set isReCalculateValueCorrect to true to re-evaluate correctness based on input/value pairs
$isReCalculateValueCorrect = true;
$scoresResult = $scoreUtility->scoreWord($testData, $isReCalculateValueCorrect);

if ($scoresResult['score'] != $correctScore) {
    echo "Cong thuc bi sai\n";
} else {
    echo "Cong thuc dung\n";
}

// Output the result
echo "Score Calculation Result:\n";
print_r($scoresResult);

echo "\n\nProcessed Data with Correctness Flags:\n";
// The $testData array might be modified by reference inside scoreNumberOrCard if isReCalculateValueCorrect is true
// print_r($testData);


?>
