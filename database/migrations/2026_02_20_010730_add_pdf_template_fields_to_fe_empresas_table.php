<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fe_empresas', function (Blueprint $table) {
            $table->longText('pdf_logo')->nullable();
            $table->text('pdf_encabezado')->nullable();
            $table->text('pdf_pie_pagina')->nullable();
            $table->string('pdf_color_primario', 7)->nullable()->default('#000000');
            $table->boolean('pdf_mostrar_comentarios')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('fe_empresas', function (Blueprint $table) {
            $table->dropColumn([
                'pdf_logo',
                'pdf_encabezado',
                'pdf_pie_pagina',
                'pdf_color_primario',
                'pdf_mostrar_comentarios',
            ]);
        });
    }
};
