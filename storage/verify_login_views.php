<?php
$root = dirname(__DIR__);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$res = $kernel->handle(Illuminate\Http\Request::create('/nike/login', 'GET'));
$body = (string) $res->getContent();
echo 'status='.$res->getStatusCode().PHP_EOL;
echo 'custom='.(str_contains($body,'Tenant Sign in') ? 'yes' : 'no').PHP_EOL;
echo 'has_csrf='.(str_contains($body,'name="_token"') ? 'yes' : 'no').PHP_EOL;
$res2 = $kernel->handle(Illuminate\Http\Request::create('/super-admin/login', 'GET'));
$body2 = (string) $res2->getContent();
echo 'super_custom='.(str_contains($body2,'Super Admin Sign in') ? 'yes' : 'no').PHP_EOL;
