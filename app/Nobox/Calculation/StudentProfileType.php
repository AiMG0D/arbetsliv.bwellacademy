<?php

namespace App\Nobox\Calculation;

use App\Profile;
use App\ProfileFactor;

class StudentProfileType extends ProfileType
{
    public function calculate(Profile $profile)
    {
        $this->init($profile);

        // physical capacity tests
        $this->calcAgility();
        $this->calcStrength();
        $this->calcBalance();
        $this->calcMotor();
        $this->calcPosture();
        $this->calcFitness();
        $this->calcStepcount();

        // physical capacity questions
        $this->calcWeight();
        $this->calcIFIS();
        $this->calcTraining();
        $this->calcActivities();
        $this->calcPhysicalCondition();
        $this->calcSitting();
        $this->calcNewPhysicalQuestions();

        // experienced health
        $this->calcHealth();
        $this->calcBody();
        $this->calcShouldersNeckBack();
        $this->calcRest();
        $this->calcStomach();
        $this->calcHead();
        $this->calcSleep();
        $this->calcStress();

        // ANDT
        $this->calcSmoking();
        $this->calcSnuffing();
        $this->calcAlcohol();
        $this->calcAlcoholDrink();
        $this->calcOtcDrugs();
        $this->calcMoneyGames();

        // food and energy
        $this->calcEatingHabits();
        $this->calcFoodContent();
        $this->calcFoodQuantity();
        $this->calcSweets();
        $this->calcEnergyDrinks();
        $this->calcEnergyNeeds();
        $this->calcEnergyIntakeNew();
        $this->calcEnergyBalance();
        $this->calcFoodFruit();
        $this->calcFoodFluid();

        // life at leisure
        $this->calcLeisureInterests();
        $this->calcMedia();
        $this->calcFriends();
        if (config('fms.type') == 'school') {
            $this->calcAdults();
        }

        // life at school
        if (config('fms.type') == 'school') {
            /*$this->calcAmbition();
            $this->calcAccomplishment();
            $this->calcFeelingSeen();
            $this->calcWellbeing();
            $this->calcSafety();
            $this->calcSupport();
            $this->calcInfluence();*/
            $this->calcSchool();
        }

        // life at work
        if (config('fms.type') == 'work') {
            $this->calcWork();
        }

        // SOC
        $this->calcSOC();

        // TODO: BMI?

        $this->calcRiskGroup();

        $this->commit();
    }

    private function calcAgility()
    {
        $questions = [
            'neckRotL',
            'neckRotR',
            'neckBendL',
            'neckBendR',
            'shldrHiL',
            'shldrHiR',
            'shldrLoL',
            'shldrLoR',
            'shldrXL',
            'shldrXR',
            'backRotL',
            'backRotR',
            'backBendL',
            'backBendR',
            'backBendF',
            'brstStretch',
            'pelIliL',
            'pelIliR',
            'pelHamL',
            'pelHamR',
        ];

        $healty = 0;

        foreach ($questions as $name) {
            $value = $this->getValue($name);
            if (is_null($value) || $value == -1) {
                $this->setFactor('agility', null, 'profile.unknown');

                return;
            } elseif ($value > 0) {
                ++$healty;
            }
        }

        $risk = count($questions) - $healty;

        if ($risk == 0) {
            $value = 5;
        } elseif ($risk < 3) {
            $value = 4;
        } elseif ($risk < 5) {
            $value = 3;
        } elseif ($risk < 6) {
            $value = 2;
        } else {
            $value = 1;
        }

        if ($value < 3) {
            $this->setFactor('agility', $value, 'profile.risk');
        } else {
            $this->setFactor('agility', $value, 'profile.healthy');
        }
    }

