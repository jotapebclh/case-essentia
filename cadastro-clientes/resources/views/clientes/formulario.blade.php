<dialog id="modal-cliente" class="modal" aria-labelledby="titulo-modal" aria-describedby="descricao-modal" data-ocupado="false">
    <div class="modal-cabecalho">
        <div>
            <h2 id="titulo-modal">Cadastrar cliente</h2>
            <p id="descricao-modal">Preencha os dados para adicionar um novo cliente.</p>
        </div>
        <button type="button" class="botao-icone" data-fechar-modal aria-label="Fechar formulário">×</button>
    </div>
    <form id="formulario-cliente" method="post" action="{{ route('clientes.create') }}" enctype="multipart/form-data" novalidate>
        @csrf
        <div class="modal-corpo">
            <p id="erro-formulario" class="aviso-erro" role="alert" hidden></p>
            <p id="estado-formulario" class="estado-formulario" role="status" hidden></p>
            <fieldset id="campos-cliente">
                <legend class="somente-leitor">Dados do cliente</legend>
                <div class="campo campo-foto">
                    <span class="preview-foto" aria-hidden="true">
                        <svg id="foto-placeholder" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/></svg>
                        <img id="foto-preview" alt="" width="80" height="80" hidden>
                    </span>
                    <div class="campo-foto-controle">
                        <label for="foto">Foto <span id="foto-obrigatoriedade" class="indicacao-campo">(obrigatória)</span></label>
                        <input id="foto" name="foto" type="file" accept="image/jpeg,image/png,image/webp" required aria-describedby="ajuda-foto erro-foto">
                        <p id="ajuda-foto" class="ajuda-campo">JPEG, PNG ou WebP. Até 2 MB.</p>
                        <p id="erro-foto" class="erro-campo" aria-live="polite"></p>
                    </div>
                </div>
                <div class="campo">
                    <label for="nome">Nome <span aria-hidden="true">*</span></label>
                    <input id="nome" name="nome" type="text" maxlength="150" autocomplete="name" required aria-describedby="erro-nome">
                    <p id="erro-nome" class="erro-campo" aria-live="polite"></p>
                </div>
                <div class="campo">
                    <label for="email">E-mail <span aria-hidden="true">*</span></label>
                    <input id="email" name="email" type="email" maxlength="255" autocomplete="email" required aria-describedby="erro-email">
                    <p id="erro-email" class="erro-campo" aria-live="polite"></p>
                </div>
                <div class="campo">
                    <label for="telefone">Telefone <span aria-hidden="true">*</span></label>
                    <input id="telefone" name="telefone" type="tel" inputmode="tel" maxlength="15" autocomplete="tel-national" required
                        pattern="(?:\([0-9]{2}\)|[0-9]{2}) ?[0-9]{4,5}-?[0-9]{4}"
                        placeholder="(99) 99999-9999" aria-describedby="ajuda-telefone erro-telefone">
                    <p id="ajuda-telefone" class="ajuda-campo">Informe 10 ou 11 dígitos, incluindo o DDD.</p>
                    <p id="erro-telefone" class="erro-campo" aria-live="polite"></p>
                </div>
            </fieldset>
        </div>
        <div class="modal-rodape">
            <button type="button" class="botao botao-secundario" data-fechar-modal>Cancelar</button>
            <button id="salvar-cliente" type="submit" class="botao botao-primario">Cadastrar cliente</button>
        </div>
    </form>
</dialog>
