<?php
namespace App\Services;

final class OfficialPdfExporterV2
{
    public static function certificate(array $c, array $brand, bool $specimen = false): never
    {
        self::assertTcpdf();
        $pdf = self::pdf('L', 'Diplôme de réussite IFMAP');
        $pdf->AddPage('L', 'A4');

        [$pr,$pg,$pb] = self::rgb($brand['primary'] ?? '#5547e8');
        [$ar,$ag,$ab] = self::rgb($brand['accent'] ?? '#f59e0b');
        $w = 297.0; $h = 210.0;
        $ref = (string)($c['reference'] ?? '');

        self::securityBackground($pdf, $brand, $ref, $w, $h, $pr,$pg,$pb,$ar,$ag,$ab, true);
        self::frame($pdf, $w, $h, $pr,$pg,$pb,$ar,$ag,$ab);
        self::header($pdf, $brand, $ref, $pr,$pg,$pb, true);

        if ($specimen) self::specimen($pdf, $w, $h);

        $pdf->SetTextColor($ar,$ag,$ab);
        $pdf->SetFont('dejavusans','B',8.5);
        $pdf->SetXY(0,42);
        $pdf->Cell($w,5,$specimen ? 'APERÇU AVANT VALIDATION' : 'CERTIFICATION OFFICIELLE',0,1,'C');

        $pdf->SetTextColor($pr,$pg,$pb);
        $pdf->SetFont('dejavusans','B',28);
        $pdf->SetXY(20,50);
        $pdf->Cell(257,14,'DIPLÔME DE RÉUSSITE',0,1,'C');
        $pdf->SetDrawColor($ar,$ag,$ab);
        $pdf->SetLineWidth(0.9);
        $pdf->Line(136,67,161,67);

        $pdf->SetTextColor(88,98,95);
        $pdf->SetFont('dejavusans','',9.3);
        $pdf->SetXY(25,75);
        $pdf->Cell(247,6,'Le présent diplôme certifie que',0,1,'C');

        $name = trim((string)($c['user_name'] ?? ''));
        $pdf->SetTextColor($pr,$pg,$pb);
        $pdf->SetFont('dejavuserif','BI',22.5);
        $pdf->SetXY(32,84);
        $pdf->Cell(233,11,$name,0,1,'C');
        $nameW = min(130.0,max(72.0,$pdf->GetStringWidth($name)+18));
        $pdf->SetDrawColor($ar,$ag,$ab);
        $pdf->SetLineWidth(0.32);
        $pdf->Line(($w-$nameW)/2,97,($w+$nameW)/2,97);

        $pdf->SetTextColor(88,98,95);
        $pdf->SetFont('dejavusans','',9.3);
        $pdf->SetXY(25,100);
        $pdf->Cell(247,6,'a suivi et validé avec succès la formation',0,1,'C');

        $course = mb_strtoupper(trim((string)($c['course_title'] ?? '')), 'UTF-8');
        $pdf->SetTextColor($pr,$pg,$pb);
        $pdf->SetFont('dejavuserif','B',15.3);
        $pdf->MultiCell(233,13,$course,0,'C',false,1,32,108,true,0,false,true,13,'M');

        self::certificateMeta($pdf,$c,$specimen,$pr,$pg,$pb,$ar,$ag,$ab);
        self::certificateBottom($pdf,$brand,$c,$specimen,$pr,$pg,$pb,$ar,$ag,$ab);
        self::send($pdf,'diplome-ifmap-'.date('Y-m-d').'.pdf');
    }

