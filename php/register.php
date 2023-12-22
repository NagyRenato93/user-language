<?php
declare(strict_types=1);

// Using namespaces aliasing
use Util\Util as Util;
use Database\Database as Database;
use Language\Language as Language;
use Document\Document as Document;
use PHPMailer\Email as Email;

// Set environment
require_once('../../common/php/environment.php');

// Get arguments
$args = Util::getArgs();

// Connect to database
$db = new Database();

// Set query (Check new email already exist)
$query = "SELECT `id` 
					FROM 	`user` 
					WHERE `email` = :email
					LIMIT 1;";

// Execute query with arguments
$success	= $db->execute($query, array(
							'email' => $args['email']
						));

// Check result
if (!is_null($success)) {

	// Set error
	Util::setError('user_email_already_exist', $db);
}

// Check image exist
if (!is_null($args['img'])) {

	// Decode image
	$args['img'] = Util::base64Decode($args['img']);
}

// Save, and create a new password hash
$password_current = $args['password'];
$args['password'] = password_hash($args['password'], PASSWORD_DEFAULT);

// Set random email verification code
$args['email_verification_code'] = bin2hex(random_bytes(16));

// Set created datetime
$args['created'] = date("Y-m-d H:i:s");


// Set user type
$args['type'] = "N";

// Set query
$query = "INSERT INTO `user` (`type`, `prefix_name`, `first_name`, `middle_name`, `last_name`, `suffix_name`,
 															`nick_name`, `born`, `gender`, `img`, `img_type`, `country`, `country_code`, 
															`phone`, `city`, `postcode`, `address`, `email`, `password`,
															`email_verification_code`, `created`) VALUES";

// Set params
$params = Util::objMerge(array(
	"type" => null, 
	"prefix_name" => null, 
	"first_name" => null,
	"middle_name" => null,
	"last_name" => null,
	"suffix_name" => null,
 	"nick_name" => null,
	"born" => null,
	"gender" => null,
	"img" => null,
	"img_type" => null,
	"country" => null,
	"country_code" => null,
	"phone" => null,
	"city" => null,
	"postcode" => null,
	"address" => null,
	"email" => null,
	"password" => null,
	"email_verification_code" => null,
	"created" => null
), $args, true);

// Execute query
$result = $db->execute($query, $params);

// Close connection
$db = null;

// Check not success
if (!$result['affectedRows']) {

	// Set error
	Util::setError('registration_failed');
}

// Set language
$lang 		= new Language($args['langId'], $args['langType']);
$language = $lang->translate(array(
  "%register%" => "register",
	"%register_thanks%" => "register_thanks",
	"%login_details%" => "login_details",
	"%login_details_keep_save%" => "login_details_keep_save",
	"%email_address%" => "email_address",
	"%password%" => "password",
  "%email_confirm%" => "email_confirm",
  "%confirmation%" => "confirmation",
  "%informatics%" => "informatics",
  "%dear%" => "dear",
  "%email_do_not_reply%" => "email_do_not_reply",
  "email_send_failed" => "email_send_failed",
  "email_crete_failed" => "email_crete_failed",
	"file_name_missing"=> "file_name_missing",
	"file_not_found" => "file_not_found",
	"file_unable_to_read" => "file_unable_to_read"
));
$language["%lang_id%"] = $args['langId'];
$language["%user_name%"] = $lang->getUserName($args);
$language["%current_date%"] = date("Y-m-d");
$language["%current_year%"] = date("Y");
$language["%email_current%"] = $args['email'];
$language["%password_current%"] = $password_current;
$message = "{$language["%register%"]}!\n{$language["%email_address%"]}: {$args["email"]}";
$lang = null;

// Set url, and query
$u = "{$args['appUrl']}php/email_confirm.php";
$l = Util::base64Encode($args['langId']);
$t = Util::base64Encode($args['langType']);
$e = Util::base64Encode($args['event']);
$v = Util::base64Encode($args['appUrl']);
$x = Util::base64Encode(strval($result['lastInsertId']));
$y = Util::base64Encode($args['email']);
$z = password_hash($args['email_verification_code'], PASSWORD_DEFAULT);
$language["%email_confirm_url%"] = 
					"{$u}?l={$l}&t={$t}&e={$e}&v={$v}&x={$x}&y={$y}&z={$z}";

// Create document
$document = Document::createDocument('register_confirm.html', $language, 'html/email');

// Check has error
if (!is_null($document["error"])) {

	// Get error message, and set error
	Util::setError("{$document["error"]}\n{$message}");
}

// Create email
$phpMailer = new Email("KERI " . $language["%informatics%"]);

// Check is not created
if ($phpMailer->isError()) {

	// Set error
	Util::setError("{$language['email_crete_failed']}!\n{$message}", $phpMailer);
}

// Get image
$imgFile = searchForFile('keri.png', 'media/image/logo');

try {

  // Check image found
	if (!is_null($imgFile)) {
  	$phpMailer->AddEmbeddedImage($imgFile, 'logoimg');
	}

	// Add rest properties
  $phpMailer->Subject = $language["%register%"];
  $phpMailer->Body 		= $document["content"];
  $phpMailer->addAddress($args['email'], $language["%user_name%"]);

	// Send email
  $phpMailer->send();

// Exception
} catch (Exception $e) {

  // Set error
	Util::setError("{$language['email_send_failed']}!\n{$message}", $phpMailer);
}

// Close email
$phpMailer = null;

// Set response
Util::setResponse(array(
	"id"		=> $result['lastInsertId'],
	"type"	=> $args['type']
));