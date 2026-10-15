<dialog id="modal-exclusao" class="modal modal-exclusao" aria-labelledby="titulo-exclusao" aria-describedby="descricao-exclusao" data-ocupado="false">
    <div class="modal-cabecalho">
        <h2 id="titulo-exclusao">Excluir cliente?</h2>
        <button type="button" class="botao-icone" data-fechar-modal aria-label="Fechar confirmação">×</button>
    </div>
    <form id="formulario-exclusao" method="dialog">
        <div class="modal-corpo">
            <p id="descricao-exclusao">Deseja excluir <strong id="nome-exclusao"></strong>? O cliente será removido da listagem.</p>
            <p id="erro-exclusao" class="aviso-erro" role="alert" hidden></p>
        </div>
        <div class="modal-rodape">
            <button type="button" class="botao botao-secundario" data-fechar-modal autofocus>Cancelar</button>
            <button type="submit" id="confirmar-exclusao" class="botao botao-perigo">Excluir cliente</button>
        </div>
    </form>
</dialog>
