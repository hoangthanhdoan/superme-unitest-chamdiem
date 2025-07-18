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

$testData = json_decode(file_get_contents('testdata1_nameface.json'), true);
// // Define parameters for TDScoreUtility
$column = 3;
$row = 3;
$count = 9; // Constructor expects 'count'
$viewConfig = [];
$correctScore = 6;



// Instantiate TDScoreUtility
$scoreUtility = new TDScoreUtility($column, $row, $count, $viewConfig);

// Calculate the score assuming it's number/card data
// Pass the entire $testData array which includes the 'items' key
// Set isReCalculateValueCorrect to true if you want the utility to re-evaluate correctness based on input/value pairs
$isReCalculateValueCorrect = true;
$scoresResult = $scoreUtility->scoreFace($testData, $isReCalculateValueCorrect);

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