    public static function attestation(array $d, array $brand): never
    {
        self::assertTcpdf();
        $pdf = self::pdf('P', 'Attestation de fin de formation IFMAP');
        $pdf->AddPage('P','A4');

        [$pr,$pg,$pb] = self::rgb($brand['primary'] ?? '#5547e8');
        [$ar,$ag,$ab] = self::rgb($brand['accent'] ?? '#f59e0b');
        $w=210.0; $h=297.0;
        $ref=(string)($d['reference'] ?? '');

        self::securityBackground($pdf,$brand,$ref,$w,$h,$pr,$pg,$pb,$ar,$ag,$ab,false);
        self::frame($pdf,$w,$h,$pr,$pg,$pb,$ar,$ag,$ab);
        self::header($pdf,$brand,$ref,$pr,$pg,$pb,false);

        $pdf->SetTextColor($ar,$ag,$ab);
        $pdf->SetFont('dejavusans','B',8.3);
        $pdf->SetXY(0,57);
        $pdf->Cell($w,5,'DOCUMENT OFFICIEL',0,1,'C');

        $pdf->SetTextColor($pr,$pg,$pb);
        $pdf->SetFont('dejavusans','B',21.5);
        $pdf->MultiCell(174,13,'ATTESTATION DE FIN DE FORMATION',0,'C',false,1,18,66,true,0,false,true,13,'M');
        $pdf->SetDrawColor($ar,$ag,$ab);
        $pdf->SetLineWidth(0.75);
        $pdf->Line(91,84,119,84);

        $brandName=(string)($brand['name'] ?? 'IFMAP');
        $pdf->SetTextColor(64,74,71);
        $pdf->SetFont('dejavusans','',10.3);
        $pdf->SetXY(25,97); $pdf->Cell(160,6,'La Direction de',0,1,'C');
        $pdf->SetTextColor($pr,$pg,$pb);
        $pdf->SetFont('dejavusans','B',13.5);
        $pdf->SetXY(25,105); $pdf->Cell(160,7,$brandName,0,1,'C');
        $pdf->SetTextColor(64,74,71);
        $pdf->SetFont('dejavusans','',10.3);
        $pdf->SetXY(25,116); $pdf->Cell(160,6,'atteste que',0,1,'C');

        $pdf->SetTextColor($pr,$pg,$pb);
        $pdf->SetFont('dejavuserif','BI',19);
        $pdf->SetXY(25,127); $pdf->Cell(160,9,(string)($d['user_name'] ?? ''),0,1,'C');
        $pdf->SetTextColor(64,74,71);
        $pdf->SetFont('dejavusans','',10.3);
        $pdf->SetXY(25,139); $pdf->Cell(160,6,'a suivi et achevé la formation',0,1,'C');

        $pdf->SetTextColor($pr,$pg,$pb);
        $pdf->SetFont('dejavusans','B',14.3);
        $pdf->MultiCell(156,13,mb_strtoupper((string)($d['course_title'] ?? ''),'UTF-8'),0,'C',false,1,27,149,true,0,false,true,13,'M');

        self::attestationMeta($pdf,$d,$pr,$pg,$pb,$ar,$ag,$ab);
        self::attestationBottom($pdf,$brand,$d,$pr,$pg,$pb,$ar,$ag,$ab);
        self::send($pdf,'attestation-de-fin-de-formation-'.date('Y-m-d').'.pdf');
    }

