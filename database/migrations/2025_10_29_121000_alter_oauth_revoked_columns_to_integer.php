<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Convert boolean revoked columns to integer (Passport inserts 0/1)
        if (DB::getPdo()) {
            // oauth_access_tokens
            DB::statement("ALTER TABLE oauth_access_tokens ALTER COLUMN revoked TYPE integer USING (CASE WHEN revoked THEN 1 ELSE 0 END);");

            // oauth_refresh_tokens
            DB::statement("ALTER TABLE oauth_refresh_tokens ALTER COLUMN revoked TYPE integer USING (CASE WHEN revoked THEN 1 ELSE 0 END);");

            // oauth_auth_codes
            DB::statement("ALTER TABLE oauth_auth_codes ALTER COLUMN revoked TYPE integer USING (CASE WHEN revoked THEN 1 ELSE 0 END);");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            DB::statement("ALTER TABLE oauth_access_tokens ALTER COLUMN revoked TYPE boolean USING (revoked <> 0);");
            DB::statement("ALTER TABLE oauth_refresh_tokens ALTER COLUMN revoked TYPE boolean USING (revoked <> 0);");
            DB::statement("ALTER TABLE oauth_auth_codes ALTER COLUMN revoked TYPE boolean USING (revoked <> 0);");
        } catch (\Throwable $e) {
            // noop
        }
    }
};
