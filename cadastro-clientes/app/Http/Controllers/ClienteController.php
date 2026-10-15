<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClienteRequest;
use App\Models\Cliente;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use RuntimeException;
use Throwable;

class ClienteController extends Controller
{
    public function list(Request $request): View
    {
        $parametros = $request->validate([
            'busca' => ['nullable', 'string', 'max:255'],
            'ordenar' => ['sometimes', Rule::in(['nome', 'email', 'telefone'])],
            'direcao' => ['sometimes', Rule::in(['asc', 'desc'])],
            'page' => ['sometimes', 'integer', 'min:1'],
        ], [
            'busca.string' => 'A busca deve ser um texto.',
            'busca.max' => 'A busca deve ter no máximo 255 caracteres.',
            'ordenar.in' => 'Ordene por nome, e-mail ou telefone.',
            'direcao.in' => 'A direção deve ser asc ou desc.',
            'page.integer' => 'A página deve ser um número inteiro.',
            'page.min' => 'A página deve ser maior ou igual a 1.',
        ]);

        $consulta = Cliente::query();
        $busca = $parametros['busca'] ?? '';

        if ($busca !== '') {
            $consulta->where(function ($consulta) use ($busca) {
                $consulta->where('nome', 'like', '%'.$busca.'%')
                    ->orWhere('email', 'like', '%'.$busca.'%')
                    ->orWhere('telefone', 'like', '%'.$busca.'%');
            });
        }

        $clientes = $consulta->orderBy($parametros['ordenar'] ?? 'nome', $parametros['direcao'] ?? 'asc')
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();

        return view($request->ajax() ? 'clientes.listagem' : 'clientes.index', compact('clientes'));
    }

    public function create(ClienteRequest $request): JsonResponse
    {
        $foto = null;

        try {
            $dados = $request->safe()->except('foto');
            $foto = $this->armazenarFoto($request->file('foto'));
            $dados['foto'] = $foto;
            $cliente = DB::transaction(fn () => Cliente::create($dados));
        } catch (Throwable $excecao) {
            if ($foto !== null) {
                $this->removerFoto($foto);
            }

            report($excecao);

            return response()->json(['message' => 'Não foi possível cadastrar o cliente.'], 500);
        }

        return response()->json($cliente, 201);
    }

    public function show(Cliente $cliente): JsonResponse
    {
        return response()->json($cliente);
    }

    public function update(ClienteRequest $request, Cliente $cliente): JsonResponse
    {
        $foto = null;
        $fotoAntiga = $cliente->foto;

        try {
            $dados = $request->safe()->except('foto');

            if ($request->hasFile('foto')) {
                $foto = $this->armazenarFoto($request->file('foto'));
                $dados['foto'] = $foto;
            }

            DB::transaction(fn () => $cliente->update($dados));
        } catch (Throwable $excecao) {
            if ($foto !== null) {
                $this->removerFoto($foto);
            }

            report($excecao);

            return response()->json(['message' => 'Não foi possível atualizar o cliente.'], 500);
        }

        if ($foto !== null) {
            $this->removerFoto($fotoAntiga);
        }

        return response()->json($cliente);
    }

    public function delete(Cliente $cliente): JsonResponse
    {
        try {
            $cliente->delete();
        } catch (Throwable $excecao) {
            report($excecao);

            return response()->json(['message' => 'Não foi possível excluir o cliente.'], 500);
        }

        return response()->json(['message' => 'Cliente excluído com sucesso.']);
    }

    private function armazenarFoto(UploadedFile $foto): string
    {
        $caminho = $foto->store('clientes', 'public');

        if ($caminho === false) {
            throw new RuntimeException('Falha ao armazenar a foto do cliente.');
        }

        return $caminho;
    }

    private function removerFoto(string $caminho): void
    {
        try {
            if (! Storage::disk('public')->delete($caminho)) {
                throw new RuntimeException('Falha ao remover a foto do cliente.');
            }
        } catch (Throwable $excecao) {
            report($excecao);
        }
    }
}
