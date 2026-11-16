@if ($paginator->hasPages())
    <nav class="clinic-pager" aria-label="Halaman daftar klinik">
        <p class="clinic-pager-summary">Menampilkan <strong>{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</strong> dari {{ $paginator->total() }} klinik</p>
        <div class="clinic-pager-controls" x-on:click="if ($event.target.closest('button:not(:disabled)')) document.getElementById('clinic-results-heading')?.scrollIntoView({ block: 'start' })">
            <button type="button" class="clinic-page-button clinic-page-direction" wire:click="previousPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" @disabled($paginator->onFirstPage()) aria-label="Halaman sebelumnya"><flux:icon.chevron-left class="size-4" /><span>Sebelumnya</span></button>
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="clinic-page-gap">{{ $element }}</span>
                @else
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="clinic-page-button is-current" aria-current="page" aria-label="Halaman {{ $page }}">{{ $page }}</span>
                        @else
                            <button type="button" wire:key="clinic-page-{{ $paginator->getPageName() }}-{{ $page }}" class="clinic-page-button" wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" aria-label="Ke halaman {{ $page }}">{{ $page }}</button>
                        @endif
                    @endforeach
                @endif
            @endforeach
            <button type="button" class="clinic-page-button clinic-page-direction" wire:click="nextPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" @disabled(! $paginator->hasMorePages()) aria-label="Halaman berikutnya"><span>Berikutnya</span><flux:icon.chevron-right class="size-4" /></button>
        </div>
    </nav>
@endif
