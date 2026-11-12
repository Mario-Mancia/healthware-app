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
        Schema::create('facturas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_paciente')
                ->constrained('pacientes')
                ->restrictOnDelete();

            $table->foreignId('id_orden')
                ->constrained('ordenes')
                ->restrictOnDelete();

            $table->string('numero_factura', 50)->unique();
            $table->dateTime('fecha_emision')->useCurrent();

            $table->enum('estado', [
                'pendiente',
                'pago_parcial',
                'pagada',
                'cancelada'
            ])->default('pendiente');

            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('impuesto', 10, 2)->default(0);
            $table->decimal('descuento', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);

            $table->string('notas')->nullable();
            $table->string('motivo_anulacion', 250)->nullable();

            $table->foreignId('emitida_por')
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
        Schema::dropIfExists('facturas');
    }
};