    private function normalizeStrength($name, $value, $age, $sex)
    {
        if (config('fms.type') == 'work') {
            switch ($name) {
                case 'strLegs':
                    if ($sex == 'M') {
                        $limits = [28, 73, 111, 120];
                    } else {
                        $limits = [11, 46, 76, 60];
                    }
                    break;
                case 'strLegs2':
                    if ($age < 26) {
                        $limits = [30, 34, 43, 49];
                    } elseif ($age < 36) {
                        $limits = [28, 30, 39, 45];
                    } elseif ($age < 46) {
                        $limits = [22, 26, 34, 41];
                    } elseif ($age < 56) {
                        $limits = [17, 21, 29, 35];
                    } elseif ($age < 66) {
                        $limits = [13, 17, 25, 30];
                    } else {
                        $limits = [12, 16, 22, 28];
                    }
                    break;
                case 'strBack2':
                    if ($sex == 'M') {
                        $limits = [12, 35, 55, 61];
                    } else {
                        $limits = [8, 24, 38, 43];
                    }
                    break;
                case 'strArm5kg':
                    $limits = [7, 18, 27, 35];
                    break;
                case 'strArm10kg':
                    $limits = [7, 15, 22, 30];
                    break;
                case 'pushupsToes':
                    if ($sex == 'M') {
                        if ($age < 20) {
                            $limits = [3, 18, 35, 50];
                        } elseif ($age < 30) {
                            $limits = [3, 16, 29, 42];
                        } elseif ($age < 40) {
                            $limits = [1, 12, 24, 36];
                        } elseif ($age < 50) {
                            $limits = [0, 10, 20, 30];
                        } elseif ($age < 60) {
                            $limits = [0, 8, 17, 27];
                        } else {
                            $limits = [0, 5, 16, 26];
                        }
                    } else {
                        if ($age < 20) {
                            $limits = [0, 10, 20, 31];
                        } elseif ($age < 30) {
                            $limits = [0, 11, 22, 32];
                        } elseif ($age < 40) {
                            $limits = [0, 9, 21, 33];
                        } elseif ($age < 50) {
                            $limits = [0, 7, 17, 27];
                        } elseif ($age < 60) {
                            $limits = [0, 6, 14, 22];
                        } else {
                            $limits = [0, 4, 12, 20];
                        }
                    }
                    break;
                case 'pushupsKnees':
                    // TODO: remove from work?
                    if ($age < 20) {
                        $limits = [3, 18, 35, 50];
                    } elseif ($age < 30) {
                        $limits = [3, 16, 29, 42];
                    } elseif ($age < 40) {
                        $limits = [1, 12, 24, 36];
                    } elseif ($age < 50) {
                        $limits = [0, 10, 20, 30];
                    } elseif ($age < 60) {
                        $limits = [0, 8, 17, 27];
                    } else {
                        $limits = [0, 5, 16, 26];
                    }
                    break;
                case 'strAb':
                    if ($sex == 'M') {
                        if ($age < 26) {
                            $limits = [29, 34, 38, 44];
                        } elseif ($age < 36) {
                            $limits = [27, 30, 34, 40];
                        } elseif ($age < 46) {
                            $limits = [21, 26, 29, 35];
                        } elseif ($age < 56) {
                            $limits = [16, 21, 24, 29];
                        } elseif ($age < 66) {
                            $limits = [11, 16, 20, 25];
                        } else {
                            $limits = [10, 14, 18, 22];
                        }
                    } else {
                        if ($age < 26) {
                            $limits = [24, 28, 36, 43];
                        } elseif ($age < 36) {
                            $limits = [20, 24, 32, 39];
                        } elseif ($age < 46) {
                            $limits = [14, 18, 26, 33];
                        } elseif ($age < 56) {
                            $limits = [9, 13, 21, 27];
                        } elseif ($age < 66) {
                            $limits = [6, 9, 17, 24];
                        } else {
                            $limits = [4, 10, 16, 23];
                        }
                    }
                    break;
                default:
                    throw new \Exception('Unknown factor: ' . $name);
            }
        } else {
            switch ($name) {
                case 'strLegs':
                    if ($sex == 'M') {
                        $limits = $age < 16 ? [80, 100, 201, 221] : [69, 85, 169, 185];
                    } else {
                        $limits = $age < 16 ? [69, 86, 173, 190] : [57, 72, 144, 158];
                    }
                    break;
                case 'strLegs2':
                    $limits = [30, 34, 43, 49];
                    break;
                case 'strBack2':
                    if ($sex == 'M') {
                        $limits = $age < 16 ? [37, 46, 92, 102] : [33, 41, 82, 91];
                    } else {
                        $limits = $age < 16 ? [35, 43, 87, 96] : [33, 41, 82, 91];
                    }
                    break;
                case 'strArm5kg':
                    if ($sex == 'M') {
                        $limits = $age < 16 ? [21, 25, 47, 52] : [20, 24, 45, 50];
                    } else {
                        $limits = $age < 16 ? [14, 18, 34, 37] : [15, 18, 34, 37];
                    }
                    break;
                case 'strArm10kg':
                    $limits = $age < 16 ? [10, 12, 24, 26] : [12, 14, 25, 27];
                    break;
                case 'pushupsToes':
                    if ($sex == 'M') {
                        $limits = [3, 18, 35, 50];
                    } else {
                        $limits = [0, 10, 20, 31];
                    }
                    break;
                case 'pushupsKnees':
                    $limits = [3, 18, 35, 50];
                    break;
                case 'strAb':
                    if ($sex == 'M') {
                        $limits = [29, 34, 38, 44];
                    } else {
                        $limits = [24, 28, 36, 43];
                    }
                    break;
                default:
                    throw new \Exception('Unknown factor: ' . $name);
            }
        }

        for ($i = 0; $i < 4; ++$i) {
            if ($value <= $limits[$i]) {
                return $i + 1;
            }
        }

        return 5;
    }

