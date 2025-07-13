<?php

namespace App\Services;
use Illuminate\Support\Facades\Cache;

use App\QuestionnairePage;
use Illuminate\Support\Str;

class QuestionsService
{
    public function workQuestions(string $locale = 'sv'): array
    {
        $key = env('DEEPL_KEY');
        $page = QuestionnairePage::where('name', 'work')
            ->with('groups.questions.type')
            ->first();

        if (is_null($page)) {
            return [];
        }

        $groups = $this->transformGroupsWorkAndSchool($page->groups, $locale);

        $result = [
            'pageName' => $page->name,
            'pageLabel' => $this->getTranslatedLabel($page, $locale),
            'groups' => $groups,
        ];

        // Apply DeepL translation if needed and key is available
        if ($locale === 'en' && $key) {
            $result = $this->applyDeepLTranslationToWorkSchool($result);
        }

        return $result;
    }

    public function schoolQuestions(string $locale = 'sv'): array
    {
        $key = env('DEEPL_KEY');
        $page = QuestionnairePage::where('name', 'school')
            ->with('groups.questions.type')
            ->first();

        if (is_null($page)) {
            return [];
        }

        $groups = $this->transformGroupsWorkAndSchool($page->groups, $locale);

        $result = [
            'pageName' => $page->name,
            'pageLabel' => $this->getTranslatedLabel($page, $locale),
            'groups' => $groups,
        ];

        // Apply DeepL translation if needed and key is available
        if ($locale === 'en' && $key) {
            $result = $this->applyDeepLTranslationToWorkSchool($result);
        }

        return $result;
    }

    public function lifeQuestions(string $locale = 'sv'): array
    {
        $key = env('DEEPL_KEY');
        $pages = QuestionnairePage::whereNotIn('name', ['work', 'school'])
            ->with('groups.questions.type')
            ->get();

        $sections = [];

        foreach ($pages as $page) {
            $groups = $this->transformGroups($page->groups, $locale);

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

                $groups = [
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
                            'text' => $locale === 'en' ? '<br><br>How are you doing<br>in your free time then?' : '<br><br>Hur har du det på<br>din fritid då?',
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
                $groups[0]['questions'][0]['description'] = $locale === 'en' ? 'I eat the following 5 meals: breakfast, lunch, dinner and snacks (including evening meal)' : 'Jag äter följande 5 måltider: frukost, lunch, middag och mellanmål (inkl kvällsmat)';
                $oldGroup = $groups;
                $groups = [
                    ['label' => 'buddy-energy'],
                    ...$oldGroup,
                ];
            }

            $sections[] = [
                'pageName' => $page->name,
                'pageLabel' => $this->getTranslatedLabel($page, $locale),
                'groups' => $groups,
                'oldGroup' => $oldGroup ?? null,
            ];
        }

        // Apply DeepL translation if needed and key is available
        if ($locale === 'en' && $key) {
            $sections = $this->applyDeepLTranslation($sections);
        }

        return [
            'sections' => $sections,
        ];
    }

