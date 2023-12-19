<?php

$language = $lang->translate(array(
	"%lang_id%" => null,
	"%password%" => "password",
	"%password_frogot%" => "password_frogot",
	"%password_changed%" => "password_changed",
	"%password_new%" => "password_new",
	"%password_change_it_soon%" => "password_change_it_soon",
	"%email_confirm%" => "email_confirm",
	"%confirmation%" => "confirmation",
	"%email_changed%" => "email_changed",
	"%informatics%" => "informatics",
	"%dear%" => "dear",
	"%user_name%" => null,
	"%register_email_address_changed%" => "register_email_address_changed",
	"%old%" => "old",
	"%new%" => "new",
	"%email_address%" => "email_address",
	"%previous%" => "previous",
	"%current%" => "current",
	"%current_date%" => null,
	"%email_visit_and_confirm%" => "email_visit_and_confirm",
	"%email_do_not_reply%" => "email_do_not_reply",
	"%current_year%" => null,
	"email_send_failed" => "email_send_failed",
	"email_crete_failed" => "email_crete_failed",
	"file_name_missing"=> "file_name_missing",
	"file_not_found" => "file_not_found",
	"file_unable_to_read" => "file_unable_to_read"
),  "email_address,password");
$language["%lang_id%"] = $args['langId'];
$language["%user_name%"] = $lang->getUserName($result);
$language["%current_date%"] = date("Y-m-d");
$language["%current_year%"] = date("Y");
$language["%email_previous%"] = $args["email_curent"];
$language["%email_current%"] = $args["email"];
$message = "{$language["%password_changed%"]}!\n{$language["%password_new%"]}: {$passwordNew}";