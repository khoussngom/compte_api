<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     * Convert various oauth_* id columns to text so they can store UUIDs.
     */
    public function up(): void
    {
        // Run safe ALTERs only if the columns exist (Postgres)
        DB::statement(<<<'SQL'
DO $$
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='oauth_access_tokens' AND column_name='id') THEN
        EXECUTE 'ALTER TABLE oauth_access_tokens ALTER COLUMN id TYPE text USING id::text';
    END IF;
    IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='oauth_access_tokens' AND column_name='user_id') THEN
        EXECUTE 'ALTER TABLE oauth_access_tokens ALTER COLUMN user_id TYPE text USING user_id::text';
    END IF;
    IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='oauth_access_tokens' AND column_name='client_id') THEN
        EXECUTE 'ALTER TABLE oauth_access_tokens ALTER COLUMN client_id TYPE text USING client_id::text';
    END IF;

    IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='oauth_refresh_tokens' AND column_name='id') THEN
        EXECUTE 'ALTER TABLE oauth_refresh_tokens ALTER COLUMN id TYPE text USING id::text';
    END IF;
    IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='oauth_refresh_tokens' AND column_name='access_token_id') THEN
        EXECUTE 'ALTER TABLE oauth_refresh_tokens ALTER COLUMN access_token_id TYPE text USING access_token_id::text';
    END IF;

    IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='oauth_clients' AND column_name='id') THEN
        EXECUTE 'ALTER TABLE oauth_clients ALTER COLUMN id TYPE text USING id::text';
    END IF;
    -- oauth_clients uses nullableMorphs('owner') -> owner_id
    IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='oauth_clients' AND column_name='owner_id') THEN
        EXECUTE 'ALTER TABLE oauth_clients ALTER COLUMN owner_id TYPE text USING owner_id::text';
    END IF;

    IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='oauth_auth_codes' AND column_name='id') THEN
        EXECUTE 'ALTER TABLE oauth_auth_codes ALTER COLUMN id TYPE text USING id::text';
    END IF;
    IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='oauth_auth_codes' AND column_name='user_id') THEN
        EXECUTE 'ALTER TABLE oauth_auth_codes ALTER COLUMN user_id TYPE text USING user_id::text';
    END IF;
    IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='oauth_auth_codes' AND column_name='client_id') THEN
        EXECUTE 'ALTER TABLE oauth_auth_codes ALTER COLUMN client_id TYPE text USING client_id::text';
    END IF;
END$$;
SQL
        );
    }

    /**
     * Reverse the migrations.
     * Attempt to revert columns back to bigint where it makes sense — if values are numeric.
     */
    public function down(): void
    {
        // Best-effort reversible operations. If the columns contain non-numeric values (UUIDs) this will fail.
        try {
            DB::statement("ALTER TABLE oauth_access_tokens ALTER COLUMN user_id TYPE bigint USING NULLIF(user_id, '')::bigint");
        } catch (\Throwable $e) {
            // skip — cannot safely convert back
        }
    }
};
