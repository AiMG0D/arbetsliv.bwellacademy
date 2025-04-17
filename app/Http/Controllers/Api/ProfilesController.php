<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\CreateNewProfile;
use App\Actions\FinishProfile;
use App\Factors;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateProfileRequest;
use App\Http\Requests\FinishProfileRequest;
use App\Profile;
use App\ProfileFactor;
use App\ProfileText;
use App\ProfileValue;
use App\QuestionnaireCategory;
use App\QuestionnaireQuestion;
use App\Services\ProfilesService;
use App\Services\QuestionsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProfilesController extends Controller
{
    public function profileValues(Profile $profile)
    {
        return $profile->values;
    }

    public function updateImproveValue(Profile $profile, Request $request)
    {
        // TODO: check permission

        $category_id = $request->input('category_id');
        $value = $request->input('value');

        $factor = $profile->factors()->where('category_id', $category_id)->first();

        if (is_null($factor)) {
            return response('', 404)->header('Content-Type', 'application/json');
        }

        $factor->improve = $value;
        $factor->save();

        return response('Saved!')->header('Content-Type', 'application/json');
    }

    public function updateProfileValue(Profile $profile, Request $request)
    {
        if ($profile->user_id !== $request->user()->id) {
            abort(403);
        }

        $data = $request->all();

        $values = $data['values'] ?? $data;

        if ($values['name'] === 'cooper' || $values['name'] === 'fitCooperDistance') {
            $profile->setValue('fitMethod', 7);
        }

        $this->parseValueArray($values, $profile);

        $profile->calculate();

        $profile = $profile->fresh(['values','factors']);

        $data['profile_values'] = $profile->values;
        $data['profile_factors'] = $profile->factors->map(function ($f) {
            $f->label = t($f->status);

            return $f;
        });

        $category_id = null;

        if (isset($data['name'])) {
            if (Str::startsWith($data['name'], 'fit')) {
                $category = QuestionnaireCategory::where('name', 'fitness')->first();
                if ($category) {
                    $category_id = $category->id;
                }
            } elseif (in_array($data['name'], ['armMethod', 'strArmWeights', 'pushupsType'])) {
                $category = QuestionnaireCategory::where('name', 'strArms')->first();
                if ($category) {
                    $category_id = $category->id;
                }
            } elseif (Str::startsWith($data['name'], 'energyIntake')) {
                $category = QuestionnaireCategory::where('name', 'foodEnergyBalance')->first();
                if ($category) {
                    $category_id = $category->id;
                }
            } elseif (Str::startsWith($data['name'], 'alcoholDrink')) {
                $category = QuestionnaireCategory::where('name', 'alcoholDrink')->first();
                if ($category) {
                    $category_id = $category->id;
                }
            } else {
                $question = QuestionnaireQuestion::where('form_name', $data['name'])->first();
                if ($question) {
                    $category_id = $question->category_id;
                }
            }
        }

        Log::info("Category==== : ".$category_id);
        if (! $category_id) {
            $data['error'] = 'missing category';

            return $data;
        }

        Log::info(json_encode($profile));
        $factor = $profile->factors()->where('category_id', $category_id)->first();
        if (! $factor) {
            $factor = new ProfileFactor;

            $factor->profile_id = $profile->id;
            $factor->category_id =$category_id;

            $factor->save();
          //  $data['error'] = 'missing factor';

           // return $data;
        }

        $data['factor'] = $factor;
        $data['risk_value'] = $factor->value;
        $data['risk_status_label'] = t($factor->status);
        $data['risk_status_name'] = $factor->status == 'profile.risk' ? 'risk' : 'healthy';
        \Log::info($data['risk_status_name']);
        $data['category_id'] = $category_id;

        $show_improve = $factor->status != 'profile.unknown';
        if ($show_improve) {
            $statusClass = 'status-' . ($factor->status == 'profile.risk' ? 'risk' : 'healthy');
            if (App::isLocale('sv')) {
                $data['status_text'] = 'Du har värde <span class="' . $statusClass . '">' . $factor->value . '</span> - ' . t($factor->status);
            } else {
                $data['status_text'] = 'Your value is  <span class="' . $statusClass . '">' . $factor->value . '</span> - ' . t($factor->status);
            }
        } else {
            if (App::isLocale('sv')) {
                $data['status_text'] = 'Du har inget värde än';
            } else {
                $data['status_text'] = 'You don\'t have a value yet';
            }
        }

        $fitness_category = QuestionnaireCategory::where('name', 'fitness')->first();
        if ($category_id == $fitness_category->id) {
            // $weight = $profile->getValue('weight');
            // if (empty($weight)) {
            //     $data['fitness_text'] = 'Du måste ange vikt först';
            //     $data['status_text'] = '';
            // } else {
            if ($show_improve) {
                $fitO2min = $profile->getValue('fitO2min');
                $fitO2kg = $profile->getValue('fitO2kg');
                $data['fitness_text'] = $fitO2min . ' L/min, ' . $fitO2kg . ' ml/kg/min';
            } else {
                $data['fitness_text'] = '';
            }
            // }
        }

        $energy_balance_category = QuestionnaireCategory::where('name', 'foodEnergyBalance')->first();
        if ($category_id == $energy_balance_category->id) {
            $data['energy_intake_value'] = $profile->getValue('foodEnergyIntake') ?? 0;
            $data['energy_balance_value'] = $profile->getValue('foodEnergyBalance') ?? 0;
        }

        if (in_array($category_id, [23, 50, 52, 98])) {
            $paf = $profile->getFactor(97);
            if ($paf) {
                $paf->label = t($paf->status);
                $data['physical_activity_factor'] = $paf;
            }
        }

        return $data;
    }

    protected function parseValueArray(array $data, Profile $profile)
    {
        if (isset($data['name'])) {
            $profval = $profile->getValueByName($data['name']);

            if (is_null($profval)) {
                $profval = new ProfileValue();
                $profval->name = $data['name'];
                $profval->profile_id = $profile->id;
            }

            $profval->value = is_numeric($data['value']) ? $data['value'] : null;
            $profval->save();
        } else {
            foreach ($data as $entry) {
                $this->parseValueArray($entry, $profile);
            }
        }
    }

    public function getSatisfaction(Profile $profile)
    {
        return [
            'satisfied' => $profile
                ->factors()
                ->where('improve', 1)
                ->with('category')
                ->get()
                ->map(fn ($factor) => [
                    'label' => t($factor->category->label),
                    'category_id' => $factor->category->id,
                    'selected' => $factor->satisfied,
                ]),
        ];
    }

    public function updateSatisfaction(Request $request, Profile $profile)
    {
        if ($profile->user_id !== $request->user()->id) {
            abort(403);
        }

        $selectedCategories = $request->input('category_ids');

        $profileFactors = $profile->factors()->with('category')->get();
        foreach ($profileFactors as $factor) {
            if (in_array($factor->category_id, $selectedCategories, true)) {
                $factor->satisfied = 1;
            } else {
                $factor->satisfied = 0;
            }
            $factor->save();
        }

        return ['status' => 'success'];
    }

    public function getGoals(Profile $profile)
    {
        return [
            'goals' => $profile
                ->factors()
                ->where('improve', 2)
                ->with('category')
                ->get()
                ->map(fn ($factor) => [
                    'label' => t($factor->category->label),
                    'category_id' => $factor->category->id,
                    'selected' => $factor->target,
                    'value' => $factor->value,
                    'vision' => $factor->vision,
                ]),
        ];
    }

    public function updateGoals(Request $request, Profile $profile)
    {
        if ($profile->user_id !== $request->user()->id) {
            abort(403);
        }

        $profile->factors()->update(['target' => 0]);

        $newGoals = $request->input('goals');
        foreach ($newGoals as $goal) {
            $profileFactor = $profile->factors()->where('category_id', $goal['category_id'])->first();
            $profileFactor->vision = $goal['vision'];
            $profileFactor->target = 1;
            $profileFactor->save();
        }

        return ['status' => 'success'];
    }

    public function getPlannedGoals(Request $request, Profile $profile, QuestionsService $questionsService, ProfilesService $profilesService)
    {
        $profile->loadMissing('texts');

        $allFactors = $profile->factors()->with('category')->get();

        $allGoals = $allFactors->filter(fn ($factor) => $factor->improve === 2 && $factor->target === 1);

        $goals = [];

        $texts = [];
        foreach ($profile->texts as $text) {
            $texts[$text->name] = $text->content;
        }

        $isOldPlan = !array_key_exists('planFactors0', $texts);

        $i = 0;
        if ($request->has('finished')) {
            if ($isOldPlan) {
                foreach ($allGoals as $goalFactor) {
                    $factors = [];
                    foreach ($texts as $factor => $text) {
                        $factors[] = [
                            'factor' => $factor,
                            'plan' => $text,
                        ];
                    }

                    $goals[] = [
                        'label' => t($goalFactor->category->label),
                        'date' => null,
                        'texts' => $factors,
                    ];

                    ++$i;
                }
            } else {
                if (count($texts) > 0) {
                    foreach ($allGoals as $goalFactor) {
                        $factors = [];
                        foreach (json_decode($texts['planFactors' . $i]) as $factor) {
                            $factors[] = [
                                'label' => t(QuestionnaireCategory::find($factor->categoryId)->label),
                                'plan' => $factor->plan,
                            ];
                        }

                        $goals[] = [
                            'label' => t($goalFactor->category->label),
                            'date' => $texts['planWhen' . $i],
                            'factors' => $factors,
                        ];

                        ++$i;
                    }
                }
            }
        } else {
            /** @var ProfileFactor $goalFactor */
            foreach ($allGoals as $goalFactor) {
                $affectingFactors = [];
                if ($goalFactor->isRisk()) {
                    $categoryId = $goalFactor->category_id;

                    // All work factors should use the same factor (workSum)
                    if (str_starts_with($goalFactor->category->name, 'work')) {
                        $categoryId = Factors::Work->value;
                    }

                    $connections = DB::table('category_connections')
                        ->where('category_id', $categoryId)
                        ->get('affected_by_id')
                        ->pluck('affected_by_id');
                    foreach ($connections as $connection) {
                        $connectedFactor = $allFactors->first(fn ($f) => $f->category_id === $connection);
                        if ($connectedFactor->isRisk()) {
                            $affectingFactors[] = [
                                'category_id' => $connectedFactor->category_id,
                                'label' => t($connectedFactor->category->label),
                            ];
                        }
                    }
                }

                $factors = [];
                if (isset($texts['planFactors' . $i])) {
                    foreach (json_decode($texts['planFactors' . $i]) as $factor) {
                        $factors[] = [
                            'category_id' => $factor->categoryId,
                            'label' => t(QuestionnaireCategory::find($factor->categoryId)->label),
                            'plan' => $factor->plan,
                        ];
                    }
                }

                $goals[] = [
                    'label' => t($goalFactor->category->label),
                    'category_id' => $goalFactor->category->id,
                    'selected' => $goalFactor->target,
                    'value' => $goalFactor->value,
                    'vision' => $goalFactor->vision,
                    'affected_by_factors' => $affectingFactors,
                    'factors' => $factors,
                ];

                ++$i;
            }
        }

        $response = [
            'goals' => $goals,
            'questions' => [
                'life' => $questionsService->lifeQuestions(),
            ],
            'profile' => $profilesService->profileWithId($request->user(), $profile->id)->only(['values', 'factors']),
        ];

        if (config('fms.type') === 'school') {
            $response['questions']['school'] = $questionsService->schoolQuestions();
        }
        else if (config('fms.type') === 'work') {
            $response['questions']['work'] = $questionsService->workQuestions();
        }

        return $response;
    }

    public function updatePlannedGoals(Request $request, Profile $profile)
    {
        if ($profile->user_id !== $request->user()->id) {
            abort(403);
        }

        $plan = $request->input('plan');

        foreach ($plan as $i => $goal) {
            ProfileText::updateOrCreate(
                ['profile_id' => $profile->id, 'name' => 'planWhen' . $i],
                ['content' => $goal['date']],
            );

            ProfileText::updateOrCreate(
                ['profile_id' => $profile->id, 'name' => 'planFactors' . $i],
                ['content' => json_encode($goal['factors'])],
            );
        }

        return ['status' => 'success'];
    }

    public function create(CreateProfileRequest $request, CreateNewProfile $createNewProfile)
    {
        $profile = $createNewProfile->handle($request);

        return response()->json([
            'status' => 'success',
            'profile' => $profile,
        ], 201);
    }

    public function finish(FinishProfileRequest $request, FinishProfile $finishProfile)
    {
        $finishProfile->handle($request);

        return response()->json([
            'status' => 'success',
        ]);
    }
}
