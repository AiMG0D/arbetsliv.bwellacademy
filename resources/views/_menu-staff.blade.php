{!! $active == "panel" ? '<li class="active">' : '<li>' !!}<a href="{{ url('/') }}">{{ __('nav.start') }}</a></li>
@if ($user->isSuperAdmin())
    {!! $active == 'units' ? '<li class="active units-link">' : '<li class="units-link">' !!}<a href="{{ url('/admin/units') }}">{{ __('nav.units') }}</a></li>
@endif
@if ($user->isSuperAdmin() || $user->isAdmin())
    {!! $active == 'staff' ? '<li class="active staff-link">' : '<li class="staff-link">' !!}<a href="{{ url('/admin/staff') }}">{{ __('nav.staff') }}</a></li>
@endif
{!! $active == 'sections' ? '<li class="active sections-link">' : '<li class="sections-link">' !!}<a href="{{ url('/admin/sections') }}">{{ __('nav.sections') }}</a></li>
{!! $active == 'students' ? '<li class="active students-link">' : '<li class="students-link">' !!}<a href="{{ url('/admin/students') }}">{{ __('nav.students') }}</a></li>
{!! $active == 'sample' ? '<li class="active sample-link">' : '<li class="sample-link">' !!}<a href="{{ url('/admin/samples') }}">{{ __('nav.sample') }}</a></li>
{!! $active == "statistics" ? '<li class="active">' : '<li>' !!}<a href="{{ url('/statistics') }}">{{ __('nav.stats') }}</a></li>
@if ($user->isSuperAdmin())
    {!! $active == 'questionnaire' ? '<li class="active questionnaire-link">' : '<li class="questionnaire-link">' !!}<a href="{{ url('/admin/questionnaire/pages') }}">{{ __('nav.questionnaire') }}</a></li>
@endif
{!! $active == "test-profile" ? '<li class="active">' : '<li>' !!}<a href="{{ config('app.student_app_url') }}" target="_blank">{{ __('nav.test-profile') }}</a></li>
@if (config('fms.type') == 'school')
    @if (App::isLocale('sv'))
        <li><a target="_blank" href="{{ url('/etc/Coach Manual Skola.docx') }}">Manual</a></li>
        <!--<li><a target="_blank" href="{{ url('/etc/Manual BEHÖRIGHETER åk 7-9 gymnasiet.docx') }}">Manual för administratör</a></li>-->
    @else
        <li><a target="_blank" href="{{ url('/etc/Teachers Guide FMS ENG.pdf') }}">Teacher's Guide</a></li>
    @endif
@else
    @if (App::isLocale('sv'))
        <li><a target="_blank" href="https://www.canva.com/design/DAGgJrXoVRo/f7pRvPDEmVzQeLbzumhwFg/view?utm_content=DAGgJrXoVRo&utm_campaign=designshare&utm_medium=link2&utm_source=uniquelinks&utlId=h8ecd170e53">Manual</a></li>
    @endif
@endif
