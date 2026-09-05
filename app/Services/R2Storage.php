<?php
namespace App\Services;

use App\Core\Env;
use Aws\S3\S3Client;

final class R2Storage
{
    public function configured(): bool
    {
        foreach(['R2_ACCOUNT_ID','R2_BUCKET','R2_ACCESS_KEY_ID','R2_SECRET_ACCESS_KEY'] as $key)if(trim((string)Env::get($key,''))==='')return false;return true;
    }

    public function createVideoUpload(int $courseId,string $originalName,string $contentType,int $size): array
    {
        $allowed=['video/mp4'=>'mp4','video/webm'=>'webm','video/quicktime'=>'mov'];if(!isset($allowed[$contentType]))throw new \InvalidArgumentException('Format vidéo non autorisé. Utilisez MP4, WebM ou MOV.');if($size<1||$size>5*1024*1024*1024)throw new \InvalidArgumentException('La vidéo doit peser moins de 5 Go.');$base=pathinfo($originalName,PATHINFO_FILENAME);$base=iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$base)?:'video';$base=strtolower(trim(preg_replace('/[^a-z0-9]+/i','-',$base),'-'))?:'video';$key='courses/'.$courseId.'/'.date('Y/m').'/'.bin2hex(random_bytes(8)).'-'.mb_substr($base,0,80).'.'.$allowed[$contentType];$command=$this->client()->getCommand('PutObject',['Bucket'=>$this->bucket(),'Key'=>$key,'ContentType'=>$contentType]);$request=$this->client()->createPresignedRequest($command,'+15 minutes');return ['upload_url'=>(string)$request->getUri(),'key'=>$key,'content'=>'r2://'.$key,'expires_in'=>900];
    }

    public function playbackUrl(string $content): string
    {
        $key=str_starts_with($content,'r2://')?substr($content,5):$content;$command=$this->client()->getCommand('GetObject',['Bucket'=>$this->bucket(),'Key'=>$key,'ResponseContentType'=>'video/mp4','ResponseContentDisposition'=>'inline']);return (string)$this->client()->createPresignedRequest($command,'+4 hours')->getUri();
    }

    private function client(): S3Client
    {
        if(!$this->configured())throw new \RuntimeException('La configuration Cloudflare R2 est incomplète.');$account=(string)Env::get('R2_ACCOUNT_ID');return new S3Client(['version'=>'latest','region'=>'auto','endpoint'=>'https://'.$account.'.r2.cloudflarestorage.com','credentials'=>['key'=>(string)Env::get('R2_ACCESS_KEY_ID'),'secret'=>(string)Env::get('R2_SECRET_ACCESS_KEY')]]);
    }
    private function bucket(): string { return (string)Env::get('R2_BUCKET'); }
}
