@php
    $record = $getRecord();
@endphp

@if($record)
    <div class="col-span-full">
        @livewire('documents-grid', ['encounter' => $record], key('documents-grid-' . $record->id))
    </div>
@endif
