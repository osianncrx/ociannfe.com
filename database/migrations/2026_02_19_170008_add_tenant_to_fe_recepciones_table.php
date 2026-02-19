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
            $table->unsignedBigInteger('tenant_id')->nullable()->after('id_recepcion');

            $table->index('tenant_id');
            $table->index(['tenant_id', 'id_empresa']);
        });
    }

    public function down(): void
    {
        Schema::table('fe_recepciones', function (Blueprint $table) {
            $table->dropIndex(['tenant_id']);
            $table->dropIndex(['tenant_id', 'id_empresa']);
            $table->dropColumn('tenant_id');
        });
    }
};
