<?php
$path = __DIR__ . '/app/Http/Controllers/EquiposBajaController.php';
$c = file_get_contents($path);
$c = preg_replace('/^\xEF\xBB\xBF/', '', $c);
$c = preg_replace('/^\s+<\?php/', '<?php', $c);
file_put_contents($path, $c);
echo substr($c, 0, 40) . PHP_EOL;
echo 'ok' . PHP_EOL;
