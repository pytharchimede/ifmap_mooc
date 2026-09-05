<?php
namespace App\Services;

use App\Core\Env;
use CinetPay\Country;
use CinetPay\Currency;
use CinetPay\Language;
use CinetPay\Request\CreatePaymentRequest;

final class CinetPay
{
    private const PAYMENT_URL='https://api-checkout.cinetpay.com/v2/payment';
    private const CHECK_URL='https://api-checkout.cinetpay.com/v2/payment/check';

    public function configured(): bool
    {
        return trim((string)Env::get('CINETPAY_API_KEY'))!==''&&trim((string)Env::get('CINETPAY_API_PASSWORD'))!=='';
    }

    public function initialize(array $order,string $returnUrl,string $notifyUrl): array
    {
        $customer=json_decode((string)($order['customer_data']??'{}'),true)?:[];$names=preg_split('/\s+/',trim((string)($customer['name']??'')),2)?:[];$first=mb_substr(trim((string)($names[0]??'Client')),0,255);$last=mb_substr(trim((string)($names[1]??'IFMAP')),0,255);if(mb_strlen($first)<2)$first='Client';if(mb_strlen($last)<2)$last='IFMAP';
        try{$payment=$this->client()->payments()->create(new CreatePaymentRequest(currency:Currency::XOF,merchantTransactionId:$this->transactionId($order),amount:(int)$order['total'],successUrl:$returnUrl,failedUrl:$returnUrl.'?payment=failed',notifyUrl:$notifyUrl,language:Language::French,designation:'Commande IFMAP '.$order['reference'],clientFirstName:$first,clientLastName:$last,clientEmail:(string)($customer['email']??'')));}catch(\CinetPay\Exception\AuthenticationException){throw new \RuntimeException('Les identifiants API REST Paiement sont refusés. Le mot de passe API Transfert ne peut pas être utilisé pour Checkout.');}
        return ['code'=>(string)$payment->code,'message'=>$payment->status,'data'=>['payment_url'=>$payment->paymentUrl,'payment_token'=>$payment->transactionId,'notify_token'=>$payment->notifyToken,'merchant_transaction_id'=>$payment->merchantTransactionId]];
    }

    public function seamlessData(array $order,string $notifyUrl): array
    {
        $customer=json_decode((string)($order['customer_data']??'{}'),true)?:[];$names=preg_split('/\s+/',trim((string)($customer['name']??'')),2)?:[];
        $mode=strtoupper(trim((string)Env::get('CINETPAY_MODE','PRODUCTION')));if(!in_array($mode,['PRODUCTION','TEST'],true))$mode='PRODUCTION';
        return ['apikey'=>(string)Env::get('CINETPAY_API_KEY'),'site_id'=>(string)Env::get('CINETPAY_SITE_ID'),'mode'=>$mode,'transaction_id'=>$this->transactionId($order),'amount'=>(int)$order['total'],'currency'=>'XOF','description'=>'Commande IFMAP '.$order['reference'],'notify_url'=>$notifyUrl,'channels'=>'ALL','customer_name'=>$names[0]??'Client','customer_surname'=>$names[1]??'IFMAP','customer_email'=>(string)($customer['email']??''),'customer_phone_number'=>(string)($customer['phone']??''),'customer_address'=>(string)($customer['address']??'Abidjan'),'customer_city'=>'Abidjan','customer_country'=>'CI','customer_state'=>'CI','customer_zip_code'=>'00225'];
    }

    private function transactionId(array $order): string
    {
        $created=preg_replace('/\D/','',(string)($order['created_at']??''));$created=substr(str_pad($created,14,'0'),0,14);
        return $created.str_pad((string)(int)($order['id']??0),6,'0',STR_PAD_LEFT);
    }

    public function check(string $transactionId): array
    {
        $payment=$this->client()->payments()->find($transactionId);return ['code'=>$payment->code,'data'=>$payment->raw+['status'=>$payment->status,'merchant_transaction_id'=>$payment->merchantTransactionId,'transaction_id'=>$payment->transactionId]];
    }

    private function client(): \CinetPay\CinetPay
    {
        $key=trim((string)Env::get('CINETPAY_API_KEY'));$password=(string)Env::get('CINETPAY_API_PASSWORD');if($key===''||$password==='')throw new \RuntimeException('La clé API ou le mot de passe API CinetPay manque dans le fichier .env.');$mode=strtoupper(trim((string)Env::get('CINETPAY_MODE','PRODUCTION')));return $mode==='TEST'?\CinetPay\CinetPay::sandbox($key,$password,Country::IvoryCoast):\CinetPay\CinetPay::production($key,$password,Country::IvoryCoast);
    }

    private function post(string $url,array $payload): array
    {
        $lastError='';$lastErrno=0;
        for($attempt=1;$attempt<=3;$attempt++){$curl=curl_init($url);if($curl===false)throw new \RuntimeException('Initialisation réseau impossible.');curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_HTTPHEADER=>['Content-Type: application/json','Accept: application/json','User-Agent: IFMAP-MOOC/1.0'],CURLOPT_POSTFIELDS=>json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_TIMEOUT=>30,CURLOPT_ENCODING=>'',CURLOPT_FOLLOWLOCATION=>true,CURLOPT_MAXREDIRS=>5,CURLOPT_HTTP_VERSION=>CURL_HTTP_VERSION_1_1]);$body=curl_exec($curl);$status=(int)curl_getinfo($curl,CURLINFO_RESPONSE_CODE);$lastError=curl_error($curl);$lastErrno=curl_errno($curl);curl_close($curl);if($body!==false&&$lastError===''){if($status<200||$status>=300)throw new \RuntimeException('CinetPay a retourné le statut HTTP '.$status.'.');$data=json_decode($body,true);if(!is_array($data))throw new \RuntimeException('Réponse CinetPay invalide.');return $data;}if(!in_array($lastErrno,[CURLE_COULDNT_RESOLVE_HOST,CURLE_COULDNT_CONNECT,CURLE_OPERATION_TIMEDOUT],true))break;if($attempt<3)usleep(300000*$attempt);}
        throw new \RuntimeException('Transport CinetPay impossible (cURL '.$lastErrno.'): '.$lastError);
    }
}
