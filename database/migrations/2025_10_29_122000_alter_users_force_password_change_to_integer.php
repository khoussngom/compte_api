<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Make the conversion robust: drop default, alter type using CASE, then set default to 0
        DB::statement(<<<'SQL'
DO $$
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='users' AND column_name='force_password_change') THEN
        -- drop default if any
        BEGIN
            EXECUTE 'ALTER TABLE users ALTER COLUMN force_password_change DROP DEFAULT';
        EXCEPTION WHEN others THEN
            -- ignore
        END;

        EXECUTE 'ALTER TABLE users ALTER COLUMN force_password_change TYPE integer USING (CASE WHEN force_password_change THEN 1 ELSE 0 END)';
        EXECUTE 'ALTER TABLE users ALTER COLUMN force_password_change SET DEFAULT 0';
    END IF;
END$$;
SQL
        );
    }

    public function down(): void
    {
        try {
            DB::statement("ALTER TABLE users ALTER COLUMN force_password_change TYPE boolean USING (force_password_change <> 0);");
        } catch (\Throwable $e) {
            // noop
        }
    }
};
