<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Cast boolean revoked to integer (0/1) to be tolerant with inserts that use 0/1 literals
        DB::statement("ALTER TABLE oauth_clients ALTER COLUMN revoked TYPE integer USING (CASE WHEN revoked THEN 1 ELSE 0 END);");
    }

    public function down(): void
    {
        // Cast back to boolean
        DB::statement("ALTER TABLE oauth_clients ALTER COLUMN revoked TYPE boolean USING (revoked <> 0);");
    }
};
