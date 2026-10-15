const pagina = document.querySelector('[data-pagina-clientes]');

if (pagina) {
    const listagem = document.getElementById('listagem-clientes');
    const busca = document.getElementById('busca');
    const formularioBusca = document.getElementById('formulario-busca');
    const carregamento = document.getElementById('carregamento-listagem');
    const notificacao = document.getElementById('notificacao');
    const modal = document.getElementById('modal-cliente');
    const formulario = document.getElementById('formulario-cliente');
    const campos = document.getElementById('campos-cliente');
    const salvar = document.getElementById('salvar-cliente');
    const foto = document.getElementById('foto');
    const preview = document.getElementById('foto-preview');
    const telefone = document.getElementById('telefone');
    const modalExclusao = document.getElementById('modal-exclusao');
    const formularioExclusao = document.getElementById('formulario-exclusao');
    const confirmarExclusao = document.getElementById('confirmar-exclusao');
    const cabecalhosJson = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
    };
    let requisicaoListagem = null;
    let temporizadorBusca = null;
    let fotoAtual = '';
    let previewTemporario = null;

    function notificar(mensagem, tipo = 'sucesso') {
        document.getElementById('texto-notificacao').textContent = mensagem;
        notificacao.dataset.tipo = tipo;
        notificacao.hidden = false;
    }

    async function conferirResposta(resposta) {
        if (resposta.ok) return;

        const dados = await resposta.json().catch(() => ({}));
        const erro = new Error();
        erro.mensagem = typeof dados.message === 'string' ? dados.message : 'Não foi possível concluir a solicitação. Tente novamente.';
        erro.erros = dados.errors ?? {};
        throw erro;
    }

    function mensagemErro(erro) {
        return erro.mensagem ?? 'Não foi possível conectar ao sistema. Verifique sua conexão e tente novamente.';
    }

    function aplicarBusca(url) {
        const termo = busca.value.trim();
        if (termo) url.searchParams.set('busca', termo);
        else url.searchParams.delete('busca');
        return url;
    }

    function urlDaBusca() {
        const url = new URL(window.location.href);
        url.pathname = new URL(formularioBusca.action).pathname;
        url.searchParams.delete('page');
        return aplicarBusca(url);
    }

    async function atualizarListagem(endereco, { historico = 'substituir', foco = null } = {}) {
        requisicaoListagem?.abort();
        const requisicao = new AbortController();
        requisicaoListagem = requisicao;
        const url = new URL(endereco, window.location.href);
        carregamento.hidden = false;
        listagem.setAttribute('aria-busy', 'true');
        listagem.inert = true;

        try {
            const resposta = await fetch(url, {
                headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
                signal: requisicao.signal,
            });
            await conferirResposta(resposta);
            const documento = new DOMParser().parseFromString(await resposta.text(), 'text/html');
            if (requisicao.signal.aborted) return null;
            const conteudo = documento.querySelector('[data-conteudo-listagem]');
            if (!conteudo) throw new Error();

            // Após excluir o último registro de uma página, volta à última página disponível.
            if (Number(conteudo.dataset.paginaAtual) > Number(conteudo.dataset.ultimaPagina)) {
                url.searchParams.set('page', conteudo.dataset.ultimaPagina);
                return await atualizarListagem(url, { historico, foco });
            }

            // O fragmento vem do Blade, que escapa os dados dos clientes no servidor.
            listagem.replaceChildren(document.importNode(conteudo, true));
            if (historico === 'adicionar') window.history.pushState(null, '', url);
            else if (historico === 'substituir') window.history.replaceState(null, '', url);
            if (document.activeElement !== busca) busca.value = url.searchParams.get('busca') ?? '';
            listagem.inert = false;
            if (foco) (listagem.querySelector(foco) ?? document.getElementById('cadastrar-cliente')).focus();
            return true;
        } catch (erro) {
            if (erro.name === 'AbortError') return null;
            notificar(mensagemErro(erro), 'erro');
            return false;
        } finally {
            if (requisicaoListagem === requisicao) {
                carregamento.hidden = true;
                listagem.setAttribute('aria-busy', 'false');
                listagem.inert = false;
                requisicaoListagem = null;
            }
        }
    }

    function definirPreview(endereco = '') {
        if (previewTemporario) URL.revokeObjectURL(previewTemporario);
        previewTemporario = endereco.startsWith('blob:') ? endereco : null;
        preview.hidden = !endereco;
        document.getElementById('foto-placeholder').toggleAttribute('hidden', Boolean(endereco));
        if (endereco) preview.src = endereco;
        else preview.removeAttribute('src');
    }

    function limparErros() {
        formulario.querySelectorAll('.erro-campo').forEach((elemento) => { elemento.textContent = ''; });
        formulario.querySelectorAll('[aria-invalid]').forEach((elemento) => elemento.removeAttribute('aria-invalid'));
        document.getElementById('erro-formulario').hidden = true;
    }

    function mostrarErros(erros) {
        let primeiroCampo = null;
        for (const [nome, mensagens] of Object.entries(erros)) {
            const campo = formulario.elements.namedItem(nome);
            const aviso = document.getElementById(`erro-${nome}`);
            if (!campo || !aviso) continue;
            aviso.textContent = Array.isArray(mensagens) ? mensagens[0] : mensagens;
            campo.setAttribute('aria-invalid', 'true');
            primeiroCampo ??= campo;
        }
        primeiroCampo?.focus();
    }

    function bloquearModal(ocupado, mensagem = '') {
        modal.dataset.ocupado = String(ocupado);
        formulario.setAttribute('aria-busy', String(ocupado));
        campos.disabled = ocupado;
        salvar.disabled = ocupado;
        modal.querySelectorAll('[data-fechar-modal]').forEach((botao) => { botao.disabled = ocupado; });
        const estado = document.getElementById('estado-formulario');
        estado.textContent = mensagem;
        estado.hidden = !ocupado;
        salvar.textContent = ocupado ? mensagem : (formulario.dataset.modo === 'editar' ? 'Salvar alterações' : 'Cadastrar cliente');
    }

    function prepararFormulario(edicao = false) {
        formulario.reset();
        limparErros();
        formulario.dataset.modo = edicao ? 'editar' : 'cadastrar';
        formulario.action = pagina.dataset.urlCadastro;
        fotoAtual = '';
        definirPreview();
        foto.required = !edicao;
        document.getElementById('foto-obrigatoriedade').textContent = edicao ? '(opcional)' : '(obrigatória)';
        document.getElementById('titulo-modal').textContent = edicao ? 'Editar cliente' : 'Cadastrar cliente';
        document.getElementById('descricao-modal').textContent = edicao ? 'Atualize os dados. Selecione uma foto apenas se desejar substituí-la.' : 'Preencha os dados para adicionar um novo cliente.';
        bloquearModal(false);
    }

    function formatarTelefone(valor) {
        // Mantém caracteres inválidos visíveis e limita a máscara a 11 dígitos.
        if (/[^0-9() -]/.test(valor)) return valor;
        const numeros = valor.replace(/\D/g, '').slice(0, 11);
        if (!numeros) return '';
        if (numeros.length < 3) return `(${numeros}`;
        const assinante = numeros.slice(2);
        const separacao = assinante.length > 8 ? 5 : 4;
        const final = assinante.length > separacao ? `-${assinante.slice(separacao)}` : '';
        return `(${numeros.slice(0, 2)}) ${assinante.slice(0, separacao)}${final}`;
    }

    async function abrirEdicao(botao) {
        prepararFormulario(true);
        bloquearModal(true, 'Carregando cliente…');
        modal.showModal();
        let carregou = false;
        try {
            const resposta = await fetch(botao.dataset.url, { headers: cabecalhosJson, credentials: 'same-origin' });
            await conferirResposta(resposta);
            const cliente = await resposta.json();
            formulario.action = botao.dataset.url;
            formulario.elements.nome.value = cliente.nome;
            formulario.elements.email.value = cliente.email;
            // Apresenta no formato nacional os registros antigos que possuem +55.
            telefone.value = formatarTelefone(cliente.telefone.replace(/^\+55 ?/, ''));
            fotoAtual = new URL(cliente.foto.split('/').map(encodeURIComponent).join('/'), pagina.dataset.urlFotos).href;
            definirPreview(fotoAtual);
            carregou = true;
        } catch (erro) {
            modal.close();
            notificar(mensagemErro(erro), 'erro');
        } finally {
            bloquearModal(false);
            if (carregou) formulario.elements.nome.focus();
        }
    }

    function validarFormulario() {
        const erros = {};
        for (const campo of [formulario.elements.nome, formulario.elements.email, telefone, foto]) {
            if (campo.validity.valid) continue;
            erros[campo.name] = campo.validity.valueMissing ? 'Preencha este campo.' : 'Verifique o valor informado.';
            if (campo.validity.typeMismatch && campo.name === 'email') erros.email = 'Informe um e-mail válido.';
            if (campo.validity.patternMismatch && campo.name === 'telefone') erros.telefone = 'Informe um telefone com DDD e 10 ou 11 dígitos.';
        }
        mostrarErros(erros);
        return Object.keys(erros).length === 0;
    }

    async function atualizarAposOperacao(mensagem, foco = null) {
        clearTimeout(temporizadorBusca);
        notificar(mensagem);
        const url = aplicarBusca(new URL(window.location.href));
        const atualizou = await atualizarListagem(url, { foco });
        if (atualizou === false) notificar(`${mensagem} A listagem não pôde ser atualizada. Tente buscar novamente.`, 'aviso');
    }

    formulario.addEventListener('submit', async (evento) => {
        evento.preventDefault();
        if (modal.dataset.ocupado === 'true') return;
        limparErros();
        if (!validarFormulario()) return;

        const edicao = formulario.dataset.modo === 'editar';
        const dados = new FormData(formulario);
        if (edicao) dados.set('_method', 'PUT');
        bloquearModal(true, 'Salvando…');
        let salvou = false;
        try {
            const resposta = await fetch(formulario.action, {
                method: 'POST', body: dados, headers: cabecalhosJson, credentials: 'same-origin',
            });
            await conferirResposta(resposta);
            salvou = true;
            modal.close();
        } catch (erro) {
            const aviso = document.getElementById('erro-formulario');
            aviso.textContent = mensagemErro(erro);
            aviso.hidden = false;
            // Os campos precisam estar habilitados para receber foco nos erros 422.
            bloquearModal(false);
            mostrarErros(erro.erros ?? {});
        } finally {
            bloquearModal(false);
        }
        if (salvou) await atualizarAposOperacao(edicao ? 'Cliente atualizado com sucesso.' : 'Cliente cadastrado com sucesso.', '[data-ordenar="nome"]');
    });

    formularioExclusao.addEventListener('submit', async (evento) => {
        evento.preventDefault();
        if (modalExclusao.dataset.ocupado === 'true') return;
        modalExclusao.dataset.ocupado = 'true';
        formularioExclusao.setAttribute('aria-busy', 'true');
        modalExclusao.querySelectorAll('button').forEach((botao) => { botao.disabled = true; });
        confirmarExclusao.textContent = 'Excluindo…';
        document.getElementById('erro-exclusao').hidden = true;
        let mensagemExclusao = null;
        try {
            const resposta = await fetch(formularioExclusao.dataset.url, {
                method: 'DELETE', headers: cabecalhosJson, credentials: 'same-origin',
            });
            await conferirResposta(resposta);
            const dados = await resposta.json();
            mensagemExclusao = dados.message || 'Cliente excluído com sucesso.';
            modalExclusao.close();
        } catch (erro) {
            const aviso = document.getElementById('erro-exclusao');
            aviso.textContent = mensagemErro(erro);
            aviso.hidden = false;
        } finally {
            modalExclusao.dataset.ocupado = 'false';
            formularioExclusao.setAttribute('aria-busy', 'false');
            modalExclusao.querySelectorAll('button').forEach((botao) => { botao.disabled = false; });
            confirmarExclusao.textContent = 'Excluir cliente';
        }
        if (mensagemExclusao) await atualizarAposOperacao(mensagemExclusao, '[data-ordenar="nome"]');
    });

    document.getElementById('cadastrar-cliente').addEventListener('click', () => {
        prepararFormulario();
        modal.showModal();
        formulario.elements.nome.focus();
    });

    listagem.addEventListener('click', (evento) => {
        const link = evento.target.closest('a[data-listagem]');
        if (link && !evento.ctrlKey && !evento.metaKey && !evento.shiftKey && !evento.altKey && evento.button === 0) {
            evento.preventDefault();
            clearTimeout(temporizadorBusca);
            const url = aplicarBusca(new URL(link.href));
            const foco = link.dataset.ordenar ? `[data-ordenar="${link.dataset.ordenar}"]` : '[aria-current="page"]';
            atualizarListagem(url, { historico: 'adicionar', foco });
            return;
        }
        const editar = evento.target.closest('[data-editar]');
        if (editar) abrirEdicao(editar);
        const excluir = evento.target.closest('[data-excluir]');
        if (excluir) {
            document.getElementById('nome-exclusao').textContent = excluir.dataset.nome;
            document.getElementById('erro-exclusao').hidden = true;
            formularioExclusao.dataset.url = excluir.dataset.url;
            modalExclusao.showModal();
        }
    });

    busca.addEventListener('input', () => {
        clearTimeout(temporizadorBusca);
        // Invalida a busca anterior imediatamente, antes do próximo debounce.
        requisicaoListagem?.abort();
        temporizadorBusca = setTimeout(() => atualizarListagem(urlDaBusca()), 300);
    });
    formularioBusca.addEventListener('submit', (evento) => {
        evento.preventDefault();
        clearTimeout(temporizadorBusca);
        atualizarListagem(urlDaBusca());
    });
    window.addEventListener('popstate', () => {
        clearTimeout(temporizadorBusca);
        busca.value = new URL(window.location.href).searchParams.get('busca') ?? '';
        atualizarListagem(window.location.href, { historico: false });
    });

    telefone.addEventListener('input', () => {
        const posicao = telefone.selectionStart;
        const noFinal = posicao === telefone.value.length;
        const digitosAntes = telefone.value.slice(0, posicao).replace(/\D/g, '').length;
        telefone.value = formatarTelefone(telefone.value);
        if (!noFinal) {
            let contagem = 0;
            let cursor = 0;
            while (cursor < telefone.value.length && contagem < digitosAntes) {
                if (/\d/.test(telefone.value[cursor])) contagem++;
                cursor++;
            }
            telefone.setSelectionRange(cursor, cursor);
        }
    });
    foto.addEventListener('change', () => {
        const arquivo = foto.files[0];
        definirPreview(arquivo?.type.startsWith('image/') ? URL.createObjectURL(arquivo) : fotoAtual);
    });
    preview.addEventListener('error', () => {
        preview.hidden = true;
        document.getElementById('foto-placeholder').removeAttribute('hidden');
    });
    document.addEventListener('error', (evento) => {
        if (evento.target.matches?.('[data-foto-cliente]')) evento.target.hidden = true;
    }, true);
    // Cobre imagens cujo carregamento já falhou antes da execução do módulo.
    document.querySelectorAll('[data-foto-cliente]').forEach((imagem) => {
        if (imagem.complete && imagem.naturalWidth === 0) imagem.hidden = true;
    });

    for (const dialogo of [modal, modalExclusao]) {
        dialogo.addEventListener('cancel', (evento) => {
            if (dialogo.dataset.ocupado === 'true') evento.preventDefault();
        });
        dialogo.addEventListener('click', (evento) => {
            const fechar = evento.target.closest('[data-fechar-modal]');
            const limite = dialogo.getBoundingClientRect();
            const fora = evento.target === dialogo && (evento.clientX < limite.left || evento.clientX > limite.right || evento.clientY < limite.top || evento.clientY > limite.bottom);
            if ((fechar || fora) && dialogo.dataset.ocupado !== 'true') dialogo.close();
        });
    }
    modal.addEventListener('close', () => definirPreview());
    document.getElementById('fechar-notificacao').addEventListener('click', () => { notificacao.hidden = true; });
}
