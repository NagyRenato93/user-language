<?php
declare(strict_types=1);

// Use namescapes aliasing
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
$query =  "SELECT `id`,
									`prefix_name`,
									`first_name`,
									`middle_name`,
									`last_name`,
									`suffix_name`,
									`valid`,
									`wrong_attempts` 
						 FROM `user` 
						WHERE `email` = ?
						LIMIT 1;";

// Execute query with argument
$result = $db->execute($query, array($args['email']));

// Check user exist
if (is_null($result)) {

	// Set error
	Util::setError('user_not_exist', $db);
}

// Simplify result
$result = $result[0];

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

// Unset not necessary key(s)
unset( 
	$result['valid'], 
	$result['wrong_attempts']
);

// Creates a new defeault password
$passwordNew = '1234Aa';

// Creates a new password hash
$passwordHash = password_hash($passwordNew, PASSWORD_DEFAULT);

// Set query
$query 	= "UPDATE `user` 
							SET `password` = :password,
									`modified` = :modified
						WHERE `id` = :id";

// Execute query with arguments
$success	= $db->execute($query, array(
							"password"	=> $passwordHash,
							"modified"	=> date("Y-m-d H:i:s"),
							"id"				=> $result['id']
						));

// Close connection
$db = null;

// Check not success
if (!$success['affectedRows']) {

	// Set error
	Util::setError('password_change_failed');
}

// Set language
$lang 		= new Language($args['langId'], $args['langType']);
$language = $lang->translate(array(
    "%password_frogot%" => "password_frogot",
		"%password_changed%" => "password_changed",
    "%password_new%" => "password_new",
    "%password_change_it_soon%" => "password_change_it_soon",
    "%informatics%" => "informatics",
    "%dear%" => "dear",
		"%register_password_changed%" => "register_password_changed",
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
$language["%password_current%"] = $passwordNew;
$message = "{$language["%password_changed%"]}!\n{$language["%password_new%"]}: {$passwordNew}";
$lang = null;

// Create document
$document = Document::createDocument('password_frogot.html', $language, 'html/email');

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
  $phpMailer->Subject = $language["%password_changed%"];
  $phpMailer->Body 		= $document["content"];
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
Util::setResponse('password_changed');