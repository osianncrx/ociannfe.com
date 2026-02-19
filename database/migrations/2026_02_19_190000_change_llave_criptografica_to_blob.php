<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        \Illuminate\Support\Facades\DB::statement(
            'ALTER TABLE fe_empresas MODIFY llave_criptografica LONGBLOB NULL'
        );
    }

    public function down(): void
    {
        \Illuminate\Support\Facades\DB::statement(
            'ALTER TABLE fe_empresas MODIFY llave_criptografica LONGTEXT NULL'
        );
    }
};
