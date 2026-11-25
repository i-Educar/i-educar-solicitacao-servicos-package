<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE SCHEMA IF NOT EXISTS solicitacao_servicos');
        DB::statement("CREATE TABLE IF NOT EXISTS solicitacao_servicos.poc_registro (
            id bigserial PRIMARY KEY,
            tipo varchar(40) NOT NULL,
            titulo varchar(180) NOT NULL,
            meta varchar(180) NULL,
            payload jsonb NULL,
            created_at timestamp without time zone DEFAULT NOW()
        )");
    }

    public function down(): void
    {
        DB::statement('DROP SCHEMA IF EXISTS solicitacao_servicos CASCADE');
    }
};
