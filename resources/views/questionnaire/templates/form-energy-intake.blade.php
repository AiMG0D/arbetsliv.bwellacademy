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
            @foreach (\App\Nobox\Calculation\EnergyIntake::getGroups() as $group)
                <div class="food-group">
                    <h3>{{ $group['label'] }}</h3>
                    @foreach (\App\Nobox\Calculation\EnergyIntake::getOptions() as $option)
                        @if ($option['group'] === $group['name'])
                            <div class="food-option">
                                <input type="number" min="0" max="9" id="{{ $option['name'] }}" name="{{ $option['name'] }}" value="{{ $values[$option['name']] ?? '0' }}" {{ !$editable ? 'disabled="disabled"' : '' }}>
                                <label for="{{ $option['name'] }}">{{ $option['label'] }} ({{ $option['kcal'] }} kcal)</label>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endforeach
            <div>
                @if (App::isLocale('en'))
                    Your calorie intake is <span class="energy-intake-value">{{ $values['foodEnergyIntake'] ?? '0' }}</span> kcal
                @else
                    Ditt kcaloriintag är <span class="energy-intake-value">{{ $values['foodEnergyIntake'] ?? '0' }}</span> kcal
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
