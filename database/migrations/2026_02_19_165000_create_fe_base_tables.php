<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fe_ambientes')) {
            Schema::create('fe_ambientes', function (Blueprint $table) {
                $table->integer('id_ambiente')->primary();
                $table->string('nombre', 25);
                $table->string('client_id', 55);
                $table->string('uri_idp', 255);
                $table->string('uri_api', 255);
            });

            DB::table('fe_ambientes')->insert([
                ['id_ambiente' => 1, 'nombre' => 'Staging/Sandbox', 'client_id' => 'api-stag', 'uri_idp' => 'https://idp.comprobanteselectronicos.go.cr/auth/realms/rut-stag/protocol/openid-connect/token', 'uri_api' => 'https://api-sandbox.comprobanteselectronicos.go.cr/recepcion/v1/'],
                ['id_ambiente' => 2, 'nombre' => 'Producción', 'client_id' => 'api-prod', 'uri_idp' => 'https://idp.comprobanteselectronicos.go.cr/auth/realms/rut/protocol/openid-connect/token', 'uri_api' => 'https://api.comprobanteselectronicos.go.cr/recepcion/v1/'],
            ]);
        }

        if (!Schema::hasTable('fe_empresas')) {
            Schema::create('fe_empresas', function (Blueprint $table) {
                $table->increments('id_empresa');
                $table->string('id_cliente', 64)->nullable();
                $table->integer('id_ambiente')->default(1);
                $table->string('cedula', 12)->nullable();
                $table->string('sucursal', 3)->default('001');
                $table->text('usuario_mh')->nullable();
                $table->text('contra_mh')->nullable();
                $table->text('pin_llave')->nullable();
                $table->longText('llave_criptografica')->nullable();
                $table->string('Nombre', 255)->nullable();
                $table->string('Tipo', 2)->nullable();
                $table->string('Numero', 12)->nullable();
                $table->string('NombreComercial', 255)->nullable();
                $table->string('Provincia', 2)->nullable();
                $table->string('Canton', 3)->nullable();
                $table->string('Distrito', 3)->nullable();
                $table->string('Barrio', 3)->nullable();
                $table->string('OtrasSenas', 255)->nullable();
                $table->string('CorreoElectronico', 255)->nullable();
                $table->string('CodigoPais', 5)->nullable();
                $table->string('NumTelefono', 20)->nullable();
                $table->string('CodigoActividad', 255)->nullable();
            });
        }

        if (!Schema::hasTable('fe_emisiones')) {
            Schema::create('fe_emisiones', function (Blueprint $table) {
                $table->increments('id_emision');
                $table->decimal('clave', 50, 0)->nullable()->default(0);
                $table->string('CodigoActividad', 50)->nullable();
                $table->string('NumeroConsecutivo', 50)->nullable();
                $table->dateTime('FechaEmision')->nullable();
                $table->string('Emisor_Nombre', 255)->nullable();
                $table->string('Emisor_TipoIdentificacion', 10)->nullable();
                $table->string('Emisor_NumeroIdentificacion', 50)->nullable();
                $table->string('Emisor_Provincia', 10)->nullable();
                $table->string('Emisor_Canton', 10)->nullable();
                $table->string('Emisor_Distrito', 10)->nullable();
                $table->string('Emisor_OtrasSenas', 255)->nullable();
                $table->string('Emisor_CorreoElectronico', 255)->nullable();
                $table->string('Receptor_Nombre', 255)->nullable();
                $table->string('Receptor_TipoIdentificacion', 10)->nullable();
                $table->string('Receptor_NumeroIdentificacion', 50)->nullable();
                $table->string('Receptor_Provincia', 10)->nullable();
                $table->string('Receptor_Canton', 10)->nullable();
                $table->string('Receptor_Distrito', 10)->nullable();
                $table->string('Receptor_OtrasSenas', 255)->nullable();
                $table->string('Receptor_CorreoElectronico', 255)->nullable();
                $table->string('CondicionVenta', 10)->nullable();
                $table->string('MedioPago', 10)->nullable();
                $table->decimal('TotalServGravados', 15, 2)->default(0);
                $table->decimal('TotalMercanciasExentas', 15, 2)->default(0);
                $table->decimal('TotalGravado', 15, 2)->default(0);
                $table->decimal('TotalExento', 15, 2)->default(0);
                $table->decimal('TotalVenta', 15, 2)->default(0);
                $table->decimal('TotalDescuentos', 15, 2)->default(0);
                $table->decimal('TotalVentaNeta', 15, 2)->default(0);
                $table->decimal('TotalImpuesto', 15, 2)->default(0);
                $table->decimal('TotalComprobante', 15, 2)->default(0);
                $table->timestamp('FechaCreacion')->nullable()->useCurrent();
                $table->timestamp('FechaActualizacion')->nullable()->useCurrentOnUpdate();
                $table->integer('id_empresa');
                $table->tinyInteger('estado')->nullable();
                $table->longText('mensaje')->nullable();
                $table->longText('xml_comprobante')->nullable();
            });
        }

        if (!Schema::hasTable('fe_emision_lineas')) {
            Schema::create('fe_emision_lineas', function (Blueprint $table) {
                $table->increments('id_linea');
                $table->integer('id_emision');
                $table->integer('NumeroLinea')->default(0);
                $table->string('Codigo', 255)->nullable();
                $table->text('CodigoComercial')->nullable();
                $table->decimal('Cantidad', 15, 4)->default(0);
                $table->string('UnidadMedida', 20)->nullable();
                $table->string('Detalle', 255)->nullable();
                $table->decimal('PrecioUnitario', 15, 4)->default(0);
                $table->decimal('MontoTotal', 15, 2)->default(0);
                $table->decimal('Descuento_MontoDescuento', 15, 2)->nullable();
                $table->string('Descuento_NaturalezaDescuento', 255)->nullable();
                $table->decimal('SubTotal', 15, 2)->default(0);
                $table->string('Impuesto_Codigo', 10)->nullable();
                $table->string('Impuesto_CodigoTarifa', 10)->nullable();
                $table->decimal('Impuesto_Tarifa', 5, 2)->nullable();
                $table->decimal('Impuesto_Monto', 15, 2)->nullable();
                $table->decimal('MontoTotalLinea', 15, 2)->default(0);
            });
        }

        if (!Schema::hasTable('fe_recepciones')) {
            Schema::create('fe_recepciones', function (Blueprint $table) {
                $table->increments('id_recepcion');
                $table->decimal('clave', 50, 0)->nullable();
                $table->integer('id_empresa');
                $table->tinyInteger('estado')->nullable();
                $table->longText('mensaje')->nullable();
                $table->string('NumeroConsecutivo', 50)->nullable();
            });
        }

        if (!Schema::hasTable('fe_cola')) {
            Schema::create('fe_cola', function (Blueprint $table) {
                $table->integer('id_empresa');
                $table->decimal('clave', 50, 0);
                $table->tinyInteger('accion');
                $table->integer('tiempo_creado');
                $table->integer('tiempo_enviar')->default(0);
                $table->tinyInteger('intentos_envio')->nullable()->default(0);
                $table->string('respuesta_envio', 31)->nullable()->default('0');
                $table->longText('mensaje')->nullable();
                $table->primary('clave');
            });
        }

        if (!Schema::hasTable('fe_monolog')) {
            Schema::create('fe_monolog', function (Blueprint $table) {
                $table->id();
                $table->string('channel', 255)->nullable();
                $table->integer('level')->nullable();
                $table->text('message')->nullable();
                $table->integer('time')->nullable();
            });
        }

        if (!Schema::hasTable('fe_ratelimiting')) {
            Schema::create('fe_ratelimiting', function (Blueprint $table) {
                $table->string('id_rate', 100)->primary();
                $table->integer('tokens')->default(0);
                $table->integer('time_stamp')->default(0);
            });
        }

        if (!Schema::hasTable('fe_settings')) {
            Schema::create('fe_settings', function (Blueprint $table) {
                $table->string('name', 100)->primary();
                $table->text('value')->nullable();
            });
        }

        if (!Schema::hasTable('fe_tokens')) {
            Schema::create('fe_tokens', function (Blueprint $table) {
                $table->string('cedula', 15);
                $table->integer('id_ambiente');
                $table->longText('access_token')->nullable();
                $table->integer('expires_in')->nullable();
                $table->longText('refresh_token')->nullable();
                $table->integer('refresh_expires_in')->nullable();
                $table->primary(['cedula', 'id_ambiente']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fe_tokens');
        Schema::dropIfExists('fe_settings');
        Schema::dropIfExists('fe_ratelimiting');
        Schema::dropIfExists('fe_monolog');
        Schema::dropIfExists('fe_cola');
        Schema::dropIfExists('fe_recepciones');
        Schema::dropIfExists('fe_emision_lineas');
        Schema::dropIfExists('fe_emisiones');
        Schema::dropIfExists('fe_empresas');
        Schema::dropIfExists('fe_ambientes');
    }
};
