<?php

namespace App;

enum Factors: int
{
    case Agility = 13;
    case Strength = 65;
    case Balance = 15;
    case Motor = 58;
    case Posture = 59;
    case Weight = 3;
    case Fitness = 4;
    case PhysicalTraining = 52;
    case ActivitiesTimeSpent = 50;
    case Sitting = 23;
    case BodySat = 12;
    case BodySympt = 2;
    case Health = 11;
    case Relaxed = 47;
    case Stomachache = 48;
    case Headache = 49;
    case Sleep = 43;
    case Stress = 46;
    case Smoking = 18; // Tobacco v
    case Snuffing = 25; // Tobacco ^
    case Alcohol = 19;
    case FoodHabits = 53;
    case FoodEnergyBalance = 80;
    case FoodFruit = 81;
    case FoodSweets = 56;
    case FoodFluid = 82;
    case FoodEnergyDrinks = 57;
    case Freetime = 22;
    case Media = 34;
    case Friends = 16;
    case Adults = 21;

    case Work = 86;
    case PhysicalActivity = 97;

    case Performance = 100;
    case physicalCondition = 99;
    case Wellbeing = 102;

    case Safety = 103;

    case Kasam = 24;
}
