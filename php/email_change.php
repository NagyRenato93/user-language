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

// Set query
$query =  "SELECT `prefix_name`,
									`first_name`,
									`middle_name`,
									`last_name`,
									`suffix_name`,
									`type`,
									`email`,
									`password`,
									`valid`,
									`wrong_attempts`
						 FROM `user`
						WHERE `id` = ?
						LIMIT 1;";

// Execute query with argument
$result = $db->execute($query, array($args['userId']));

// Check user exist
if (is_null($result)) {

	// Set error
	Util::setError('user_not_exist', $db);
}

// Simplify result
$result = $result[0];

// Check email is not equal
if ($args['email_current'] !== $result['email']) {

	// Set error
	Util::setError('user_id_email_not_match', $db);
}

// Check user valid
if (!$result['valid']) {

	// Set error
	Util::setError('user_disabled', $db);
}

// Check the number of attempts
if ($result['wrong_attempts'] > 5) {

	// Set error
	Util::setError('user_wrong_attempts', $db);
}

// Verify the current password
if (!password_verify($args['password'], $result['password'])) {

	// Set query
	$query = 	"UPDATE `user` 
								SET `wrong_attempts` = `wrong_attempts` + 1
							WHERE `id` = ?;";

	// Execute query with arguments
	$success = $db->execute($query, array($result['id']));

	// Set error
	if ($success['affectedRows'])
				Util::setError('password_incorrect', $db);
	else	Util::setError('failed_increase_retries', $db);
}

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

// Set random email verification code
$args['code'] = bin2hex(random_bytes(16));

// Set query
$query 	= "UPDATE `user` 
							SET `type`			= :type,
									`type_old` 	= :type_old,
									`email`			= :email,
									`email_verification_code`	= :code,
						 			`modified`	= :modified
						WHERE `id` = :id";

// Execute query with arguments
$success = $db->execute($query, array(
	"type"			=> 'N',
	"type_old"	=> $result['type'] === 'N' ? null : $result['type'],
	"email"			=> $args['email'],
	"code"			=> $args['code'],
	"modified"	=> date("Y-m-d H:i:s"),
	"id"				=> $args['userId']
));

// Close connection
$db = null;

// Check not success
if (!$success['affectedRows']) {

	// Set error
	Util::setError('email_change_failed');
}

// Set language
$lang 		= new Language($args['langId'], $args['langType']);
$language = $lang->translate(array(
  "%email_new%" => "email_new",
	"%email_previous%" => "email_previous",
  "%email_confirm%" => "email_confirm",
  "%confirmation%" => "confirmation",
  "%email_changed%" => "email_changed",
  "%informatics%" => "informatics",
  "%dear%" => "dear",
  "%register_email_address_changed%" => "register_email_address_changed",
  "%email_visit_and_confirm%" => "email_visit_and_confirm",
  "%email_do_not_reply%" => "email_do_not_reply",
  "email_send_failed" => "email_send_failed",
  "email_crete_failed" => "email_crete_failed",
	"file_name_missing"=> "file_name_missing",
	"file_not_found" => "file_not_found",
	"file_unable_to_read" => "file_unable_to_read"
));
$language["%lang_id%"] = $args['langId'];
$language["%user_name%"] = $lang->getUserName($result);
$language["%current_date%"] = date("Y-m-d");
$language["%current_year%"] = date("Y");
$language["%email_old%"] = $args["email_current"];
$language["%email_current%"] = $args["email"];
$message = "{$language["%email_changed%"]}!\n{$language["%email_new%"]}: {$args["email"]}";
$lang = null;

// Create document
$document = Document::createDocument('email_change_previous.html', $language, 'html/email');

// Check has error
if (!is_null($document["error"])) {

	// Get error message, and set error
	Util::setError("{$document["error"]}\n{$message}");
}

// Create email
$phpMailer = new Email(null, "KERI " . $language["%informatics%"]);

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
  $phpMailer->Subject = $language["%email_changed%"];
  $phpMailer->Body 		= $document["content"];
  $phpMailer->addAddress($args['email_current'], $language["%user_name%"]);

	// Send email
  $phpMailer->send();

// Exception
} catch (Exception $e) {

  // Set error
	Util::setError("{$language['email_send_failed']}!\n{$message}", $phpMailer);
}

// Set url, and query
$u = "{$args['appUrl']}php/email_confirm.php";
$l = Util::base64Encode($args['langId']);
$t = Util::base64Encode($args['langType']);
$e = Util::base64Encode($args['event']);
$v = Util::base64Encode($args['appUrl']);
$x = Util::base64Encode(strval($args['userId']));
$y = Util::base64Encode($args['email']);
$z = password_hash($args['code'], PASSWORD_DEFAULT);
$language["%email_confirm_url%"] = 
					"{$u}?l={$l}&t={$t}&e={$e}&v={$v}&x={$x}&y={$y}&z={$z}";

// Create document
$document = Document::createDocument('email_change_confirm.html', $language, 'html/email');

// Check has error
if (!is_null($document["error"])) {

	// Get error message, and set error
	Util::setError("{$document["error"]}\n{$message}", $phpMailer);
}

// Clear all addresses to
$phpMailer->clearToAddresses();

try {

	// Add rest properties
  $phpMailer->Body = $document["content"];
  $phpMailer->addAddress($args['email'], $language["%user_name%"]);

	// Send email
  $phpMailer->send();

// Exception
} catch (Exception $e) {

  // Set error
	Util::setError("{$language['email_send_failed']}!\n{$message}");
}

// Close email
$phpMailer = null;

// Set response
Util::setResponse('email_changed');