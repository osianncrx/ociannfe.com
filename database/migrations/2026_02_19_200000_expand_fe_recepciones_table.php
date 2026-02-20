<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fe_recepciones', function (Blueprint $table) {
            if (!Schema::hasColumn('fe_recepciones', 'NumeroConsecutivo')) {
                $table->string('NumeroConsecutivo', 50)->nullable()->after('mensaje');
            }
            $table->string('TipoDocumento', 2)->nullable()->after('mensaje');
            $table->dateTime('FechaEmision')->nullable()->after('TipoDocumento');
            $table->string('Emisor_Nombre', 255)->nullable()->after('FechaEmision');
            $table->string('Emisor_TipoIdentificacion', 10)->nullable()->after('Emisor_Nombre');
            $table->string('Emisor_NumeroIdentificacion', 50)->nullable()->after('Emisor_TipoIdentificacion');
            $table->string('Emisor_CorreoElectronico', 255)->nullable()->after('Emisor_NumeroIdentificacion');
            $table->string('Receptor_Nombre', 255)->nullable()->after('Emisor_CorreoElectronico');
            $table->string('Receptor_TipoIdentificacion', 10)->nullable()->after('Receptor_Nombre');
            $table->string('Receptor_NumeroIdentificacion', 50)->nullable()->after('Receptor_TipoIdentificacion');
            $table->decimal('TotalComprobante', 15, 2)->default(0)->after('Receptor_NumeroIdentificacion');
            $table->decimal('TotalImpuesto', 15, 2)->default(0)->after('TotalComprobante');
            $table->string('CodigoMoneda', 3)->default('CRC')->after('TotalImpuesto');
            $table->longText('xml_original')->nullable()->after('CodigoMoneda');
            $table->string('respuesta_tipo', 2)->nullable()->after('xml_original');
            $table->string('respuesta_consecutivo', 50)->nullable()->after('respuesta_tipo');
            $table->tinyInteger('respuesta_estado')->nullable()->after('respuesta_consecutivo');
            $table->longText('respuesta_mensaje')->nullable()->after('respuesta_estado');
        });
    }

    public function down(): void
    {
        Schema::table('fe_recepciones', function (Blueprint $table) {
            $table->dropColumn([
                'TipoDocumento', 'FechaEmision',
                'Emisor_Nombre', 'Emisor_TipoIdentificacion', 'Emisor_NumeroIdentificacion', 'Emisor_CorreoElectronico',
                'Receptor_Nombre', 'Receptor_TipoIdentificacion', 'Receptor_NumeroIdentificacion',
                'TotalComprobante', 'TotalImpuesto', 'CodigoMoneda',
                'xml_original', 'respuesta_tipo', 'respuesta_consecutivo', 'respuesta_estado', 'respuesta_mensaje',
            ]);
        });
    }
};
