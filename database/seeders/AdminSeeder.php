<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Create or update the admin user
        $email = 'admin@gmail.com';
        $password = 'admin';

        $user = User::where('email', $email)->first();

        if (! $user) {
            $user = User::create([
                'id' => (string) Str::uuid(),
                'nom' => 'Admin',
                'prenom' => 'System',
                'email' => $email,
                'telephone' => null,
                'password' => Hash::make($password),
            ]);
            $this->command->info("Created user: {$user->id}");
        } else {
            // ensure password is set to requested value (useful for dev)
            $user->password = Hash::make($password);
            $user->save();
            $this->command->info("Updated existing user: {$user->id}");
        }

        // Create or update admin record linked to this user.
        // We must be tolerant to the table schema (id may be bigint or uuid; fonction may be missing).
        $adminRow = \Illuminate\Support\Facades\DB::table('admins')->where('user_id', $user->id)->first();

        // detect whether admins.id is uuid to decide whether to provide an id value
        $col = \Illuminate\Support\Facades\DB::select("SELECT udt_name FROM information_schema.columns WHERE table_name = 'admins' AND column_name = 'id'");
        $idIsUuid = false;
        if (! empty($col) && isset($col[0]->udt_name) && $col[0]->udt_name === 'uuid') {
            $idIsUuid = true;
        }

        $adminData = [
            'user_id' => $user->id,
        ];
        if (Schema::hasColumn('admins', 'fonction')) {
            $adminData['fonction'] = 'super-admin';
        }

        if (! $adminRow) {
            if ($idIsUuid) {
                $adminData['id'] = (string) Str::uuid();
                $admin = Admin::create($adminData);
                $this->command->info("Created admin record: {$admin->id}");
            } else {
                $newId = \Illuminate\Support\Facades\DB::table('admins')->insertGetId($adminData);
                $this->command->info("Created admin record id: {$newId}");
            }
        } else {
            // update existing row
            \Illuminate\Support\Facades\DB::table('admins')->where('user_id', $user->id)->update($adminData + ['updated_at' => now()]);
            $this->command->info("Updated admin record for user: {$user->id}");
        }

        $this->command->info("Admin ready. email={$email} password={$password}");
    }
}