    private function calcStrength()
    {
        $value = $this->getValue('strLegs2');
        if (is_numeric($value)) {
            $norm = $this->normalizeStrength('strLegs2', $value, $this->age, $this->sex);
            $this->setValue('strLegsNorm', $norm);
        } else {
            $value = $this->getValue('strLegs');
            if (is_numeric($value)) {
                $norm = $this->normalizeStrength('strLegs', $value, $this->age, $this->sex);
                $this->setValue('strLegsNorm', $norm);
            } else {
                $this->setValue('strLegsNorm', null);
            }
        }

        $value = $this->getValue('strBack2');
        if (is_null($value)) {
            $value = $this->getValue('strBack');
        }
        if (is_numeric($value)) {
            $norm = $this->normalizeStrength('strBack2', $value, $this->age, $this->sex);
            $this->setValue('strBackNorm', $norm);
        } else {
            $this->setValue('strBackNorm', null);
        }

        $method = $this->getValue('armMethod');
        if (is_null($method)) {
            if (! empty($this->getValue('strArm'))) {
                $method = 1;
                $this->setValue('armMethod', $method);
            } elseif (! empty($this->getValue('pushups'))) {
                $method = 2;
                $this->setValue('armMethod', $method);
            }
        }
        $method = intval($method);

        if ($method == 1) {
            $value = $this->getValue('strArm');
            if (is_numeric($value)) {
                $weights = intval($this->getValue('strArmWeights'));
                if ($weights == 10) {
                    $this->setValue('strArm10kg', $value);
                    $this->setValue('strArm5kg', null);

                    $norm = $this->normalizeStrength('strArm10kg', $value, $this->age, $this->sex);
                    $this->setValue('strArmNorm', $norm);
                } else {
                    $this->setValue('strArm5kg', $value);
                    $this->setValue('strArm10kg', null);

                    $norm = $this->normalizeStrength('strArm5kg', $value, $this->age, $this->sex);
                    $this->setValue('strArmNorm', $norm);
                }
            } else {
                $this->setValue('strArmNorm', null);
            }
        } elseif ($method == 2) {
            $value = $this->getValue('pushups');
            if (is_numeric($value)) {
                $type = intval($this->getValue('pushupsType'));
                if ($type == 1) {
                    $this->setValue('pushupsKnees', $value);
                    $this->setValue('pushupsToes', null);

                    $norm = $this->normalizeStrength('pushupsKnees', $value, $this->age, $this->sex);
                    $this->setValue('strArmNorm', $norm);
                } else {
                    $this->setValue('pushupsToes', $value);
                    $this->setValue('pushupsKnees', null);

                    $norm = $this->normalizeStrength('pushupsToes', $value, $this->age, $this->sex);
                    $this->setValue('strArmNorm', $norm);
                }
            } else {
                $this->setValue('strArmNorm', null);
            }
        } else {
            $this->setValue('strArmNorm', null);
        }

        $value = $this->getValue('strAb');
        if (is_numeric($value)) {
            $norm = $this->normalizeStrength('strAb', $value, $this->age, $this->sex);
            $this->setValue('strAbNorm', $norm);
        } else {
            $this->setValue('strAbNorm', null);
        }

        $this->singleValue('strLegs', 'strLegsNorm');
        $this->singleValue('strBack', 'strBackNorm');
        $this->singleValue('strArms', 'strArmNorm');
        $this->singleValue('strAbs', 'strAbNorm');

        // TODO: remove this later
        $value = $this->getValue('strBackNorm');
        if (is_numeric($value)) {
            $this->multiValueAvg('strength2', ['strLegsNorm', 'strBackNorm', 'strArmNorm', 'strAbNorm']);
        } else {
            $this->multiValueAvg('strength2', ['strLegsNorm', 'strArmNorm', 'strAbNorm']);
        }
    }

    private function normalizeBalance($value)
    {
        $limits = [0, 1, 4, 6];

        for ($i = 0; $i < 4; ++$i) {
            if ($value <= $limits[$i]) {
                return 5 - $i;
            }
        }

        return 1;
    }

    private function calcBalance()
    {
        $value = $this->getValue('balance');
        if (is_null($value) || $value < 0) {
            $this->setFactor('balance', null, 'profile.unknown');

            return;
        }

        $value = $this->normalizeBalance($value);

        if ($value < 3) {
            $this->setFactor('balance', $value, 'profile.risk');
        } else {
            $this->setFactor('balance', $value, 'profile.healthy');
        }
    }

    private function calcMotor()
    {
        $value = $this->getValue('motor');
        if (is_null($value) || $value < 0 || $value > 1) {
            $this->setFactor('motor', null, 'profile.unknown');

            return;
        }

        if ($value == 1) {
            $this->setFactor('motor', 5, 'profile.healthy');
        } else {
            $this->setFactor('motor', 1, 'profile.risk');
        }
    }

    private function calcPosture()
    {
        $value = $this->getValue('posture');
        if (is_null($value) || $value < 0 || $value > 1) {
            $this->setFactor('posture', null, 'profile.unknown');

            return;
        }

        if ($value == 1) {
            $this->setFactor('posture', 5, 'profile.healthy');
        } else {
            $this->setFactor('posture', 1, 'profile.risk');
        }
    }

