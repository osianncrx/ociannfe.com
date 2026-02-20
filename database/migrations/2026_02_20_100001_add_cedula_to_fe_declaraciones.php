<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fe_declaraciones', function (Blueprint $table) {
            $table->string('cedula', 50)->nullable()->after('id_empresa');
            $table->unsignedInteger('id_empresa')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('fe_declaraciones', function (Blueprint $table) {
            $table->dropColumn('cedula');
        });
    }
};
