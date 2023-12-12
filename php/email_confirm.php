<?php
declare(strict_types=1);

// Using namespaces aliasing
use \Util\Util as Util;
use \Database\Database as Database;
use \Language\Language as Language;
use \Document\Document as Document;
use \Document\Tag as Tag;

// Atika
//$_SERVER['QUERY_STRING'] = 
//'l=aHU=&t=ZWFzdA==&v=aHR0cDovL2xvY2FsaG9zdC9wcm9qZWN0cy8yMDIzXzIwMjQvYW5ndWxhcl92aWV3cy8wMS8=&x=MQ==&y=b2RyeS5hdHRpbGFAa2VyaS5tYWtvLmh1&z=$2y$10$L8AJCkvvGcQ9sC10Inxp0.1/rHVvtQ5cGA4eGuwQxGM9OaKP3JBfm';

// Get url query
parse_str($_SERVER['QUERY_STRING'], $args);

// Set environment
require_once('../../common/php/environment.php');

// Check url query
if (!isset($args['l']) || 
		!isset($args['t']) || 
		!isset($args['v']) || 
		!isset($args['x']) || 
		!isset($args['y']) ||
		!isset($args['z'])) {

	// Set error
	createDocument("Invalid url parameters!");
}

// Decode arguments
$args = array(
	'langId'		=> Util::base64Decode($args['l']),
	'langType'	=> Util::base64Decode($args['t']),
	'url'				=> Util::base64Decode($args['v']),
	'id' 				=> intval(Util::base64Decode($args['x'])),
	'email'			=> Util::base64Decode($args['y']),
	'code'			=> $args['z']
);

// Set language
$lang 		= new Language($args['langId'], $args['langType']);
$language = $lang->translate(array(
							"user_not_exist",
							"email_verification_code_invalid",
							"failed_increase_retries",
							"email_confirmation_successful",
							"email_confirmation_failed"
						), true);
$lang = null;

// Connect to database
$db = new Database();

// Set query
$query =  "SELECT `id`,
									`type_old`,
									`email_verification_code`
						 FROM `user` 
						WHERE `id` = :id AND
									`email` = :email
						LIMIT 1;";

// Execute query with argument
$result = $db->execute($query, array(
	"id"		=> $args['id'],
	"email"	=> $args['email']
));

// Check user exist
if (is_null($result)) {

	// Set error
	createDocument("{$language['user_not_exist']}!", $db);
}

// Simplify result
$result = $result[0];

// Atika
//$result['email_verification_code'] = 'f036bb3f87b356ee60a6e502c48e88af';

// Verify verification code
if (!password_verify($result['email_verification_code'], $args['code'])) {

	// Set query
	$query = 	"UPDATE `user` 
								SET `wrong_attempts` = `wrong_attempts` + 1
							WHERE `id` = ?;";

	// Execute query with arguments
	$success = $db->execute($query, array($result['id']));

	// Set error
	if ($success['affectedRows'])
				createDocument("{$language['email_verification_code_invalid']}!", $db);
	else	createDocument("{$language['failed_increase_retries']}!", $db);
}

// Set query
$query = 	"UPDATE `user` 
							SET `type` = :type,
									`email_confirmed` = :dateNow,
									`type_old` = :type_old,
									`email_verification_code` = :code
						WHERE `id` = :id;";

// Execute query with arguments
$success = $db->execute($query, array(
	'type' 			=> $result['type_old'] ? $result['type_old'] : 'U',
	"dateNow"		=> date("Y-m-d H:i:s"),
	'type_old'	=> NULL,
	'code' 			=> NULL,
	'id' 				=> $result['id']
));

// Create document
createDocument($success['affectedRows'] ? 
							"{$language['email_confirmation_successful']}!" : 
							"{$language['email_confirmation_failed']}!", $db);

// Create document
function createDocument($msg=null, &$db=null) {
	if ($db) $db=null;
	$document = new Document();
	$document->getHtml()->setAttr(array('lang'=>'hu'));
	$document->getHead()->add(array(
		new Tag('title', null, 'E-mail cím hitelesítése'),
		new Tag('link', array(
			'rel'		=>	'stylesheet',
			'href'	=>	'../../../components/bootstrap/5.3.2/css/bootstrap.min.css'
		))
	));
	$document->getBody()->add(
		new Tag('div', 
			array('class'=>'d-flex align-items-center justify-content-center vh-100 vw-100'), null, array(
				new Tag('h1', array('class' => 'w-auto'), $msg)
			)
		));
	echo $document;
	exit(0);
}