<li class="question">
    <div class="info">
        <span class="title">{!! t($label) !!}</span>
        <span class="description">{!! t($description) !!}</span>
    </div>
    @if ($has_help)
        <?php $thelp = t($help); ?>
        @if (!empty($thelp))
        <div class="help-button"></div>
        @else
        <div class="help-button-disabled"></div>
        @endif
    @else
    <div class="help-button-padding"></div>
    @endif
    <div class="elements energy-question">
        @if (empty($values['weight']) || empty($values['length']) || empty($values['training']))
            @if (App::isLocale('en'))
                You must answer the questions <em>Weight</em>, <em>Length</em> and <em>Physical training</em>
            @else
                Du måste svara på frågorna <em>Vikt</em>, <em>Längd</em> och <em>Fysisk träning</em>
            @endif
        @else
            <div>
                @if (App::isLocale('en'))
                    Your weight is {{ $values['weight'] ?? '0' }} kg
                @else
                    Din vikt är {{ $values['weight'] ?? '0' }} kg
                @endif
            </div>
            <div>
                @if (App::isLocale('en'))
                    Your length is {{ $values['length'] ?? '0' }} cm
                @else
                    Din längd är {{ $values['length'] ?? '0' }} cm
                @endif
            </div>
            <div>
                @if (App::isLocale('en'))
                    <strong>Your BMR</strong> is {{ $values['foodBMR'] ?? '0' }} kcal <a href="#food1-popup" class="open-popup-link">(Info 1)</a>
                @else
                    <strong>Ditt BMR</strong> är {{ $values['foodBMR'] ?? '0' }} kcal <a href="#food1-popup" class="open-popup-link">(Info 1)</a>
                @endif
            </div>
            <div>
                @if (App::isLocale('en'))
                    <strong>Your PAL</strong> is {{ $values['foodPAL'] ?? '0' }} <a href="#food2-popup" class="open-popup-link">(Info 2)</a>
                @else
                    <strong>Ditt PAL</strong> är {{ $values['foodPAL'] ?? '0' }} <a href="#food2-popup" class="open-popup-link">(Info 2)</a>
                @endif
            </div>
            <div>
                @if (App::isLocale('en'))
                    <strong>Your energy needs</strong> are {{ $values['foodEnergyNeeds'] ?? '0' }} kcal on a normal day
                @else
                    <strong>Ditt energibehov</strong> är {{ $values['foodEnergyNeeds'] ?? '0' }} kcal en normal dag
                @endif
            </div>
        @endif
    </div>
    @if ($has_help)
    <div class="help">
        <div class="help-icon"></div>
        <p>
            {!! t($help) !!}
        </p>
    </div>
    @endif
</li>
