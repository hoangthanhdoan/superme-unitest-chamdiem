<?php

//namespace Modules\Competitions\Services\Scores;

class TDScoreUtility
{
    /**
     * @var int Column count
     */
    protected $column;

    /**
     * @var int Row count
     */
    protected $row;

    /**
     * @var int Count
     */
    protected $count;

    /**
     * @var array View configuration
     */
    protected $viewConfig;

    /**
     * @var array Champion score configuration
     */
    protected $championScoreConfig;

    /**
     * Constructor for TDScoreUtility
     *
     * @param int $column Column count
     * @param int $row Row count
     * @param int $count Count
     * @param array $viewConfig View configuration
     */
    public function __construct(int $column, int $row, int $count, array $viewConfig)
    {
        $this->column = $column;
        $this->row = $row;
        $this->count = $count;
        $this->viewConfig = $viewConfig;
    }

    public function setChampionScoreConfig(array $championScoreConfig)
    {
        $this->championScoreConfig = $championScoreConfig;
    }

    /**
     * Get the value of a cell based on row and column position
     *
     * @param int $r Row position
     * @param int $c Column position
     * @param array $questions Array of questions
     * @return mixed Value of the cell
     */
    public function valueOfCell(int $r, int $c, array $questions)
    {
        $idx = $r * $this->column + $c;
        if ($idx < count($questions)) {
            return $questions[$idx];
        }
        return null;
    }

    /**
     * Check if the answer item is empty
     *
     * @param mixed $answerItem The answer item to check
     * @return bool True if the answer is empty, false otherwise
     */
    public function checkAnswerEmpty($answerItem): bool
    {
        if (!isset($answerItem)) {
            return true;
        }

        if ((!isset($answerItem['input1']) || $answerItem['input1'] == "") &&
            (!isset($answerItem['input2']) || $answerItem['input2'] == "") &&
            (!isset($answerItem['input3']) || $answerItem['input3'] == "")) {
            return true;
        }


        return false;
    }

