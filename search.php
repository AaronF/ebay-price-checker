<?php
require 'token.php';

$token = getToken() ?? null;
if(empty($token['access_token'])){
	echo json_encode(['message' => 'Missing token']);
	exit;
}

$browse_url = 'https://api.ebay.com/buy/browse/v1/item_summary/search';
$query	= isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$limit	= max(1, min((int)($_GET['limit'] ?? 50), 100));
$filter	= isset($_GET['filter']) ? (string)$_GET['filter'] : 'buyingOptions:{FIXED_PRICE},conditionIds:{3000|4000},itemLocationCountry:{GB},deliveryCountry:{GB},priceCurrency:{GBP}';

$params = http_build_query([
	'q'			=> $query,
	'limit'		=> $limit,
	'filter'	=> $filter
]);
$ch = curl_init($browse_url . '?' . $params);
curl_setopt_array($ch, [
	CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token['access_token'], 'Accept: application/json', 'X-EBAY-C-MARKETPLACE-ID: EBAY_GB'],
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_TIMEOUT => 20
]);
$response = curl_exec($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

header('Content-Type: application/json');
echo $response;