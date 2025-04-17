<?php

namespace App\Services;
use Illuminate\Support\Facades\Cache;

use App\QuestionnairePage;
use Illuminate\Support\Str;

class QuestionsService
{
    public function workQuestions(): array
    {
        $page = QuestionnairePage::where('name', 'work')
            ->with('groups.questions.type')
            ->first();

        if (is_null($page)) {
            return [];
        }

        $groups = $this->transformGroupsWorkAndSchool($page->groups);

        return [
            'pageName' => $page->name,
            'pageLabel' => $page->label_sv,
            'groups' => $groups,
        ];
    }

    public function schoolQuestions(): array
    {
        $page = QuestionnairePage::where('name', 'school')
            ->with('groups.questions.type')
            ->first();

        if (is_null($page)) {
            return [];
        }

        $groups = $this->transformGroupsWorkAndSchool($page->groups);

        return [
            'pageName' => $page->name,
            'pageLabel' => $page->label_sv,
            'groups' => $groups,
        ];
    }

    public function lifeQuestions(): array
    {

        $key = env('DEEPL_KEY');
        $pages = QuestionnairePage::whereNotIn('name', ['work', 'school'])
            ->with('groups.questions.type')
            ->get();

        $sections = [];

        foreach ($pages as $page) {
            $groups = $this->transformGroups($page->groups);

            if ($page->name === 'physical') {
                if (config('fms.type') === 'school') {
                    array_splice($groups, 4, 0, [
                        [
                            'label' => 'physical-strength-results',
                            'category_id' => 0,
                            'can_improve' => true,
                            'improve_name' => 'strength2',
                            'questions' => [],
                        ],
                    ]);
                }

                // array_splice($groups, -1, 0, [
                //     [
                //         'label' => 'fitness-instruction',
                //         'category_id' => 0,
                //         'can_improve' => false,
                //         'improve_name' => null,
                //         'questions' => [],
                //     ],
                // ]);

                $groups = [
                    // [
                    //     'label' => 'prelude',
                    //     'category_id' => 0,
                    //     'can_improve' => false,
                    //     'improve_name' => null,
                    //     'questions' => [],
                    // ],
                    ...$groups,
                    [
                        'label' => 'epilogue',
                        'category_id' => 0,
                        'can_improve' => false,
                        'improve_name' => null,
                        'questions' => [],
                    ],
                ];
            } elseif ($page->name === 'physical_questions') {
                $groups = array_map(function ($group) {
                    return [
                        ...$group,
                        'can_improve' => true, // Ensure all groups are improvable
                    ];
                }, $groups);

                $groups = [
                    ...$groups,
                    [
                        'label' => 'Resultat Fysisk Aktivitet',
                        'category_id' => 97,
                        'can_improve' => true,
                        'improve_name' => 'physicalActivity',
                        'questions' => [],
                    ],
                ];
            } elseif ($page->name === 'wellbeing') {
                $groups = [
                    [
                        'label' => 'buddy',
                        'value' => [
                            'text' => '<br><br>Och nu några frågor<br>om hur du mår i livet.',
                            'color' => 'green',
                        ],
                    ],
                    ...$groups,
                ];
            } elseif ($page->name === 'drugs') {
                $processedGroups = [
                    [
                        'label' => 'buddy',
                        'value' => [
                            'text' => '<br>Nu över till några riktigt viktiga frågor.<br>Det är bara du som ser dina svar, så var ärlig mot dig själv!',
                            'color' => 'green',
                        ],
                    ],
                ];

                foreach ($groups as $group) {
                    $processedGroups[] = $group;
                }

                $groups = $processedGroups;
            } elseif ($page->name === 'activities') {
                $groups = [
                    [
                        'label' => 'buddy',
                        'value' => [
                            'text' => '<br><br>Hur har du det på<br>din fritid då?',
                            'color' => 'green',
                        ],
                    ],
                    ...$groups,
                ];
            } elseif ($page->name === 'kasam') {
                $groups = [
                    [
                        'label' => 'prelude',
                        'category_id' => 0,
                        'can_improve' => false,
                        'improve_name' => null,
                        'questions' => [],
                    ],
                    ...$groups,
                ];
            } elseif ($page->name === 'energy') {
                $groups[0]['questions'][0]['description'] = 'Jag äter följande 5 måltider: frukost, lunch, middag och mellanmål (inkl kvällsmat)';
                $oldGroup = $groups;
                $groups = [
                    ['label' => 'buddy-energy'],
                    ...$oldGroup,
                    /*$oldGroup[0],
                    [
                        'label' => 'Frukost',
                        'type' => 'food-breakfast',
                    ],
                    [
                        'label' => 'Mellanmål',
                        'type' => 'food-snack-count',
                    ],
                    [
                        'label' => 'Mellanmål',
                        'type' => 'food-snack',
                    ],
                    [
                        'label' => 'Lunch',
                        'type' => 'food-lunch',
                    ],
                    [
                        'label' => 'Middag',
                        'type' => 'food-dinner',
                    ],
                    ...array_slice($oldGroup, 1),*/
                ];
            }

            $sections[] = [
                'pageName' => $page->name,
                'pageLabel' => $page->label_sv,
                'groups' => $groups,
                'oldGroup' => $oldGroup ?? null,
            ];
        }


/*
        if ($key) {
            $deeplClient = new \DeepL\DeepLClient($key);

            foreach ($sections as &$section) {
                $page_label = $section['pageLabel'];
                $cacheKey = 'deepl:pageLabel:' . md5($page_label);
                $translated = Cache::remember($cacheKey, now()->addDays(7), function () use ($deeplClient, $page_label) {
                    return $deeplClient->translateText($page_label, 'sv', 'en-US')->text;
                });
                $section['pageLabel'] = $translated;

                foreach ($section['groups'] as &$group) {
                    // Translate group label
                    if (isset($group['label'])) {
                        $cacheKey = 'deepl:groupLabel:' . md5($group['label']);
                        $group['label'] = Cache::remember($cacheKey, now()->addDays(7), function () use ($deeplClient, $group) {
                            try {
                                return $deeplClient->translateText($group['label'], 'sv', 'en-US')->text;
                            } catch (\Exception $e) {
                                \Log::error('DeepL translation error (group label): ' . $e->getMessage());
                                return $group['label'];
                            }
                        });
                    }

                    // Translate question descriptions
                    if (isset($group['questions']) && is_array($group['questions'])) {
                        foreach ($group['questions'] as &$question) {
                            if (!empty($question['description'])) {
                                $cacheKey = 'deepl:questionDesc:' . md5($question['description']);
                                $question['description'] = Cache::remember($cacheKey, now()->addDays(7), function () use ($deeplClient, $question) {
                                    try {
                                        return $deeplClient->translateText($question['description'], 'sv', 'en-US')->text;
                                    } catch (\Exception $e) {
                                        \Log::error('DeepL translation error (question description): ' . $e->getMessage());
                                        return $question['description'];
                                    }
                                });
                            }

                            if (!empty($question['poster']) && !empty($question['poster']['text'])) {
                                $cacheKey = 'deepl:posterText:' . md5($question['poster']['text']);
                                $question['poster']['text'] = Cache::remember($cacheKey, now()->addDays(7), function () use ($deeplClient, $question) {
                                    try {
                                        return $deeplClient->translateText($question['poster']['text'], 'sv', 'en-US')->text;
                                    } catch (\Exception $e) {
                                        \Log::error('DeepL translation error (poster text): ' . $e->getMessage());
                                        return $question['poster']['text'];
                                    }
                                });
                            }
                        }
                    }
                }
            }
        }
*/
        return [
            'sections' => $sections,
        ];
    }

