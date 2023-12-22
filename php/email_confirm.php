<?php
declare(strict_types=1);

// Use namescapes aliasing
use Util\Util as Util;
use Database\Database as Database;
use Language\Language as Language;
use Document\Document as Document;
use Document\Tag as Tag;

// Atika
//$_SERVER['QUERY_STRING'] = 'l=aHU=&t=ZWFzdA==&e=ZW1haWxfY2hhbmdl&v=aHR0cDovL2xvY2FsaG9zdC9wcm9qZWN0cy8yMDIzXzIwMjQvdml6c2dhcmVtZWsvdXNlci1sYW5ndWFnZS8=&x=MQ==&y=b2RyeS5hdHRpbGFAa2VyaS5tYWtvLmh1&z=$2y$10$Mv0KWdZqDm4rs94I5JUcH.Ui75Ip6w6j8JsZBpskhxyGE7K2qxo8u';


// Get url query
parse_str($_SERVER['QUERY_STRING'], $args);

// Set environment
require_once('../../common/php/environment.php');

// Check url query
if (!isset($args['l']) || 
		!isset($args['t']) ||
		!isset($args['e']) || 
		!isset($args['v']) || 
		!isset($args['x']) || 
		!isset($args['y']) ||
		!isset($args['z'])) {

	// Set error
	createDocument("Invalid url parameters!");
}

// Decode arguments
$args = array(
	'langId'			=> Util::base64Decode($args['l']),
	'langType'		=> Util::base64Decode($args['t']),
	'event'				=> Util::base64Decode($args['e']),
	'url'					=> Util::base64Decode($args['v']),
	'id' 					=> intval(Util::base64Decode($args['x'])),
	'email'				=> Util::base64Decode($args['y']),
	'code'				=> $args['z']
);

// Set language
$lang 		= new Language($args['langId'], $args['langType']);
$language = $lang->translate(array(
	"user_not_exist" => "user_not_exist",
	"email_verification_code_invalid" => "email_verification_code_invalid",
	"failed_increase_retries" => "failed_increase_retries",
	"%register_thanks%" => "register_thanks",
	"%email_confirmation_successful%" => "email_confirmation_successful",
	"%email_confirmation_failed%" => "email_confirmation_faile",
	"%email_confirm_short%" => "email_confirm_short",
	"%informatics%" => "informatics",
  "%dear%" => "dear", 
));
$language["%lang_id%"] = $args['langId'];
$language["%current_date%"] = date("Y-m-d");
$language["%current_year%"] = date("Y");
$language["%app_url%"] = $args['url'];
$language["%class%"] = $args['event'] === 'register' ? '' : 'd-none';

// Connect to database
$db = new Database();

// Set query
$query =  "SELECT `id`,
									`prefix_name`,
									`first_name`,
									`middle_name`,
									`last_name`,
									`suffix_name`,
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

// Get user name
$language["%user_name%"] = $lang->getUserName($result);
$lang = null;

// Atika
//$result['email_verification_code'] = 'd58d79ee9efa344b5103dd873ef41a52';

// Verify verification code
if (is_null($result['email_verification_code']) ||
		!password_verify($result['email_verification_code'], $args['code'])) {

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
//$success['affectedRows'] = 1;

// Close connection
$db = null;

// Check not success
if (!$success['affectedRows']) {

	// Set error
	createDocument("{$language['email_confirmation_failed']}!");
}

// Create document
$document = Document::createDocument('email_confirm.html', $language, 'html/email');

// Check has error
if (!is_null($document["error"])) {

	// Set error
	createDocument("{$language['email_confirmation_successful']}!");
}

// Show document content
echo $document['content'];
exit(0);

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
				new Tag('h3', array('class' => 'w-auto'), $msg)
			)
		));
	echo $document;
	exit(0);
}