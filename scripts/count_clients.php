<?php
$db = new PDO('sqlite:' . __DIR__ . '/../database/database.sqlite');
$stmt = $db->query('SELECT count(*) as c FROM clients');
$res = $stmt->fetch(PDO::FETCH_ASSOC);
echo 'clients: ' . ($res['c'] ?? '0') . PHP_EOL;
$rows = $db->query('SELECT id, user_id, nom, prenom, email, telephone, nci FROM clients LIMIT 10');
foreach ($rows as $r) {
    echo json_encode($r, JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
