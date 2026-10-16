<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Gerencie os clientes cadastrados na Essentia.">
    <title>Clientes · Essentia</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <a class="pular-conteudo" href="#conteudo">Pular para o conteúdo</a>
    <main id="conteudo" class="pagina" data-pagina-clientes
        data-url-cadastro="{{ route('clientes.create') }}"
        data-url-fotos="{{ asset('storage') }}/">
        <div class="titulo-pagina">
            <div>
                <h1>Clientes</h1>
            </div>
            <button id="cadastrar-cliente" class="botao botao-primario" type="button">
                <span class="icone-mais" aria-hidden="true">+</span> Cadastrar cliente
            </button>
        </div>

        <div id="notificacao" class="notificacao" role="status" aria-live="polite" hidden>
            <p id="texto-notificacao"></p>
            <button type="button" id="fechar-notificacao" class="botao-icone" aria-label="Fechar mensagem">×</button>
        </div>

        <section class="painel" aria-label="Gerenciamento de clientes">
            <div class="barra-listagem">
                <div>
                    <h2>Todos os clientes</h2>
                </div>
                <form id="formulario-busca" class="busca" action="{{ route('clientes.list') }}" method="get" role="search">
                    <label class="somente-leitor" for="busca">Buscar por nome, e-mail ou telefone</label>
                    <svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 4 4"/></svg>
                    <input id="busca" name="busca" type="search" maxlength="255" placeholder="Buscar por nome, e-mail ou telefone"
                        value="{{ request('busca') }}" autocomplete="off">
                    <button type="submit" class="somente-leitor ao-focar">Buscar</button>
                </form>
            </div>
            <p id="carregamento-listagem" class="carregamento-listagem" role="status" hidden>
                <span class="indicador-carregamento" aria-hidden="true"></span> Atualizando clientes…
            </p>
            <div id="listagem-clientes" aria-busy="false">
                @include('clientes.listagem')
            </div>
        </section>
    </main>

    @include('clientes.formulario')
    @include('clientes.exclusao')
</body>
</html>
