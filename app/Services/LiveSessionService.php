<?php
namespace App\Services;

final class LiveSessionService
{
    public function createRoom(int $requestId,string $label,?string $startsAt=null): array
    {
        $settings=new SecureSettings();$provider=strtolower($settings->get('live_provider','jitsi'));$slug='ifmap-mentor-'.$requestId.'-'.bin2hex(random_bytes(4));
        if($provider==='daily')return $this->daily($settings,$slug,$label,$startsAt);
        $base=rtrim($settings->get('jitsi_base_url','https://meet.jit.si'),'/');return ['provider'=>'jitsi','room_name'=>$slug,'room_url'=>$base.'/'.$slug,'room_data'=>json_encode(['label'=>$label],JSON_UNESCAPED_UNICODE)];
    }
    private function daily(SecureSettings $settings,string $slug,string $label,?string $startsAt): array
    {
        $key=$settings->get('daily_api_key');if($key==='')throw new \RuntimeException('Clé API Daily non configurée.');$exp=time()+86400;if($startsAt){$t=strtotime($startsAt);if($t)$exp=$t+7200;}$payload=['name'=>$slug,'privacy'=>'public','properties'=>['exp'=>$exp,'enable_screenshare'=>true,'enable_chat'=>true,'start_video_off'=>false,'start_audio_off'=>false]];$ch=curl_init('https://api.daily.co/v1/rooms');curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$key,'Content-Type: application/json'],CURLOPT_POSTFIELDS=>json_encode($payload),CURLOPT_TIMEOUT=>20,CURLOPT_SSL_VERIFYPEER=>true]);$body=curl_exec($ch);$code=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);curl_close($ch);$data=json_decode((string)$body,true);if($code<200||$code>=300||empty($data['url']))throw new \RuntimeException('Impossible de créer la salle Daily.');return ['provider'=>'daily','room_name'=>$data['name']??$slug,'room_url'=>$data['url'],'room_data'=>json_encode($data,JSON_UNESCAPED_SLASHES)];
    }
}
