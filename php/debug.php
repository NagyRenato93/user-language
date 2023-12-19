<?php
declare(strict_types=1);

// Set type
$type = 'email_change';

// Switch type
switch($type) {

	// Login
	case 'login':
		$requireFile = 'login.php';
		$_GET['data'] = 
			'{"email":"odry.attila@keri.mako.hu","password":"1234Aa"}';
		break;

	// Register
	case 'register':
		$requireFile = 'register.php';
		$_POST['data'] = '';
		break;

	// Profile
	case 'profile':
		$requireFile = 'profile.php';
		$_GET['data'] = '';
		break;

	// Email change
	case 'email_change':
		$requireFile = 'email_change.php';
		$current = 1;
		if ($current) {
			$_GET['data'] = 
				'{"email_current":"odry.attila@keri.mako.hu","email":"odry.attila@gmail.com","password":"1234Aa","langId":"hu","langType":"east","userId":1,"appUrl":"http://localhost/projects/2023_2024/vizsgaremek/user-language/","event":"email_change"}';
		} else {
			$_GET['data'] = 
				'{"email_current":"odry.attila@gmail.com","email":"odry.attila@keri.mako.hu","password":"1234Aa","langId":"hu","langType":"east","userId":1,"appUrl":"http://localhost/projects/2023_2024/vizsgaremek/user-language/","event":"email_change"}';
		}
		break;

	// Password frogot
	case 'password_frogot':
		$requireFile = './password_frogot.php';
		$_GET['data'] = 
			'{"email":"odry.attila@keri.mako.hu","langId":"en","langType":"west"}';
		break;

	// Password change
	case 'password_change':
		$requireFile = './password_change.php';
		$_GET['data'] = 
			'{"email":"odry.attila@keri.mako.hu","langId":"en","langType":"west"}';
		break;

	default:
		$requireFile = '';
		$_GET['data'] = '';
}

// Call php file to debug
require_once($requireFile);