<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tarjetas_prueba', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 19)->unique();
            $table->string('titular', 100);

            $table->enum('tipo', [
                'credito',
                'debito'
            ]);

            $table->string('marca', 30);
            $table->unsignedTinyInteger('mes_vencimiento');
            $table->unsignedSmallInteger('anio_vencimiento');
            $table->string('cvv', 4);
            $table->decimal('saldo', 10, 2)->default(0);

            $table->enum('estado', [
                'activa',
                'bloqueada'
            ])->default('activa');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tarjetas_prueba');
    }
};