    private function fitnessStepTest($weight, $sex, $age)
    {
        $this->setValue('fitO2min', null);
        $this->setValue('fitO2kg', null);
        $this->setValue('fitBorg', null);

        $borg = $this->getValue('fitBorgSteady');
        if (is_null($borg) || $borg < 12) {
            return;
        }
        $this->setValue('fitBorg', $borg);

        $pulse = $this->getValue('fitPulseSteady');
        if (empty($pulse)) {
            return;
        }

        $height = $this->getValue('fitBenchSteady');
        if (empty($height)) {
            return;
        }
        $height = intval($height);

        $o2lmin = Fitness::stepTest($weight, $pulse, $sex, $height);
        $o2lmin = Fitness::ageCorrection($o2lmin, $age);
        $this->setValue('fitO2min', $o2lmin);

        $o2mlkgmin = Fitness::o2mlkg($o2lmin, $weight);
        $this->setValue('fitO2kg', $o2mlkgmin);
    }

    private function fitnessBicycle($weight, $sex, $age)
    {
        $this->setValue('fitO2min', null);
        $this->setValue('fitO2kg', null);
        $this->setValue('fitBorg', null);

        $borg = $this->getValue('fitBicBorgSteady');
        if (is_null($borg) || $borg < 12) {
            return;
        }
        $this->setValue('fitBorg', $borg);

        $pulse = $this->getValue('fitBicPulseSteady');
        if (empty($pulse)) {
            return;
        }

        $watts = $this->getValue('fitBicWattsSteady');
        if (empty($watts)) {
            return;
        }

        $o2lmin = Fitness::bicycle($pulse, $watts, $sex);
        $o2lmin = Fitness::ageCorrection($o2lmin, $age);
        $this->setValue('fitO2min', $o2lmin);

        $o2mlkgmin = Fitness::o2mlkg($o2lmin, $weight);
        $this->setValue('fitO2kg', $o2mlkgmin);
    }

    private function fitnessWalk($weight, $sex, $age)
    {
        $this->setValue('fitO2min', null);
        $this->setValue('fitO2kg', null);

        $fitWalkPulse = $this->getValue('fitWalkPulse');
        if (empty($fitWalkPulse)) {
            return;
        }

        $fitWalkDistance = $this->getValue('fitWalkDistance');
        if (empty($fitWalkDistance)) {
            return;
        }

        $fitWalkMin = $this->getValue('fitWalkMin');
        if (empty($fitWalkMin)) {
            return;
        }

        $fitWalkSec = $this->getValue('fitWalkSec');
        if (empty($fitWalkSec)) {
            return;
        }

        // https://en.wikipedia.org/wiki/VO2_max#Rockport_fitness_walking_test

        // weight in lbs
        $lbs = $weight * 2.205;

        // one-mile walk time in minutes and seconds
        $t = ($fitWalkMin + ($fitWalkSec / 60.0)) * (1609.0 / $fitWalkDistance);

        $o2mlkgmin = 132.853;
        $o2mlkgmin -= 0.0769 * $lbs;
        $o2mlkgmin -= 0.3877 * $age;
        $o2mlkgmin -= 3.2649 * $t;
        $o2mlkgmin -= 0.1565 * $fitWalkPulse;

        if ($sex == 'M') {
            $o2mlkgmin += 6.3150;
        }

        $o2lmin = Fitness::o2lmin($o2mlkgmin, $weight);

        $this->setValue('fitO2min', $o2lmin);
        $this->setValue('fitO2kg', $o2mlkgmin);
    }

    private function fitnessBeep($weight)
    {
        $this->setValue('fitO2min', null);
        $this->setValue('fitO2kg', null);

        $fitBeepLevel = $this->getValue('fitBeepLevel');
        if (empty($fitBeepLevel)) {
            return;
        }

        $fitBeepShuttles = $this->getValue('fitBeepShuttles');
        if (empty($fitBeepShuttles)) {
            return;
        }

        // https://www.topendsports.com/testing/beepcalc.htm

        $ends = $fitBeepLevel * 0.4325 + 7.0048;
        $deci = $fitBeepShuttles / $ends;
        $score = $fitBeepLevel + $deci;
        $o2mlkgmin = 3.46 * $score + 12.2;

        $o2lmin = Fitness::o2lmin($o2mlkgmin, $weight);

        $this->setValue('fitO2min', $o2lmin);
        $this->setValue('fitO2kg', $o2mlkgmin);
    }

    private function fitnessCooper($weight)
    {
        $this->setValue('fitO2min', null);
        $this->setValue('fitO2kg', null);

        $fitCooperDistance = $this->getValue('fitCooperDistance');
        if (empty($fitCooperDistance)) {
            return;
        }

        // https://en.wikipedia.org/wiki/Cooper_test

        $o2mlkgmin = ($fitCooperDistance - 504.9) / 44.73;
        $o2lmin = Fitness::o2lmin($o2mlkgmin, $weight);

        $this->setValue('fitO2min', $o2lmin);
        $this->setValue('fitO2kg', $o2mlkgmin);
    }

