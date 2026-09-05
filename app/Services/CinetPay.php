<?php
namespace App\Services;

use App\Core\Env;

final class CinetPay
{
    private const PAYMENT_URL='https://api-checkout.cinetpay.com/v2/payment';
    private const CHECK_URL='https://api-checkout.cinetpay.com/v2/payment/check';

    public function configured(): bool
    {
        return trim((string)Env::get('CINETPAY_API_KEY'))!==''&&trim((string)Env::get('CINETPAY_SITE_ID'))!=='';
    }

    public function initialize(array $order,string $returnUrl,string $notifyUrl): array
    {
        $customer=json_decode((string)($order['customer_data']??'{}'),true)?:[];$names=preg_split('/\s+/',trim((string)($customer['name']??'')),2)?:[];
        $transactionId=$this->transactionId($order);$response=$this->post(self::PAYMENT_URL,['apikey'=>(string)Env::get('CINETPAY_API_KEY'),'site_id'=>(string)Env::get('CINETPAY_SITE_ID'),'transaction_id'=>$transactionId,'amount'=>(int)$order['total'],'currency'=>'XOF','description'=>'Commande IFMAP '.$order['reference'],'notify_url'=>$notifyUrl,'return_url'=>$returnUrl,'channels'=>'ALL','lang'=>'fr','metadata'=>(string)$order['id'],'invoice_data'=>['Commande'=>$order['reference'],'Plateforme'=>'IFMAP MOOC'],'customer_name'=>$names[0]??'Client','customer_surname'=>$names[1]??'IFMAP','customer_email'=>(string)($customer['email']??''),'customer_phone_number'=>(string)($customer['phone']??''),'customer_address'=>(string)($customer['address']??'Abidjan'),'customer_city'=>'Abidjan','customer_country'=>'CI','customer_state'=>'CI','customer_zip_code'=>'00225']);$response['data']['merchant_transaction_id']=$transactionId;return $response;
    }

    public function check(string $transactionId): array
    {
        return $this->post(self::CHECK_URL,['apikey'=>(string)Env::get('CINETPAY_API_KEY'),'site_id'=>(string)Env::get('CINETPAY_SITE_ID'),'transaction_id'=>$transactionId]);
    }

    public function validNotificationHmac(array $payload,?string $receivedToken): bool
    {
        $secret=(string)Env::get('CINETPAY_SECRET_KEY');if($secret===''||$receivedToken===null||$receivedToken==='')return false;return hash_equals(strtolower($receivedToken),strtolower(hash_hmac('sha256',implode('',$payload),$secret)));
    }

    private function transactionId(array $order): string
    {
        $created=preg_replace('/\D/','',(string)($order['created_at']??''));return substr(str_pad($created,14,'0'),0,14).str_pad((string)(int)($order['id']??0),6,'0',STR_PAD_LEFT);
    }

    private function post(string $url,array $payload): array
    {
        $lastError='';$lastErrno=0;for($attempt=1;$attempt<=3;$attempt++){$curl=curl_init($url);if($curl===false)throw new \RuntimeException('Initialisation réseau impossible.');curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_HTTPHEADER=>['Content-Type: application/json','Accept: application/json','User-Agent: IFMAP-MOOC/1.0'],CURLOPT_POSTFIELDS=>json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_TIMEOUT=>35,CURLOPT_ENCODING=>'',CURLOPT_FOLLOWLOCATION=>true,CURLOPT_MAXREDIRS=>5,CURLOPT_HTTP_VERSION=>CURL_HTTP_VERSION_1_1,CURLOPT_SSL_VERIFYPEER=>true]);$body=curl_exec($curl);$status=(int)curl_getinfo($curl,CURLINFO_RESPONSE_CODE);$lastError=curl_error($curl);$lastErrno=curl_errno($curl);curl_close($curl);if($body!==false&&$lastError===''){$data=json_decode($body,true);if($status<200||$status>=300)throw new \RuntimeException('CinetPay a retourné le statut HTTP '.$status.'.');if(!is_array($data))throw new \RuntimeException('Réponse CinetPay invalide.');return $data;}if(!in_array($lastErrno,[CURLE_COULDNT_RESOLVE_HOST,CURLE_COULDNT_CONNECT,CURLE_OPERATION_TIMEDOUT],true))break;if($attempt<3)usleep(300000*$attempt);}throw new \RuntimeException('Transport CinetPay impossible (cURL '.$lastErrno.'): '.$lastError);
    }
}
