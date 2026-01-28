@php
    $record = $getRecord();
@endphp

{{-- Filament table grid CSS --}}
@once
<style>
    .fi-ta-content table.fi-ta-table {
        display: grid !important;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)) !important;
        gap: 1.25rem !important;
        padding: 1rem !important;
        background: transparent !important;
    }
    .fi-ta-content table.fi-ta-table thead { display: none !important; }
    .fi-ta-content table.fi-ta-table tbody { display: contents !important; }
    .fi-ta-content table.fi-ta-table tbody tr {
        display: block !important;
        background: transparent !important;
        border: none !important;
    }
    .fi-ta-content table.fi-ta-table tbody tr td {
        display: block !important;
        padding: 0 !important;
        border: none !important;
        background: transparent !important;
    }
    .fi-ta-content table.fi-ta-table tbody tr:hover {
        background: transparent !important;
    }
</style>
@endonce

<x-document-card :document="$record" />