    private function normalizeO2lmin($value, $sex, $age)
    {
        if ($sex == 'M') {
            if ($age < 20) {
                $limits = [2.2, 2.9, 3.6, 4.3];
            } elseif ($age < 30) {
                $limits = [2.6, 3.2, 3.9, 4.6];
            } elseif ($age < 40) {
                $limits = [2.3, 2.9, 3.6, 4.4];
            } elseif ($age < 50) {
                $limits = [1.9, 2.5, 3.2, 3.9];
            } elseif ($age < 60) {
                $limits = [1.7, 2.2, 2.8, 3.4];
            } else {
                $limits = [1.4, 1.9, 2.4, 2.9];
            }
        } else {
            if ($age < 20) {
                $limits = [1.8, 2.2, 2.7, 3.0];
            } elseif ($age < 30) {
                $limits = [2.0, 2.4, 2.9, 3.2];
            } elseif ($age < 40) {
                $limits = [1.9, 2.2, 2.7, 3.0];
            } elseif ($age < 50) {
                $limits = [1.7, 1.9, 2.4, 2.8];
            } elseif ($age < 60) {
                $limits = [1.4, 1.7, 2.2, 2.6];
            } else {
                $limits = [1.3, 1.6, 2.1, 2.4];
            }
        }

        for ($i = 0; $i < 4; ++$i) {
            if ($value <= $limits[$i]) {
                return $i + 1;
            }
        }

        return 5;
    }

    private function normalizeO2mlkgmin($value, $sex, $age)
    {
        if ($sex == 'M') {
            if ($age < 20) {
                $limits = [32, 39, 47, 55];
            } elseif ($age < 30) {
                $limits = [33, 41, 50, 59];
            } elseif ($age < 40) {
                $limits = [28, 35, 44, 53];
            } elseif ($age < 50) {
                $limits = [24, 32, 40, 48];
            } elseif ($age < 60) {
                $limits = [22, 28, 35, 42];
            } else {
                $limits = [18, 23, 29, 36];
            }
        } else {
            if ($age < 20) {
                $limits = [29, 37, 45, 52];
            } elseif ($age < 30) {
                $limits = [31, 39, 47, 55];
            } elseif ($age < 40) {
                $limits = [27, 35, 43, 51];
            } elseif ($age < 50) {
                $limits = [23, 30, 37, 44];
            } elseif ($age < 60) {
                $limits = [20, 26, 32, 39];
            } else {
                $limits = [17, 22, 28, 34];
            }
        }

        for ($i = 0; $i < 4; ++$i) {
            if ($value <= $limits[$i]) {
                return $i + 1;
            }
        }

        return 5;
    }

    private function calcFitness()
    {
        $this->setFactor('fitness', null, 'profile.unknown');
        $this->setValue('fitO2minNorm', null);
        $this->setValue('fitO2kgNorm', null);

        $weight = $this->getValue('weight');
        if (empty($weight)) {
            return;
        }

        $method = $this->getValue('fitMethod');
        if (is_null($method)) {
            return;
        }
        $method = intval($method);

        switch ($method) {
            case $this::METHOD_STEPTEST:
                $this->fitnessStepTest($weight, $this->sex, $this->age);
                break;
            case $this::METHOD_BICYCLE:
                $this->fitnessBicycle($weight, $this->sex, $this->age);
                break;
            case $this::METHOD_MLO2:
            case $this::METHOD_FLEX:
                $o2mlkgmin = $this->getValue('fitO2kg');
                if (empty($o2mlkgmin)) {
                    return;
                }
                $o2lmin = Fitness::o2lmin($o2mlkgmin, $weight);
                $this->setValue('fitO2min', $o2lmin);
                break;
            case $this::METHOD_LO2:
                $o2lmin = $this->getValue('fitO2min');
                if (empty($o2lmin)) {
                    return;
                }
                $o2mlkgmin = Fitness::o2mlkg($o2lmin, $weight);
                $this->setValue('fitO2kg', $o2mlkgmin);
                break;
            case $this::METHOD_WALK:
                $this->fitnessWalk($weight, $this->sex, $this->age);
                break;
            case $this::METHOD_BEEP:
                $this->fitnessBeep($weight);
                break;
            case $this::METHOD_COOPER:
                $this->fitnessCooper($weight);
                break;
            default:
                throw new \Exception('Unknown fitness method: ' . $method);
        }

        $value = $this->getValue('fitO2min');
        if (! empty($value)) {
            $norm = $this->normalizeO2lmin($value, $this->sex, $this->age);
            $this->setValue('fitO2minNorm', $norm);
        }

        $value = $this->getValue('fitO2kg');
        if (! empty($value)) {
            $norm = $this->normalizeO2mlkgmin($value, $this->sex, $this->age);
            $this->setValue('fitO2kgNorm', $norm);
        }

        $this->singleValue('fitness', 'fitO2kgNorm');
    }

