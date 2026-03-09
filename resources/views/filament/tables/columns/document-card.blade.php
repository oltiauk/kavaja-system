@php
    $record = $getRecord();
@endphp

@once
<style>
    [data-doc-view="cards"] .fi-ta-content table.fi-ta-table {
        display: grid !important;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)) !important;
        gap: 1.25rem !important;
        padding: 1rem !important;
        background: transparent !important;
    }
    [data-doc-view="cards"] .fi-ta-content table.fi-ta-table thead { display: none !important; }
    [data-doc-view="cards"] .fi-ta-content table.fi-ta-table tbody { display: contents !important; }
    [data-doc-view="cards"] .fi-ta-content table.fi-ta-table tbody tr {
        display: block !important;
        background: transparent !important;
        border: none !important;
    }
    [data-doc-view="cards"] .fi-ta-content table.fi-ta-table tbody tr td {
        display: block !important;
        padding: 0 !important;
        border: none !important;
        background: transparent !important;
    }
    [data-doc-view="cards"] .fi-ta-content table.fi-ta-table tbody tr:hover {
        background: transparent !important;
    }

    [data-doc-view="rows"] .fi-ta-content table.fi-ta-table thead { display: none !important; }
    [data-doc-view="rows"] .fi-ta-content table.fi-ta-table tbody tr {
        background: transparent !important;
        border: none !important;
    }
    [data-doc-view="rows"] .fi-ta-content table.fi-ta-table tbody tr td {
        padding: 0.25rem 1rem !important;
        border: none !important;
        background: transparent !important;
    }
</style>
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.store('docView', {
            mode: localStorage.getItem('doc-view-mode') || 'cards',
            toggle() {
                this.mode = this.mode === 'cards' ? 'rows' : 'cards';
                localStorage.setItem('doc-view-mode', this.mode);
            }
        });
    });
</script>
@endonce

<div x-show="$store.docView.mode === 'cards'">
    <x-document-card :document="$record" />
</div>
<div x-show="$store.docView.mode === 'rows'">
    <x-document-row :document="$record" />
</div>
