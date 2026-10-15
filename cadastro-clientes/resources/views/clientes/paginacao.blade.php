@if ($paginator->hasPages())
    <nav class="paginacao" aria-label="Paginação de clientes">
        @if ($paginator->onFirstPage())
            <span class="pagina-desabilitada" aria-disabled="true">Anterior</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" data-listagem>Anterior</a>
        @endif
        @foreach ($elements as $elemento)
            @if (is_string($elemento))
                <span class="pagina-reticencias" aria-hidden="true">{{ $elemento }}</span>
            @else
                @foreach ($elemento as $numero => $url)
                    @if ($numero === $paginator->currentPage())
                        <span class="pagina-atual" aria-current="page" aria-label="Página {{ $numero }}" tabindex="-1">{{ $numero }}</span>
                    @else
                        <a href="{{ $url }}" data-listagem aria-label="Ir para a página {{ $numero }}">{{ $numero }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" data-listagem>Próxima</a>
        @else
            <span class="pagina-desabilitada" aria-disabled="true">Próxima</span>
        @endif
    </nav>
@endif
