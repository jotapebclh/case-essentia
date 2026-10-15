<?php

namespace Tests\Feature;

use App\Models\Cliente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class ClienteTest extends TestCase
{
    use RefreshDatabase;

    private int $sequencia = 0;

    public function createApplication()
    {
        $aplicacao = parent::createApplication();
        $aplicacao['config']->set('database.default', 'sqlite');
        $aplicacao['config']->set('database.connections.sqlite.url', null);
        $aplicacao['config']->set('database.connections.sqlite.database', ':memory:');

        return $aplicacao;
    }

    protected function beforeRefreshingDatabase(): void
    {
        // Confere a conexão resolvida antes de permitir migrate:fresh.
        $conexao = $this->app['db']->connection();
        $this->assertSame('sqlite', $conexao->getDriverName());
        $this->assertSame(':memory:', $conexao->getDatabaseName());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Storage::fake('public');
    }

    public function test_renderiza_interface_inicial_com_dados_escapados(): void
    {
        $nome = '<img src=x onerror=alert(1)>';
        $this->criarCliente(['nome' => $nome, 'telefone' => '11999999999']);

        $this->get('/clientes')->assertOk()->assertViewIs('clientes.index')
            ->assertSeeText('Gerencie os clientes')
            ->assertSee('id="modal-cliente"', false)
            ->assertSee('id="modal-exclusao"', false)
            ->assertSee('(11) 99999-9999')
            ->assertSee($nome)->assertDontSee($nome, false);
    }

    public function test_listagem_ajax_retorna_apenas_parcial_com_filtros_e_paginacao(): void
    {
        for ($indice = 0; $indice < 12; $indice++) {
            $this->criarCliente(['nome' => 'Contato '.$indice]);
        }
        $this->criarCliente(['nome' => 'Contato excluido'])->delete();

        $this->get('/clientes?busca=Contato&ordenar=email&direcao=desc&page=2', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertViewIs('clientes.listagem')
            ->assertViewHas('clientes', fn ($clientes) => $clientes->total() === 12 && $clientes->count() === 2)
            ->assertSee('data-conteudo-listagem', false)
            ->assertSee('data-pagina-atual="2"', false)
            ->assertSee('busca=Contato', false)->assertSee('ordenar=email', false)
            ->assertDontSee('<html', false)->assertDontSee('id="modal-cliente"', false)
            ->assertDontSee('Contato excluido');
    }

    public function test_cadastra_cliente_com_foto_e_retorna_json(): void
    {
        $resposta = $this->post('/clientes', $this->dados());

        $resposta->assertCreated()->assertJsonPath('nome', 'Maria Silva');
        $cliente = Cliente::findOrFail($resposta->json('id'));
        $this->assertStringStartsWith('clientes/', $cliente->foto);
        $this->assertNotSame('clientes/foto.png', $cliente->foto);
        Storage::disk('public')->assertExists($cliente->foto);
        $this->assertDatabaseHas('clientes', ['id' => $cliente->id, 'email' => 'maria@example.com']);
    }

    public function test_consulta_cliente_para_edicao(): void
    {
        $cliente = $this->criarCliente();

        $this->get('/clientes/'.$cliente->id)->assertOk()
            ->assertJsonPath('id', $cliente->id)
            ->assertJsonPath('foto', $cliente->foto);
    }

    public function test_atualiza_sem_foto_e_aceita_o_proprio_email(): void
    {
        $cliente = $this->criarCliente();
        $dados = ['nome' => 'Nome atualizado', 'email' => $cliente->email, 'telefone' => '(11) 3333-4444'];

        $this->put('/clientes/'.$cliente->id, $dados)->assertOk()
            ->assertJsonPath('nome', 'Nome atualizado')->assertJsonPath('foto', $cliente->foto);

        $this->assertDatabaseHas('clientes', ['id' => $cliente->id, 'nome' => 'Nome atualizado']);
        Storage::disk('public')->assertExists($cliente->foto);
    }

    public function test_substitui_foto_somente_depois_de_persistir(): void
    {
        $cliente = $this->criarCliente();
        $fotoAntiga = $cliente->foto;

        Cliente::updated(function () use ($fotoAntiga) {
            Storage::disk('public')->assertExists($fotoAntiga);
        });

        $resposta = $this->put('/clientes/'.$cliente->id, $this->dados(['email' => $cliente->email]));

        $resposta->assertOk();
        $this->assertNotSame($fotoAntiga, $cliente->fresh()->foto);
        Storage::disk('public')->assertExists($resposta->json('foto'));
        Storage::disk('public')->assertMissing($fotoAntiga);
    }

    public function test_exclui_logicamente_preserva_foto_e_libera_email(): void
    {
        $cliente = $this->criarCliente();

        $this->delete('/clientes/'.$cliente->id)->assertOk()
            ->assertJsonPath('message', 'Cliente excluído com sucesso.');
        $this->assertSoftDeleted($cliente);
        Storage::disk('public')->assertExists($cliente->foto);

        $this->post('/clientes', $this->dados(['email' => $cliente->email]))->assertCreated();
    }

    public function test_edicao_pode_reutilizar_email_de_cliente_excluido(): void
    {
        $excluido = $this->criarCliente();
        $excluido->delete();
        $cliente = $this->criarCliente();

        $this->put('/clientes/'.$cliente->id, $this->dados(['email' => $excluido->email]))
            ->assertOk()->assertJsonPath('email', $excluido->email);
    }

    public function test_rejeita_email_de_outro_cliente_ativo_no_cadastro_e_edicao(): void
    {
        $existente = $this->criarCliente();
        $cliente = $this->criarCliente();
        $dados = $this->dados(['email' => $existente->email]);

        $this->post('/clientes', $dados)->assertUnprocessable()->assertJsonValidationErrors('email')
            ->assertJsonPath('errors.email.0', 'Este e-mail já está em uso por um cliente ativo.');
        $this->put('/clientes/'.$cliente->id, $dados)->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->assertCount(2, Storage::disk('public')->allFiles('clientes'));
    }

    public function test_campos_obrigatorios_retornam_erros_em_portugues(): void
    {
        $this->post('/clientes')->assertUnprocessable()
            ->assertJsonValidationErrors(['nome', 'email', 'telefone', 'foto'])
            ->assertJsonPath('message', 'Os dados informados são inválidos.')
            ->assertJsonPath('errors.nome.0', 'O campo nome é obrigatório.');
    }

    #[DataProvider('camposInvalidos')]
    public function test_rejeita_dados_invalidos(string $campo, mixed $valor): void
    {
        $this->post('/clientes', $this->dados([$campo => $valor]))
            ->assertUnprocessable()->assertJsonValidationErrors($campo);

        $this->assertDatabaseCount('clientes', 0);
        $this->assertSame([], Storage::disk('public')->allFiles('clientes'));
    }

    public static function camposInvalidos(): array
    {
        return [
            'nome longo' => ['nome', str_repeat('a', 151)],
            'nome como lista' => ['nome', ['Maria']],
            'email invalido' => ['email', 'sem-arroba'],
            'email longo' => ['email', str_repeat('a', 245).'@example.com'],
            'telefone sem ddd' => ['telefone', '99999-9999'],
            'telefone com letras' => ['telefone', '(11) 99999-9999a'],
            'telefone estrangeiro' => ['telefone', '+1 212 555-1212'],
            'telefone com prefixo internacional' => ['telefone', '+55 (11) 99999-9999'],
            'telefone longo' => ['telefone', str_repeat('1', 21)],
            'telefone com doze digitos' => ['telefone', '119999999999'],
            'telefone incompleto' => ['telefone', '(11) 999-9999'],
            'telefone com mascara incompleta' => ['telefone', '(11 99999-9999'],
            'telefone com caractere invalido' => ['telefone', '(11) 99999/9999'],
            'telefone com quebra de linha' => ['telefone', "(11)\n99999-9999"],
            'telefone com tabulacao' => ['telefone', "(11)\t99999-9999"],
        ];
    }

    #[DataProvider('telefonesValidos')]
    public function test_aceita_telefones_brasileiros_com_ddd(string $telefone): void
    {
        $this->post('/clientes', $this->dados(['telefone' => $telefone]))->assertCreated();
    }

    public static function telefonesValidos(): array
    {
        return [
            ['(11) 99999-9999'], ['11999999999'], ['(21) 3333-4444'],
            ['2133334444'], ['(01) 99999-9999'], ['(20) 1234-5678'],
            ['(00) 0000-0000'], ['11234567890'],
        ];
    }

    public function test_rejeita_foto_acima_de_dois_megabytes(): void
    {
        $foto = UploadedFile::fake()->createWithContent('foto.png', $this->imagem())->size(2049);

        $this->post('/clientes', $this->dados(['foto' => $foto]))->assertUnprocessable()
            ->assertJsonValidationErrors('foto')
            ->assertJsonPath('errors.foto.0', 'A foto deve ter no máximo 2 MB.');
    }

    public function test_aceita_foto_no_limite_de_dois_megabytes(): void
    {
        $foto = UploadedFile::fake()->createWithContent('foto.png', $this->imagem())->size(2048);

        $this->post('/clientes', $this->dados(['foto' => $foto]))->assertCreated();
    }

    public function test_rejeita_arquivo_disfarcado_de_imagem_e_formato_gif(): void
    {
        $arquivoFalso = UploadedFile::fake()->createWithContent('foto.png', '<?php echo "invalido";');
        // UploadedFile real verifica o conteúdo; o fake deduz o MIME pelo nome.
        $fotoFalsa = new UploadedFile($arquivoFalso->path(), 'foto.png', 'image/png', null, true);
        $gif = UploadedFile::fake()->createWithContent('foto.gif', base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'));

        foreach ([$fotoFalsa, $gif] as $foto) {
            $this->post('/clientes', $this->dados(['foto' => $foto]))
                ->assertUnprocessable()->assertJsonValidationErrors('foto');
        }
    }

    public function test_remove_foto_nova_e_desfaz_cadastro_se_banco_falhar(): void
    {
        Cliente::created(fn () => throw new RuntimeException('Detalhe interno confidencial'));

        $this->post('/clientes', $this->dados())->assertStatus(500)
            ->assertExactJson(['message' => 'Não foi possível cadastrar o cliente.']);

        $this->assertDatabaseCount('clientes', 0);
        $this->assertSame([], Storage::disk('public')->allFiles('clientes'));
    }

    public function test_remove_foto_nova_e_preserva_antiga_se_atualizacao_falhar(): void
    {
        $cliente = $this->criarCliente();
        Cliente::updated(fn () => throw new RuntimeException('Detalhe interno confidencial'));

        $this->put('/clientes/'.$cliente->id, $this->dados(['email' => $cliente->email]))
            ->assertStatus(500)->assertExactJson(['message' => 'Não foi possível atualizar o cliente.']);

        $this->assertDatabaseHas('clientes', ['id' => $cliente->id, 'nome' => $cliente->nome, 'foto' => $cliente->foto]);
        Storage::disk('public')->assertExists($cliente->foto);
        $this->assertCount(1, Storage::disk('public')->allFiles('clientes'));
    }

    public function test_falha_ao_excluir_retorna_mensagem_generica(): void
    {
        $cliente = $this->criarCliente();
        Cliente::deleting(fn () => throw new RuntimeException('Detalhe interno confidencial'));

        $this->delete('/clientes/'.$cliente->id)->assertStatus(500)
            ->assertExactJson(['message' => 'Não foi possível excluir o cliente.']);
        $this->assertNotSoftDeleted($cliente);
        Storage::disk('public')->assertExists($cliente->foto);
    }

    public function test_clientes_excluidos_e_inexistentes_retornam_404_em_json(): void
    {
        $cliente = $this->criarCliente();
        $cliente->delete();

        foreach ([$cliente->id, 99999] as $id) {
            $this->get('/clientes/'.$id)->assertNotFound()
                ->assertExactJson(['message' => 'Cliente não encontrado.']);
            $this->put('/clientes/'.$id, $this->dados())->assertNotFound();
            $this->delete('/clientes/'.$id)->assertNotFound();
        }
    }

    public function test_excecao_antes_do_controller_nao_expoe_detalhes_internos(): void
    {
        $this->app['config']->set('app.debug', true);
        $this->app['router']->bind('cliente', fn () => throw new RuntimeException('Detalhe interno confidencial'));

        $this->get('/clientes/1')->assertStatus(500)
            ->assertExactJson(['message' => 'Não foi possível processar a solicitação.']);
    }

    public function test_lista_apenas_ativos_em_paginas_de_dez_preservando_parametros(): void
    {
        for ($indice = 0; $indice < 12; $indice++) {
            $this->criarCliente(['nome' => sprintf('Cliente %02d', $indice)]);
        }

        $this->criarCliente()->delete();
        $this->get('/clientes?ordenar=nome&direcao=asc')->assertOk()
            ->assertViewIs('clientes.index')->assertViewHas('clientes', function ($clientes) {
                return $clientes->total() === 12 && $clientes->count() === 10
                    && str_contains($clientes->nextPageUrl(), 'ordenar=nome');
            });
        $this->get('/clientes?page=2')->assertOk()->assertViewHas('clientes', function ($clientes) {
            return $clientes->count() === 2 && $clientes->first()->nome === 'Cliente 10';
        });
    }

    public function test_busca_por_nome_email_e_telefone_sem_incluir_excluidos(): void
    {
        $cliente = $this->criarCliente(['nome' => 'Maria Silva', 'email' => 'maria@example.com', 'telefone' => '(11) 98765-4321']);
        $this->criarCliente(['nome' => 'Outra pessoa']);
        $this->criarCliente(['nome' => $cliente->nome, 'email' => $cliente->email, 'telefone' => $cliente->telefone])->delete();

        foreach (['maria', 'maria@example.com', '98765'] as $busca) {
            $this->get('/clientes?'.http_build_query(['busca' => $busca]))->assertOk()
                ->assertViewHas('clientes', fn ($clientes) => $clientes->pluck('id')->all() === [$cliente->id]);
        }
    }

    public function test_ordena_apenas_pelas_colunas_permitidas_nas_duas_direcoes(): void
    {
        $primeiro = $this->criarCliente(['nome' => 'Ana', 'email' => 'zelia@example.com', 'telefone' => '2133334444']);
        $segundo = $this->criarCliente(['nome' => 'Bruna', 'email' => 'ana@example.com', 'telefone' => '31999998888']);
        $terceiro = $this->criarCliente(['nome' => 'Zelia', 'email' => 'bruna@example.com', 'telefone' => '1133334444']);

        $ordens = [
            'nome' => [$primeiro->id, $segundo->id, $terceiro->id],
            'email' => [$segundo->id, $terceiro->id, $primeiro->id],
            'telefone' => [$terceiro->id, $primeiro->id, $segundo->id],
        ];

        foreach ($ordens as $coluna => $ids) {
            foreach (['asc', 'desc'] as $direcao) {
                $esperados = $direcao === 'asc' ? $ids : array_reverse($ids);
                $this->get('/clientes?'.http_build_query(['ordenar' => $coluna, 'direcao' => $direcao]))
                    ->assertOk()->assertViewHas('clientes', fn ($clientes) => $clientes->pluck('id')->all() === $esperados);
            }
        }

        $this->get('/clientes?ordenar=deleted_at')->assertUnprocessable()->assertJsonValidationErrors('ordenar');
        $this->get('/clientes?direcao=invalida')->assertUnprocessable()->assertJsonValidationErrors('direcao');
        $this->get('/clientes?ordenar[]=nome')->assertUnprocessable()->assertJsonValidationErrors('ordenar');
    }

    public function test_rotas_preservam_protecao_csrf(): void
    {
        // O Laravel ignora CSRF no ambiente testing; habilitamos a verificação neste teste.
        $this->app->instance('env', 'local');

        $this->post('/clientes', $this->dados())->assertStatus(419);
        $this->assertDatabaseCount('clientes', 0);

        $token = 'token-de-teste';
        $this->withSession(['_token' => $token])
            ->post('/clientes', $this->dados(['_token' => $token]))->assertCreated();
    }

    private function criarCliente(array $dados = []): Cliente
    {
        $this->sequencia++;
        $cliente = Cliente::create(array_replace([
            'nome' => 'Cliente '.$this->sequencia,
            'email' => 'cliente'.$this->sequencia.'@example.com',
            'telefone' => '(11) 99999-9999',
            'foto' => 'clientes/antiga-'.$this->sequencia.'.png',
        ], $dados));
        Storage::disk('public')->put($cliente->foto, $this->imagem());

        return $cliente;
    }

    private function dados(array $dados = []): array
    {
        return array_replace([
            'nome' => 'Maria Silva',
            'email' => 'maria@example.com',
            'telefone' => '(11) 99999-9999',
            'foto' => UploadedFile::fake()->createWithContent('foto.png', $this->imagem()),
        ], $dados);
    }

    private function imagem(): string
    {
        // PNG real de 1x1 pixel, sem depender da extensão GD para executar os testes.
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=');
    }
}
