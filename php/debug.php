<?php
declare(strict_types=1);

// Set arguments
$_GET['data'] = 
'{"email_current":"odry.attila@keri.mako.hu","email":"odry.attila@gmail.com","password":"1234Aa","langId":"hu","langType":"east","userId":1,"appUrl":"http://localhost/projects/2023_2024/angular_views/01/"}';
$_GET['data'] = 
'{"email_current":"odry.attila@gmail.com","email":"odry.attila@keri.mako.hu","password":"1234Aa","langId":"hu","langType":"east","userId":1,"appUrl":"http://localhost/projects/2023_2024/angular_views/01/"}';

// Call php file to debug
require_once('./email_change.php');