<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fe_declaraciones', function (Blueprint $table) {
            $table->increments('id_declaracion');
            $table->unsignedInteger('tenant_id');
            $table->unsignedInteger('id_empresa');
            $table->string('tipo_declaracion', 10);
            $table->integer('periodo_anio');
            $table->integer('periodo_mes')->nullable();
            $table->string('estado', 20)->default('borrador');
            $table->decimal('total_ventas_gravadas', 15, 2)->default(0);
            $table->decimal('total_ventas_exentas', 15, 2)->default(0);
            $table->decimal('total_compras_gravadas', 15, 2)->default(0);
            $table->decimal('total_compras_exentas', 15, 2)->default(0);
            $table->decimal('total_iva_trasladado', 15, 2)->default(0);
            $table->decimal('total_iva_acreditable', 15, 2)->default(0);
            $table->decimal('impuesto_neto', 15, 2)->default(0);
            $table->json('detalle_actividades')->nullable();
            $table->json('detalle_tarifas')->nullable();
            $table->json('datos_calculados')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'id_empresa']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fe_declaraciones');
    }
};