    private function calculateCooperFitness()
    {
        $fitCooperDistance = $this->getValue('fitCooperDistance');
        if (empty($fitCooperDistance)) {
            return;
        }

        if ($this->sex == 'M') {
            if ($this->age < 20) {
                $limits = [2299, 2499, 2699, 3000];
            } elseif ($this->age < 30) {
                $limits = [1599, 2199, 2399, 2800];
            } elseif ($this->age < 40) {
                $limits = [1499, 1899, 2299, 2700];
            } elseif ($this->age < 50) {
                $limits = [1399, 1699, 2099, 2500];
            } else {
                $limits = [1299, 1599, 1999, 2400];
            }
        } else {
            if ($this->age < 20) {
                $limits = [1699, 1799, 2099, 2300];
            } elseif ($this->age < 30) {
                $limits = [1499, 1799, 2199, 2700];
            } elseif ($this->age < 40) {
                $limits = [1399, 1699, 1999, 2500];
            } elseif ($this->age < 50) {
                $limits = [1199, 1499, 1899, 2300];
            } else {
                $limits = [1099, 1399, 1699, 2200];
            }
        }

        $value = 5;
        foreach ($limits as $i => $limit) {
            if ($fitCooperDistance <= $limit) {
                $value = $i + 1;
                break;
            }
        }

        if ($value < 3) {
            $this->setFactor('fitness', $value, 'profile.risk');
        } else {
            $this->setFactor('fitness', $value, 'profile.healthy');
        }
    }

    private function normalizeStepcount($value)
    {
        $limits = [4999, 7499, 9999, 12500];

        for ($i = 0; $i < 4; ++$i) {
            if ($value <= $limits[$i]) {
                return $i + 1;
            }
        }

        return 5;
    }

    private function calcStepcount()
    {
        $value = $this->getValue('stepcount');
        if (is_null($value) || $value < 0) {
            $this->setFactor('stepcount', null, 'profile.unknown');

            return;
        }

        $value = $this->normalizeStepcount($value);

        if ($value < 3) {
            $this->setFactor('stepcount', $value, 'profile.risk');
        } else {
            $this->setFactor('stepcount', $value, 'profile.healthy');
        }
    }

    private function calcWeight()
    {
        $value = $this->getValue('bodyWeightEst');
        if (is_null($value) || $value < -1 || $value > 1) {
            $this->setFactor('weight', null, 'profile.unknown');

            return;
        }

        if ($value == 0) {
            $this->setFactor('weight', 5, 'profile.healthy');
        } else {
            $this->setFactor('weight', 1, 'profile.risk');
        }
    }

    private function calcIFIS()
    {
        $questions = [
            'physicalCapacity',
            'physicalAir',
            'physicalStrength',
            'physicalQuickness',
            'physicalAgility',
        ];

        $this->multiValueAvg('physical', $questions);
    }

    private function calcTraining()
    {
        $this->singleValue('physicalTraining', 'training');
    }

    private function calcActivities()
    {
        $this->singleValue('activitiesTimeSpent', 'energy');

        if (config('fms.type') === 'work') {
            $this->singleValue('energyWork', 'energyWork');
        }
    }

    private function calcPhysicalCondition()
    {
        $this->singleValue('physicalCondition', 'physicalCondition');
    }

    private function calcSitting()
    {
        $this->singleValue('sitting', 'sitting');
    }

    private function calcNewPhysicalQuestions()
    {
        $this->multiValueAvg('physicalActivity', [
            'energy',
            'training',
            'sitting',
            'physicalCondition',
        ]);
    }

    public function calcHealth()
    {
        $this->singleValue('health', 'health');
    }

    public function calcBody()
    {
        $this->singleValue('bodySat', 'bodySat');
    }

    public function calcShouldersNeckBack()
    {
        $this->singleValue('bodySympt', 'bodySympt');
    }

    public function calcRest()
    {
        $this->singleValue('diffSymptRelaxed', 'diffSymptRelaxed');
    }

    public function calcStomach()
    {
        $this->singleValue('diffSymptStomach', 'diffSymptStomach');
    }

    public function calcHead()
    {
        $this->singleValue('diffSymptHead', 'diffSymptHead');
    }

    public function calcSleep()
    {
        if (config('fms.type') == 'work') {
            $this->singleValue('sleepHour', 'sleepHour', 4);
        } else {
            $this->singleValue('sleepHour', 'sleepHour');
        }
    }

    public function calcStress()
    {
        $this->singleValue('calmness', 'calmness');
    }

    private function calcSmoking()
    {
        $this->singleValue('smoking', 'smoking', 5);
    }

    private function calcSnuffing()
    {
        $this->singleValue('snuffing', 'snuffing', 5);
    }

    private function calcOtcDrugs()
    {
        $this->singleValue('otcDrugs', 'otcDrugs');
    }

    private function calcMoneyGames()
    {
        $this->singleValue('moneyGames', 'moneyGames', 5);
    }

    private function calcAlcohol()
    {
        if (config('fms.type') == 'work') {
            $this->singleValue('alcohol', 'alcoholOften', 4);
        } else {
            $this->singleValue('alcohol', 'alcoholOften', 5);
        }
    }