    protected function transformGroupsWorkAndSchool($pageGroups): array
    {
        $groups = [];

        foreach ($pageGroups as $group) {
            $questions = collect($group->questions)->map(function ($question) {
                $questionData = $question->data !== null ? json_decode($question->data) : null;

                return [
                    'id' => $question->id,
                    'name' => $question->form_name,
                    'description' => $question->description_sv,
                    'category_id' => $question->category_id,
                    'help_text' => $question->help_sv,
                    'type' => Str::of($question->type->template_name)->after('form-')->value(),
                    'label' => $question->label_sv,
                    'data' => collect($questionData->items)->map(fn ($value, $index) => [
                        'label' => $questionData->labels_sv[$index],
                        'value' => (string) $value,
                    ])->all(),
                    'video_id' => $question->video_id,
                ];
            });

            if (count($questions) === 0) {
                continue;
            }

            $groups[] = [
                'label' => $group->label_sv,
                'questions' => $questions,
            ];
        }

        return $groups;
    }

    protected function transformGroups($pageGroups): array
    {
        $skipQuestions = [
            'stepcount',
            'bodyWeightEst',
            'physicalText',
            'physicalCapacity',
            'physicalAir',
            'physicalStrength',
            'physicalQuickness',
            'physicalAgility',
            'energyNeeds',
            'energyIntake',
            'energyBalance',
            'energyDrink',
            'foodFruit',
            'foodFluid',
            // 'badFood',
        ];

        $skipQuestions[] = 'mlo2';
        $skipQuestions[] = 'cooper';
        $skipQuestions[] = 'beep';
        $skipQuestions[] = 'step';
        $skipQuestions[] = 'bicycle';
        $skipQuestions[] = 'walk';

        if (config('fms.type') === 'work') {
            $skipQuestions[] = 'drugsFriends';
        }

        $groupedQuestions = [
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

        $unimprovableQuestions = [
            'weight',
            'length',
            'length,weight',
            'energy',
            'energyWork',
            'sitting',
            'drugsFriends',
            'drugsFriendsAction',
            'training',
        ];

        $groups = [];

        $lastParentQuestionIndex = null;

        foreach ($pageGroups as $group) {
            $questions = [];
            $groupedQuestionsBucket = [];

            foreach ($group->questions as $question) {
                if (in_array($question->form_name, $skipQuestions, true)) {
                    continue;
                }

                $type = Str::of($question->type->template_name)->after('form-')->value();

                $questionData = $question->data !== null ? json_decode($question->data) : null;

                $items = null;

                if ($type === 'list-item') {
                    $collection = collect($questionData->items);

                    if ($questionData->count === 7) {
                        $items = $collection->map(function ($value, $index) use ($questionData) {
                            $label = $index + 1;
                            if ($questionData->labels_sv[$index] !== null) {
                                $label .= ' - ' . $questionData->labels_sv[$index];
                            }

                            return [
                                'label' => (string) $label,
                                'value' => (string) ($index + 1),
                            ];
                        });
                    } elseif ($questionData->count === 2) {
                        $items = $collection->map(function ($value, $index) use ($questionData) {
                            return [
                                'label' => $questionData->labels_sv[$index],
                                'value' => $value === '1' ? '0' : '1',
                            ];
                        });
                    } else {
                        $items = $collection->map(fn ($value, $index) => [
                            'label' => $questionData->labels_sv[$index],
                            'value' => (string) $value,
                        ]);
                    }
                } elseif ($type === 'joint') {
                    $items = [
                        [
                            'label' => t('elements.joint-train'),
                            'value' => '0',
                        ],
                        [
                            'label' => t('elements.joint-good'),
                            'value' => '1',
                        ],
                    ];
                } elseif (str_starts_with($type, 'fitness-') && ! str_ends_with($type, 'cooper')) {
                    continue;
                }

                $categoryId = $question->category_id;

                $name = $question->form_name;
                if ($name === 'cooper') {
                    $name = 'fitCooperDistance';
                } elseif ($name === 'alcoholOften') {
                    $name = 'alcoholDrink';
                    $type = 'alcohol';
                    $categoryId = 95;
                } elseif ($name === 'pushups') {
                    $type = 'pushups';
                }

                // Todo: Return correct localized strings from request language
                $description = $question->description_sv;

                $label = $question->label_sv ?? '';
                if (str_contains($label, 'Rörlighet')) {
                    $label = Str::before($label, ' ');
                } elseif (str_contains($label, 'Dopning')) {
                    $description = (string) Str::of($description)->before('OBS')->trim();
                }

                if (is_string($description) && preg_match('/<a.+href="(.+?\.jpg)".*?>(.+?)<\/a>/i', $description, $matches)) {
                    $question->poster_sv_url = url($matches[1]);
                    $question->poster_sv_text = trim($matches[2], ' ()');

                    $description = trim(preg_replace('/<a.+href="(.+?)".*?>(.+?)<\/a>/i', '', $description));
                }

                $transformedQuestion = [
                    'id' => $question->id,
                    'name' => $name,
                    'description' => $description,
                    'category_id' => $categoryId,
                    'type' => $type,
                    'label' => $label,
                    'data' => $items ?? $questionData,
                    'video_id' => $question->video_id,
                    'poster' => [
                        'url' => $question->poster_sv_url !== null ? url($question->poster_sv_url) : null,
                        'text' => $question->poster_sv_text,
                    ],
                ];

                if ($question->has_subquestion && $question->is_conditional) {
                    $transformedQuestion['subquestions'] = [];
                    $lastParentQuestionIndex = count($questions);
                }

                if ($question->is_subquestion && $question->is_part_of_conditional) {
                    $transformedQuestion['toggle_value'] = $questionData->toggle_value;
                    $questions[$lastParentQuestionIndex][0]['subquestions'][] = $transformedQuestion;
                } elseif (in_array($question->form_name, $groupedQuestions, true)) {
                    $groupedQuestionsBucket[] = $transformedQuestion;
                } else {
                    $questions[] = [$transformedQuestion];
                }
            }

            if (count($groupedQuestionsBucket) > 0) {
                $groups[] = [
                    'label' => $group->label_sv,
                    'category_id' => $groupedQuestionsBucket[0]['category_id'],
                    'can_improve' => true,
                    'improve_name' => 'agility',
                    'questions' => $groupedQuestionsBucket,
                ];
            }

            if (count($questions) > 0) {
                foreach ($questions as $qs) {
                    $improveName = $qs[0]['name'];

                    $groups[] = [
                        'label' => $qs[0]['label'],
                        'category_id' => $qs[0]['category_id'],
                        'can_improve' => ! in_array($improveName, $unimprovableQuestions, true) &&
                            ! str_contains($improveName, 'kasam'),
                        'improve_name' => $improveName,
                        'questions' => $qs,
                    ];
                }
            }
        }

        return $groups;
    }
}