    protected function transformGroupsWorkAndSchool($pageGroups, string $locale): array
    {
        $groups = [];

        foreach ($pageGroups as $group) {
            $questions = collect($group->questions)->map(function ($question) use ($locale) {
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
                'label' => $this->getTranslatedLabel($group, $locale),
                'questions' => $questions,
            ];
        }

        return $groups;
    }

    protected function transformGroups($pageGroups, string $locale): array
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
                        $items = $collection->map(function ($value, $index) use ($questionData, $locale) {
                            $label = $index + 1;
                            $labels = $locale === 'en' && isset($questionData->labels_en) && !empty($questionData->labels_en[$index]) ? $questionData->labels_en : $questionData->labels_sv;
                            if ($labels[$index] !== null) {
                                $label .= ' - ' . $labels[$index];
                            }

                            return [
                                'label' => (string) $label,
                                'value' => (string) ($index + 1),
                            ];
                        });
                    } elseif ($questionData->count === 2) {
                        $items = $collection->map(function ($value, $index) use ($questionData, $locale) {
                            $labels = $locale === 'en' && isset($questionData->labels_en) && !empty($questionData->labels_en[$index]) ? $questionData->labels_en : $questionData->labels_sv;
                            return [
                                'label' => $labels[$index],
                                'value' => $value === '1' ? '0' : '1',
                            ];
                        });
                    } else {
                        $items = $collection->map(function ($value, $index) use ($questionData, $locale) {
                            // Check if English labels exist and are different from Swedish
                            $useEnglishLabels = false;
                            if ($locale === 'en' && isset($questionData->labels_en) && !empty($questionData->labels_en[$index])) {
                                // If English label exists and is different from Swedish, use it
                                if (isset($questionData->labels_sv[$index]) && $questionData->labels_en[$index] !== $questionData->labels_sv[$index]) {
                                    $useEnglishLabels = true;
                                }
                            }
                            
                            $labels = $useEnglishLabels ? $questionData->labels_en : $questionData->labels_sv;
                            return [
                                'label' => $labels[$index],
                                'value' => (string) $value,
                            ];
                        });
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

                // Always use Swedish content - DeepL will translate if needed
                $description = $question->description_sv;
                $label = $question->label_sv ?? '';
                if (str_contains($label, 'Rörlighet')) {
                    $label = Str::before($label, ' ');
                } elseif (str_contains($label, 'Dopning')) {
                    $description = (string) Str::of($description)->before('OBS')->trim();
                }

                if (is_string($description) && preg_match('/<a.+href="(.+?\.jpg)".*?>(.+?)<\/a>/i', $description, $matches)) {
                    $posterUrl = url($matches[1]);
                    $posterText = trim($matches[2], ' ()');

                    $description = trim(preg_replace('/<a.+href="(.+?)".*?>(.+?)<\/a>/i', '', $description));
                } else {
                    // Always use Swedish content - DeepL will translate if needed
                    $posterUrl = $question->poster_sv_url;
                    $posterText = $question->poster_sv_text;
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
                        'url' => $posterUrl !== null ? url($posterUrl) : null,
                        'text' => $posterText,
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
                    'label' => $this->getTranslatedLabel($group, $locale),
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

    protected function getTranslatedLabel($page, string $locale): string
    {
        $key = env('DEEPL_KEY');
        if ($locale === 'en' && $key) {
            $deeplClient = new \DeepL\DeepLClient($key);
            $cacheKey = 'deepl:pageLabel:' . md5($page->label_sv);
            return Cache::remember($cacheKey, now()->addDays(7), function () use ($deeplClient, $page) {
                try {
                    return $deeplClient->translateText($page->label_sv, 'sv', 'en-US')->text;
                } catch (\Exception $e) {
                    \Log::error('DeepL translation error (page label): ' . $e->getMessage());
                    return $page->label_sv;
                }
            });
        }
        return $page->label_sv;
    }

    protected function translateText(string $text, string $locale): string
    {
        $key = env('DEEPL_KEY');
        if ($locale === 'en' && $key) {
            $deeplClient = new \DeepL\DeepLClient($key);
            $cacheKey = 'deepl:text:' . md5($text);
            return Cache::remember($cacheKey, now()->addDays(7), function () use ($deeplClient, $text) {
                try {
                    return $deeplClient->translateText($text, 'sv', 'en-US')->text;
                } catch (\Exception $e) {
                    \Log::error('DeepL translation error (text): ' . $e->getMessage());
                    return $text;
                }
            });
        }
        return $text;
    }

    protected function applyDeepLTranslation(array $sections): array
    {
        $key = env('DEEPL_KEY');
        if ($key) {
            $deeplClient = new \DeepL\DeepLClient($key);

            // Collect all strings that need translation
            $stringsToTranslate = [];
            $translationMap = [];

            foreach ($sections as $sectionIndex => &$section) {
                $page_label = $section['pageLabel'];
                $stringsToTranslate[] = $page_label;
                $translationMap['section_page_' . $sectionIndex] = count($stringsToTranslate) - 1;

                if (isset($section['groups']) && is_array($section['groups'])) {
                    foreach ($section['groups'] as $groupIndex => &$group) {
                        if (!empty($group['label'])) {
                            $stringsToTranslate[] = $group['label'];
                            $translationMap['section_group_' . $sectionIndex . '_' . $groupIndex] = count($stringsToTranslate) - 1;
                        }

                        // Handle buddy text values
                        if (isset($group['value']) && isset($group['value']['text'])) {
                            $stringsToTranslate[] = $group['value']['text'];
                            $translationMap['section_buddy_text_' . $sectionIndex . '_' . $groupIndex] = count($stringsToTranslate) - 1;
                        }

                        if (isset($group['questions']) && is_array($group['questions'])) {
                            foreach ($group['questions'] as $questionIndex => &$question) {
                                if (!empty($question['description'])) {
                                    $stringsToTranslate[] = $question['description'];
                                    $translationMap['section_question_desc_' . $sectionIndex . '_' . $groupIndex . '_' . $questionIndex] = count($stringsToTranslate) - 1;
                                }

                                if (!empty($question['label'])) {
                                    // Check if English label is missing or identical to Swedish
                                    $shouldTranslate = true;
                                    if (isset($question['label_en']) && !empty($question['label_en'])) {
                                        // If English label exists and is different from Swedish, use it
                                        if ($question['label_en'] !== $question['label']) {
                                            $question['label'] = $question['label_en'];
                                            $shouldTranslate = false;
                                        }
                                    }
                                    
                                    if ($shouldTranslate) {
                                        $stringsToTranslate[] = $question['label'];
                                        $translationMap['section_question_label_' . $sectionIndex . '_' . $groupIndex . '_' . $questionIndex] = count($stringsToTranslate) - 1;
                                    }
                                }

                                if (!empty($question['poster']) && !empty($question['poster']['text'])) {
                                    $stringsToTranslate[] = $question['poster']['text'];
                                    $translationMap['section_poster_' . $sectionIndex . '_' . $groupIndex . '_' . $questionIndex] = count($stringsToTranslate) - 1;
                                }

                                // Collect option strings for question data
                                if (isset($question['data']) && is_array($question['data'])) {
                                    foreach ($question['data'] as $oIndex => $option) {
                                        if (!empty($option['label'])) {
                                            // Check if English label is missing or identical to Swedish
                                            $shouldTranslate = true;
                                            if (isset($option['label_en']) && !empty($option['label_en'])) {
                                                // If English label exists and is different from Swedish, use it
                                                if ($option['label_en'] !== $option['label']) {
                                                    $option['label'] = $option['label_en'];
                                                    $shouldTranslate = false;
                                                }
                                            }
                                            
                                            if ($shouldTranslate) {
                                                $stringsToTranslate[] = $option['label'];
                                                $translationMap['section_option_' . $sectionIndex . '_' . $groupIndex . '_' . $questionIndex . '_' . $oIndex] = count($stringsToTranslate) - 1;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // Batch translate all strings at once
            if (!empty($stringsToTranslate)) {
                \Log::info('Batch translating sections', ['count' => count($stringsToTranslate)]);
                
                try {
                    // Split into chunks of 50 to avoid DeepL limits
                    $chunks = array_chunk($stringsToTranslate, 50);
                    $allTranslatedStrings = [];
                    
                    foreach ($chunks as $chunkIndex => $chunk) {
                        \Log::info('Translating section chunk', ['chunk_index' => $chunkIndex, 'size' => count($chunk)]);
                        
                        $translatedChunk = $deeplClient->translateText($chunk, 'sv', 'en-US');
                        $allTranslatedStrings = array_merge($allTranslatedStrings, $translatedChunk);
                        
                        // Small delay between chunks to avoid rate limiting
                        if ($chunkIndex < count($chunks) - 1) {
                            usleep(200000); // 200ms delay
                        }
                    }
                    
                    \Log::info('Section batch translation completed', ['total_translated' => count($allTranslatedStrings)]);
                    
                    // Apply translations back to the data structure
                    foreach ($sections as $sectionIndex => &$section) {
                        if (isset($translationMap['section_page_' . $sectionIndex])) {
                            $section['pageLabel'] = $allTranslatedStrings[$translationMap['section_page_' . $sectionIndex]]->text;
                        }

                        if (isset($section['groups']) && is_array($section['groups'])) {
                            foreach ($section['groups'] as $groupIndex => &$group) {
                                if (!empty($group['label']) && isset($translationMap['section_group_' . $sectionIndex . '_' . $groupIndex])) {
                                    $group['label'] = $allTranslatedStrings[$translationMap['section_group_' . $sectionIndex . '_' . $groupIndex]]->text;
                                }

                                // Apply buddy text translations
                                if (isset($group['value']) && isset($group['value']['text']) && isset($translationMap['section_buddy_text_' . $sectionIndex . '_' . $groupIndex])) {
                                    $group['value']['text'] = $allTranslatedStrings[$translationMap['section_buddy_text_' . $sectionIndex . '_' . $groupIndex]]->text;
                                }

                                if (isset($group['questions']) && is_array($group['questions'])) {
                                    foreach ($group['questions'] as $questionIndex => &$question) {
                                        if (!empty($question['description']) && isset($translationMap['section_question_desc_' . $sectionIndex . '_' . $groupIndex . '_' . $questionIndex])) {
                                            $question['description'] = $allTranslatedStrings[$translationMap['section_question_desc_' . $sectionIndex . '_' . $groupIndex . '_' . $questionIndex]]->text;
                                        }

                                        if (!empty($question['label']) && isset($translationMap['section_question_label_' . $sectionIndex . '_' . $groupIndex . '_' . $questionIndex])) {
                                            $question['label'] = $allTranslatedStrings[$translationMap['section_question_label_' . $sectionIndex . '_' . $groupIndex . '_' . $questionIndex]]->text;
                                        }

                                        if (!empty($question['poster']) && !empty($question['poster']['text']) && isset($translationMap['section_poster_' . $sectionIndex . '_' . $groupIndex . '_' . $questionIndex])) {
                                            $question['poster']['text'] = $allTranslatedStrings[$translationMap['section_poster_' . $sectionIndex . '_' . $groupIndex . '_' . $questionIndex]]->text;
                                        }

                                        // Apply option translations
                                        if (isset($question['data']) && is_array($question['data'])) {
                                            foreach ($question['data'] as $oIndex => &$option) {
                                                if (!empty($option['label']) && isset($translationMap['section_option_' . $sectionIndex . '_' . $groupIndex . '_' . $questionIndex . '_' . $oIndex])) {
                                                    $option['label'] = $allTranslatedStrings[$translationMap['section_option_' . $sectionIndex . '_' . $groupIndex . '_' . $questionIndex . '_' . $oIndex]]->text;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    
                } catch (\Exception $e) {
                    \Log::error('DeepL section batch translation error: ' . $e->getMessage());
                }
            }
        }

        return $sections;
    }

    protected function applyDeepLTranslationToWorkSchool(array $result): array
    {
        $key = env('DEEPL_KEY');
        if ($key) {
            $deeplClient = new \DeepL\DeepLClient($key);

            // Debug: Log the structure of the data
            \Log::info('applyDeepLTranslationToWorkSchool called', [
                'result_keys' => array_keys($result),
                'groups_count' => count($result['groups'] ?? []),
                'first_group' => $result['groups'][0] ?? null
            ]);

            // Collect all strings that need translation
            $stringsToTranslate = [];
            $translationMap = [];

            // Translate page label
            if (!empty($result['pageLabel'])) {
                $stringsToTranslate[] = $result['pageLabel'];
                $translationMap['pageLabel'] = count($stringsToTranslate) - 1;
            }

            // Process groups
            foreach ($result['groups'] as &$group) {
                \Log::info('Processing group', [
                    'group_keys' => array_keys($group),
                    'has_questions' => isset($group['questions']),
                    'questions_count' => isset($group['questions']) ? (is_array($group['questions']) ? count($group['questions']) : $group['questions']->count()) : 0
                ]);

                // Translate group label
                if (!empty($group['label'])) {
                    $stringsToTranslate[] = $group['label'];
                    $translationMap['group_' . $group['label']] = count($stringsToTranslate) - 1;
                }

                // Process questions
                if (isset($group['questions'])) {
                    $questions = is_array($group['questions']) ? $group['questions'] : $group['questions']->toArray();
                    
                    foreach ($questions as $qIndex => &$question) {
                        // Debug: Log question structure
                        \Log::info('Processing question', [
                            'question_keys' => array_keys($question),
                            'has_description' => !empty($question['description']),
                            'description' => $question['description'] ?? 'NO_DESCRIPTION'
                        ]);

                        // Collect question strings
                        if (!empty($question['description'])) {
                            $stringsToTranslate[] = $question['description'];
                            $translationMap['question_desc_' . $qIndex] = count($stringsToTranslate) - 1;
                        }

                        if (!empty($question['label'])) {
                            // Check if English label is missing or identical to Swedish
                            $shouldTranslate = true;
                            if (isset($question['label_en']) && !empty($question['label_en'])) {
                                // If English label exists and is different from Swedish, use it
                                if ($question['label_en'] !== $question['label']) {
                                    $question['label'] = $question['label_en'];
                                    $shouldTranslate = false;
                                }
                            }
                            
                            if ($shouldTranslate) {
                                $stringsToTranslate[] = $question['label'];
                                $translationMap['question_label_' . $qIndex] = count($stringsToTranslate) - 1;
                            }
                        }

                        if (!empty($question['help_text'])) {
                            $stringsToTranslate[] = $question['help_text'];
                            $translationMap['question_help_' . $qIndex] = count($stringsToTranslate) - 1;
                        }

                        // Collect option strings
                        if (isset($question['data']) && is_array($question['data'])) {
                            foreach ($question['data'] as $oIndex => $option) {
                                if (!empty($option['label'])) {
                                    // Check if English label is missing or identical to Swedish
                                    $shouldTranslate = true;
                                    if (isset($option['label_en']) && !empty($option['label_en'])) {
                                        // If English label exists and is different from Swedish, use it
                                        if ($option['label_en'] !== $option['label']) {
                                            $option['label'] = $option['label_en'];
                                            $shouldTranslate = false;
                                        }
                                    }
                                    
                                    if ($shouldTranslate) {
                                        $stringsToTranslate[] = $option['label'];
                                        $translationMap['option_' . $qIndex . '_' . $oIndex] = count($stringsToTranslate) - 1;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // Batch translate all strings at once
            if (!empty($stringsToTranslate)) {
                \Log::info('Batch translating', ['count' => count($stringsToTranslate)]);
                
                try {
                    // Split into chunks of 50 to avoid DeepL limits
                    $chunks = array_chunk($stringsToTranslate, 50);
                    $allTranslatedStrings = [];
                    
                    foreach ($chunks as $chunkIndex => $chunk) {
                        \Log::info('Translating chunk', ['chunk_index' => $chunkIndex, 'size' => count($chunk)]);
                        
                        $translatedChunk = $deeplClient->translateText($chunk, 'sv', 'en-US');
                        $allTranslatedStrings = array_merge($allTranslatedStrings, $translatedChunk);
                        
                        // Small delay between chunks to avoid rate limiting
                        if ($chunkIndex < count($chunks) - 1) {
                            usleep(200000); // 200ms delay
                        }
                    }
                    
                    \Log::info('Batch translation completed', ['total_translated' => count($allTranslatedStrings)]);
                    
                    // Apply translations back to the data structure
                    if (!empty($result['pageLabel']) && isset($translationMap['pageLabel'])) {
                        $result['pageLabel'] = $allTranslatedStrings[$translationMap['pageLabel']]->text;
                    }

                    foreach ($result['groups'] as &$group) {
                        if (!empty($group['label']) && isset($translationMap['group_' . $group['label']])) {
                            $group['label'] = $allTranslatedStrings[$translationMap['group_' . $group['label']]]->text;
                        }

                        if (isset($group['questions'])) {
                            $questions = is_array($group['questions']) ? $group['questions'] : $group['questions']->toArray();
                            
                            foreach ($questions as $qIndex => &$question) {
                                if (!empty($question['description']) && isset($translationMap['question_desc_' . $qIndex])) {
                                    $question['description'] = $allTranslatedStrings[$translationMap['question_desc_' . $qIndex]]->text;
                                }

                                if (!empty($question['label']) && isset($translationMap['question_label_' . $qIndex])) {
                                    $question['label'] = $allTranslatedStrings[$translationMap['question_label_' . $qIndex]]->text;
                                }

                                if (!empty($question['help_text']) && isset($translationMap['question_help_' . $qIndex])) {
                                    $question['help_text'] = $allTranslatedStrings[$translationMap['question_help_' . $qIndex]]->text;
                                }

                                if (isset($question['data']) && is_array($question['data'])) {
                                    foreach ($question['data'] as $oIndex => &$option) {
                                        if (!empty($option['label']) && isset($translationMap['option_' . $qIndex . '_' . $oIndex])) {
                                            $option['label'] = $allTranslatedStrings[$translationMap['option_' . $qIndex . '_' . $oIndex]]->text;
                                        }
                                    }
                                }
                            }
                            
                            // Update the group with translated questions
                            $group['questions'] = $questions;
                        }
                    }
                    
                } catch (\Exception $e) {
                    \Log::error('DeepL batch translation error: ' . $e->getMessage());
                }
            }
        }

        return $result;
    }
}
