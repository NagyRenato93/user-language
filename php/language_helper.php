<?php

/*
// Check that the language keys are not included in the document
$file = searchForFile('language_helper.php', 'php');
require_once($file);
$langKeysNotExist = checkLanguageKeysExist('password_frogot.html', $language);
*/


// Check that the language keys are not included in the document
function checkLanguageKeysExist($fileName, $language, $path=null) {
	if (!is_string($path) || 
		empty(($path = trim($path)))) {
		$path = 'html';
	}
	$file 		= searchForFile($fileName, $path);
	$document = file_get_contents($file);
	$languageFiltered = array_filter(
											array_filter($language, 'ucfirst'), 
												function ($key) {
													return (substr($key, 0, 1) === "%" && 
																	substr($key, 	 -1) === "%");
												}, ARRAY_FILTER_USE_KEY
											);
	$result = array();
	foreach(array_keys($languageFiltered) as $key) {
		if (strrpos($document, $key) === false) {
			array_push($result, $key);
		}
	}
	return $result;
}