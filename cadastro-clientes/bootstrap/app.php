<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware()
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (Throwable $excecao, Request $request) {
            if (! $request->is('clientes', 'clientes/*')) {
                return null;
            }

            if ($excecao instanceof ValidationException) {
                return response()->json([
                    'message' => 'Os dados informados são inválidos.',
                    'errors' => $excecao->errors(),
                ], $excecao->status);
            }

            $status = $excecao instanceof HttpExceptionInterface ? $excecao->getStatusCode() : 500;
            $mensagem = match ($status) {
                404 => 'Cliente não encontrado.',
                419 => 'A sessão expirou. Atualize a página e tente novamente.',
                default => 'Não foi possível processar a solicitação.',
            };
            $cabecalhos = $excecao instanceof HttpExceptionInterface ? $excecao->getHeaders() : [];

            return response()->json(['message' => $mensagem], $status, $cabecalhos);
        });
    })->create();
