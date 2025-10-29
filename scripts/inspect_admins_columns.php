<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$rows = DB::select("SELECT column_name, data_type, udt_name FROM information_schema.columns WHERE table_name = 'admins'");
foreach ($rows as $r) {
    echo "{$r->column_name}\t{$r->data_type}\t{$r->udt_name}\n";
}
