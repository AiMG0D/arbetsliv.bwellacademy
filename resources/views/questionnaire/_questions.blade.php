<?php
// number of questions in each category
$counts = DB::table('questionnaire_questions')
    ->select('category_id', DB::raw('count(*) as total'))
    ->whereNotNull('category_id')
    ->groupBy('category_id')
    ->pluck('total', 'category_id');

// Simple DeepL translation function for frontend
function translateWithDeepL($text, $from = 'sv', $to = 'en') {
    if (App::isLocale('sv')) {
        return $text; // No translation needed for Swedish
    }
    
    $key = env('DEEPL_KEY');
    if (!$key) {
        return $text; // No DeepL key available
    }
    
    try {
        $deeplClient = new \DeepL\DeepLClient($key);
        $result = $deeplClient->translateText($text, $from, $to);
        return $result[0]->text;
    } catch (Exception $e) {
        \Log::error('DeepL translation failed: ' . $e->getMessage());
        return $text; // Return original text if translation fails
    }
}

$groups = $page->groups()
    ->with('type')
    ->orderBy('weight', 'ASC')
    ->get();

foreach ($groups as $group) {
    $hidden = false;

    if ($group->type->name !== "list") {
        $classes = "header";
        if ($group->hide_label) {
            $classes .= " hidden";
        }
        echo "<li class=\"" . $classes . "\"><h3>" . $group->t_label() . "</h3></li>";
    }

    echo '<ol class="question-group">';

    $category_count = [];

    $questions = $group->questions()
        ->with('category', 'type')
        ->orderBy('weight', 'ASC')
        ->get();

    foreach ($questions as $question) {
        if (App::isLocale('en')) {
            $skip = array('physicalText', 'physicalCapacity', 'physicalAir', 'physicalStrength', 'physicalQuickness', 'physicalAgility');
            if (in_array($question->form_name, $skip)) {
                continue;
            }
        }
        $typeName = $question->type->template_name;
        $json = json_decode($question->data, true);

        if (!isset($json['labels'])) {
            if (App::isLocale('sv')) {
                if (isset($json['labels_sv'])) {
                    $json['labels'] = $json['labels_sv'];
                }
            } elseif (App::isLocale('en')) {
                // Check if English labels exist and are different from Swedish
                $useEnglishLabels = false;
                if (isset($json['labels_en']) && !empty(array_filter($json['labels_en']))) {
                    // Check if English labels are different from Swedish
                    if (isset($json['labels_sv'])) {
                        $differentLabels = false;
                        foreach ($json['labels_en'] as $index => $enLabel) {
                            if (isset($json['labels_sv'][$index]) && $enLabel !== $json['labels_sv'][$index]) {
                                $differentLabels = true;
                                break;
                            }
                        }
                        if ($differentLabels) {
                            $json['labels'] = $json['labels_en'];
                            $useEnglishLabels = true;
                        }
                    } else {
                        $json['labels'] = $json['labels_en'];
                        $useEnglishLabels = true;
                    }
                }
                
                // If English labels are missing or identical to Swedish, use Swedish and translate with DeepL
                if (!$useEnglishLabels && isset($json['labels_sv'])) {
                    $json['labels'] = $json['labels_sv'];
                    // Translate Swedish labels to English if we're in English mode
                    if (App::isLocale('en')) {
                        foreach ($json['labels'] as $index => $label) {
                            $json['labels'][$index] = translateWithDeepL($label);
                        }
                    }
                }
            }
        }
        
        // DEBUG: Check if this is the physicalCondition question
        if ($question->form_name === 'physicalCondition') {
            \Log::info('PHYSICAL CONDITION DEBUG', [
                'question_name' => $question->form_name,
                'locale' => App::getLocale(),
                'has_labels' => isset($json['labels']),
                'labels' => $json['labels'] ?? 'NOT SET',
                'labels_sv' => $json['labels_sv'] ?? 'NOT SET',
                'labels_en' => $json['labels_en'] ?? 'NOT SET',
                'useEnglishLabels' => $useEnglishLabels ?? 'NOT SET'
            ]);
        }

        $description = $question->t_description();
        if ($question->t_poster() !== null) {
            $url = $question->t_poster();
            $urlText = $question->t_poster_text();

            $description .= " <a href='{$url}' class='show-image-popup' target='_blank'>({$urlText})</a>";
        }

        $data = array(
            'label' => $question->t_label(),
            'form_name' => $question->form_name,
            'description' => $description,
            'has_help' => $question->has_help,
            'help' => $question->t_help(),
            'hidden' => $hidden,
            'is_subquestion' => $question->is_subquestion,
            'has_subquestion' => $question->has_subquestion,
            'in_special_group' => false,
            'editable' => $editable,
            'participant' => $participant,
            'mock' => $mock,
        );

        switch ($question->type_id) {
            case 1:
                $data['suffix'] = isset($json['suffix']) ? $json['suffix'] : "";

                $template = 'questionnaire.templates.form-textbox';
                break;
            case 2:
                $data['min'] = $json['min'];
                $data['max'] = $json['max'];
                $data['items'] = $json['items'];
                $data['is_conditional'] = $question->is_conditional;
                $data['is_part_of_conditional'] = $question->is_part_of_conditional;

                $template = 'questionnaire.templates.form-radio';
                break;
            case 3:
                $data['items'] = $json['items'];
                $data['labels'] = $json['labels'];
                $data['count'] = $json['count'];
                $data['is_conditional'] = $question->is_conditional;
                $data['is_part_of_conditional'] = $question->is_part_of_conditional;

                if ($data['is_subquestion'] && $hidden) {
                    $data['hidden'] = true;
                } else {
                    $hidden = false;
                    $data['hidden'] = false;
                }

                $template = 'questionnaire.templates.list-item';

                if (isset($data['is_conditional']) && $data['is_conditional']) {
                    $hidden = true;
                }
                break;
            case 4:
                $template = 'questionnaire.templates.form-joint';
                break;
            case 5:
                $data['labels'] = $json['labels'];

                $template = 'questionnaire.templates.form-estimation';
                break;
            case 6:
                $template = 'questionnaire.templates.form-bmi';
                break;
            case 7:
                $data['suffix'] = isset($json['suffix']) ? $json['suffix'] : "";
                $data['form_name'] = explode(",", $data['form_name']);

                $template = 'questionnaire.templates.form-twovalues';
                break;
            case 8:
                $template = 'questionnaire.templates.form-mfr';
                break;
            case 9:
                $template = 'questionnaire.templates.form-fit-method';
                break;
            case 10:
                $template = 'questionnaire.templates.fit-step';
                break;
            case 11:
                $template = 'questionnaire.templates.fit-bike';
                break;
            case 12:
                $template = 'questionnaire.templates.fit-walk';
                break;
            case 13:
                $template = 'questionnaire.templates.fit-mlo2';
                break;
            case 14:
                $template = 'questionnaire.templates.fit-lo2';
                break;
            case 15:
                $data['is_part_of_conditional'] = $question->is_part_of_conditional;

                $template = 'questionnaire.templates.list-text';
                break;
            case 16:
                $template = 'questionnaire.templates.list-text-node';
                break;
            case 17:
                $template = 'questionnaire.templates.form-text-node';
                break;
            case 18:
                $template = 'questionnaire.templates.fit-beep';
                break;
            case 19:
                $template = 'questionnaire.templates.form-arm-method';
                break;
            case 20:
                $template = 'questionnaire.templates.fit-cooper';
                break;
            case 21:
                $template = 'questionnaire.templates.form-energy-needs';
                break;
            case 22:
                $template = 'questionnaire.templates.form-energy-intake';
                break;
            case 23:
                $template = 'questionnaire.templates.form-energy-balance';
                break;
            default:
                break;
        }

        $data['type_id'] = $question->type_id;
        if (isset($json['toggle_value'])) {
            $data['toggle_value'] = $json['toggle_value'];
        }

        // temp
        $category = $question->category;
        $last_in_category = false;
        if ($category) {
            if (array_key_exists($category->id, $category_count)) {
                $category_count[$category->id]++;
            } else {
                $category_count[$category->id] = 1;
            }
            $last_in_category = $counts[$category->id] == $category_count[$category->id];

            $data['category_id'] = $category->id;
            $data['category_id_n'] = $category_count[$category->id];
        }

        // echo view($template, $data); ?>
        @include($template, $data)
        <?php

        // improvements
        if ($category && $last_in_category) {
            // last question with this category
            $improve_data = [];
            $improve_data['category_id'] = $category->id;
            $improve_data['name'] = $category->name;
            $improve_data['label'] = t($category->label);
            $improve_data['profile'] = $profile;
            $improve_data['editable'] = $editable;

            // echo view('questionnaire.templates.improve', $improve_data);
            ?>
            @include('questionnaire.templates.improve', $improve_data)
            <?php
        }
    } // foreach question

    echo "</ol>\n";
}
?>
