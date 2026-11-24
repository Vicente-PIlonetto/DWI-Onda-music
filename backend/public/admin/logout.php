<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
use Onda\Admin;
Admin::guard();
if ($_SERVER['REQUEST_METHOD']!=='POST') {http_response_code(405);header('Allow: POST');exit;}
try { Admin::csrf(); } catch(RuntimeException $e) {http_response_code(403);echo h($e->getMessage());exit;}
$_SESSION=[];session_destroy();
setcookie(session_name(),'', ['expires'=>time()-3600,'path'=>'/','secure'=>getenv('SESSION_SECURE')==='1','httponly'=>true,'samesite'=>'Lax']);
header('Location: /admin/login.php',true,303);exit;
