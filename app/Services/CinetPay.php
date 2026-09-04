<?php
namespace App\Services;

use App\Core\Env;
use CinetPay\CinetPay as CinetPaySdk;
use CinetPay\Country;
use CinetPay\Currency;
use CinetPay\Language;
use CinetPay\Request\CreatePaymentRequest;

final class CinetPay
{
    public function configured(): bool
    {
        return trim((string)Env::get('CINETPAY_API_KEY'))!==''&&trim((string)Env::get('CINETPAY_API_PASSWORD'))!=='';
    }

    public function initialize(array $order,string $returnUrl,string $notifyUrl): array
    {
        $customer=json_decode((string)($order['customer_data']??'{}'),true)?:[];$names=preg_split('/\s+/',trim((string)($customer['name']??'')),2)?:[];$first=mb_substr(trim((string)($names[0]??'Client')),0,255);$last=mb_substr(trim((string)($names[1]??'IFMAP')),0,255);
        $payment=$this->client()->payments()->create(new CreatePaymentRequest(currency:Currency::XOF,merchantTransactionId:mb_substr((string)$order['reference'],0,30),amount:(int)$order['total'],successUrl:$returnUrl,failedUrl:$returnUrl,notifyUrl:$notifyUrl,language:Language::French,designation:'Commande IFMAP '.(string)$order['reference'],clientFirstName:mb_strlen($first)>=2?$first:'Client',clientLastName:mb_strlen($last)>=2?$last:'IFMAP',clientEmail:(string)($customer['email']??''),clientPhoneNumber:$this->phone((string)($customer['phone']??'')),extra:['metadata'=>(string)$order['id']]));
        return ['code'=>'201','message'=>$payment->status,'data'=>['payment_url'=>$payment->paymentUrl,'payment_token'=>$payment->paymentToken,'notify_token'=>$payment->notifyToken,'transaction_id'=>$payment->transactionId,'merchant_transaction_id'=>$payment->merchantTransactionId],'raw'=>$payment->raw];
    }

    public function check(string $transactionId): array
    {
        $payment=$this->client()->payments()->find($transactionId);$raw=$payment->raw;$amount=$raw['amount']??($raw['details']['amount']??null);$currency=$raw['currency']??($raw['details']['currency']??null);
        return ['code'=>(string)$payment->code,'message'=>$payment->status,'data'=>array_filter(['status'=>$payment->status,'amount'=>$amount,'currency'=>$currency,'merchant_transaction_id'=>$payment->merchantTransactionId,'transaction_id'=>$payment->transactionId,'operator_id'=>$raw['operator_id']??null,'payment_method'=>$raw['payment_method']??null],static fn($value)=>$value!==null),'raw'=>$raw];
    }

    private function client(): CinetPaySdk
    {
        $factory=strtoupper((string)Env::get('CINETPAY_MODE','PRODUCTION'))==='PRODUCTION'?'production':'sandbox';return CinetPaySdk::$factory((string)Env::get('CINETPAY_API_KEY'),(string)Env::get('CINETPAY_API_PASSWORD'),Country::IvoryCoast);
    }

    private function phone(string $phone): ?string
    {
        $digits=preg_replace('/\D+/','',$phone)?:'';if(str_starts_with($digits,'00225'))$digits=substr($digits,5);elseif(str_starts_with($digits,'225'))$digits=substr($digits,3);$digits=ltrim($digits,'0');return strlen($digits)>=8?'+225'.$digits:null;
    }
}