    private function calcAlcoholDrink()
    {
        if ($this->getValue('alcoholDrinking') === null) {
            return;
        }

        if ($this->getValue('alcoholDrinking') == 0) {
            $this->setValue('alcoholDrink', 5);
        } else {
            if (config('fms.type') === 'school') {
                $this->setValue('alcoholDrink', 1);
            } else {
                $ratioMap = [
                    1,
                    1.75,
                    1,
                    1,
                    2,
                    6,
                    1,
                ];

                $units = 0;

                $valueNames = array_map(fn($i) => 'alcoholDrink' . $i, range(1, 7));
                foreach ($valueNames as $idx => $name) {
                    $units += ($this->getValue($name) ?? 0) * $ratioMap[$idx];
                }

                $limit = $this->sex === 'F' ? 9 : 14;

                $this->setValue('alcoholDrink', round($units) < $limit ? 5 : 1);
            }
        }

        $this->singleValue('alcoholDrink', 'alcoholDrink');
    }

    private function calcEatingHabits()
    {
        if (! empty($this->getValue('foodDistribution'))) {
            $this->singleValue('foodHabits', 'foodDistribution', 4);
        } else {
            $this->singleValue('foodHabits', 'goodFood');
        }
    }

    private function calcFoodContent()
    {
        $this->singleValue('foodContents', 'contentFood');
    }

    private function calcFoodQuantity()
    {
        $this->singleValue('foodAmount', 'amountFood');
    }

    private function calcSweets()
    {
        $this->singleValue('foodSweets', 'badFood');
    }

    private function calcEnergyDrinks()
    {
        $this->singleValue('foodEnergyDrinks', 'energyDrink');
    }

    private function calcEnergyNeeds()
    {
        $this->setValue('foodBMR', null);
        $this->setValue('foodPAL', null);
        $this->setValue('foodEnergyNeeds', null);

        $length = $this->getValue('length');
        if (empty($length)) {
            return;
        }

        $weight = $this->getValue('weight');
        if (empty($weight)) {
            return;
        }

        $bmr = (10.0 * $weight) + (6.25 * $length) - (5.0 * $this->age);
        if ($this->sex == 'M') {
            $bmr += 5.0;
        } else {
            $bmr -= 161.0;
        }
        $this->setValue('foodBMR', $bmr);

        $minCondition = $this->getValue('Min kondition');
        if (empty($minCondition)) {
            return;
        }

        $minCondition = intval($minCondition);

        if ($minCondition == 5) {
            $pal = 2.55;
        } elseif ($minCondition == 4) {
            $pal = 2.2;
        } elseif ($minCondition == 3) {
            $pal = 1.85;
        } elseif ($minCondition == 2) {
            $pal = 1.55;
        } else {
            $pal = 1.25;
        }


        $training = $this->getValue('training');
        if (empty($training)) {
            return;
        }

        $training = intval($training);

        if ($training == 5) {
            $pal = 2.55;
        } elseif ($training == 4) {
            $pal = 2.2;
        } elseif ($training == 3) {
            $pal = 1.85;
        } elseif ($training == 2) {
            $pal = 1.55;
        } else {
            $pal = 1.25;
        }
        $this->setValue('foodPAL', $pal);

        $energyNeeds = $bmr * $pal;
        $this->setValue('foodEnergyNeeds', $energyNeeds);
    }

    private function calcEnergyIntake()
    {
        $this->setValue('foodEnergyIntake', null);

        $total = 0;

        foreach (EnergyIntake::$options as $option) {
            $value = $this->getValue($option['name']);
            if (is_numeric($value)) {
                $total += $value * $option['kcal'];
            }
        }

        $this->setValue('foodEnergyIntake', $total);
    }

    private function calcEnergyIntakeNew()
    {
        $this->setValue('foodEnergyIntake', null);

        $total = 0;

        foreach (EnergyIntakeNew::$options as $option) {
            $value = $this->getValue($option['name']);
            if (is_numeric($value)) {
                $total += $option['kcal'][$value];
            }
        }

        $snackKcal = [0, 150, 450, 550];

        $energyIntakeCount = (int) $this->getValue('energyIntakeNewSnackCount');
        if ($energyIntakeCount > 0) {
            foreach (range(0, $energyIntakeCount) as $idx) {
                $key = 'energyIntakeNewSnack' . ($idx + 1);
                $value = $this->getValue($key);
                if (is_numeric($value)) {
                    $total += $snackKcal[(int) $value];
                }
            }
        }

        $this->setValue('foodEnergyIntake', $total);
    }

    private function calcEnergyBalance()
    {
        $this->setFactor('foodEnergyBalance', null, 'profile.unknown');
        $this->setValue('foodEnergyBalance', null);

        $needs = $this->getValue('foodEnergyNeeds') ?? 0;
        $intake = $this->getValue('foodEnergyIntake') ?? 0;

        $balance = $needs - $intake;
        $this->setValue('foodEnergyBalance', $balance);

        $absBalance = abs($balance);
        if ($absBalance <= 100) {
            $this->setFactor('foodEnergyBalance', 5, 'profile.healthy');
        } elseif ($absBalance <= 200) {
            $this->setFactor('foodEnergyBalance', 4, 'profile.healthy');
        } elseif ($absBalance <= 300) {
            $this->setFactor('foodEnergyBalance', 3, 'profile.healthy');
        } elseif ($absBalance <= 500) {
            $this->setFactor('foodEnergyBalance', 2, 'profile.risk');
        } else {
            $this->setFactor('foodEnergyBalance', 1, 'profile.risk');
        }
    }

