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
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_factura')
                ->constrained('facturas')
                ->restrictOnDelete();

            $table->string('referencia', 50)->unique();

            $table->enum('metodo_pago', [
                'efectivo',
                'tarjeta'
            ]);

            $table->decimal('monto', 10, 2);
            $table->decimal('monto_recibido', 10, 2)->nullable();
            $table->decimal('cambio', 10, 2)->default(0);

            $table->foreignId('id_tarjeta_prueba')
                ->nullable()
                ->constrained('tarjetas_prueba')
                ->nullOnDelete();

            $table->string('ultimos_digitos', 4)->nullable();

            $table->enum('estado', [
                'aprobado',
                'rechazado'
            ])->default('aprobado');

            $table->string('motivo_rechazo', 250)->nullable();
            $table->dateTime('fecha_pago')->useCurrent();

            $table->foreignId('registrado_por')
                ->nullable()
                ->constrained('usuarios')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