    /**
     * Check if a row is empty
     *
     * @param int $r Row index
     * @param array $questions Array of questions
     * @return bool True if the row is empty, false otherwise
     */
    public function checkEmptyRow(int $r, array $questions): bool
    {
        for ($c = 0; $c < $this->column; $c++) {
            $questionItemDict = $this->valueOfCell($r, $c, $questions);
            if (!$this->checkAnswerEmpty($questionItemDict)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Calculate score for a middle row
     *
     * @param int $r Row index
     * @param array $questions Array of questions
     * @return float|int Score for the middle row
     */
    public function scoreForMiddleRow(int $r, array $questions)
    {
        $score = 0;
        $answerCount = $this->column;

        for ($c = 0; $c < $this->column; $c++) {
            $questionItemDict = $this->valueOfCell($r, $c, $questions);
            if (isset($questionItemDict['correct']) && $questionItemDict['correct']) {
                $score++;
            } else if (is_object($questionItemDict) && isset($questionItemDict->correct) && $questionItemDict->correct) {
                $score++;
            }
        }

        if ($score == $answerCount) {
            return $score;
        }
        if ($score == $answerCount - 1) {
            return round($score / 2);
        }
        return 0;
    }

    /**
     * Calculate score for the end row
     *
     * @param int $r Row index
     * @param array $questions Array of questions
     * @return float|int Score for the end row
     */
    public function scoreForEndRow(int $r, array $questions)
    {
        $score = 0;
        $answerCount = 0;

        // Find the last non-empty cell in the row
        for ($c = min($this->count - $this->column * $r - 1, $this->column - 1); $c >= 0; $c--) {
            $questionItemDict = $this->valueOfCell($r, $c, $questions);

            if (!$this->checkAnswerEmpty($questionItemDict)) {
                $answerCount = $c + 1;
                break;
            }
        }

        // Calculate score for non-empty cells
        for ($c = 0; $c < $this->column; $c++) {
            $questionItemDict = $this->valueOfCell($r, $c, $questions);
            if (!$this->checkAnswerEmpty($questionItemDict)) {
                $isCorrect = false;

                if (is_array($questionItemDict) && isset($questionItemDict['correct'])) {
                    $isCorrect = $questionItemDict['correct'];
                } else if (is_object($questionItemDict) && isset($questionItemDict->correct)) {
                    $isCorrect = $questionItemDict->correct;
                }

                if ($isCorrect) {
                    $score++;
                }
            }
        }

        if ($score == $answerCount) {
            return $score;
        }
        if ($score == $answerCount - 1) {
            return round($answerCount / 2);
        }
        return 0;
    }

    /**
     * Calculate score for number or card test
     *
     * @param array $testData Test data array
     * @param bool $isReCalculateValueCorrect Whether to recalculate the value correctness
     * @return array Result containing score and correct count
     */
    public function scoreNumberOrCard(array $testData, bool $isReCalculateValueCorrect = false): array
    {
        $questions = $testData['items'] ?? [];
        $score = 0;
        $correctCount = 0;

        // Update correct values for each question
        for ($i = 0; $i < $this->count; $i++) {
            if (!isset($questions[$i])) {
                continue;
            }

            $re_questionItemDict = &$questions[$i];

            if ($isReCalculateValueCorrect) {
                $re_questionItemDict['isCorrect1'] = false;
                $re_questionItemDict['isCorrect2'] = false;

                if (isset($re_questionItemDict['value1'], $re_questionItemDict['input1'])) {
                    $re_questionItemDict['isCorrect1'] = ($re_questionItemDict['value1'] === $re_questionItemDict['input1']);
                }

                if (isset($re_questionItemDict['value2'], $re_questionItemDict['input2'])) {
                    $re_questionItemDict['isCorrect2'] = ($re_questionItemDict['value2'] === $re_questionItemDict['input2']);
                }
            }

            $re_questionItemDict['correct'] = $re_questionItemDict['isCorrect1'] ?? false;
            $re_questionItemDict['isCorrect'] = $re_questionItemDict['correct'];

            if ($re_questionItemDict['correct']) {
                $correctCount++;
            }
        }

        // Calculate score for rows
        $scoreRowDict = [];
        $r = $this->row - 1;

        // Score for the end row
        for (; $r >= 0; $r--) {
            if (!$this->checkEmptyRow($r, $questions)) {
                $scoreAtRow = $this->scoreForEndRow($r, $questions);
                $score += $scoreAtRow;
                $scoreRowDict[$r] = $scoreAtRow;
                $r--;
                break;
            }
        }

        // Score for middle rows
        for (; $r >= 0; $r--) {
            if (!$this->checkEmptyRow($r, $questions)) {
                $scoreAtRow = $this->scoreForMiddleRow($r, $questions);
                $score += $scoreAtRow;
                $scoreRowDict[$r] = $scoreAtRow;
            }
        }

        return [
            'score' => $score,
            'correctCount' => $correctCount,
            'scoreRowDict' => $scoreRowDict
        ];
    }

    /**
     * Calculate score for spoken or speed card test
     *
     * @param array $testData Test data array
     * @param bool $isReCalculateValueCorrect Whether to recalculate the value correctness
     * @return array Result containing score and correct count
     */
    public function scoreSpokenOrSpeedCard(array $testData, bool $isReCalculateValueCorrect = false): array
    {
        $questions = $testData['items'] ?? [];
        $score = 0;
        $correctCount = 0;

        // Update correct values for each question
        for ($i = 0; $i < $this->count; $i++) {
            if (!isset($questions[$i])) {
                continue;
            }

            $re_questionItemDict = &$questions[$i];

            if ($isReCalculateValueCorrect) {
                $re_questionItemDict['isCorrect1'] = false;
                $re_questionItemDict['isCorrect2'] = false;

                if (isset($re_questionItemDict['value1'], $re_questionItemDict['input1'])) {
                    $re_questionItemDict['isCorrect1'] = ($re_questionItemDict['value1'] === $re_questionItemDict['input1']);
                }

                if (isset($re_questionItemDict['value2'], $re_questionItemDict['input2'])) {
                    $re_questionItemDict['isCorrect2'] = ($re_questionItemDict['value2'] === $re_questionItemDict['input2']);
                }
            }

            $re_questionItemDict['correct'] = $re_questionItemDict['isCorrect1'] ?? false;
            $re_questionItemDict['isCorrect'] = $re_questionItemDict['correct'];

            if ($re_questionItemDict['correct']) {
                $correctCount++;
            }
        }

        // Calculate score - for spoken/speed card, score is the count of consecutive correct answers
        for ($i = 0; $i < $this->count; $i++) {
            if (!isset($questions[$i])) {
                break;
            }

            $questionItemDict = $questions[$i];

            if (!($questionItemDict['correct'] ?? false)) {
                break;
            } else {
                $score++;
            }
        }

        return [
            'score' => $score,
            'correctCount' => $correctCount
        ];
    }

    /**
     * Calculate score for word test
     *
     * @param array $testData Test data array
     * @param bool $isReCalculateValueCorrect Whether to recalculate the value correctness
     * @return array Result containing score and correct count
     */
    public function scoreWord(array $testData, bool $isReCalculateValueCorrect = false): array
    {
        $questions = $testData['items'] ?? [];
        $score = 0;
        $correctCount = 0;

        // Update correct values for each question
        for ($i = 0; $i < $this->count; $i++) {
            if (!isset($questions[$i])) {
                continue;
            }

            $re_questionItemDict = &$questions[$i];

            if ($isReCalculateValueCorrect) {
                $re_questionItemDict['isCorrect1'] = false;
                $re_questionItemDict['isCorrect2'] = false;

                if (isset($re_questionItemDict['value1'], $re_questionItemDict['input1'])) {
                    // For word comparison, we need case-insensitive comparison
                    $re_questionItemDict['isCorrect1'] = $this->compareStrings(
                        $re_questionItemDict['value1'],
                        $re_questionItemDict['input1']
                    );
                }

                if (isset($re_questionItemDict['value2'], $re_questionItemDict['input2'])) {
                    $re_questionItemDict['isCorrect2'] = $this->compareStrings(
                        $re_questionItemDict['value2'],
                        $re_questionItemDict['input2']
                    );
                }
            }

            $re_questionItemDict['correct'] = $re_questionItemDict['isCorrect1'] ?? false;
            $re_questionItemDict['isCorrect'] = $re_questionItemDict['correct'];

            if ($re_questionItemDict['correct']) {
                $correctCount++;
            }
        }

        // Calculate score for rows
        $scoreRowDict = [];
        $r = $this->row - 1;

        // Score for the end row
        for (; $r >= 0; $r--) {
            if (!$this->checkEmptyRow($r, $questions)) {
                $scoreAtRow = $this->scoreForEndRow($r, $questions);
                $score += $scoreAtRow;
                $scoreRowDict[$r] = $scoreAtRow;
                $r--;
                break;
            }
        }

        // Score for middle rows
        for (; $r >= 0; $r--) {
            if (!$this->checkEmptyRow($r, $questions)) {
                $scoreAtRow = $this->scoreForMiddleRow($r, $questions);
                $score += $scoreAtRow;
                $scoreRowDict[$r] = $scoreAtRow;
            }
        }

        return [
            'score' => $score,
            'correctCount' => $correctCount,
            'scoreRowDict' => $scoreRowDict
        ];
    }

    /**
     * Calculate score for history test
     *
     * @param array $testData Test data array
     * @param bool $isReCalculateValueCorrect Whether to recalculate the value correctness
     * @return array Result containing score and correct count
     */
    public function scoreHistory(array $testData, bool $isReCalculateValueCorrect = false): array
    {
        $questions = $testData['items'] ?? [];
        $score = 0;
        $correctCount = 0;

        // Update correct values for each question
        for ($i = 0; $i < $this->count; $i++) {
            if (!isset($questions[$i])) {
                continue;
            }

            $re_questionItemDict = &$questions[$i];

            $re_questionItemDict['isCorrect1'] = false;
            $re_questionItemDict['isCorrect2'] = false;

            if (isset($re_questionItemDict['value1'], $re_questionItemDict['input1'])) {
                $re_questionItemDict['isCorrect1'] = ($re_questionItemDict['value1'] == $re_questionItemDict['input1']);
            }

            if (isset($re_questionItemDict['value2'], $re_questionItemDict['input2'])) {
                $re_questionItemDict['isCorrect2'] = ($re_questionItemDict['value2'] == $re_questionItemDict['input2']);
            }

            $re_questionItemDict['correct'] = $re_questionItemDict['isCorrect1'] ?? false;
            $re_questionItemDict['isCorrect'] = $re_questionItemDict['correct'];

            if ($re_questionItemDict['correct']) {
                $correctCount++;
            }
        }

        // Calculate score for each non-empty question
        $scoreRowDict = [];

        for ($i = 0; $i < $this->count; $i++) {
            if (!isset($questions[$i])) {
                continue;
            }

            $questionItemDict = $questions[$i];

            if (!$this->checkAnswerEmpty($questionItemDict)) {
                if ($questionItemDict['correct'] ?? false) {
                    $score += 1;
                    $scoreRowDict[$i] = 1;
                } else {
                    $score -= 0.5;
                    $scoreRowDict[$i] = -0.5;
                }
            }
        }

        return [
            'score' => max(0, round($score)),
            'correctCount' => $correctCount,
            'scoreRowDict' => $scoreRowDict
        ];
    }

    /**
     * Calculate score for image test
     *
     * @param array $testData Test data array
     * @param bool $isReCalculateValueCorrect Whether to recalculate the value correctness
     * @return array Result containing score and correct count
     */
    public function scoreImage(array $testData, bool $isReCalculateValueCorrect = false): array
    {
        $questions = $testData['items'] ?? [];
        $score = 0;
        $correctCount = 0;

        // Update correct values for each question
        for ($i = 0; $i < $this->count; $i++) {
            if (!isset($questions[$i])) {
                continue;
            }

            $re_questionItemDict = &$questions[$i];

            if ($isReCalculateValueCorrect) {
                $re_questionItemDict['isCorrect1'] = false;
                $re_questionItemDict['isCorrect2'] = false;

                if (isset($re_questionItemDict['value1'], $re_questionItemDict['input1'])) {
                    $re_questionItemDict['isCorrect1'] = ($re_questionItemDict['value1'] === $re_questionItemDict['input1']);
                }

                if (isset($re_questionItemDict['value2'], $re_questionItemDict['input2'])) {
                    $re_questionItemDict['isCorrect2'] = ($re_questionItemDict['value2'] === $re_questionItemDict['input2']);
                }
            }

            $re_questionItemDict['correct'] = $re_questionItemDict['isCorrect1'] ?? false;
            $re_questionItemDict['isCorrect'] = $re_questionItemDict['correct'];

            if ($re_questionItemDict['correct']) {
                $correctCount++;
            }
        }

        // Calculate score for each non-empty question
        $scoreRowDict = [];

        $r = $this->row - 1;

        // Score for middle rows
        for (; $r >= 0; $r--) {
            if (!$this->checkEmptyRow($r, $questions)) {
                $scoreAtRow = $this->scoreForMiddleRow($r, $questions);
                if($scoreAtRow === 5) {
                    $score += 5;
                    $scoreRowDict[$r] = 5;
                }else {
                    $score -= 1;
                    $scoreRowDict[$r] = -1;
                }
                
            }
        }

        // for ($i = 0; $i < $this->count; $i++) {
        //     if (!isset($questions[$i])) {
        //         continue;
        //     }

        //     $questionItemDict = $questions[$i];

        //     if (!$this->checkAnswerEmpty($questionItemDict)) {
        //         if ($questionItemDict['correct'] ?? false) {
        //             $score += 1;
        //             $scoreRowDict[$i] = 1;
        //         } else {
        //             $score -= 1;
        //             $scoreRowDict[$i] = -1;
        //         }
        //     }
        // }

        return [
            'score' => max(0, $score),
            'correctCount' => $correctCount,
            'scoreRowDict' => $scoreRowDict
        ];
    }

    /**
     * Calculate score for face test
     *
     * @param array $testData Test data array
     * @param bool $isReCalculateValueCorrect Whether to recalculate the value correctness
     * @return array Result containing score and correct count
     */
    public function scoreFace(array $testData, bool $isReCalculateValueCorrect = false): array
    {
        $questions = $testData['items'] ?? [];
        $score = 0;
        $correctCount = 0;

        // Update correct values for each question
        for ($i = 0; $i < $this->count; $i++) {
            if (!isset($questions[$i])) {
                continue;
            }

            $re_questionItemDict = &$questions[$i];

            if (true) {
                $re_questionItemDict['isCorrect1'] = false;
                $re_questionItemDict['isCorrect2'] = false;

                if (isset($re_questionItemDict['value1'], $re_questionItemDict['input1']) &&
                    $re_questionItemDict['input1'] !== "-1" && !empty($re_questionItemDict['input1'])) {
                    $re_questionItemDict['isCorrect1'] = $this -> compareStrings (
                        $re_questionItemDict['value1'] ,
                        $re_questionItemDict['input1']
                    );
                    if ($re_questionItemDict['isCorrect1']) {
                        $re_questionItemDict['score1'] = 1;
                        $score += 1;
                    }
                } else {
                    $re_questionItemDict['score1'] = 0;
                }

                if (isset($re_questionItemDict['value2'], $re_questionItemDict['input2']) &&
                    $re_questionItemDict['input2'] !== "-1" && !empty($re_questionItemDict['input2'])) {
                    $re_questionItemDict['isCorrect2'] = $this -> compareStrings (
                        $re_questionItemDict['value2'] ,
                        $re_questionItemDict['input2']
                    );
                    if ($re_questionItemDict['isCorrect2']) {
                        $re_questionItemDict['score2'] = 1;
                        $score += 1;
                    }
                } else {
                    $re_questionItemDict['score2'] = 0;
                }
            }

            $re_questionItemDict['correct'] = $re_questionItemDict['isCorrect1'] ?? false;
            $re_questionItemDict['isCorrect'] = $re_questionItemDict['correct'];

            if ($re_questionItemDict['isCorrect1']) {
                $correctCount++;
            }

            if ($re_questionItemDict['isCorrect2']) {
                $correctCount++;
            }
        }

        // Check for duplicate input1 entries
        $checkDuplicateInput1 = [];

        for ($i = 0; $i < $this->count; $i++) {
            if (!isset($questions[$i])) {
                continue;
            }

            $re_questionItemDict2 = $questions[$i];

            if (!isset($re_questionItemDict2['input1']) || $re_questionItemDict2['input1'] === null ||
                $re_questionItemDict2['input1'] === "-1" || empty($re_questionItemDict2['input1'])) {
                continue;
            }

            $input1Key = strtolower(trim($re_questionItemDict2['input1']));

            if (isset($checkDuplicateInput1[$input1Key])) {
                $checkDuplicateInput1[$input1Key]++;
            } else {
                $checkDuplicateInput1[$input1Key] = 1;
            }
        }

        // Check for duplicate input2 entries
        $checkDuplicateInput2 = [];

        for ($i = 0; $i < $this->count; $i++) {
            if (!isset($questions[$i])) {
                continue;
            }

            $re_questionItemDict3 = $questions[$i];

            if (!isset($re_questionItemDict3['input2']) || $re_questionItemDict3['input2'] === null ||
                $re_questionItemDict3['input2'] === "-1" || empty($re_questionItemDict3['input2'])) {
                continue;
            }

            $input2Key = strtolower(trim($re_questionItemDict3['input2']));

            if (isset($checkDuplicateInput2[$input2Key])) {
                $checkDuplicateInput2[$input2Key]++;
            } else {
                $checkDuplicateInput2[$input2Key] = 1;
            }
        }

        for ($i = 0; $i < $this->count; $i++) {
            if (!isset($questions[$i])) {
                continue;
            }

            $re_questionItemDict4 = $questions[$i];

            if (!isset($re_questionItemDict4['input1']) || $re_questionItemDict4['input1'] === null ||
                $re_questionItemDict4['input1'] === "-1" || empty($re_questionItemDict4['input1'])) {
                continue;
            }

            $input1Key = strtolower(trim($re_questionItemDict4['input1']));

            if (isset($checkDuplicateInput1[$input1Key])) {
                if ($checkDuplicateInput1[$input1Key] > 2) {
                    $score -= 0.5;
                }
            }
        }

        for ($i = 0; $i < $this->count; $i++) {
            if (!isset($questions[$i])) {
                continue;
            }

            $re_questionItemDict5 = $questions[$i];

            if (!isset($re_questionItemDict5['input2']) || $re_questionItemDict5['input2'] === null ||
                $re_questionItemDict5['input2'] === "-1" || empty($re_questionItemDict5['input2'])) {
                continue;
            }

            $input2Key = strtolower(trim($re_questionItemDict5['input2']));

            if (isset($checkDuplicateInput2[$input2Key])) {
                if ($checkDuplicateInput2[$input2Key] > 2) {
                    $score -= 0.5;
                }
            }
        }


        //neu cap ho ten bi trung qua 3 lan thi score bi chia doi 
        $checkDuplicateInput1And2Count = 0;
        for ($i = 0; $i < $this->count; $i++) {
            if (!isset($questions[$i])) {
                continue;
            }

            $re_questionItemDict6 = $questions[$i];

            if (!isset($re_questionItemDict6['input1']) || $re_questionItemDict6['input1'] === null ||
                $re_questionItemDict6['input1'] === "-1" || empty($re_questionItemDict6['input1'])) {
                continue;
            }

            if (!isset($re_questionItemDict6['input2']) || $re_questionItemDict6['input2'] === null ||
                $re_questionItemDict6['input2'] === "-1" || empty($re_questionItemDict6['input2'])) {
                continue;
            }

            $input1Key = strtolower(trim($re_questionItemDict6['input1']));
            $input2Key = strtolower(trim($re_questionItemDict6['input2']));

            $compare = $this -> compareStrings (
                $input1Key ,
                $input2Key
            );

            if ($compare) {
                $checkDuplicateInput1And2Count++;
            }
        }

        if ($checkDuplicateInput1And2Count > 3) {
            $score = $score / 2;
        }


        return [
            'score' => max(0, round($score)),
            'correctCount' => $correctCount
        ];
    }

    /**
     * Compare two strings in a case-insensitive manner
     *
     * @param string $str1 First string
     * @param string $str2 Second string
     * @return bool True if strings match, ignoring case
     */
    private function compareStrings(string $str1, string $str2): bool
    {
        return strtolower(trim($str1)) === strtolower(trim($str2));
    }



    public function public_score_name_and_face(array $testData, bool $isReCalculateValueCorrect = false): array
    {
        $resultScoreArray = $this->scoreFace($testData, $isReCalculateValueCorrect);
        $championShipScore = 0;
        if ($resultScoreArray['score'] > 0) {
            $championShipScore = ($resultScoreArray['score'] / $this->championScoreConfig["game_name_face"]) * 1000;
        }
        return [
            'score' => $resultScoreArray['score'],
            'correctCount' => $resultScoreArray['correctCount'],
            "championShipScore" => round($championShipScore)
        ];
    }

    //game_random_binary
    public function public_score_random_binary(array $testData, bool $isReCalculateValueCorrect = false): array
    {
        $resultScoreArray = $this->scoreNumberOrCard($testData, $isReCalculateValueCorrect);
        $championShipScore = 0;
        if ($resultScoreArray['score'] > 0) {
            $championShipScore = ($resultScoreArray['score'] / $this->championScoreConfig["game_random_binary"]) * 1000;
        }
        return [
            'score' => $resultScoreArray['score'],
            'correctCount' => $resultScoreArray['correctCount'],
            "championShipScore" => round($championShipScore)
        ];
    }

    //game_random_number
    public function public_score_random_number(array $testData, bool $isReCalculateValueCorrect = false): array
    {
        $resultScoreArray = $this->scoreNumberOrCard($testData, $isReCalculateValueCorrect);
        $championShipScore = 0;
        if ($resultScoreArray['score'] > 0) {
            $championShipScore = ($resultScoreArray['score'] / $this->championScoreConfig["game_random_number"]) * 1000;
        }
        return [
            'score' => $resultScoreArray['score'],
            'correctCount' => $resultScoreArray['correctCount'],
            "championShipScore" => round($championShipScore)
        ];
    }

    //game_abstract_image
    public function public_score_abstract_image(array $testData, bool $isReCalculateValueCorrect = false): array
    {
        $resultScoreArray = $this->scoreImage($testData, $isReCalculateValueCorrect);
        $championShipScore = 0;
        if ($resultScoreArray['score'] > 0) {
            $championShipScore = ($resultScoreArray['score'] / $this->championScoreConfig["game_abstract_image"]) * 1000;
        }
        return [
            'score' => $resultScoreArray['score'],
            'correctCount' => $resultScoreArray['correctCount'],
            "championShipScore" => round($championShipScore)
        ];
    }

    //game_speed_number
    public function public_score_speed_number(array $testData, bool $isReCalculateValueCorrect = false): array
    {
        $resultScoreArray = $this->scoreNumberOrCard($testData, $isReCalculateValueCorrect);
        $championShipScore = 0;
        if ($resultScoreArray['score'] > 0) {
            $championShipScore = ($resultScoreArray['score'] / $this->championScoreConfig["game_speed_number"]) * 1000;
        }
        return [
            'score' => $resultScoreArray['score'],
            'correctCount' => $resultScoreArray['correctCount'],
            "championShipScore" => round($championShipScore)
        ];
    }

    //game_historic_date
    public function public_score_historic_date(array $testData, bool $isReCalculateValueCorrect = false): array
    {
        $resultScoreArray = $this->scoreHistory($testData, $isReCalculateValueCorrect);
        $championShipScore = 0;
        if ($resultScoreArray['score'] > 0) {
            $championShipScore = ($resultScoreArray['score'] / $this->championScoreConfig["game_historic_date"]) * 1000;
        }
        return [
            'score' => $resultScoreArray['score'],
            'correctCount' => $resultScoreArray['correctCount'],
            "championShipScore" => round($championShipScore)
        ];
    }

    //game_random_word
    public function public_score_random_word(array $testData, bool $isReCalculateValueCorrect = false): array
    {
        $resultScoreArray = $this->scoreWord($testData, $isReCalculateValueCorrect);
        $championShipScore = 0;
        if ($resultScoreArray['score'] > 0) {
            $championShipScore = ($resultScoreArray['score'] / $this->championScoreConfig["game_random_word"]) * 1000;
        }
        return [
            'score' => $resultScoreArray['score'],
            'correctCount' => $resultScoreArray['correctCount'],
            "championShipScore" => round($championShipScore)
        ];
    }

    //game_random_card
    public function public_score_random_card(array $testData, bool $isReCalculateValueCorrect = false): array
    {
        $resultScoreArray = $this->scoreNumberOrCard($testData, $isReCalculateValueCorrect);
        $championShipScore = 0;
        if ($resultScoreArray['score'] > 0) {
            $championShipScore = ($resultScoreArray['score'] / $this->championScoreConfig["game_random_card"]) * 1000;
        }
        return [
            'score' => $resultScoreArray['score'],
            'correctCount' => $resultScoreArray['correctCount'],
            "championShipScore" => round($championShipScore)
        ];
    }

    //game_spoken_number
    public function public_score_spoken_number(array $testData, bool $isReCalculateValueCorrect = false): array
    {
        $resultScoreArray = $this->scoreSpokenOrSpeedCard($testData, $isReCalculateValueCorrect);
        $championShipScore = 0;
        if ($resultScoreArray['score'] > 0) {
            //$competitionScore = sqrt($score) * $constantScore;
            $championShipScore = sqrt($resultScoreArray['score']) * $this->championScoreConfig["game_spoken_number"];
        }
        return [
            'score' => $resultScoreArray['score'],
            'correctCount' => $resultScoreArray['correctCount'],
            "championShipScore" => round($championShipScore)
        ];
    }

    //game_speed_card
    public function public_score_speed_card(array $testData, bool $isReCalculateValueCorrect = false): array
    {
        $resultScoreArray = $this->scoreSpokenOrSpeedCard($testData, $isReCalculateValueCorrect);
        $championShipScore = 0;
        if ($resultScoreArray['score'] > 0) {
            
            $thoigiannho = 60;//"KHONG BIET DOC TU DAU";
            $score = $resultScoreArray['score'];
            if ($score >= 52) {
                $championShipScore = $this->championScoreConfig['game_speed_card'] / pow($thoigiannho / 1000, 0.75);
            } else {
                $championShipScore = 130.21 * ($score / 52);
            }
        }
        return [
            'score' => $resultScoreArray['score'],
            'correctCount' => $resultScoreArray['correctCount'],
            "championShipScore" => round($championShipScore)
        ];
    }
}
