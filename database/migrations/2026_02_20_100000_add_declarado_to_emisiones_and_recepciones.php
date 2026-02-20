<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fe_emisiones', function (Blueprint $table) {
            $table->tinyInteger('declarado')->default(0)->after('estado');
            $table->unsignedInteger('id_declaracion')->nullable()->after('declarado');
        });

        Schema::table('fe_recepciones', function (Blueprint $table) {
            $table->tinyInteger('declarado')->default(0)->after('estado');
            $table->unsignedInteger('id_declaracion')->nullable()->after('declarado');
        });
    }

    public function down(): void
    {
        Schema::table('fe_emisiones', function (Blueprint $table) {
            $table->dropColumn(['declarado', 'id_declaracion']);
        });

        Schema::table('fe_recepciones', function (Blueprint $table) {
            $table->dropColumn(['declarado', 'id_declaracion']);
        });
    }
};
