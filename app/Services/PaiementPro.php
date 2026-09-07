<?php
namespace App\Services;

use App\Core\Env;

final class PaiementPro
{
    private const ENDPOINT = 'https://www.paiementpro.net/webservice/OnlineServicePayment_v2.php';

    public function merchantId(): string
    {
        return trim((string) Env::get('PAIEMENTPRO_MERCHANT_ID', 'PP-F92695'));
    }

    public function initialize(array $order, string $returnUrl, string $notifyUrl): array
    {
        $customer = json_decode((string) $order['customer_data'], true) ?: [];
        $names = preg_split('/\s+/', trim($customer['name'] ?? ''), 2);
        $fields = [
            'merchantId' => $this->merchantId(), 'referenceNumber' => $order['reference'],
            'amount' => (int) $order['total'], 'channel' => '', 'countryCurrencyCode' => '952',
            'customerId' => (string) ($order['user_id'] ?? ''),
            'customerFirstName' => $names[0] ?? 'Client', 'customerLastname' => $names[1] ?? $names[0] ?? 'Client',
            'customerEmail' => $customer['email'], 'customerPhoneNumber' => $customer['phone'],
            'description' => 'Commande IFMAP '.$order['reference'],
            'notificationURL' => $notifyUrl, 'returnURL' => $returnUrl, 'returnContext' => '',
        ];
        // SOAP 1.1 RPC, following the provider's live WSDL; no ext-soap dependency.
        $body = '';
        foreach ($fields as $name => $value) {
            $type = $name === 'amount' ? 'int' : 'string';
            $body .= '<'.$name.' xsi:type="xsd:'.$type.'">'.htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</'.$name.'>';
        }
        $xml = '<?xml version="1.0" encoding="UTF-8"?><soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:tns="'.self::ENDPOINT.'"><soap:Body><tns:initTransact soap:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><request xsi:type="tns:initRequest">'.$body.'</request></tns:initTransact></soap:Body></soap:Envelope>';
        $curl = curl_init(self::ENDPOINT);
        curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: text/xml; charset=utf-8', 'SOAPAction: "'.self::ENDPOINT.'#initTransact"'],
            CURLOPT_POSTFIELDS => $xml, CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_TIMEOUT => 35,
            CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2]);
        $response = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);
        if ($response === false || $status !== 200) throw new \RuntimeException('Paiement Pro est momentanément indisponible.');
        return self::parseResponse($response);
    }

    public static function parseResponse(string $response): array
    {
        $previous = libxml_use_internal_errors(true);
        try {
            if (stripos($response, '<!DOCTYPE') !== false) throw new \RuntimeException('Réponse Paiement Pro invalide.');
            $xml = simplexml_load_string($response, \SimpleXMLElement::class, LIBXML_NONET);
            if ($xml === false) throw new \RuntimeException('Réponse Paiement Pro invalide.');
            $code = $xml->xpath('//*[local-name()="Code"]');
            $sessions = $xml->xpath('//*[local-name()="Sessionid"]');
            $session = (string) ($sessions[0] ?? '');
            if ((string) ($code[0] ?? '') !== '0' || $session === '' || strlen($session) > 190) {
                throw new \RuntimeException('Paiement Pro a refusé l’initialisation du paiement.');
            }
            return ['session_id' => $session, 'payment_url' => 'https://www.paiementpro.net/webservice/onlinepayment/processing_v2.php?sessionid='.rawurlencode($session)];
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    public function validNotification(array $payload, array $order, string $token, string $tokenHash): bool
    {
        foreach (['merchantId', 'referenceNumber', 'countryCurrencyCode', 'amount', 'responsecode'] as $key) {
            if (!isset($payload[$key]) || !is_scalar($payload[$key])) return false;
        }
        return preg_match('/^[a-f0-9]{64}$/D', $token) === 1
            && hash_equals($tokenHash, hash('sha256', $token))
            && (string) $payload['merchantId'] === $this->merchantId()
            && (string) $payload['referenceNumber'] === (string) $order['reference']
            && (string) $payload['countryCurrencyCode'] === '952'
            && is_numeric($payload['amount']) && (float) $payload['amount'] === (float) $order['total']
            && in_array((string) $payload['responsecode'], ['0', '-1'], true);
    }
}
