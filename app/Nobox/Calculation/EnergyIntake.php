<?php

namespace App\Nobox\Calculation;

use Illuminate\Support\Facades\App;

class EnergyIntake
{
    public static $groups = [
        [
            'name' => 'breakfast',
            'label' => 'Frukost',
        ],
        [
            'name' => 'lunch',
            'label' => 'Lunch (om du upplever att du äter en stor portion eller tar två portioner eller vid fler tillfälle under dagen så klickar du fler än en portion)',
        ],
        [
            'name' => 'snacks',
            'label' => 'Fika/snacks',
        ],
        [
            'name' => 'dinner',
            'label' => 'Middag (om du upplever att du äter en stor portion eller tar två portioner eller vid fler tillfälle under dagen så klickar du fler än en portion)',
        ],
        [
            'name' => 'eveningmeal',
            'label' => 'Kvällsmål',
        ],
    ];

    public static function getGroups()
    {
        $locale = App::getLocale();
        
        if ($locale === 'en') {
            return [
                [
                    'name' => 'breakfast',
                    'label' => 'Breakfast',
                ],
                [
                    'name' => 'lunch',
                    'label' => 'Lunch (if you feel you eat a large portion or take two portions or at several times during the day, you click more than one portion)',
                ],
                [
                    'name' => 'snacks',
                    'label' => 'Coffee break/snacks',
                ],
                [
                    'name' => 'dinner',
                    'label' => 'Dinner (if you feel you eat a large portion or take two portions or at several times during the day, you click more than one portion)',
                ],
                [
                    'name' => 'eveningmeal',
                    'label' => 'Evening meal',
                ],
            ];
        }
        
        return self::$groups;
    }

    public static function getOptions()
    {
        $locale = App::getLocale();
        
        if ($locale === 'en') {
            // For English, we'll use DeepL translation if available
            $key = env('DEEPL_KEY');
            if ($key) {
                try {
                    $deeplClient = new \DeepL\DeepLClient($key);
                    
                    // Collect all Swedish labels for translation
                    $swedishLabels = array_column(self::$options, 'label');
                    
                    // Translate all labels at once
                    $translatedLabels = $deeplClient->translateText($swedishLabels, 'sv', 'en-US');
                    
                    // Create new options array with translated labels
                    $translatedOptions = [];
                    foreach (self::$options as $index => $option) {
                        $translatedOptions[] = [
                            'group' => $option['group'],
                            'name' => $option['name'],
                            'label' => $translatedLabels[$index]->text,
                            'kcal' => $option['kcal'],
                        ];
                    }
                    
                    return $translatedOptions;
                } catch (\Exception $e) {
                    // If DeepL fails, fall back to Swedish
                    \Log::error('DeepL translation error (EnergyIntake options): ' . $e->getMessage());
                }
            }
        }
        
        return self::$options;
    }

