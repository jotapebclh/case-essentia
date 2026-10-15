@php
    $ordenar = request('ordenar', 'nome');
    $direcao = request('direcao', 'asc');
@endphp
<div data-conteudo-listagem data-pagina-atual="{{ $clientes->currentPage() }}" data-ultima-pagina="{{ $clientes->lastPage() }}">
    <div class="tabela-rolagem" tabindex="0" role="region" aria-label="Tabela de clientes; role horizontalmente em telas pequenas">
        <table class="tabela-clientes">
            <caption class="somente-leitor">Clientes ativos e suas informações de contato</caption>
            <thead>
                <tr>
                    @foreach (['nome' => 'Nome', 'email' => 'E-mail', 'telefone' => 'Telefone'] as $coluna => $rotulo)
                        @php
                            $proximaDirecao = $ordenar === $coluna && $direcao === 'asc' ? 'desc' : 'asc';
                            $urlOrdenacao = route('clientes.list', array_merge(request()->only('busca'), ['ordenar' => $coluna, 'direcao' => $proximaDirecao]));
                        @endphp
                        <th scope="col" aria-sort="{{ $ordenar === $coluna ? ($direcao === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                            <a href="{{ $urlOrdenacao }}" data-listagem data-ordenar="{{ $coluna }}"
                                aria-label="Ordenar por {{ $rotulo }} em ordem {{ $proximaDirecao === 'asc' ? 'crescente' : 'decrescente' }}">
                                {{ $rotulo }} <span class="seta-ordenacao" aria-hidden="true">{{ $ordenar === $coluna ? ($direcao === 'asc' ? '↑' : '↓') : '↕' }}</span>
                            </a>
                        </th>
                    @endforeach
                    <th scope="col" class="coluna-acoes">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($clientes as $cliente)
                    @php
                        $digitos = preg_replace('/\D/', '', $cliente->telefone);
                        $prefixo = strlen($digitos) > 11 && str_starts_with($digitos, '55') ? '+55 ' : '';
                        $digitos = $prefixo !== '' ? substr($digitos, 2) : $digitos;
                        $telefoneFormatado = $prefixo.preg_replace('/^(\d{2})(\d{4,5})(\d{4})$/', '($1) $2-$3', $digitos);
                    @endphp
                    <tr>
                        <td>
                            <div class="identificacao-cliente">
                                <span class="avatar" aria-hidden="true">
                                    <span>{{ mb_strtoupper(mb_substr($cliente->nome, 0, 1)) }}</span>
                                    <img src="{{ asset('storage/'.$cliente->foto) }}" alt="" loading="lazy" width="40" height="40" data-foto-cliente>
                                </span>
                                <span class="nome-cliente">{{ $cliente->nome }}</span>
                            </div>
                        </td>
                        <td class="email-cliente">{{ $cliente->email }}</td>
                        <td class="telefone-cliente">{{ $telefoneFormatado }}</td>
                        <td>
                            <div class="acoes-cliente">
                                <button type="button" class="botao-acao" data-editar
                                    data-url="{{ route('clientes.show', $cliente) }}" aria-label="Editar {{ $cliente->nome }}">
                                    <svg aria-hidden="true" viewBox="0 0 24 24"><path d="m15 5 4 4M4 20l4-1L20 7a2.8 2.8 0 0 0-4-4L4 15z"/></svg>
                                    Editar
                                </button>
                                <button type="button" class="botao-acao botao-acao-perigo" data-excluir data-nome="{{ $cliente->nome }}"
                                    data-url="{{ route('clientes.delete', $cliente) }}" aria-label="Excluir {{ $cliente->nome }}">
                                    <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7M14 10v7"/></svg>
                                    Excluir
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="estado-vazio">
                            <span class="icone-vazio" aria-hidden="true">
                                <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/></svg>
                            </span>
                            <h3>{{ request('busca') ? 'Nenhum cliente encontrado' : 'Sua lista de clientes começa aqui' }}</h3>
                            <p>{{ request('busca') ? 'Tente buscar por outro nome, e-mail ou telefone.' : 'Clique em “Cadastrar cliente” para adicionar seu primeiro contato.' }}</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="rodape-listagem">
        <p role="status" aria-live="polite">
            @if ($clientes->total() > 0 && $clientes->count() > 0)
                Mostrando <strong>{{ $clientes->firstItem() }}–{{ $clientes->lastItem() }}</strong> de <strong>{{ $clientes->total() }}</strong> clientes
            @else
                Nenhum cliente nesta página
            @endif
        </p>
        {{ $clientes->onEachSide(1)->links('clientes.paginacao') }}
    </div>
</div>
