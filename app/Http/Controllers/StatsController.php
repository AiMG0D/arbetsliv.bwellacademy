<?php

namespace App\Http\Controllers;

use App\BusinessCategory;
use App\Country;
use App\Profile;
use App\ProfileFactor;
use App\QuestionnaireCategory;
use App\SampleGroup;
use App\SampleGroupMember;
use App\Section;
use App\StatsFilter;
use App\FeedbackAnswer;
use App\Unit;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class StatsController extends Controller
{



    private $categoryPageMap = [
        // physical
       // 'agility' => 'physical',
        'strength2' => 'physical',
        'strLegs' => 'physical',
        'strBack' => 'physical',
        'strArms' => 'physical',
        'strAbs' => 'physical',
       'balance' => 'physical',
      //  'motor' => 'physical',
        'posture' => 'physical',
      //  'fitness' => 'physical',
       // 'stepcount' => 'physical',
      //  'weight' => 'physical',
       // 'physical' => 'physical',
        'physicalTraining' => 'physical',
        'physicalCondition' => 'physical',
        'activitiesTimeSpent' => 'physical',
        'sitting' => 'physical',
        // wellbeing
        'health' => 'wellbeing',
        'bodySat' => 'wellbeing',
        'bodySympt' => 'wellbeing',
        'diffSymptRelaxed' => 'wellbeing',
        'diffSymptStomach' => 'wellbeing',
        'diffSymptHead' => 'wellbeing',
        'sleepHour' => 'wellbeing',
        'calmness' => 'wellbeing',
        // ant
        'smoking' => 'ant',
        'snuffing' => 'ant',
        'alcoholDrink' => 'ant',
        // energy
        'foodHabits' => 'energy',
        'foodEnergyBalance' => 'energy',
      //  'foodFruit' => 'energy',
        'foodSweets' => 'energy',
      //  'foodFluid' => 'energy',
      //  'foodEnergyDrinks' => 'energy',
        // freetime
        'freetime' => 'freetime',
        'media' => 'freetime',
        'friends' => 'freetime',
        'adults' => 'freetime',
        // school
        'schlGoal' => 'school',
        'schlResult' => 'school',
        'schlSituationInfo' => 'school',
        'schlComfort' => 'school',
        'schlSafety' => 'school',
        'schlSeen' => 'school',
        'school1' => 'school',
        'school2' => 'school',
        'school3' => 'school',
        'school4' => 'school',
        'school5' => 'school',
        'school6' => 'school',
        'school7' => 'school',
        'school8' => 'school',
        'school9' => 'school',
        'school10' => 'school',
        'school11' => 'school',
        // work
        'work1' => 'work',
        'work2' => 'work',
        'work3' => 'work',
        'work4' => 'work',
        'work5' => 'work',
        'work6' => 'work',
        'work7' => 'work',
        'work8' => 'work',
        'work9' => 'work',
        'work10' => 'work',
        'work11' => 'work',
        'work12' => 'work',
        'work13' => 'work',
        'work14' => 'work',
        'work15' => 'work',
        'work16' => 'work',
        // kasam
        'kasam' => 'kasam',
    ];

    private $pageSortOrder = [
        'school' => 0,
        'work' => 0,
        'physical' => 1,
        'wellbeing' => 2,
        'ant' => 3,
        'energy' => 4,
        'freetime' => 5,
        'kasam' => 6,
    ];

    private function getFactors()
    {
        return Cache::remember('stats_factors', 300, function () {
            $factors = [];

            foreach (ProfileFactor::sortedNames() as $factor) {
                $category = QuestionnaireCategory::where('name', $factor)->first();
                if (! is_null($category)) {
                    $factors[$category->name] = $category->id;
                }
            }

            return $factors;
        });
    }

    private function getDefaultFactors()
    {
        $factors = $this->getFactors();

        unset($factors['strLegs']);
        unset($factors['strBack']);
        unset($factors['strArms']);
        unset($factors['strAbs']);

        return $factors;
    }

    private function getCategories()
    {
        return Cache::remember('stats_categories', 300, function () {
            $categories = [];

            foreach (ProfileFactor::sortedNames() as $factor) {
                $category = QuestionnaireCategory::where('name', $factor)->first();
                if (! is_null($category)) {
                    $categories[$category->name] = $category;
                }
            }

            return $categories;
        });
    }

    private function getDefaultCategories()
    {
        $categories = $this->getCategories();

        unset($categories['strLegs']);
        unset($categories['strBack']);
        unset($categories['strArms']);
        unset($categories['strAbs']);

        return $categories;
    }

    public function getSelection($cacheId)
    {
        $cachePrefix = 'selection-profiles-';
        $data = Cache::get($cachePrefix . $cacheId);

        if (is_null($data)) {
            abort(404);
        }

        $sections = [];
        $users = [];
        foreach ($data as $id) {
            $profile = Profile::find($id);
            $user = User::find($profile->user_id);
            $users[] = $user;

            if (is_null($user->section_id)) {
                continue;
            }

            if (! isset($sections[$user->section_id])) {
                $sections[$user->section_id] = [];
            }

            $sections[$user->section_id][] = $user;
        }

        $sectionLabels = [];
        foreach ($sections as $sectionId => $sectionUsers) {
            $section = Section::find($sectionId);
            $sectionLabels[$sectionId] = $section->full_name();
        }

        return view('statistics.show-selection', compact('users', 'sectionLabels'));
    }

    public function getIndex(Request $request)
    {
        $user = Auth::user();

        $sectionIds = [];
        $filters = [];

        if ($user->isSuperAdmin()) {
            $allUnits = Unit::with('sections')->get();
            foreach ($allUnits as $unit) {
                $filters[] = [
                    'type' => 'unit',
                    'id' => $unit->id,
                    'name' => $unit->name,
                ];
            }
            $sections = [];

            $schoolId = $request->get('school_id');
            if (! is_null($schoolId)) {
                // TODO: Vi vill också se till att den blir vald i högerspalten under Filter
                $units = [Unit::findOrFail($schoolId)];
            } else {
                $units = $allUnits;
            }

            foreach ($units->sortBy('name') as $unit) {
                $sections[$unit->name] = [
                    ['id' => $unit->id . '.' . 0, 'name' => __('statistics.all')],
                ];

                $unitSections = $unit->sections()->whereNull('archived_at')->orderBy('name')->get();

                foreach ($unitSections as $section) {
                    $sections[$unit->name][] = [
                        'id' => $unit->id . '.' . $section->id,
                        'name' => $section->full_name(),
                    ];
                    $sectionIds[] = $section->id;
                }
            }
        } else {
            $sections = [];

            foreach ($user->unit->sections as $section) {
                $filters[] = [
                    'type' => 'section',
                    'id' => $section->id,
                    'name' => $section->full_name(),
                ];
                $sections[] = $section;
                $sectionIds[] = $section->id;
            }

            uasort($sections, function ($a, $b) {
                return strcasecmp($a->full_name(), $b->full_name());
            });
        }

        $allProgrammes = DB::table('secondary_programs')
            ->orderby('label')->get();

        $programmes = DB::table('secondary_programs')
            ->select(['secondary_programs.id', 'secondary_programs.label'])
            ->join('sections', 'secondary_programs.id', '=', 'sections.program_id')
            ->whereIn('sections.id', $sectionIds)
            ->groupBy('secondary_programs.id')
            ->orderby('label')->get();

        $categories = $this->getCategories();

        $defaultCategories = $this->getCategories();

        $defaultCategoryLabels = [];
        foreach ($defaultCategories as $category) {
            $defaultCategoryLabels[] = t($category->label);
        }

        $userFiltersObj = StatsFilter::where('user_id', $user->id)->get();
        $userFilters = [];
        foreach ($userFiltersObj as $obj) {
            $userFilters[] = $obj;
        }

        uasort($userFilters, function ($a, $b) {
            return strcasecmp($a->name, $b->name);
        });

        $countries = [];
        $baseCounties = [];
        $counties = [];

        foreach (Country::all() as $country) {
            $countries[$country->label] = $country->id;
            $countryCounties = [];
            $countryBaseCounties = [];

            foreach ($country->counties as $county) {
                $countryBaseCounties[$county->id] = $county->label;
                $countryCounties[$county->id] = $county->label;
            }

            $baseCounties[$country->id] = $countryBaseCounties;
            $counties[$country->label] = $countryCounties;
        }

        if (! is_null($countries)) {
            uksort($countries, function ($a, $b) {
                return strcasecmp(t($a), t($b));
            });

            foreach ($counties as $countryLabel => &$countryCounties) {
                uasort($countryCounties, function ($a, $b) {
                    return strcasecmp($a, $b);
                });
            }
        }

        $sampleGroups = [];
        $groups = SampleGroup::where('unit_id', $user->unit_id)->get();
        foreach ($groups as $group) {
            $sampleGroups[] = $group;
        }

        uasort($sampleGroups, function ($a, $b) {
            return strcasecmp($a->label, $b->label);
        });

        $data = [
            'user' => $user,
            'filters' => $filters,
            'userFilters' => $userFilters,
            'barLabels' => json_encode(rsort($defaultCategoryLabels)),
            'categories' => $categories,
            'defaultCategories' => $defaultCategories,
            'countries' => $countries,
            'counties' => $counties,
            'sampleGroups' => $sampleGroups,
            'allProgrammes' => $allProgrammes,
            'programmes' => $programmes,
            'numWomen' => 0,
            'numMen' => 0,
            'active' => 'statistics',
            'cssClasses' => 'stats-page',
        ];

        if (config('fms.type') === 'work') {
            $data['businessCategories'] = BusinessCategory::all(['id', 'name']);
        } else {
            $data['businessCategories'] = null;
        }

        if (isset($sections)) {
            $data['sections'] = $sections;
        }

        return view('statistics.regular', $data);
    } // getIndex

    public function ajaxGetFilter($name)
    {
        $user = Auth::user();

        if (is_null($user)) {
            Log::error("StatsController@ajaxGetFilter: Försökte komma åt filtret $name utan att vara inloggad.");

            return '[]';
        }

        $filter = StatsFilter::where('user_id', $user->id)
                    ->where('slug', $name)->first();

        if (is_null($filter)) {
            return '[]';
        }

        return $filter->filter;
    }

    public function ajaxSetFilter(Request $request)
    {
        $user = Auth::user();

        if (is_null($user)) {
            abort(404);
        }

        if ($user->isStudent()) {
            abort(404);
        }

        $filters = $request->all();

        return $this->getFilteredSelection($filters);
    }

    public function ajaxSaveFilter(request $request)
    {
        $user = Auth::user();

        $data = $request->all();

        $rules = [
            'name' => 'required',
            'slug' => 'required',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            return ['status' => 'error', 'message' => 'Båda fälten måste vara ifyllda.'];
        }

        $check = StatsFilter::where('user_id', $user->id)
            ->where('slug', $data['slug'])->first();

        if (! is_null($check)) {
            return ['status' => 'error', 'message' => 'Du har redan ett filter med det här kortnamnet.'];
        }

        $userFilter = new StatsFilter;
        $userFilter->user_id = $user->id;
        $userFilter->name = $data['name'];
        $userFilter->slug = $data['slug'];
        $userFilter->filter = json_encode($data['filter']);
        $userFilter->save();

        return ['status' => 'ok'];
    }

    public function ajaxRemoveFilter(Request $request)
    {
        $user = Auth::user();

        if (is_null($user)) {
            return ['status' => 'error', 'message' => 'Användaren är inte inloggad.'];
        }

        $data = $request->all();
        $slug = $data['slug'];

        $check = StatsFilter::where('user_id', $user->id)
            ->where('slug', $data['slug'])->first();

        if (is_null($check)) {
            return ['status' => 'error', 'message' => 'Du har inte ett filter med det här kortnamnet'];
        }

        $check->delete();

        return ['status' => 'ok'];
    }

    public function ajaxCompare(Request $request)
    {
        $user = Auth::user();

        if (is_null($user)) {
            return ['status' => 'error', 'message' => 'Användaren är inte inloggad'];
        }

        $input = $request->all();
        $data = $input['data'];

        $values = [];
        foreach ($data as $req) {
            if ($req['type'] !== 'user-filter') {
                $filters = [$req['type'] => $req['id']];
            } else {
                $filters = $req['filters'];
            }

            $filters['compare'] = true;

            $filtered = $this->getFilteredSelection($filters);
            $filtered['label'] = $req['name'];
            $values[] = $filtered;
        }

        return ['status' => 'ok', 'data' => $values];
    }


    public function getFilteredSelection($filters)
    {
        $user = Auth::user();

        // Sätt upp alla flaggor
        $flags = 0;
        foreach ($filters as $name => $value) {
            switch ($name) {
                default:
                    Log::error('Vet inte hur detta filter ska hanteras');
                    break;
                case 'range':
                    $flags |= FilterFlags::Range;
                    break;
                case 'sex':
                    if ($value != 0) {
                        $flags |= FilterFlags::Sex;
                    }
                    break;
                case 'age-to':
                    $flags |= FilterFlags::AgeTo;
                    break;
                case 'age-from':
                    $flags |= FilterFlags::AgeFrom;
                    break;
                case 'grade':
                    if ($value != 0) {
                        $flags |= FilterFlags::Grade;
                    }
                    break;
                case 'year-from':
                    if ($value != 0) {
                        $flags |= FilterFlags::YearFrom;
                    }
                    break;
                case 'year-to':
                    if ($value != 0) {
                        $flags |= FilterFlags::YearTo;
                    }
                    break;
                case 'semester-from':
                    if ($value != 0) {
                        $flags |= FilterFlags::SemesterFrom;
                    }
                    // no break
                case 'semester-to':
                    if ($value != 0) {
                        $flags |= FilterFlags::SemesterTo;
                    }
                    break;
                case 'programme':
                    if ($value != 0) {
                        $flags |= FilterFlags::Programme;
                    }
                    break;
                case 'school-type':
                    if ($value != 0) {
                        $flags |= FilterFlags::SchoolType;
                    }
                    break;
                case 'country':
                    $flags |= FilterFlags::Country;
                    break;
                case 'county':
                    if ($value != 0) {
                        $flags |= FilterFlags::County;
                    }
                    break;
                case 'date-from':
                    $flags |= FilterFlags::DateFrom;
                    break;
                case 'date-to':
                    $flags |= FilterFlags::DateTo;
                    break;
                case 'got-help':
                    if ($value != 'all' || $filters['alone-group'] != 'all') {
                        $flags |= FilterFlags::ProfileType;
                    }
                    break;
                case 'unit':
                case 'section':
                    if ($value != 0) {
                        $flags |= FilterFlags::SectionOrUnit;
                    }
                    break;
                case 'constraints':
                    $flags |= FilterFlags::CrossReference;
                    break;
                case 'categories':
                    $flags |= FilterFlags::FilterCategories;
                    break;
                case 'risk-groups':
                    $flags |= FilterFlags::RiskGroups;
                    break;
                case 'profile-nb':
                    if ($value != 5) {
                        $flags |= FilterFlags::ProfileNumber;
                    }
                    break;
                case 'business-category':
                    if ($value != 0) {
                        $flags |= FilterFlags::BusinessCategory;
                    }
                    break;
                case 'sample-group':
                    if ($value > 0) {
                        $flags |= FilterFlags::SampleGroup;
                    }
                    break;
                case 'compare':
                    break;
            }
        }

        if ($flags & FilterFlags::CrossReference) {
            $factorConstraints = [];
            $constraintFilter = $filters['constraints'];
            foreach (explode('|', $constraintFilter) as $cr) {
                $parts = explode(':', $cr);
                $factorConstraints[$parts[0]] = explode(',', $parts[1]);
            }
        }

        $schoolType = 0;
        if ($flags & FilterFlags::SchoolType) {
            $wantedSchoolType = intval($filters['school-type']);
            $schoolType = intval($filters['school-type']);
        }

        $showSelectionLink = false;

        $sections = [];
        if ($flags & FilterFlags::County) {
            $countiesWithSections = get_all_county_sections();
            $county = intval($filters['county']);
            if (! isset($countiesWithSections[$county])) {
                return [];
            }
            $countySections = $countiesWithSections[$county];
            $sections = [];
            foreach ($countySections as $sectionEntry) {
                if ($flags & FilterFlags::SchoolType) {
                    $sectionSchoolType = $sectionEntry['school_type'];
                    $testSchoolType = 0;
                    if ($sectionSchoolType == 'unit.primary') {
                        $testSchoolType = 1;
                    }
                    if ($sectionSchoolType == 'unit.secondary') {
                        $testSchoolType = 2;
                    }

                    if ($testSchoolType !== $wantedSchoolType) {
                        continue;
                    }
                }

                if ($flags & FilterFlags::Grade) {
                    $grade = intval($filters['grade']);
                    $sectionSchoolType = $sectionEntry['school_type'];

                    if ($grade > 10) {
                        if ($sectionSchoolType != 'unit.secondary') {
                            continue;
                        }
                    } else {
                        if ($sectionSchoolType != 'unit.primary') {
                            continue;
                        }
                    }
                }

                $sections[] = $sectionEntry['section'];
            }
        }
        elseif ($flags & FilterFlags::Country) {
            $countriesWithSections = get_all_country_sections();
            $country = intval($filters['country']);
            if ($country > 0) {
                $countrySections = [];
                if (isset($countriesWithSections[$country])) {
                    $countrySections = $countriesWithSections[$country];
                }
                $sections = [];
                foreach ($countrySections as $sectionEntry) {
                    if ($flags & FilterFlags::SchoolType) {
                        $sectionSchoolType = $sectionEntry['school_type'];
                        $testSchoolType = 0;
                        if ($sectionSchoolType == 'unit.primary') {
                            $testSchoolType = 1;
                        }
                        if ($sectionSchoolType == 'unit.secondary') {
                            $testSchoolType = 2;
                        }

                        if ($testSchoolType !== $wantedSchoolType) {
                            continue;
                        }
                    }

                    if ($flags & FilterFlags::Grade) {
                        $grade = intval($filters['grade']);
                        $sectionSchoolType = $sectionEntry['school_type'];

                        if ($grade > 10) {
                            if ($sectionSchoolType != 'unit.secondary') {
                                continue;
                            }
                        } else {
                            if ($sectionSchoolType != 'unit.primary') {
                                continue;
                            }
                        }
                    }

                    $sections[] = $sectionEntry['section'];
                }
            } else {
                foreach ($countriesWithSections as $countrySections) {
                    foreach ($countrySections as $sectionEntry) {
                        if ($flags & FilterFlags::SchoolType) {
                            $sectionSchoolType = $sectionEntry['school_type'];
                            $testSchoolType = 1;
                            if ($sectionSchoolType == 'unit.primary') {
                                $testSchoolType = 1;
                            } elseif ($sectionSchoolType == 'unit.secondary') {
                                $testSchoolType = 2;
                            }

                            if ($testSchoolType !== $wantedSchoolType) {
                                continue;
                            }
                        }

                        if ($flags & FilterFlags::Grade) {
                            $grade = intval($filters['grade']);
                            $sectionSchoolType = $sectionEntry['school_type'];

                            if ($grade > 10) {
                                if ($sectionSchoolType != 'unit.secondary') {
                                    continue;
                                }
                            } else {
                                if ($sectionSchoolType != 'unit.primary') {
                                    continue;
                                }
                            }
                        }

                        $sections[] = $sectionEntry['section'];
                    }
                }
            }
        } elseif ($flags & FilterFlags::SampleGroup) {
            $showSelectionLink = true;
            $sampleId = intval($filters['sample-group']);
            $sampleMembers = SampleGroupMember::where('sample_group_id', $sampleId)->get();
        }
        else {
            if ($user->isSuperAdmin()) {
                if ($flags & FilterFlags::SectionOrUnit) {
                    Log::info("Sections Filter ");
                    $showSelectionLink = true;

                    $unitsAndSections = [];
                    foreach (explode(',', $filters['section']) as $v) {
                        [$unitId, $sectionId] = explode('.', $v);

                        if (! array_key_exists($unitId, $unitsAndSections)) {
                            $unitsAndSections[$unitId] = [];

                            $unit = Unit::find($unitId);
                            $schoolType = $unit->school_type == 'unit.secondary' ? 2 : 1;
                        }

                        $unitsAndSections[$unitId][] = $sectionId;
                    }

                     $sections = Section::where(function ($query) use ($unitsAndSections) {
                        foreach ($unitsAndSections as $unitId => $unitSections) {
                            $query->orWhere(function ($query1) use ($unitId, $unitSections) {
                                if (in_array(0, $unitSections)) {

                                    $query1->where('unit_id', $unitId);
                                } else {
                                    $sectionIds = [];
                                    foreach ($unitSections as $sectionId) {
                                        $sectionIds[] =(int) $sectionId;
                                    }

                                     $query1->where('unit_id', $unitId)->whereIn('id', $sectionIds);
                                }
                            });
                        }
                    })->get();
                }
                else {
                    $allSectionsQuery = DB::table('sections')
                        ->select('sections.*')
                        ->join('unit', 'unit.id', '=', 'sections.unit_id')
                        ->where('unit.included', true);

                    if ($flags & FilterFlags::Grade) {
                        $grade = intval($filters['grade']);

                        if ($grade > 10) {
                            $allSectionsQuery = $allSectionsQuery->where('unit.school_type', '"unit.secondary"');
                        } else {
                            $allSectionsQuery = $allSectionsQuery->where('unit.school_type', '"unit.primary"');
                        }
                    }

                    $sections = [];
                    foreach ($allSectionsQuery->get() as $result) {
                        $sections[] = $result;
                    }
                }
            }
            else {
                $showSelectionLink = true;
                $unitId = $user->unit_id;
                $unit = $user->unit;
                $schoolType = $unit->school_type == 'unit.secondary' ? 2 : 1;
                $staffSections = $unit->sections;

                if ($flags & FilterFlags::SectionOrUnit) {
                    $wantedSections = explode(',', $filters['section']);
                    $sections = [];
                    foreach ($staffSections as $section) {
                        if (in_array(0, $wantedSections) || in_array($section->id, $wantedSections)) {
                            $sections[] = $section;
                        }
                    }
                } else {
                    if ($flags & FilterFlags::Grade) {
                        $grade = intval($filters['grade']);
                        $schoolType = $unit->school_type;

                        if ($grade > 10) {
                            if ($schoolType == 'unit.secondary') {
                                $sections = $staffSections;
                            }
                        } else {
                            if ($schoolType == 'unit.primary') {
                                $sections = $staffSections;
                            }
                        }
                    } else {
                        $sections = $staffSections;
                    }
                }
            }
        }

        if ($flags & FilterFlags::BusinessCategory) {
            $category = intval($filters['business-category']);

            $unitIds = collect($sections)->pluck('unit_id')->unique();

            $units = Unit::whereIn('id', $unitIds)->where('business_category_id', $category)->get();
            $unitIds = $units->pluck('id')->toArray();

            $sections = array_filter($sections, function ($section) use ($unitIds) {
                return in_array($section->unit_id, $unitIds);
            });
        }

        if (! isset($sampleId) && count($sections) == 0) {
            return [];
        }

        $numMen = 0;
        $numWomen = 0;
        $numStudents = 0;
        if (isset($sampleMembers)) {
            $numStudents = $sampleMembers->count();
        }

        $invalidDate = false;
        $profiles = [];
        $profileIds = [];
        $categoryIds = [];
        $categoryNames = [];

        if (! isset($sampleMembers)) {
            $sectionIds = [];
            foreach ($sections as $section) {
                if ($flags & FilterFlags::Programme) {
                    if (is_null($section->program_id)) {
                        continue;
                    }

                    $wantedId = intval($filters['programme']);
                    if ($section->program_id != $wantedId) {
                        continue;
                    }
                }

                if ($flags & FilterFlags::Grade) {
                    $grade = intval($filters['grade']);

                    if ($grade > 10) {
                        $grade -= 10;
                    }

                    if ($section->school_year != $grade) {
                        continue;
                    }
                }

                $sectionIds[] = intval($section->id);
            }
        } else {
            $sampleMemberIds = [];
            foreach ($sampleMembers as $member) {
                $sampleMemberIds[] = $member->user_id;
            }
        }

        if ((isset($sectionIds) && count($sectionIds) == 0) ||
            (isset($sampleMemberIds) && count($sampleMemberIds) == 0)) {
            return [];
        }

        $sex = 0;
        if ($flags & FilterFlags::Sex) {
            $wantedSex = intval($filters['sex']);
            if ($wantedSex === 1) {
                $sex = 1;
            } else {
                $sex = 2;
            }
        }

        $profilesQuery = DB::table('profiles');

        $profilesQuery = $profilesQuery->select(DB::raw('profiles.*, users.sex'));

        if ($flags & FilterFlags::ProfileNumber) {
            $wantedpnb = intval($filters['profile-nb']);

            $profilesQuery = $profilesQuery->join(DB::raw('(SELECT (SELECT b.id FROM profiles b WHERE b.in_progress = 0 AND b.user_id = profiles.user_id ORDER BY profiles.id limit ' . $wantedpnb . ',1) AS PNB FROM profiles GROUP BY user_id ) p2'), 'profiles.id', '=', 'p2.PNB');
        } else {
            $profilesQuery = $profilesQuery->join(DB::raw('(SELECT MAX(profiles.id) as id FROM profiles WHERE in_progress = 0 GROUP BY user_id) p2'), 'profiles.id', '=', 'p2.id');
        }

        $profilesQuery = $profilesQuery->join('users', 'profiles.user_id', '=', 'users.id');

        $profilesQuery = $profilesQuery->where('users.is_test', false);
        if (isset($sectionIds)) {
            $profilesQuery = $profilesQuery->whereIn('users.section_id', $sectionIds);
        } else {
            $profilesQuery = $profilesQuery->whereIn('users.id', $sampleMemberIds);
        }
        if ($sex == 1) {
            $profilesQuery = $profilesQuery->where('users.sex', 'M');
        } elseif ($sex == 2) {
            $profilesQuery = $profilesQuery->where('users.sex', 'F');
        } else {
         //   $profilesQuery = $profilesQuery->where('users.sex', '!=', 'U');
        }

        if ($flags & FilterFlags::DateFrom || $flags & FilterFlags::DateTo) {
            if ($flags & FilterFlags::DateFrom) {
                $dateFrom = $filters['date-from'];
            } else {
                $dateFrom = '2000-01-01';
            }
            if ($flags & FilterFlags::DateTo) {
                $dateTo = $filters['date-to'];
            } else {
                $dateTo = date('Y-m-d');
            }

            $profilesQuery = $profilesQuery->whereBetween('profiles.date', [$dateFrom, $dateTo]);
        }

        if ($flags & FilterFlags::SemesterFrom ||
            $flags & FilterFlags::SemesterTo) {
            // TODO: SemesterFrom & SemesterTo
            if ($flags & FilterFlags::SemesterFrom &&
                $flags & FilterFlags::SemesterTo) {
                $semesterFrom = intval($filters['semester-from']);
                $yearFrom = intval($filters['year-from']);

                $semesterTo = intval($filters['semester-to']);
                $yearTo = intval($filters['year-to']);

                $semesterRange = $this->createExtendedSemesterRange($yearFrom, $semesterFrom, $yearTo, $semesterTo);
            } else {
                if ($flags & FilterFlags::SemesterFrom) {
                    $semester = intval($filters['semester-from']);
                    $year = intval($filters['year-from']);
                } else {
                    $semester = intval($filters['semester-to']);
                    $year = intval($filters['year-to']);
                }

                $semesterRange = $this->createSemesterRange($year, $semester);
            }

            $profilesQuery = $profilesQuery->whereBetween('profiles.date', $semesterRange);
        } elseif ($flags & FilterFlags::YearFrom ||
                 $flags & FilterFlags::YearTo) {
            // År
            if ($flags & FilterFlags::YearFrom) {
                $yearFrom = $filters['year-from'] . '-01-01';
            }
            if ($flags & FilterFlags::YearTo) {
                $yearTo = $filters['year-to'] . '-12-31';
            }

            if (isset($yearFrom) && isset($yearTo)) {
                if ($yearTo < $yearFrom) {
                    $dates = [$yearTo, $yearFrom];
                } else {
                    $dates = [$yearFrom, $yearTo];
                }

                $profilesQuery = $profilesQuery->whereBetween('profiles.date', $dates);
            } elseif (isset($yearFrom)) {
                $profilesQuery = $profilesQuery->whereBetween('profiles.date', [$yearFrom, date('Y-m-d')]);
            } elseif (isset($yearTo)) {
                $profilesQuery = $profilesQuery->whereBetween('profiles.date', ['2000-01-01', $yearTo]);
            }
        }

        if ($flags & FilterFlags::AgeFrom ||
            $flags & FilterFlags::AgeTo) {
            // $profile->updated_at, $student->birth_date
            if ($flags & FilterFlags::AgeFrom) {
                $ageFrom = intval($filters['age-from']);
            }

            if ($flags & FilterFlags::AgeTo) {
                $ageTo = intval($filters['age-to']);
            }

            if ($flags & FilterFlags::AgeFrom &&
                $flags & FilterFlags::AgeTo) {
                $profilesQuery = $profilesQuery->whereBetween(DB::raw('timestampdiff(YEAR, `users`.`birth_date`, `profiles`.`date`)'), [$ageFrom, $ageTo]);
            } elseif ($flags & FilterFlags::AgeFrom) {
                $profilesQuery = $profilesQuery->where(DB::raw('timestampdiff(YEAR, `users`.`birth_date`, `profiles`.`date`)'), '>=', $ageFrom);
            } elseif ($flags & FilterFlags::AgeTo) {
                $profilesQuery = $profilesQuery->where(DB::raw('timestampdiff(YEAR, `users`.`birth_date`, `profiles`.`date`)'), '<=', $ageTo);
            }
        }

        if ($flags & FilterFlags::RiskGroups) {
            $riskGroups = explode('|', $filters['risk-groups']);
            $profilesQuery = $profilesQuery->whereIn('risk_group', $riskGroups);

            //$profilesQuery = $profilesQuery->whereRaw('profiles.id IN (SELECT MAX(profiles.id) as id from profiles group by profiles.user_id)');
        }

        $profilesQuery = $profilesQuery->orderBy('profiles.date', 'DESC');
        //->groupBy("profiles.user_id");

        // Profilnummer

        if ($flags & FilterFlags::ProfileNumber) {
            $profilenb = intval($filters['profile-nb']);

            //$profilesQuery = $profilesQuery->havingRaw('COUNT(profiles.id) > ' .$profilenb)->groupBy("profiles.user_id");
        }

        // $profileSQLQuery = query_to_string($profilesQuery);

        $profilesQueryResults = $profilesQuery->get();

         $checkProfileType = false;
        if ($flags & FilterFlags::ProfileType) {
            $checkProfileType = true;
            $helped = $filters['got-help'];
            $aloneOrGroup = $filters['alone-group'];
        }

        $profiles = [];
        $userIds = [];

        $riskGroupMen = [
            'risk' => 0,
            'healthy' => 0,
            'warning' => 0,
            'unknown' => 0,
        ];

        $riskGroupWomen = [
            'risk' => 0,
            'healthy' => 0,
            'warning' => 0,
            'unknown' => 0,
        ];

        foreach ($profilesQueryResults as $profile) {

            if ($checkProfileType) {
                $gotHelp = $profile->got_help;
                $inGroup = $profile->in_group;

                if ($helped == 'yes' && ! $gotHelp) {
                    continue;
                }
                if ($helped == 'no' && $gotHelp) {
                    continue;
                }
                /*
                if ($helped == "alone" && $inGroup)
                {
                    continue;
                }
                else if ($helped == "group" && !$inGroup)
                {
                    continue;
                }
                 *
                 */
                if ($aloneOrGroup == 'alone' && $inGroup) {
                    continue;
                }
                if ($aloneOrGroup == 'group' && ! $inGroup) {
                    continue;
                }
            }

            $profiles[] = $profile;

            $profileNb = intval($profile->id);

            /*

            if($flags & FilterFlags::ProfileNumber)
            {
                $profileNb = intval($profile->PNB);
            }

            */

            $profileIds[] = $profileNb; //update for latest project

            if ($sex == 0) {
                if ($profile->sex == 'M') {
                    ++$numMen;

                    if (array_key_exists($profile->risk_group, $riskGroupMen)) {
                        ++$riskGroupMen[$profile->risk_group];
                    }
                } else {
                    ++$numWomen;

                    if (array_key_exists($profile->risk_group, $riskGroupWomen)) {
                        ++$riskGroupWomen[$profile->risk_group];
                    }
                }
            } elseif ($sex == 1) {
                ++$numMen;

                if (array_key_exists($profile->risk_group, $riskGroupMen)) {
                    ++$riskGroupMen[$profile->risk_group];
                }
            } else {
                ++$numWomen;
                if (array_key_exists($profile->risk_group, $riskGroupWomen)) {
                    ++$riskGroupWomen[$profile->risk_group];
                }
            }
        }

        $numStudents = count($profileIds);

        if ($numStudents == 0) {
            return [];
        }


        if ($flags & FilterFlags::CrossReference) {

            $factorLookup = $this->getFactors();
            $factorsQuery = DB::table('profile_factors')
                ->select('profile_factors.*', 'users.sex')
                ->join('profiles', 'profiles.id', '=', 'profile_factors.profile_id')
                ->join('users', 'users.id', '=', 'profiles.user_id')
                ->whereIn('profile_id', $profileIds)
                //->join('questionnaire_categories', "questionnaire_categories.id", "=", "profile_factors.category_id") // TODO: Gör en lookup table istället?
                ->where(function ($query) use ($factorConstraints, $factorLookup) {
                    $numericConstraints = [];
                    $statusConstraints = [];
                    foreach ($factorConstraints as $factor => $constraints) {
                        foreach ($constraints as $constraint) {
                            if (is_numeric($constraint)) {
                                if (! isset($numericConstraints[$factor])) {
                                    $numericConstraints[$factor] = [];
                                }

                                $numericConstraints[$factor][] = $constraint;
                            } else {
                                if (! isset($statusConstraints[$factor])) {
                                    $statusConstraints[$factor] = [];
                                }

                                $statusConstraints[$factor][] = 'profile.' . $constraint;
                            }
                        }
                    }

                    foreach ($numericConstraints as $factor => $values) {
                        $query->where(function ($subquery) use ($factor, $values, $factorLookup) {
                            $subquery->where('profile_factors.category_id', $factorLookup[$factor])
                                 ->whereIn('profile_factors.value', $values);
                        });
                    }

                    foreach ($statusConstraints as $factor => $values) {
                        $query->where(function ($subquery) use ($factor, $values, $factorLookup) {
                            $subquery->where('profile_factors.category_id', $factorLookup[$factor])
                                 ->whereIn('profile_factors.status', $values);
                        });
                    }
                });

            $profileFactorIds = [];
            $count = $factorsQuery->count();

            $diff = abs($count - $numStudents);
            if ($diff > 0) {
                $numStudents = 0;
                $numMen = 0;
                $numWomen = 0;
                $results = $factorsQuery->get();

                $riskGroupMen = [
                    'risk' => 0,
                    'healthy' => 0,
                    'warning' => 0,
                    'unknown' => 0,
                ];

                $riskGroupWomen = [
                    'risk' => 0,
                    'healthy' => 0,
                    'warning' => 0,
                    'unknown' => 0,
                ];

                foreach ($factorsQuery->get() as $result) {
                    $profileFactorIds[] = $result->profile_id;

                    //Här ska det in

                    $status = str_replace('profile.', '', $result->status);

                    if ($result->sex == 'M') {
                        ++$numMen;

                        if (array_key_exists($status, $riskGroupMen)) {
                            ++$riskGroupMen[$status];
                        }
                    } else {
                        ++$numWomen;

                        if (array_key_exists($status, $riskGroupWomen)) {
                            ++$riskGroupWomen[$status];
                        }
                    }
                }

                $numStudents = count($profileFactorIds);

                if ($numStudents == 0) {
                    return [];
                }

                $profileIds = $profileFactorIds;
            }
        }

        $categories = $this->getCategories();

        $filteredCategories = [];
        if (isset($filters['compare'])) { // Fix ordning för att kunna välja rätt värden
            foreach ($categories as $category) {
                //$filteredCategories[] = $categories[$factorName];
                $filteredCategories[] = $category;
                $categoryNames[$category->id] = $category->name;
                $categoryIds[] = $category->id;
            }
        } elseif ($flags & FilterFlags::FilterCategories) {
            $wantedCategories = explode('|', $filters['categories']);
            $wantedCategories = str_replace('cat-', '', $wantedCategories);

            $filteredValuesStacked = [];
            foreach ($categories as $category) {
                if (in_array($category->name, $wantedCategories)) {
                    $filteredCategories[] = $category;
                    $categoryNames[$category->id] = $category->name;
                    $categoryIds[] = $category->id;
                }
            }
        } else {
            foreach ($this->getDefaultFactors() as $factorName => $factorId) {
                $filteredCategories[] = $categories[$factorName];
                $categoryNames[$factorId] = $factorName;
                $categoryIds[] = $factorId;
            }
        }

        $filteredCategoryMap = [];
        $pages = [];
        foreach ($categoryNames as $id => $name) {
            if (! isset($this->categoryPageMap[$name])) {
                continue;
            }
            $page = $this->categoryPageMap[$name];
            if (! isset($filteredCategoryMap[$page])) {
                $filteredCategoryMap[$page] = [];
            }
            $filteredCategoryMap[$page][] = $name;
            if (! in_array($page, $pages)) {
                $pages[] = $page;
            }
        }

        usort($pages, function ($a, $b) {
            return $this->pageSortOrder[$a] > $this->pageSortOrder[$b];
        });

        //$pages = array_reverse($pages);

         $barLabels = [];
        $barLabelsExt = [];
        $mappedLabels = [];
        Log::info("================ CATEGORIES ============");
        Log::info(json_encode($filteredCategories));
        // return $categoryNames;
        foreach ($filteredCategories as $category) {
            $label = t($category->label);
            $realName = str_replace('factors.', '', $category->label);
            $barLabels[] = $label;
            $barLabelsExt[$realName] = $label;
            $factorName = $categoryNames[$category->id];
            if (isset($this->categoryPageMap[$factorName])) {
                $page = $this->categoryPageMap[$factorName];
                if (! isset($mappedLabels[$page])) {
                    $mappedLabels[$page] = [];
                }

                $mappedLabels[$page][$factorName] = $label;
            }
        }

        $values = [];
        $valuesStacked = [];
        $valuesStackedExt = [];
        Log::info("================ Labels ============");
        Log::info(json_encode($mappedLabels));
        $stack = [];


        $factorQuery = DB::table('profile_factors')
            ->join('questionnaire_categories', 'profile_factors.category_id', '=', 'questionnaire_categories.id')
            ->select(
                'profile_factors.category_id',
                'questionnaire_categories.name',
                DB::raw('sum(case when status = "profile.healthy" then 1 else 0 end) as healthy'),
                DB::raw('sum(case when status = "profile.danger" then 1 else 0 end) as danger'),
                DB::raw('sum(case when status = "profile.risk" then 1 else 0 end) as risk'),
                DB::raw('sum(case when status = "profile.unknown" then 1 else 0 end) as unknown')
            )
            ->whereIn('profile_factors.category_id', $categoryIds)
            ->whereIn('profile_factors.profile_id', $profileIds)
            // ->groupBy('profile_factors.category_id');
            ->groupBy(
                'profile_factors.category_id',
                'questionnaire_categories.name'
            );

        $facts = [];
        $fq= $factorQuery->get();
        Log::info("================ Category Ids ============");
        Log::info(json_encode($categoryIds));
        Log::info("================ Profile Ids ============");
        Log::info(json_encode($profileIds));
        Log::info("================ Factors Query Result ============");
        Log::info(json_encode($fq));
         // $profileSQLQuery = query_to_string($factorQuery);

        foreach ($factorQuery->get() as $result) {
            $factorId = $result->category_id;
            $factorName = $categoryNames[$factorId];
            unset($categoryNames[$factorId]);
            $healthy = $result->healthy;
            $danger = $result->danger;
            $risk = $result->risk;
            $stack[$factorName] = [intval($healthy), intval($risk), intval($danger)]; // Removed danger from added to healthy
            //if ($factorName == "badfood"){ $stack["badfood"] = array(99,99);}
            //$facts[$factorName] = array(intval($healthy), intval($result->danger), intval($risk), intval($result->unknown));
        }

        Log::info("----------- STACK -------- ");
        Log::info(json_encode($stack));
        // Hämta alla skolfaktorer
        foreach ($categoryNames as $id => $name) {
            $stack[$name] = [0, 0];
        }

        $mappedValues = [];
        foreach ($stack as $key => $value) {



            if (isset($this->categoryPageMap[$key])) {
                $page = $this->categoryPageMap[$key];
                if (! isset($mappedValues[$page])) {
                    $mappedValues[$page] = [];
                }
                $mappedValues[$page][$key] = $value;
            }

            $valuesStacked[] = $value;
            $valuesStackedExt[$key] = $value;

            if ($flags & FilterFlags::FilterCategories &&
                (isset($wantedCategories) && in_array($key, $wantedCategories))) {
                $filteredValuesStacked[] = $value;
            }
        }

        //return $facts;

        /*$profilesString = "(";
        $profilesString .= implode(",", $profileIds);
        $profilesString .= ")";*/

        $cacheId = null;
        if ($showSelectionLink) {
            $cachePrefix = 'selection-profiles-';
            do {
                $cacheId = Str::random(8);
            } while (Cache::has($cachePrefix . $cacheId));

            Cache::put($cachePrefix . $cacheId, $profileIds, 300);
        }




        return [
            'filters' => $filters,
            'flags' => $flags,

            'numWomen' => $numWomen,
            'numMen' => $numMen,
            'numStudents' => $numStudents,

            'barLabels' => $barLabels,
            'barLabelsExt' => $barLabelsExt,
            'barValuesStacked' => $filteredValuesStacked ?? $valuesStacked,
            'barValuesStackedExt' => $valuesStackedExt,
            'pages' => $pages,
            'factorMap' => $filteredCategoryMap,
            'mappedLabels' => $mappedLabels,
            'mappedValues' => $mappedValues,

            'schoolType' => $schoolType,

            'cacheId' => $cacheId,

            'testCatId' => $categoryIds, //Endast test
            //'testProId' => $profileIds,
            //'testQstring' => $profileSQLQuery,

            // 'profilesQuery' => $profileSQLQuery,
            //'profileIds' => $profilesString
            'riskGroupMen' => $riskGroupMen,
            'riskGroupWomen' => $riskGroupWomen,
            'test' => $stack,
        ];
    }

    public function createSemesterRange($year, $semester)
    {
        if ($semester == 1) {
            // Januari - Juni
            return [
                Carbon::create($year, 1, 1),
                Carbon::create($year, 6, 20),
            ];
        } else {
            // Augusti - December
            return [
                Carbon::create($year, 8, 10),
                Carbon::create($year, 12, 20),
            ];
        }
    }

    public function createExtendedSemesterRange($yearFrom, $semesterFrom, $yearTo, $semesterTo)
    {
        // Start
        if ($semesterFrom == 1) {
            $start = Carbon::create($yearFrom, 1, 1);
        } else {
            $start = Carbon::create($yearFrom, 8, 10);
        }

        // Slut
        if ($semesterTo == 1) {
            $end = Carbon::create($yearTo, 6, 20);
        } else {
            $end = Carbon::create($yearTo, 12, 20);
        }

        return [$start, $end];
    }

    public function getProfile($student, $profileType)
    {
        // TODO: Datum
        if ($profileType == 1) {
            $profile = $student->firstProfile();
        } elseif ($profileType == 2) {
            $profile = $student->latestTwoProfiles();
        } elseif ($profileType == 3) {
            $profile = $student->firstAndLatestProfile();
        } else {
            $profile = $student->latestClosedProfile();
        }

        return $profile;
    }

    public function ajaxGetFeedback(Request $request) {

        $user = Auth::user();

        if (is_null($user)) {
            abort(404);
        }

        if ($user->isStudent()) {
            abort(404);
        }

        if ($user->isSuperAdmin()) {
            $units = Unit::all();
        } else {
            $units = Unit::where('id', $user->unit_id)->get();
        }

        // Get all sections for these units
        $unitIds = $units->pluck('id');
        $sections = Section::whereIn('unit_id', $unitIds)->get();

        // Get all users for these sections
        $sectionIds = $sections->pluck('id');
        $users = User::whereIn('section_id', $sectionIds)->get();

        // Get all feedback answers for all users
        $feedbackAnswers = FeedbackAnswer::whereIn('profile_id', $users->pluck('id'))->get();

        // Count feedback answers with specific values
        $countZero = $feedbackAnswers->where('value', 0)->count();
        $countOne = $feedbackAnswers->where('value', 1)->count();
        $countNegativeOne = $feedbackAnswers->where('value', -1)->count();

        // Send JSON response with counts
        return response()->json([
            'count_zero' => $countZero,
            'count_one' => $countOne,
            'count_negative_one' => $countNegativeOne,
        ]);

    }
}

abstract class FilterFlags
{
    const Range = 1 << 0;

    const Sex = 1 << 1;

    const AgeFrom = 1 << 2;

    const AgeTo = 1 << 3;

    const Grade = 1 << 4;

    const YearFrom = 1 << 5;

    const YearTo = 1 << 6;

    const DateFrom = 1 << 7;

    const DateTo = 1 << 8;

    const Programme = 1 << 9;

    const SchoolType = 1 << 10;

    const Country = 1 << 11;

    const County = 1 << 12;

    const ProfileType = 1 << 13;

    const SectionOrUnit = 1 << 14;

    const FilterCategories = 1 << 15;

    const CrossReference = 1 << 16;

    const RiskGroups = 1 << 17;

    const SampleGroup = 1 << 18;

    const SemesterFrom = 1 << 19;

    const SemesterTo = 1 << 20;

    const ProfileNumber = 1 << 21;

    const BusinessCategory = 1 << 22;
}

abstract class CrossRefFlags
{
    const Risk = 1;

    const Healthy = 2;

    const Unknown = 4;

    const RiskAndHealthy = 3;

    const RiskAndUnknown = 5;

    const HealthyAndUnknown = 6;

    const AllThree = 7;
}
