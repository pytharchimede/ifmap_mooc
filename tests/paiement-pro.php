<?php
require __DIR__.'/../app/Core/Env.php';
require __DIR__.'/../app/Services/PaiementPro.php';
use App\Services\PaiementPro;
function check(bool $condition): void { if (!$condition) throw new RuntimeException('Test failed'); }
$gateway = new PaiementPro();
$token = str_repeat('a', 64);
$hash = hash('sha256', $token);
$order = ['reference'=>'IF-TEST', 'total'=>'1000.00'];
$payload = ['merchantId'=>$gateway->merchantId(), 'referenceNumber'=>'IF-TEST', 'amount'=>'1000', 'countryCurrencyCode'=>'952', 'responsecode'=>'0'];
check($gateway->validNotification($payload, $order, $token, $hash));
check(!$gateway->validNotification($payload, $order, '', $hash));
check(!$gateway->validNotification($payload, $order, str_repeat('b',64), $hash));
foreach (['merchantId'=>'OTHER','referenceNumber'=>'OTHER','amount'=>999,'countryCurrencyCode'=>'978','responsecode'=>'','amount'=>999] as $key=>$value) {
    check(!$gateway->validNotification(array_replace($payload, [$key=>$value]), $order, $token, $hash));
}
foreach (array_keys($payload) as $key) {
    $missing = $payload; unset($missing[$key]);
    check(!$gateway->validNotification($missing, $order, $token, $hash));
    check(!$gateway->validNotification(array_replace($payload, [$key=>[]]), $order, $token, $hash));
}
check($gateway->validNotification(array_replace($payload, ['responsecode'=>'-1']), $order, $token, $hash));
$result = PaiementPro::parseResponse('<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/"><s:Body><return><Code>0</Code><Sessionid>abc&amp;x</Sessionid></return></s:Body></s:Envelope>');
check(str_ends_with($result['payment_url'], 'abc%26x'));
foreach (['invalid', '<return><Code>-1</Code></return>', '<return><Sessionid>x</Sessionid></return>', '<return><Code>0</Code></return>', '<!DOCTYPE x><return/>'] as $xml) {
    try { PaiementPro::parseResponse($xml); throw new LogicException('Unexpected success'); }
    catch (RuntimeException $e) {}
}
echo "Paiement Pro: validation des notifications et réponses SOAP OK\n";
