<?php
require "vendor/autoload.php";

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

function getToken(){
	$ebay_env = $_ENV['EBAY_ENV'];
	$ebay_client_id = $_ENV['EBAY_CLIENT_ID'];
	$ebay_client_secret = $_ENV['EBAY_CLIENT_SECRET'];

	$host = $ebay_env === 'production' ? 'https://api.ebay.com' : 'https://api.sandbox.ebay.com';
	$token_url = $host . '/identity/v1/oauth2/token';

	$scopes = 'https://api.ebay.com/oauth/api_scope';

	$cache_file = sys_get_temp_dir() . '/ebay_app_token.json';
	$token = null;

	if (file_exists($cache_file)) {
		$cached = json_decode(@file_get_contents($cache_file), true);
		if ($cached && isset($cached['access_token'], $cached['expires_in'], $cached['created_at'])) {
			if (time() - $cached['created_at'] < ($cached['expires_in'] - 120)) {
				$token = $cached['access_token'];
			}
		}
	}

	if(!$token){
		// Mint new token
		$basic = base64_encode($ebay_client_id . ':' . $ebay_client_secret);
		$body  = http_build_query([
			'grant_type'	=> 'client_credentials',
			'scope'			=> $scopes
		]);

		$ch = curl_init($token_url);
		curl_setopt_array($ch, [
			CURLOPT_POST => true,
			CURLOPT_HTTPHEADER => [
				'Content-Type: application/x-www-form-urlencoded',
				'Authorization: Basic ' . $basic
			],
			CURLOPT_POSTFIELDS => $body,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT => 20,
		]);
		$resp = curl_exec($ch);
		$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$err  = curl_error($ch);
		curl_close($ch);

		if ($resp === false || $http < 200 || $http >= 300) {
			// http_response_code(500);
			// header('Content-Type: application/json');
			echo json_encode(['error' => 'token_request_failed', 'http' => $http, 'message' => $err ?: $resp]);
			exit;
		}

		$data = json_decode($resp, true);
		if (!isset($data['access_token'])) {
			// http_response_code(500);
			// header('Content-Type: application/json');
			echo json_encode(['error' => 'token_missing', 'raw' => $data]);
			exit;
		}

		// Cache minimal fields
		$data['created_at'] = time();
		@file_put_contents($cache_file, json_encode($data));
		$token = $data['access_token'];
	}

	// header('Content-Type: application/json');
	return ['access_token' => $token];
}