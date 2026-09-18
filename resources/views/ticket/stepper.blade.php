@php
    $steps = \JeffersonGoncalves\HelpDesk\Enums\TicketStatus::pipelineSteps();
    $currentStep = $status->pipelineStep();
    $currentIndex = array_search($currentStep, $steps, true);
@endphp

<ol class="fi-hd-stepper">
    @foreach ($steps as $index => $step)
        @php
            $state = $index < $currentIndex ? 'done' : ($index === $currentIndex ? 'current' : 'future');
        @endphp

        <li class="fi-hd-stepper-step fi-hd-stepper-step--{{ $state }}">
            <span class="fi-hd-stepper-marker"></span>
            <span class="fi-hd-stepper-label">{{ $step->label() }}</span>
        </li>
    @endforeach
</ol>
