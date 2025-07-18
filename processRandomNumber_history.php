<?php

require 'TDScoreUtility.php';

//$testData = require 'randomNumber.php';
// read and parse the json file
$testData = json_decode(file_get_contents('testdata1_history.json'), true);



// Define parameters for TDScoreUtility
$column = 1;
$row = 20;
$count = 20; // Constructor expects 'count'
$viewConfig = [];



// Instantiate TDScoreUtility
$scoreUtility = new TDScoreUtility($column, $row, $count, $viewConfig);

// Calculate the score assuming it's number/card data
// Pass the entire $testData array which includes the 'items' key
// Set isReCalculateValueCorrect to true if you want the utility to re-evaluate correctness based on input/value pairs
$isReCalculateValueCorrect = true;
$scoresResult = $scoreUtility->scoreHistory($testData, $isReCalculateValueCorrect);

// Output the result
echo "Score Calculation Result:\n";
print_r($scoresResult);

echo "\n\nProcessed Data with Correctness Flags:\n";
// The $testData array might be modified by reference inside scoreNumberOrCard if isReCalculateValueCorrect is true
// print_r($testData);

$mapsData = [
    '0' => [ 
        "input1" => "11"
    ],
    '1' => [
        "input1" => "222"
    ]
];

{
    $abc = &$mapsData['0'];

    echo "abc: ".$abc['input1']."\n";
    echo "mapsData_0: ".$mapsData['0']['input1']."\n";
    echo "mapsData_1: ".$mapsData['1']['input1']."\n";
}

{
    $abc = $mapsData['1'];

    echo "abc: ".$abc['input1']."\n";
    echo "mapsData_0: ".$mapsData['0']['input1']."\n";
    echo "mapsData_1: ".$mapsData['1']['input1']."\n";
}

function counter() {
    $mapsData2 = [
        '0' => [ 
            "input1" => "11"
        ],
        '1' => [
            "input1" => "222"
        ]
    ];
    $count = &$mapsData2['0'];
    echo "Count: " . $count . "\n";
}


counter(); // Count: 1
counter(); // Count: 2
counter(); // Count: 3

?>
