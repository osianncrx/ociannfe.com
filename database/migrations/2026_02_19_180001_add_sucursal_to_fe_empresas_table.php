<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('fe_empresas', 'sucursal')) {
            Schema::table('fe_empresas', function (Blueprint $table) {
                $table->unique(['tenant_id', 'cedula', 'id_ambiente', 'sucursal'], 'uq_empresa_sucursal');
            });
            return;
        }

        Schema::table('fe_empresas', function (Blueprint $table) {
            $table->string('sucursal', 3)->default('001')->after('cedula');
            $table->unique(['tenant_id', 'cedula', 'id_ambiente', 'sucursal'], 'uq_empresa_sucursal');
        });
    }

    public function down(): void
    {
        Schema::table('fe_empresas', function (Blueprint $table) {
            $table->dropUnique('uq_empresa_sucursal');
            $table->dropColumn('sucursal');
        });
    }
};
