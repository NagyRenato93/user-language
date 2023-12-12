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

// Set current date
$curentDate = date("Y-m-d");

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
							"informatics",
							"email_changed",
							"email_address",
							"email_confirm",
							"confirmation",
							"new",
							"old",
							"dear",
							"email_visit_and_confirm",
							"email_do_not_reply",
							"email_send_failed",
							"email_crete_failed"
						), true);
$language['email_address'] = mb_strtolower($language['email_address'], 'utf-8');
$userName = $lang->getUserName($result);
$message  = "{$language["email_changed"]}!";
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
                    <h2 style="margin:10px 0;">
                      <span>KERI {$language["informatics"]}<span>
                    </h2>
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
                              {$language["email_changed"]}!
                            </h4>
                            <hr style="margin:10px 0 20px 0;">
                            <div style="font-size:15px;font-weight:300;">
                              <p style="font-size:18px;margin-bottom:20px">{$language["dear"]} <b>{$userName}</b>!</p>
															<p><b>{$language['new']} {$language['email_address']}: </b>{$args['email']}</p>
															<p style="margin-bottom:20px">{$language["email_visit_and_confirm"]}!</p>
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
$email->set_subject($language["email_changed"]);
$email->set_body($message);
$email->AltBody = $message;
$email->set_addressees($args['email_current']);
$email->send_email();

// Check is not sent
if ($email->isError()) {

	// Set error
	Util::setError($emailMsg['send_error'], $email);
}

// Close email
$email = null;

// Create new email
$email 	= new Email(array(
	"fromName" => "KERI " . $language["informatics"]
));

// Check is not success
if ($email->isError()) {

	// Set error
	Util::setError($emailMsg['crete_error'], $email);
}


// Set url, and query
$u = "{$args['appUrl']}php/email_confirm.php";
$l = Util::base64Encode($args['langId']);
$t = Util::base64Encode($args['langType']);
$v = Util::base64Encode($args['appUrl']);
$x = Util::base64Encode(strval($args['userId']));
$y = Util::base64Encode($args['email']);
$z = password_hash($args['code'], PASSWORD_DEFAULT);

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
                              {$language["email_changed"]}!
                            </h4>
                            <hr style="margin:10px 0 20px 0;">
                            <div style="font-size:15px;font-weight:300;">
                              <p style="font-size:18px;margin-bottom:20px">{$language["dear"]} <b>{$userName}</b>!</p>
															<p><b>{$language['old']} {$language['email_address']}: </b>{$args['email_current']}</p>
															<p><b>{$language['new']} {$language['email_address']}: </b>{$args['email']}</p>
                            </div>
														<hr style="margin:10px 0 20px 0;">
                            <div style="font-size:18px;font-weight:300;margin-bottom:20px">
															<h3>{$language['email_confirm']}!</h3>
															<a href={$u}?l={$l}&t={$t}&v={$v}&x={$x}&y={$y}&z={$z}>{$language['confirmation']}</a>
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
$email->set_subject($language["email_changed"]);
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
Util::setResponse('email_changed');