    private static function pdf(string $orientation,string $title): \TCPDF
    {
        $pdf=new \TCPDF($orientation,'mm','A4',true,'UTF-8',false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(0,0,0);
        $pdf->SetAutoPageBreak(false,0);
        $pdf->SetCreator('IFMAP');
        $pdf->SetAuthor('IFMAP');
        $pdf->SetTitle($title);
        $pdf->SetSubject('Document officiel IFMAP vérifiable');
        $pdf->SetKeywords('IFMAP, document officiel, diplôme, attestation, vérification');
        $pdf->setImageScale(1.25);
        return $pdf;
    }

    private static function securityBackground(\TCPDF $pdf,array $brand,string $ref,float $w,float $h,int $pr,int $pg,int $pb,int $ar,int $ag,int $ab,bool $landscape): void
    {
        // Warm security paper instead of a clinically white sheet.
        $pdf->SetFillColor(254,253,249);
        $pdf->Rect(0,0,$w,$h,'F');

        // Fine guilloche-like concentric ellipses.
        $pdf->SetAlpha(0.055);
        for($i=0;$i<8;$i++){
            $pdf->SetDrawColor($i%2===0?$pr:$ar,$i%2===0?$pg:$ag,$i%2===0?$pb:$ab);
            $pdf->SetLineWidth(0.18);
            $ew=($landscape?195:135)-($i*8);
            $eh=($landscape?118:180)-($i*9);
            if($ew>20 && $eh>20) $pdf->Ellipse($w/2,$h/2,$ew/2,$eh/2,0,0,360,'D');
        }
        $pdf->SetAlpha(1);

        // Large institutional watermark based on the uploaded logo.
        if(!empty($brand['logo'])){
            $pdf->SetAlpha(0.045);
            self::imageFit($pdf,(string)$brand['logo'],$w/2-38,$h/2-32,76,64);
            $pdf->SetAlpha(1);
        }

        // Security micro-lines crossing the document.
        $pdf->SetAlpha(0.035);
        $pdf->SetDrawColor($pr,$pg,$pb);
        $pdf->SetLineWidth(0.12);
        $step=$landscape?11:10;
        for($x=-$h;$x<$w;$x+=$step){
            $pdf->Line($x,0,$x+$h,$h);
        }
        $pdf->SetDrawColor($ar,$ag,$ab);
        for($x=0;$x<$w+$h;$x+=$step){
            $pdf->Line($x,0,$x-$h,$h);
        }
        $pdf->SetAlpha(1);

        // Microtext bands — visible under close inspection but unobtrusive.
        $micro='IFMAP  •  DOCUMENT OFFICIEL  •  AUTHENTIQUE  •  '.($ref?:'VÉRIFIABLE').'  •  ';
        $pdf->SetAlpha(0.10);
        $pdf->SetTextColor($pr,$pg,$pb);
        $pdf->SetFont('dejavusans','',3.3);
        $band=str_repeat($micro,7);
        $pdf->SetXY(12,11.2); $pdf->Cell($w-24,3,$band,0,0,'C',false,'',1);
        $pdf->SetXY(12,$h-14.2); $pdf->Cell($w-24,3,$band,0,0,'C',false,'',1);
        $pdf->SetAlpha(1);

        // Corner rosettes inspired by security printing.
        self::rosette($pdf,18,18,12,$pr,$pg,$pb,$ar,$ag,$ab);
        self::rosette($pdf,$w-18,$h-18,12,$pr,$pg,$pb,$ar,$ag,$ab);

        // Reference embedded into the paper itself.
        if($ref!==''){
            $pdf->SetAlpha(0.08);
            $pdf->SetTextColor($pr,$pg,$pb);
            $pdf->SetFont('dejavusans','B',$landscape?13:10);
            $pdf->StartTransform();
            $pdf->Rotate(90,$w-12,$h/2);
            $pdf->SetXY($w-28,$h/2-4);
            $pdf->Cell(32,5,$ref,0,0,'C');
            $pdf->StopTransform();
            $pdf->SetAlpha(1);
        }
    }

    private static function rosette(\TCPDF $pdf,float $cx,float $cy,float $r,int $pr,int $pg,int $pb,int $ar,int $ag,int $ab): void
    {
        $pdf->SetAlpha(0.08);
        for($i=0;$i<7;$i++){
            $angle=$i*(180/7);
            $pdf->SetDrawColor($i%2===0?$pr:$ar,$i%2===0?$pg:$ag,$i%2===0?$pb:$ab);
            $pdf->StartTransform();
            $pdf->Rotate($angle,$cx,$cy);
            $pdf->Ellipse($cx,$cy,$r,$r*0.34,0,0,360,'D');
            $pdf->StopTransform();
        }
        $pdf->SetAlpha(1);
    }

    private static function frame(\TCPDF $pdf,float $w,float $h,int $pr,int $pg,int $pb,int $ar,int $ag,int $ab): void
    {
        $pdf->SetDrawColor($pr,$pg,$pb); $pdf->SetLineWidth(0.9); $pdf->Rect(6,6,$w-12,$h-12);
        $pdf->SetDrawColor($ar,$ag,$ab); $pdf->SetLineWidth(0.28); $pdf->Rect(9,9,$w-18,$h-18);
        $pdf->SetFillColor($ar,$ag,$ab); $pdf->Rect(0,0,34,2.6,'F');
        $pdf->SetFillColor($pr,$pg,$pb); $pdf->Rect($w-38,$h-2.6,38,2.6,'F');
    }

    private static function header(\TCPDF $pdf,array $brand,string $ref,int $pr,int $pg,int $pb,bool $landscape): void
    {
        $x=$landscape?18.0:16.0; $y=$landscape?16.0:17.0;
        if(!empty($brand['logo'])) self::imageFit($pdf,(string)$brand['logo'],$x,$y,$landscape?27:24,$landscape?18:17);
        else{
            $pdf->SetFillColor($pr,$pg,$pb); $pdf->RoundedRect($x,$y,17,17,3.5,'1111','F');
            $pdf->SetTextColor(255,255,255); $pdf->SetFont('dejavusans','B',10); $pdf->SetXY($x,$y+4.6); $pdf->Cell(17,7,'IF',0,0,'C');
        }
        $nameX=$x+($landscape?31:27);
        $pdf->SetTextColor($pr,$pg,$pb); $pdf->SetFont('dejavusans','B',$landscape?12:11); $pdf->SetXY($nameX,$y+1.8);
        $pdf->Cell($landscape?95:86,6,(string)($brand['name']??'IFMAP'),0,1,'L');
        $pdf->SetTextColor(105,115,111); $pdf->SetFont('dejavusans','',6.2); $pdf->SetXY($nameX,$y+8.5);
        $pdf->Cell($landscape?110:95,5,'INSTITUT DE FORMATION AUX MÉTIERS',0,1,'L');
        $pdf->SetFont('dejavusans','',6.3);
        if($landscape){$pdf->SetXY(185,18);$pdf->Cell(94,5,'RÉF. '.$ref,0,1,'R');}
        else{$pdf->SetXY(122,19);$pdf->Cell(72,5,'ATTESTATION',0,1,'R');$pdf->SetXY(122,25);$pdf->Cell(72,4,$ref,0,1,'R');}
    }

    private static function certificateMeta(\TCPDF $pdf,array $c,bool $specimen,int $pr,int $pg,int $pb,int $ar,int $ag,int $ab): void
    {
        $items=[['DURÉE DU PARCOURS',(string)($c['duration_label']??'Formation complète')],['SCORE FINAL',$specimen?'À OBTENIR':((int)($c['final_score']??0).'%')],['DATE DE DÉLIVRANCE',$specimen?'APRÈS VALIDATION':date('d/m/Y',strtotime((string)($c['issued_at']??'now')))]];
        $startX=45.0;$y=132.0;$bw=64.0;$gap=7.5;
        foreach($items as $i=>[$label,$value]){
            $x=$startX+$i*($bw+$gap);
            $pdf->SetFillColor(250,250,247); $pdf->SetDrawColor(223,226,224); $pdf->SetLineWidth(0.22); $pdf->RoundedRect($x,$y,$bw,26,3,'1111','DF');
            $pdf->SetTextColor(124,132,129);$pdf->SetFont('dejavusans','B',6.6);$pdf->SetXY($x+3,$y+5);$pdf->Cell($bw-6,4,$label,0,1,'C');
            $pdf->SetTextColor($pr,$pg,$pb);$pdf->SetFont('dejavusans','B',11.3);$pdf->MultiCell($bw-6,8,$value,0,'C',false,1,$x+3,$y+12.5,true,0,false,true,8,'M');
        }
    }

    private static function certificateBottom(\TCPDF $pdf,array $brand,array $c,bool $specimen,int $pr,int $pg,int $pb,int $ar,int $ag,int $ab): void
    {
        $pdf->SetDrawColor(224,227,225);$pdf->SetLineWidth(0.22);$pdf->Line(28,166,269,166);
        if(!$specimen&&!empty($brand['signature'])) self::signature($pdf,(string)$brand['signature'],35,166.5,62,23);
        $pdf->SetDrawColor($pr,$pg,$pb);$pdf->SetLineWidth(0.28);$pdf->Line(35,190,97,190);
        $pdf->SetTextColor($pr,$pg,$pb);$pdf->SetFont('dejavusans','B',8.3);$pdf->SetXY(33,191.3);$pdf->Cell(66,4,'Direction '.(string)($brand['name']??'IFMAP'),0,1,'C');
        $pdf->SetTextColor(120,128,125);$pdf->SetFont('dejavusans','',5.9);$pdf->SetXY(33,195.7);$pdf->Cell(66,3.5,$specimen?'Signature non apposée':'Signature officielle',0,1,'C');

        $code=(string)($c['verification_code']?:($c['reference']??''));$url=self::verifyUrl($code);
        if($specimen){$pdf->SetTextColor(183,35,46);$pdf->SetFont('dejavusans','B',8.5);$pdf->SetXY(194,177);$pdf->Cell(66,7,'APERÇU NON VÉRIFIABLE',0,1,'C');}
        else{
            $style=['border'=>0,'vpadding'=>'auto','hpadding'=>'auto','fgcolor'=>[$pr,$pg,$pb],'bgcolor'=>false,'module_width'=>1,'module_height'=>1];
            $pdf->write2DBarcode($url,'QRCODE,H',218,170,24,24,$style,'N');
            $pdf->SetTextColor($pr,$pg,$pb);$pdf->SetFont('dejavusans','B',7);$pdf->SetXY(180,170);$pdf->Cell(34,4,'DOCUMENT VÉRIFIABLE',0,1,'R');
            $pdf->SetTextColor(118,126,123);$pdf->SetFont('dejavusans','',5.7);$pdf->SetXY(166,176);$pdf->Cell(48,4,'Scannez le QR code ou utilisez :',0,1,'R');
            $pdf->SetTextColor($pr,$pg,$pb);$pdf->SetFont('dejavusans','B',7);$pdf->SetXY(159,182);$pdf->Cell(55,5,$code,0,1,'R');
        }
        $pdf->SetTextColor(137,145,142);$pdf->SetFont('dejavusans','',5.0);$pdf->SetXY(20,201.1);
        $pdf->Cell(257,3.5,$specimen?'SPÉCIMEN SANS VALEUR OFFICIELLE':'Document officiel généré par '.(string)($brand['name']??'IFMAP').' · '.$url,0,1,'C');
    }

    private static function attestationMeta(\TCPDF $pdf,array $d,int $pr,int $pg,int $pb,int $ar,int $ag,int $ab): void
    {
        $items=[['FILIÈRE',(string)($d['category']??'')],['DURÉE',(string)($d['duration_label']??'')],['FORMATEUR',(string)($d['instructor_name']?:'Équipe IFMAP')],['DATE DE FIN',date('d/m/Y',strtotime((string)($d['issued_at']??'now')))]];
        $x0=24.0;$y0=175.0;$bw=78.0;$bh=24.0;$gapX=6.0;$gapY=6.0;
        foreach($items as $i=>[$label,$value]){$col=$i%2;$row=intdiv($i,2);$x=$x0+$col*($bw+$gapX);$y=$y0+$row*($bh+$gapY);
            $pdf->SetFillColor(250,250,247);$pdf->SetDrawColor(224,227,225);$pdf->RoundedRect($x,$y,$bw,$bh,3,'1111','DF');
            $pdf->SetTextColor($ar,$ag,$ab);$pdf->SetFont('dejavusans','B',6.6);$pdf->SetXY($x+5,$y+5);$pdf->Cell($bw-10,4,$label,0,1,'L');
            $pdf->SetTextColor($pr,$pg,$pb);$pdf->SetFont('dejavusans','B',9.3);$pdf->MultiCell($bw-10,7.5,$value,0,'L',false,1,$x+5,$y+11.5,true,0,false,true,7.5,'M');}
    }

    private static function attestationBottom(\TCPDF $pdf,array $brand,array $d,int $pr,int $pg,int $pb,int $ar,int $ag,int $ab): void
    {
        $y=240.0;
        $pdf->SetDrawColor($pr,$pg,$pb);$pdf->SetLineWidth(0.28);$pdf->Line(27,$y+18,78,$y+18);
        $pdf->SetTextColor(112,121,118);$pdf->SetFont('dejavusans','',6.6);$pdf->SetXY(27,$y+19.3);$pdf->Cell(51,4,'Le bénéficiaire',0,1,'C');
        if(!empty($brand['signature'])) self::signature($pdf,(string)$brand['signature'],105,$y-6,60,24);
        $pdf->SetDrawColor($pr,$pg,$pb);$pdf->Line(106,$y+18,166,$y+18);
        $pdf->SetTextColor($pr,$pg,$pb);$pdf->SetFont('dejavusans','B',7.2);$pdf->SetXY(106,$y+19.3);$pdf->Cell(60,4,'La Direction',0,1,'C');

        $code=(string)($d['verification_code']?:($d['reference']??''));$url=self::verifyUrl($code);
        $style=['border'=>0,'vpadding'=>'auto','hpadding'=>'auto','fgcolor'=>[$pr,$pg,$pb],'bgcolor'=>false,'module_width'=>1,'module_height'=>1];
        $pdf->write2DBarcode($url,'QRCODE,H',169,236,21,21,$style,'N');
        $pdf->SetTextColor(124,133,130);$pdf->SetFont('dejavusans','',5.7);$pdf->SetXY(20,274);$pdf->Cell(170,4,'Attestation vérifiable avec le code '.$code,0,1,'C');
        $pdf->SetTextColor(150,156,154);$pdf->SetFont('dejavusans','',4.7);$pdf->SetXY(18,280);$pdf->Cell(174,3.5,$url,0,1,'C');
    }

    private static function signature(\TCPDF $pdf,string $source,float $x,float $y,float $maxW,float $maxH): void
    {
        $info=self::imageInfo($source); if($info===null)return;
        [$resolved,$pxW,$pxH]=$info; if($pxW<=0||$pxH<=0)return;
        $ratio=$pxW/$pxH;
        $w=$maxW; $h=$w/$ratio;
        if($h>$maxH){$h=$maxH;$w=$h*$ratio;}
        // Center inside the requested handwritten-signature area, never distort.
        $drawX=$x+($maxW-$w)/2; $drawY=$y+($maxH-$h)/2;
        try{$pdf->Image($resolved,$drawX,$drawY,$w,$h,'','','',true,300,'',false,false,0,true,false,false);}catch(\Throwable $e){error_log('TCPDF signature: '.$e->getMessage());}
    }

    private static function imageFit(\TCPDF $pdf,string $source,float $x,float $y,float $maxW,float $maxH): void
    {
        $info=self::imageInfo($source);if($info===null)return;[$resolved,$pxW,$pxH]=$info;if($pxW<=0||$pxH<=0)return;
        $ratio=$pxW/$pxH;$w=$maxW;$h=$w/$ratio;if($h>$maxH){$h=$maxH;$w=$h*$ratio;}
        try{$pdf->Image($resolved,$x+($maxW-$w)/2,$y+($maxH-$h)/2,$w,$h,'','','',true,300,'',false,false,0,true,false,false);}catch(\Throwable $e){error_log('TCPDF image: '.$e->getMessage());}
    }

    private static function imageInfo(string $source): ?array
    {
        if($source==='')return null;
        try{
            if(str_starts_with($source,'data:image/')){$comma=strpos($source,',');if($comma===false)return null;$meta=substr($source,0,$comma);$payload=substr($source,$comma+1);$bin=str_contains($meta,';base64')?base64_decode($payload,true):urldecode($payload);if(!is_string($bin)||$bin==='')return null;$size=@getimagesizefromstring($bin);if(!$size)return null;return ['@'.$bin,(int)$size[0],(int)$size[1]];}
            if(preg_match('~^https?://~i',$source)){return [$source,1,1];}
            $path=dirname(__DIR__,2).'/'.ltrim($source,'/');if(!is_file($path))return null;$size=@getimagesize($path);if(!$size)return null;return [$path,(int)$size[0],(int)$size[1]];
        }catch(\Throwable $e){error_log('TCPDF image info: '.$e->getMessage());return null;}
    }

    private static function specimen(\TCPDF $pdf,float $w,float $h): void
    {
        $pdf->SetTextColor(190,35,45);$pdf->SetAlpha(0.13);$pdf->SetFont('dejavusans','B',50);$pdf->StartTransform();$pdf->Rotate(25,$w/2,$h/2);$pdf->SetXY(36,92);$pdf->Cell(225,26,'SPÉCIMEN - NON VALIDE',0,0,'C');$pdf->StopTransform();$pdf->SetAlpha(1);
    }

    private static function verifyUrl(string $code): string
    {
        $scheme=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')?'https':'http';$host=$_SERVER['HTTP_HOST']??'localhost';$script=str_replace('\\','/',$_SERVER['SCRIPT_NAME']??'/index.php');$base=rtrim(str_replace('/index.php','',$script),'/');return $scheme.'://'.$host.$base.'/certificats/verifier?code='.rawurlencode($code);
    }

    private static function rgb(string $hex): array
    {
        $hex=ltrim(trim($hex),'#');if(!preg_match('/^[0-9a-f]{6}$/i',$hex))$hex='5547e8';return [hexdec(substr($hex,0,2)),hexdec(substr($hex,2,2)),hexdec(substr($hex,4,2))];
    }

    private static function assertTcpdf(): void
    {
        if(!class_exists(\TCPDF::class))throw new \RuntimeException('TCPDF n’est pas installé. Exécutez composer install sur le serveur.');
    }

    private static function send(\TCPDF $pdf,string $filename): never
    {
        while(ob_get_level()>0)@ob_end_clean();$data=$pdf->Output($filename,'S');header('Content-Type: application/pdf');header('Content-Disposition: attachment; filename="'.$filename.'"');header('Content-Length: '.strlen($data));header('Cache-Control: private, no-store, max-age=0');header('X-Content-Type-Options: nosniff');echo $data;exit;
    }
}