    private function calcFoodFruit()
    {
        $this->singleValue('foodFruit', 'foodFruit');
    }

    private function calcFoodFluid()
    {
        $this->singleValue('foodFluid', 'foodFluid');
    }

    private function calcLeisureInterests()
    {
        $this->singleValue('freetime', 'activities');
    }

    private function calcMedia()
    {
        $this->singleValue('media', 'media');
    }

    private function calcFriends()
    {
        $this->singleValue('friends', 'friends');
    }

    private function calcAdults()
    {
        $this->singleValue('adults', 'adults');
    }

    /*
    private function calcAmbition()
    {
        $this->singleValue('schlGoal', 'schlGoal');
    }

    private function calcAccomplishment()
    {
        $this->singleValue('schlResult', 'schlResult');
    }

    private function calcFeelingSeen()
    {
        $this->singleValue('schlSituationInfo', 'schlSituationInfo');
    }

    private function calcWellbeing()
    {
        $this->singleValue('schlComfort', 'schlComfort');
    }

    private function calcSafety()
    {
        $this->singleValue('schlSafety', 'schlSafety');
    }

    private function calcSupport()
    {
        $this->singleValue('schlSeen', 'schlSeen');
    }

    private function calcInfluence()
    {
        $this->singleValue('schlInfl', 'schlInfl');
    }*/

    private function calcSchool()
    {
        $valueNames = array_map(fn($i) => 'school' . $i, range(1, 11));
        foreach ($valueNames as $name) {
            $this->singleValue($name, $name);
        }

        // $this->multiValueAvg('schoolSum', $valueNames);
    }

    private function calcWork()
    {
        $valueNames = array_map(fn($i) => 'work' . $i, range(1, 16));
        foreach ($valueNames as $name) {
            $this->singleValue($name, $name);
        }

        $this->multiValueAvg('workSum', $valueNames);
    }

    private function normalizeSOC($value)
    {
        if (config('fms.type') == 'work') {
            $limits = [60, 68, 75, 83];
        } else {
            $limits = [52, 62, 70, 80];
        }

        for ($i = 0; $i < 4; ++$i) {
            if ($value <= $limits[$i]) {
                return $i + 1;
            }
        }

        return 5;
    }

    private function calcSOC()
    {
        // Collect all 13 kasam values
        $kasamValues = [];
        $validValues = true;

        // Get individual values and validate them
        for ($i = 1; $i <= 13; ++$i) {
            $value = $this->getValue('kasam' . $i);

            // Check if value is valid (not null and >= 1)
            if (is_null($value) || $value < 1 || $value > 7) {
                $this->setFactor('kasam', null, 'profile.unknown');
                return;
            }

            $kasamValues[] = $value;
        }

        // Calculate the sum
        $sum = array_sum($kasamValues);

        // Calculate the average
        $average = $sum / 13;

        // Transform from 1-7 scale to 1-5 scale
        $transformedValue = ((($average - 1) / (7 - 1)) * (5 - 1)) + 1;

        // Round to make it cleaner
        $transformedValue = round($transformedValue);

        // Set factor based on threshold
        if ($transformedValue < 3) {
            $this->setFactor('kasam', $transformedValue, 'profile.risk');
        } else {
            $this->setFactor('kasam', $transformedValue, 'profile.healthy');
        }
    }

    private function calcRiskGroup()
    {
        $riskGroup = 'unknown';

        $factor = $this->getFactor('strength2');
        if (! is_null($factor) && $factor['value'] >= 1 && $factor['value'] <= 2) {
            $factor = $this->getFactor('weight');
            if (! is_null($factor) && $factor['value'] >= 1 && $factor['value'] <= 2) {
                $riskGroup = 'risk';
            }
        }

        $factor = $this->getFactor('activitiesTimeSpent');
        if (! is_null($factor) && $factor['value'] == 1) {
            $riskGroup = 'risk';
        }

        $factor = $this->getFactor('smoking');
        if (! is_null($factor) && $factor['value'] >= 1 && $factor['value'] <= 3) {
            $riskGroup = 'risk';
        }

        if (config('fms.type') == 'work') {
            $factor = $this->getFactor('alcohol');
            if (! is_null($factor) && $factor['value'] >= 1 && $factor['value'] <= 2) {
                $riskGroup = 'risk';
            }
        }

        if ($riskGroup == 'unknown') {
            $riskCount = 0;
            foreach (ProfileFactor::sortedNames() as $name) {
                $factor = $this->getFactor($name);
                if (! is_null($factor) && $factor['status'] == 'profile.risk') {
                    ++$riskCount;
                }
            }

            if ($riskCount > 3) {
                $riskGroup = 'risk';
            } elseif ($riskCount == 3) {
                $riskGroup = 'warning';
            } else {
                $riskGroup = 'healthy';
            }
        }

        $this->profile->risk_group = $riskGroup;
        $this->profile->save();
    }
}
