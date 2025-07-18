<?php

require 'TDScoreUtility.php';


//test 1 

$testData = json_decode(file_get_contents('testdata1_abtract.json'), true);
// // Define parameters for TDScoreUtility
$column = 5;
$row = 4;
$count = 20; // Constructor expects 'count'
$viewConfig = [];
$correctScore = 3;



// Instantiate TDScoreUtility
$scoreUtility = new TDScoreUtility($column, $row, $count, $viewConfig);

// Calculate the score assuming it's number/card data
// Pass the entire $testData array which includes the 'items' key
// Set isReCalculateValueCorrect to true if you want the utility to re-evaluate correctness based on input/value pairs
$isReCalculateValueCorrect = true;
$scoresResult = $scoreUtility->scoreImage($testData, $isReCalculateValueCorrect);

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