    public static $options = [
        // breakfast
        [
            'group' => 'breakfast',
            'name' => 'energyIntakeBreakfast1',
            'label' => 'Mjölk/ fil/ yoghurt (ca 3 dl) + flingor såsom cornflakes',
            'kcal' => 330,
        ],
        [
            'group' => 'breakfast',
            'name' => 'energyIntakeBreakfast2',
            'label' => 'Mjölk/ fil/ yoghurt (ca 3 dl) + musli/ granola',
            'kcal' => 340,
        ],
        [
            'group' => 'breakfast',
            'name' => 'energyIntakeBreakfast3',
            'label' => 'Havregrynsgröt med sylt/ äpplemos och mjölk',
            'kcal' => 340,
        ],
        [
            'group' => 'breakfast',
            'name' => 'energyIntakeBreakfast4',
            'label' => '1 grov smörgås med smör + pålägg som skinka + ost',
            'kcal' => 190,
        ],
        [
            'group' => 'breakfast',
            'name' => 'energyIntakeBreakfast5',
            'label' => '1 ljus smörgås med smör + ost + marmelad',
            'kcal' => 190,
        ],
        [
            'group' => 'breakfast',
            'name' => 'energyIntakeBreakfast6',
            'label' => 'Ägg/ stekt ägg/ äggröra',
            'kcal' => 85,
        ],
        [
            'group' => 'breakfast',
            'name' => 'energyIntakeBreakfast7',
            'label' => 'Knäckebröd med smör',
            'kcal' => 120,
        ],
        [
            'group' => 'breakfast',
            'name' => 'energyIntakeBreakfast8',
            'label' => '1 glas mjölk/ juice/ havredryck etc. (3 dl)',
            'kcal' => 140,
        ],
        [
            'group' => 'breakfast',
            'name' => 'energyIntakeBreakfast9',
            'label' => '1 glas chokladdryck (3dl)',
            'kcal' => 200,
        ],
        [
            'group' => 'breakfast',
            'name' => 'energyIntakeBreakfast10',
            'label' => '1 smoothie av t.ex. yoghurt/ kvarg/ mjök + bär (ca 3-4 dl)',
            'kcal' => 300,
        ],
        [
            'group' => 'breakfast',
            'name' => 'energyIntakeBreakfast11',
            'label' => 'Kaffe/ te',
            'kcal' => 0,
        ],

        // lunch
        [
            'group' => 'lunch',
            'name' => 'energyIntakeLunch1',
            'label' => '1 portion spagetti och köttfärsås',
            'kcal' => 450,
        ],
        [
            'group' => 'lunch',
            'name' => 'energyIntakeLunch2',
            'label' => '1 portion lax med potatismos + ärtor',
            'kcal' => 560,
        ],
        [
            'group' => 'lunch',
            'name' => 'energyIntakeLunch3',
            'label' => '1 kycklingfilé med ris + sås',
            'kcal' => 465,
        ],
        [
            'group' => 'lunch',
            'name' => 'energyIntakeLunch4',
            'label' => '2-3 potatis + 2 biffar/ köttbullar (7-10 st) + brunsås + lingonsylt',
            'kcal' => 470,
        ],
        [
            'group' => 'lunch',
            'name' => 'energyIntakeLunch5',
            'label' => '1 portion makaroner + falukorv med ketchup',
            'kcal' => 535,
        ],
        [
            'group' => 'lunch',
            'name' => 'energyIntakeLunch6',
            'label' => '1 dl blandade grönsaker som wok/ djupfrysta grönsaks blandning med lite olja',
            'kcal' => 35,
        ],
        [
            'group' => 'lunch',
            'name' => 'energyIntakeLunch7',
            'label' => '1 dl blandad sallad/ gurka/ tomat/ paprika',
            'kcal' => 11,
        ],
        [
            'group' => 'lunch',
            'name' => 'energyIntakeLunch8',
            'label' => '1 portion snabbnudlar',
            'kcal' => 90,
        ],
        [
            'group' => 'lunch',
            'name' => 'energyIntakeLunch9',
            'label' => '1 portion soppa såsom köttsoppa/ broccolisoppa/ gulaschsoppa',
            'kcal' => 250,
        ],
        [
            'group' => 'lunch',
            'name' => 'energyIntakeLunch10',
            'label' => '1 korv med bröd + senap + ketchup',
            'kcal' => 272,
        ],
        [
            'group' => 'lunch',
            'name' => 'energyIntakeLunch11',
            'label' => 'Matsallad som pastasallad med räkor/ ägg/ kyckling/ skinka + dressing',
            'kcal' => 300,
        ],
        [
            'group' => 'lunch',
            'name' => 'energyIntakeLunch12',
            'label' => 'Hamburgare + pommes + cola d.v.s. ett meal',
            'kcal' => 1140,
        ],
        [
            'group' => 'lunch',
            'name' => 'energyIntakeLunch13',
            'label' => '1 köpt pizza',
            'kcal' => 730,
        ],
        [
            'group' => 'lunch',
            'name' => 'energyIntakeLunch14',
            'label' => '1 glas mjölk (3 dl)',
            'kcal' => 140,
        ],
        [
            'group' => 'lunch',
            'name' => 'energyIntakeLunch15',
            'label' => '1 cola/ 1 öl/ 1 glas vin',
            'kcal' => 130,
        ],

        // snacks
        [
            'group' => 'snacks',
            'name' => 'energyIntakeSnacks1',
            'label' => 'Kexchoklad/ dajm/ snickers (annan chokladkaka)',
            'kcal' => 230,
        ],
        [
            'group' => 'snacks',
            'name' => 'energyIntakeSnacks2',
            'label' => 'Risifrutti',
            'kcal' => 220,
        ],
        [
            'group' => 'snacks',
            'name' => 'energyIntakeSnacks3',
            'label' => 'Bulle/ sockerkaka',
            'kcal' => 170,
        ],
        [
            'group' => 'snacks',
            'name' => 'energyIntakeSnacks4',
            'label' => 'Energibar/ sportbar såsom Barbells',
            'kcal' => 220,
        ],
        [
            'group' => 'snacks',
            'name' => 'energyIntakeSnacks5',
            'label' => 'Banan/ avokado',
            'kcal' => 100,
        ],
        [
            'group' => 'snacks',
            'name' => 'energyIntakeSnacks6',
            'label' => 'Äpple/ apelsin/ päron',
            'kcal' => 50,
        ],
        [
            'group' => 'snacks',
            'name' => 'energyIntakeSnacks6',
            'label' => 'Liten påse chips/ ostbågar/ popcorn (100g)',
            'kcal' => 525,
        ],
        [
            'group' => 'snacks',
            'name' => 'energyIntakeSnacks7',
            'label' => 'Nötter 50 gram (olika sorter)',
            'kcal' => 300,
        ],
        [
            'group' => 'snacks',
            'name' => 'energyIntakeSnacks8',
            'label' => 'Glasstrut/ glass pinne (såsom magnum)',
            'kcal' => 200,
        ],
        [
            'group' => 'snacks',
            'name' => 'energyIntakeSnacks9',
            'label' => 'Plockgodis (1 hg)',
            'kcal' => 400,
        ],
        [
            'group' => 'snacks',
            'name' => 'energyIntakeSnacks10',
            'label' => 'Brieost (ca 50g)',
            'kcal' => 180,
        ],
        [
            'group' => 'snacks',
            'name' => 'energyIntakeSnacks11',
            'label' => 'Digestivekex (1 st)',
            'kcal' => 70,
        ],

        // dinner
        [
            'group' => 'dinner',
            'name' => 'energyIntakeDinner1',
            'label' => '1 portion spagetti och köttfärsås',
            'kcal' => 450,
        ],
        [
            'group' => 'dinner',
            'name' => 'energyIntakeDinner2',
            'label' => '1 portion lax med potatismos + ärtor',
            'kcal' => 560,
        ],
        [
            'group' => 'dinner',
            'name' => 'energyIntakeDinner3',
            'label' => '1 kycklingfilé med ris + sås',
            'kcal' => 465,
        ],
        [
            'group' => 'dinner',
            'name' => 'energyIntakeDinner4',
            'label' => '2-3 potatis + 2 biffar/ köttbullar (7-10 st) + brunsås + lingonsylt',
            'kcal' => 470,
        ],
        [
            'group' => 'dinner',
            'name' => 'energyIntakeDinner5',
            'label' => '1 portion makaroner + falukorv med ketchup',
            'kcal' => 535,
        ],
        [
            'group' => 'dinner',
            'name' => 'energyIntakeDinner6',
            'label' => '1 dl blandade grönsaker som wok/ djupfrysta grönsaks blandning med lite olja',
            'kcal' => 35,
        ],
        [
            'group' => 'dinner',
            'name' => 'energyIntakeDinner7',
            'label' => '1 dl blandad sallad/ gurka/ tomat/ paprika',
            'kcal' => 11,
        ],
        [
            'group' => 'dinner',
            'name' => 'energyIntakeDinner8',
            'label' => '1 portion snabbnudlar',
            'kcal' => 90,
        ],
        [
            'group' => 'dinner',
            'name' => 'energyIntakeDinner9',
            'label' => '1 portion soppa såsom köttsoppa/ broccolisoppa/ gulaschsoppa',
            'kcal' => 250,
        ],
        [
            'group' => 'dinner',
            'name' => 'energyIntakeDinner10',
            'label' => '1 korv med bröd + senap + ketchup',
            'kcal' => 272,
        ],
        [
            'group' => 'dinner',
            'name' => 'energyIntakeDinner11',
            'label' => 'Matsallad som pastasallad med räkor/ ägg/ kyckling/ skinka + dressing',
            'kcal' => 300,
        ],
        [
            'group' => 'dinner',
            'name' => 'energyIntakeDinner12',
            'label' => 'Hamburgare + pommes + cola d.v.s. ett meal',
            'kcal' => 1140,
        ],
        [
            'group' => 'dinner',
            'name' => 'energyIntakeDinner13',
            'label' => '1 köpt pizza',
            'kcal' => 730,
        ],
        [
            'group' => 'dinner',
            'name' => 'energyIntakeDinner14',
            'label' => '1 glas mjölk (3 dl)',
            'kcal' => 140,
        ],
        [
            'group' => 'dinner',
            'name' => 'energyIntakeDinner15',
            'label' => '1 cola/ 1 öl/ 1 glas vin',
            'kcal' => 130,
        ],

        // eveningmeal
        [
            'group' => 'eveningmeal',
            'name' => 'energyIntakeEveningmeal1',
            'label' => 'Mjölk/ fil/ yoghurt (ca 3 dl) + flingor såsom cornflakes',
            'kcal' => 330,
        ],
        [
            'group' => 'eveningmeal',
            'name' => 'energyIntakeEveningmeal2',
            'label' => 'Mjölk/ fil/ yoghurt (ca 3 dl) + musli/ granola',
            'kcal' => 340,
        ],
        [
            'group' => 'eveningmeal',
            'name' => 'energyIntakeEveningmeal3',
            'label' => 'Havregrynsgröt med sylt/ äpplemos och mjölk',
            'kcal' => 340,
        ],
        [
            'group' => 'eveningmeal',
            'name' => 'energyIntakeEveningmeal4',
            'label' => '1 grov smörgås med smör + pålägg som skinka + ost',
            'kcal' => 190,
        ],
        [
            'group' => 'eveningmeal',
            'name' => 'energyIntakeEveningmeal5',
            'label' => '1 ljus smörgås med smör + ost + marmelad',
            'kcal' => 190,
        ],
        [
            'group' => 'eveningmeal',
            'name' => 'energyIntakeEveningmeal6',
            'label' => 'Ägg/ stekt ägg/ äggröra',
            'kcal' => 85,
        ],
        [
            'group' => 'eveningmeal',
            'name' => 'energyIntakeEveningmeal7',
            'label' => 'Knäckebröd med smör',
            'kcal' => 120,
        ],
        [
            'group' => 'eveningmeal',
            'name' => 'energyIntakeEveningmeal8',
            'label' => '1 glas mjölk/ juice/ havredryck etc. (3 dl)',
            'kcal' => 140,
        ],
        [
            'group' => 'eveningmeal',
            'name' => 'energyIntakeEveningmeal9',
            'label' => '1 glas chokladdryck (3dl)',
            'kcal' => 200,
        ],
        [
            'group' => 'eveningmeal',
            'name' => 'energyIntakeEveningmeal10',
            'label' => '1 smoothie av t.ex. yoghurt/ kvarg/ mjök + bär (ca 3-4 dl)',
            'kcal' => 300,
        ],
        [
            'group' => 'eveningmeal',
            'name' => 'energyIntakeEveningmeal11',
            'label' => 'Kaffe/ te',
            'kcal' => 0,
        ],
    ];
}
