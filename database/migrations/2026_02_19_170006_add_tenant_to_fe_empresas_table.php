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
            $table->unsignedBigInteger('tenant_id')->nullable()->after('id_empresa');

            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::table('fe_empresas', function (Blueprint $table) {
            $table->dropIndex(['tenant_id']);
            $table->dropColumn('tenant_id');
        });
    }
};
