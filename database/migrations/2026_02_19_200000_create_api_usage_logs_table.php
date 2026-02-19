<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_key_id')->nullable()->constrained('api_keys')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('method', 10);
            $table->string('endpoint', 500);
            $table->integer('status_code')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->integer('response_time_ms')->nullable();
            $table->json('request_body')->nullable();
            $table->json('response_summary')->nullable();
            $table->timestamps();

            $table->index(['api_key_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['tenant_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_usage_logs');
    }
};
