<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProfileFactor extends Model
{
    protected $table = 'profile_factors';

    public $timestamps = false;

    public $fillable = [
        'profile_id',
        'category_id',
    ];

    public function scopeFactors($query)
    {
        return [
            'factors.physical',
            'factors.bodySympt',
            'factors.weight',
            'factors.fitness',
            'factors.drugs',
            'factors.strength',
            'factors.badfood',
            'factors.stress',
            'factors.schlStress',
            'factors.sleep',
            'factors.health',
            'factors.bodySat',
            'factors.agility',
            'factors.goodfood',
            'factors.balance',
            'factors.friends',
            'factors.diffSympt',
            'factors.smoking',
            'factors.snuffing',
            'factors.alcohol',
            'factors.schlComfort',
            'factors.adults',
            'factors.freetime',
            'factors.media',
            'factors.kasam',
            'factors.alcoholDrink',
        ];
    }

    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }

    public function category()
    {
        return $this->belongsTo(QuestionnaireCategory::class, 'category_id');
    }

    public function isUnknown()
    {
        return $this->status == 'profile.unknown';
    }

    public function isHealthy()
    {
        return $this->status == 'profile.healthy';
    }

    public function isRisk()
    {
        return $this->status == 'profile.risk';
    }

    public static function sortedNames()
    {
        if (config('fms.type') == 'work') {
            return [
                'agility',
                'strength2',
                'strLegs',
                'strBack',
                'strArms',
                'strAbs',
                'balance',
                'motor',
                'posture',
                'fitness',
                'stepcount',
                'weight',
                'physical',
                'physicalTraining',
                'physicalCondition',
                'activitiesTimeSpent',
                'sitting',
                'health',
                'bodySat',
                'bodySympt',
                'diffSymptRelaxed',
                'diffSymptStomach',
                'diffSymptHead',
                'sleepHour',
                'calmness',
                'smoking',
                'snuffing',
                'alcohol',
                'alcoholDrink',
                'foodHabits',
                'foodContents',
                'foodAmount',
                'foodEnergyBalance',
                'foodFruit',
                'foodSweets',
                'foodFluid',
                'foodEnergyDrinks',
                'freetime',
                'media',
                'friends',
                'work1',
                'work2',
                'work3',
                'work4',
                'work5',
                'work6',
                'work7',
                'work8',
                'work9',
                'work10',
                'work11',
                'work12',
                'work13',
                'work14',
                'work15',
                'work16',
                'kasam',
            ];
        } else {
            return [
                'agility',
                'strength2',
                'strLegs',
                'strBack',
                'strArms',
                'strAbs',
                'balance',
                'motor',
                'posture',
                'fitness',
                'stepcount',
                'weight',
                'physical',
                'physicalTraining',
                'physicalCondition',
                'activitiesTimeSpent',
                'sitting',
                'health',
                'bodySat',
                'bodySympt',
                'diffSymptRelaxed',
                'diffSymptStomach',
                'diffSymptHead',
                'sleepHour',
                'calmness',
                'smoking',
                'snuffing',
                'alcohol',
                'alcoholDrink',
                'foodHabits',
                'foodContents',
                'foodAmount',
                'foodEnergyBalance',
                'foodFruit',
                'foodSweets',
                'foodFluid',
                'foodEnergyDrinks',
                'freetime',
                'media',
                'friends',
                'adults',
                'school1',
                'school2',
                'school3',
                'school4',
                'school5',
                'school6',
                'school7',
                'school8',
                'school9',
                'school10',
                'school11',
                'schlGoal',
                'schlResult',
                'schlSituationInfo',
                'schlComfort',
                'schlSafety',
                'schlSeen',
                'kasam',
            ];
        }
    }
}
