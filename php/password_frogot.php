<?php
declare(strict_types=1);

// Using namespaces aliasing
use \Util\Util as Util;
use \Database\Database as Database;
use \Language\Language as Language;
use \PHPMailer\Email as Email;

// Set environment
require_once('../../../common/php/environment.php');

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
$password = password_hash($passwordNew, PASSWORD_DEFAULT);

// Set query
$query 	= "UPDATE `user` 
							SET `password` = :password,
									`modified` = :modified
						WHERE `id` = :id";

// Set current date
$curentDate = date("Y-m-d");

// Execute query with arguments
$success	= $db->execute($query, array(
							"password"	=> $password,
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
							"informatics",
							"password_changed",
							"dear",
							"password_new",
							"password_change_it_soon",
							"email_do_not_reply",
							"email_send_failed",
							"email_crete_failed"
						), true);
$userName = $lang->getUserName($result);
$message  = "{$language["password_changed"]}!\n{$language["password_new"]}: {$passwordNew}";
$emailMsg = array(
	"crete_error" => "{$language['email_crete_failed']}!\n{$message}",
	"send_error" 	=> "{$language['email_send_failed']}!\n{$message}"
);
$lang = null;

// Create new email
$email 	= new Email(array(
  "fromName" => "KERI " . $language["informatics"]
));

// Check is not success
if ($email->isError()) {

	// Set error
	Util::setError($emailMsg['crete_error'], $email);
}

// Create message
$message  = <<<EOT
<!DOCTYPE html>
<html lang="{$args['langId']}">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email</title>
  </head>
  <body>
    <table style="width:100%;border-collapse:collapse;">
      <tbody>
        <tr>
          <td style="width:600px;" align="center">
            <table style="width:600px;border:none;border-collapse:collapse;border:none;padding:0;">
              <tbody>
                <tr>
                  <td style="background-color:#24a9ca;color:#fff" align="center">
                    <h2 style="margin:10px 0;">KERI {$language["informatics"]}</h2>
                  </td>
                </tr>
              </tbody>
            </table>
            <table style="border-collapse:collapse;border:none;padding:0;">
              <tbody>
                <tr>
                  <td style="width:600px;" align="center">
                    <table style="width:560px;">
                      <tbody>
                        <tr>
                          <td align="left" style="font-size:18px;font-weight:300;">
                            <h4 style="line-height:36px;margin-bottom:0;margin-top:0;text-align:center;">
                              {$language["password_changed"]}!
                            </h4>
                            <hr style="margin:10px 0 20px 0;">
                            <div style="font-size:18px;font-weight:300;">
                              <p style="margin-bottom:20px">{$language["dear"]} <b>{$userName}</b>!</p>
                              <p style="margin-bottom:20px">{$language["password_new"]}: <b>{$passwordNew}</b></p>
                            </div>
                            <p style="font-size:12px;font-weight:300;margin-top:0">{$curentDate}</p>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </td>
                </tr>
              </tbody>
            </table>
            <table style="width:600px;border-collapse:collapse;border:none;padding:0;">
              <tbody>
                <tr>
                  <td align="center" 
                      style="background-color:#385765;color:#f2f2f2;padding:5px;text-align:center;">
                    <p style="margin:0;font-size:16px;font-weight:300;line-height:30px">
                      {$language["password_change_it_soon"]}!
                    </p>
                    <p style="margin:0;font-size:16px;font-weight:300;line-height:30px">
                      {$language["email_do_not_reply"]}.
                    </p>
                  </td>
                </tr>
              </tbody>
            </table>
          </td>
        </tr>
      </tbody>
    </table>   
  </body>
</html>
EOT;  

// Set email, and send
$email->set_subject($language["password_changed"]);
$email->set_body($message);
$email->AltBody = $message;
$email->set_addressees($args['email']);
$email->send_email();

// Check is not sent
if ($email->isError()) {

	// Set error
	Util::setError($emailMsg['send_error'], $email);
}

// Close email
$email = null;

// Set response
Util::setResponse('password_changed');