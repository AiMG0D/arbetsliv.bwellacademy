<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\QuestionnairePage;
use App\Services\QuestionsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

class QuestionsController extends Controller
{
    public function workQuestions(Request $request, QuestionsService $questionsService)
    {
        if (config('fms.type') !== 'work') {
            abort(404);
        }

        // Set locale based on request parameter or header
        $locale = $this->getLocaleFromRequest($request);
        App::setLocale($locale);

        return $questionsService->workQuestions($locale);
    }

    public function schoolQuestions(Request $request, QuestionsService $questionsService)
    {
        if (config('fms.type') !== 'school') {
            abort(404);
        }

        // Set locale based on request parameter or header
        $locale = $this->getLocaleFromRequest($request);
        App::setLocale($locale);

        return $questionsService->schoolQuestions($locale);
    }

    public function lifeQuestions(Request $request, QuestionsService $questionsService)
    {
        // Set locale based on request parameter or header
        $locale = $this->getLocaleFromRequest($request);
        App::setLocale($locale);

        return $questionsService->lifeQuestions($locale);
    }

    /**
     * Get locale from request parameters or headers
     */
    private function getLocaleFromRequest(Request $request): string
    {
        // Priority: query parameter > header > default
        $locale = $request->query('lang') ?? 
                  $request->header('Accept-Language') ?? 
                  'sv';
        
        // Validate locale
        $validLocales = ['sv', 'en'];
        return in_array($locale, $validLocales) ? $locale : 'sv';
    }
}
