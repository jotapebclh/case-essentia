<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 150);
            $table->string('email', 255);
            $table->string('telefone', 20);
            $table->string('foto', 255);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['deleted_at', 'nome']);
            $table->index(['deleted_at', 'email']);
            $table->index(['deleted_at', 'telefone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
