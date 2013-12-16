<?php

if(!isset($_GET['action']))
	die("Action must be in 'action' GET parameter");
$action = $_GET['action'];

if('register' == $action)
{
	if(!isset($_POST['number']))
		die("Phone number must be in 'number' POST parameter");
	$number = filter_var($_POST['number'], FILTER_VALIDATE_INT);
	$code = createPassword($number);
	sendSms($number, $code);
	$response = [
		'meta' => ['code' => 200],
		'data' => ['number' => $number, 'code' => $code]
	];
	echo json_encode($response);
}
elseif('confirm' == $action)
{
	if(!isset($_POST['number']))
		die("Phone number must be in 'number' POST parameter");
	$number = filter_var($_POST['number'], FILTER_VALIDATE_INT);

	if(!isset($_POST['code']))
		die("Code must be in 'code' POST parameter");
	$code = filter_var($_POST['code'], FILTER_VALIDATE_INT);

	if(createPassword($number) != $code)
		die('Wrong code!');

	createJabberUser($number, $code);

	$response = [
		'meta' => ['code' => 200],
		'data' => ['number' => $number, 'code' => $code]
	];
	echo json_encode($response);
}
elseif('list_users' == $action)
{
	echo json_encode(getUsersList());
}

function createPassword($number)
{
	$hash = strtolower(sha1(sha1($number).'sa!t'));
	$sub_hash = substr($hash, 0, 4);
	return hexdec($sub_hash);
}

function sendSms($number, $password)
{
	$key = 'e1affabe';
	$api_secret = '5c307723';
	$sms_url = "https://rest.nexmo.com/sms/json?api_key={$key}&api_secret={$api_secret}&from=Emotu&to={$number}&text={$password}";
	return json_decode(file_get_contents($sms_url));
}

function createJabberUser($name, $password)
{
	$command = "/usr/bin/prosodyctl register {$name} xmpp.nebo15.me {$password} > register.log 2>&1 &";
	exec($command);
	$account_file = "/var/lib/prosody/xmpp%2enebo15%2eme/accounts/$name.dat";
	chmod($account_file, 0666);
}

function getUsersList()
{
	$command = "/usr/bin/prosodyctl mod_listusers";
	exec($command, $out);
	return $out;
}