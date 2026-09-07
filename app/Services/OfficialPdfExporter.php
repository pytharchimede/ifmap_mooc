<?php
namespace App\Services;

final class OfficialPdfExporter
{
    public static function certificate(array $certificate, array $brand, bool $specimen = false): never
    {
        self::assertTcpdf();
        $pdf = self::pdf('L');
        $pdf->AddPage('L', 'A4');

        [$primaryR,$primaryG,$primaryB] = self::rgb($brand['primary'] ?? '#5547e8');
        [$accentR,$accentG,$accentB] = self::rgb($brand['accent'] ?? '#f59e0b');
        $pageW = 297.0; $pageH = 210.0;

        self::frame($pdf, $primaryR,$primaryG,$primaryB,$accentR,$accentG,$accentB, $pageW, $pageH);
        self::brandHeader($pdf, $brand, $certificate['reference'] ?? '', $primaryR,$primaryG,$primaryB, true);

        if ($specimen) {
            $pdf->SetTextColor(190, 35, 45);
            $pdf->SetAlpha(0.13);
            $pdf->SetFont('dejavusans', 'B', 52);
            $pdf->StartTransform();
            $pdf->Rotate(25, 148.5, 105);
            $pdf->SetXY(36, 92);
            $pdf->Cell(225, 26, 'SPÉCIMEN - NON VALIDE', 2, 0, 'C');
            $pdf->StopTransform();
            $pdf->SetAlpha(1);
        }

        $pdf->SetTextColor($accentR,$accentG,$accentB);
        $pdf->SetFont('dejavusans', 'B', 9);
        $pdf->SetXY(0, 42);
        $pdf->Cell($pageW, 5, $specimen ? 'APERÇU AVANT VALIDATION' : 'CERTIFICATION OFFICIELLE', 0, 1, 'C');

        $pdf->SetTextColor($primaryR,$primaryG,$primaryB);
        $pdf->SetFont('dejavusans', 'B', 29);
        $pdf->SetXY(20, 50);
        $pdf->Cell(257, 14, 'DIPLÔME DE RÉUSSITE', 0, 1, 'C');
        $pdf->SetDrawColor($accentR,$accentG,$accentB);
        $pdf->SetLineWidth(1.0);
        $pdf->Line(136, 67, 161, 67);

        $pdf->SetTextColor(94, 105, 101);
        $pdf->SetFont('dejavusans', '', 9.5);
        $pdf->SetXY(25, 76);
        $pdf->Cell(247, 6, 'Le présent diplôme certifie que', 0, 1, 'C');

        $name = trim((string)($certificate['user_name'] ?? ''));
        $pdf->SetTextColor($primaryR,$primaryG,$primaryB);
        $pdf->SetFont('dejavuserif', 'BI', 23);
        $pdf->SetXY(32, 85);
        $pdf->Cell(233, 11, $name, 0, 1, 'C');
        $nameWidth = min(130.0, max(70.0, $pdf->GetStringWidth($name) + 18));
        $pdf->SetDrawColor($accentR,$accentG,$accentB);
        $pdf->SetLineWidth(0.35);
        $pdf->Line((297-$nameWidth)/2, 98, (297+$nameWidth)/2, 98);

        $pdf->SetTextColor(94, 105, 101);
        $pdf->SetFont('dejavusans', '', 9.5);
        $pdf->SetXY(25, 101);
        $pdf->Cell(247, 6, 'a suivi et validé avec succès la formation', 0, 1, 'C');

        $course = trim((string)($certificate['course_title'] ?? ''));
        $pdf->SetTextColor($primaryR,$primaryG,$primaryB);
        $pdf->SetFont('dejavuserif', 'B', 15.5);
        $pdf->SetXY(32, 109);
        $pdf->MultiCell(233, 13, mb_strtoupper($course, 'UTF-8'), 0, 'C', false, 1, 32, 109, true, 0, false, true, 13, 'M');

        self::certificateMeta($pdf, $certificate, $specimen, $primaryR,$primaryG,$primaryB,$accentR,$accentG,$accentB);

        $verifyCode = (string)($certificate['verification_code'] ?: ($certificate['reference'] ?? ''));
        $verifyUrl = self::verifyUrl($verifyCode);
        self::certificateBottom($pdf, $brand, $verifyCode, $verifyUrl, $specimen, $primaryR,$primaryG,$primaryB,$accentR,$accentG,$accentB);

        self::send($pdf, 'diplome-ifmap-' . date('Y-m-d') . '.pdf');
    }

    public static function attestation(array $document, array $brand): never
    {
        self::assertTcpdf();
        $pdf = self::pdf('P');
        $pdf->AddPage('P', 'A4');

        [$primaryR,$primaryG,$primaryB] = self::rgb($brand['primary'] ?? '#5547e8');
        [$accentR,$accentG,$accentB] = self::rgb($brand['accent'] ?? '#f59e0b');
        $pageW = 210.0; $pageH = 297.0;

        self::frame($pdf, $primaryR,$primaryG,$primaryB,$accentR,$accentG,$accentB, $pageW, $pageH);
        self::brandHeader($pdf, $brand, $document['reference'] ?? '', $primaryR,$primaryG,$primaryB, false);

        $pdf->SetTextColor($accentR,$accentG,$accentB);
        $pdf->SetFont('dejavusans', 'B', 8.5);
        $pdf->SetXY(0, 57);
        $pdf->Cell($pageW, 5, 'DOCUMENT OFFICIEL', 0, 1, 'C');

        $pdf->SetTextColor($primaryR,$primaryG,$primaryB);
        $pdf->SetFont('dejavusans', 'B', 21.5);
        $pdf->SetXY(18, 66);
        $pdf->MultiCell(174, 13, 'ATTESTATION DE FIN DE FORMATION', 0, 'C', false, 1, 18, 66, true, 0, false, true, 13, 'M');
        $pdf->SetDrawColor($accentR,$accentG,$accentB);
        $pdf->SetLineWidth(0.8);
        $pdf->Line(91, 84, 119, 84);

        $brandName = (string)($brand['name'] ?? 'IFMAP');
        $pdf->SetTextColor(65, 75, 72);
        $pdf->SetFont('dejavusans', '', 10.5);
        $pdf->SetXY(25, 98);
        $pdf->Cell(160, 6, 'La Direction de', 0, 1, 'C');
        $pdf->SetTextColor($primaryR,$primaryG,$primaryB);
        $pdf->SetFont('dejavusans', 'B', 13.5);
        $pdf->SetXY(25, 106);
        $pdf->Cell(160, 7, $brandName, 0, 1, 'C');
        $pdf->SetTextColor(65, 75, 72);
        $pdf->SetFont('dejavusans', '', 10.5);
        $pdf->SetXY(25, 117);
        $pdf->Cell(160, 6, 'atteste que', 0, 1, 'C');

        $pdf->SetTextColor($primaryR,$primaryG,$primaryB);
        $pdf->SetFont('dejavuserif', 'BI', 19);
        $pdf->SetXY(25, 128);
        $pdf->Cell(160, 9, (string)($document['user_name'] ?? ''), 0, 1, 'C');
        $pdf->SetTextColor(65, 75, 72);
        $pdf->SetFont('dejavusans', '', 10.5);
        $pdf->SetXY(25, 140);
        $pdf->Cell(160, 6, 'a suivi et achevé la formation', 0, 1, 'C');

        $pdf->SetTextColor($primaryR,$primaryG,$primaryB);
        $pdf->SetFont('dejavusans', 'B', 14.5);
        $pdf->SetXY(27, 150);
        $pdf->MultiCell(156, 13, mb_strtoupper((string)($document['course_title'] ?? ''), 'UTF-8'), 0, 'C', false, 1, 27, 150, true, 0, false, true, 13, 'M');

        self::attestationMeta($pdf, $document, $primaryR,$primaryG,$primaryB,$accentR,$accentG,$accentB);

        $verifyCode = (string)($document['verification_code'] ?: ($document['reference'] ?? ''));
        $verifyUrl = self::verifyUrl($verifyCode);
        self::attestationBottom($pdf, $brand, $verifyCode, $verifyUrl, $primaryR,$primaryG,$primaryB,$accentR,$accentG,$accentB);

        self::send($pdf, 'attestation-de-fin-de-formation-' . date('Y-m-d') . '.pdf');
    }

    private static function pdf(string $orientation): \TCPDF
    {
        $pdf = new \TCPDF($orientation, 'mm', 'A4', true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false, 0);
        $pdf->SetCreator('IFMAP');
        $pdf->SetAuthor('IFMAP');
        $pdf->SetTitle('Document officiel IFMAP');
        $pdf->SetSubject('Document officiel vérifiable');
        $pdf->setImageScale(1.25);
        return $pdf;
    }

    private static function frame(\TCPDF $pdf, int $pr,int $pg,int $pb,int $ar,int $ag,int $ab,float $w,float $h): void
    {
        $pdf->SetFillColor(255,255,255);
        $pdf->Rect(0,0,$w,$h,'F');
        $pdf->SetDrawColor($pr,$pg,$pb);
        $pdf->SetLineWidth(0.9);
        $pdf->Rect(6,6,$w-12,$h-12);
        $pdf->SetDrawColor($ar,$ag,$ab);
        $pdf->SetLineWidth(0.28);
        $pdf->Rect(9,9,$w-18,$h-18);
        $pdf->SetFillColor($ar,$ag,$ab);
        $pdf->Rect(0,0,34,2.6,'F');
        $pdf->SetFillColor($pr,$pg,$pb);
        $pdf->Rect($w-38,$h-2.6,38,2.6,'F');
    }

    private static function brandHeader(\TCPDF $pdf, array $brand, string $reference, int $pr,int $pg,int $pb, bool $landscape): void
    {
        $x = $landscape ? 18.0 : 16.0;
        $y = $landscape ? 16.0 : 17.0;
        if (!empty($brand['logo'])) {
            self::image($pdf, (string)$brand['logo'], $x, $y, $landscape ? 28 : 24, $landscape ? 18 : 17);
        } else {
            $pdf->SetFillColor($pr,$pg,$pb);
            $pdf->RoundedRect($x,$y,17,17,3.5,'1111','F');
            $pdf->SetTextColor(255,255,255);
            $pdf->SetFont('dejavusans','B',10);
            $pdf->SetXY($x,$y+4.6);
            $pdf->Cell(17,7,'IF',0,0,'C');
        }
        $nameX = $x + ($landscape ? 31 : 27);
        $pdf->SetTextColor($pr,$pg,$pb);
        $pdf->SetFont('dejavusans','B',$landscape ? 12 : 11);
        $pdf->SetXY($nameX,$y+1.8);
        $pdf->Cell($landscape ? 95 : 86,6,(string)($brand['name'] ?? 'IFMAP'),0,1,'L');
        $pdf->SetTextColor(105,115,111);
        $pdf->SetFont('dejavusans','',6.4);
        $pdf->SetXY($nameX,$y+8.6);
        $pdf->Cell($landscape ? 110 : 95,5,'INSTITUT DE FORMATION AUX MÉTIERS',0,1,'L');

        $pdf->SetTextColor(105,115,111);
        $pdf->SetFont('dejavusans','',6.5);
        if ($landscape) {
            $pdf->SetXY(185,18);
            $pdf->Cell(94,5,'RÉF. ' . $reference,0,1,'R');
        } else {
            $pdf->SetXY(122,19);
            $pdf->Cell(72,5,'ATTESTATION',0,1,'R');
            $pdf->SetXY(122,25);
            $pdf->Cell(72,4,$reference,0,1,'R');
        }
    }

    private static function certificateMeta(\TCPDF $pdf, array $c, bool $specimen, int $pr,int $pg,int $pb,int $ar,int $ag,int $ab): void
    {
        $items = [
            ['DURÉE DU PARCOURS', (string)($c['duration_label'] ?? 'Formation complète')],
            ['SCORE FINAL', $specimen ? 'À OBTENIR' : ((int)($c['final_score'] ?? 0) . '%')],
            ['DATE DE DÉLIVRANCE', $specimen ? 'APRÈS VALIDATION' : date('d/m/Y', strtotime((string)($c['issued_at'] ?? 'now')))],
        ];
        $startX = 45.0; $y = 132.0; $w = 64.0; $gap = 7.5;
        foreach ($items as $i => [$label,$value]) {
            $x = $startX + $i*($w+$gap);
            $pdf->SetFillColor(248,249,249);
            $pdf->SetDrawColor(224,228,226);
            $pdf->SetLineWidth(0.25);
            $pdf->RoundedRect($x,$y,$w,26,3,'1111','DF');
            $pdf->SetTextColor(126,135,132);
            $pdf->SetFont('dejavusans','B',6.8);
            $pdf->SetXY($x+3,$y+5);
            $pdf->Cell($w-6,4,$label,0,1,'C');
            $pdf->SetTextColor($pr,$pg,$pb);
            $pdf->SetFont('dejavusans','B',11.5);
            $pdf->SetXY($x+3,$y+12.5);
            $pdf->MultiCell($w-6,8,$value,0,'C',false,1,$x+3,$y+12.5,true,0,false,true,8,'M');
        }
    }

    private static function certificateBottom(\TCPDF $pdf, array $brand, string $code, string $url, bool $specimen, int $pr,int $pg,int $pb,int $ar,int $ag,int $ab): void
    {
        $pdf->SetDrawColor(226,229,228);
        $pdf->SetLineWidth(0.25);
        $pdf->Line(28,166,269,166);

        $sigX=42.0; $sigY=171.0;
        if (!$specimen && !empty($brand['signature'])) {
            self::image($pdf, (string)$brand['signature'], $sigX, $sigY, 43, 17);
        }
        $pdf->SetDrawColor($pr,$pg,$pb);
        $pdf->SetLineWidth(0.28);
        $pdf->Line(35,190,92,190);
        $pdf->SetTextColor($pr,$pg,$pb);
        $pdf->SetFont('dejavusans','B',8.5);
        $pdf->SetXY(31,191.5);
        $pdf->Cell(65,4,'Direction ' . (string)($brand['name'] ?? 'IFMAP'),0,1,'C');
        $pdf->SetTextColor(120,128,125);
        $pdf->SetFont('dejavusans','',6.2);
        $pdf->SetXY(31,196);
        $pdf->Cell(65,3.8,$specimen ? 'Signature non apposée' : 'Signature autorisée',0,1,'C');

        if ($specimen) {
            $pdf->SetTextColor(183,35,46);
            $pdf->SetFont('dejavusans','B',8.5);
            $pdf->SetXY(194,177);
            $pdf->Cell(66,7,'APERÇU NON VÉRIFIABLE',0,1,'C');
        } else {
            $style = ['border'=>0,'vpadding'=>'auto','hpadding'=>'auto','fgcolor'=>[$pr,$pg,$pb],'bgcolor'=>false,'module_width'=>1,'module_height'=>1];
            $pdf->write2DBarcode($url,'QRCODE,H',218,170,24,24,$style,'N');
            $pdf->SetTextColor($pr,$pg,$pb);
            $pdf->SetFont('dejavusans','B',7.2);
            $pdf->SetXY(180,170);
            $pdf->Cell(34,4,'DOCUMENT VÉRIFIABLE',0,1,'R');
            $pdf->SetTextColor(120,128,125);
            $pdf->SetFont('dejavusans','',5.9);
            $pdf->SetXY(166,176);
            $pdf->Cell(48,4,'Scannez le QR code ou utilisez :',0,1,'R');
            $pdf->SetTextColor($pr,$pg,$pb);
            $pdf->SetFont('dejavusans','B',7.2);
            $pdf->SetXY(159,182);
            $pdf->Cell(55,5,$code,0,1,'R');
        }

        $pdf->SetTextColor(137,145,142);
        $pdf->SetFont('dejavusans','',5.2);
        $pdf->SetXY(20,201.2);
        $footer = $specimen ? 'SPÉCIMEN SANS VALEUR OFFICIELLE' : ('Document officiel généré par ' . (string)($brand['name'] ?? 'IFMAP') . ' · ' . $url);
        $pdf->Cell(257,3.5,$footer,0,1,'C');
    }

    private static function attestationMeta(\TCPDF $pdf, array $d, int $pr,int $pg,int $pb,int $ar,int $ag,int $ab): void
    {
        $items = [
            ['FILIÈRE', (string)($d['category'] ?? '')],
            ['DURÉE', (string)($d['duration_label'] ?? '')],
            ['FORMATEUR', (string)($d['instructor_name'] ?: 'Équipe IFMAP')],
            ['DATE DE FIN', date('d/m/Y', strtotime((string)($d['issued_at'] ?? 'now')))],
        ];
        $x0=24.0; $y0=176.0; $w=78.0; $h=24.0; $gapX=6.0; $gapY=6.0;
        foreach($items as $i=>[$label,$value]){
            $col=$i%2; $row=intdiv($i,2); $x=$x0+$col*($w+$gapX); $y=$y0+$row*($h+$gapY);
            $pdf->SetFillColor(248,249,249);
            $pdf->SetDrawColor(226,229,228);
            $pdf->RoundedRect($x,$y,$w,$h,3,'1111','DF');
            $pdf->SetTextColor($ar,$ag,$ab);
            $pdf->SetFont('dejavusans','B',6.8);
            $pdf->SetXY($x+5,$y+5);
            $pdf->Cell($w-10,4,$label,0,1,'L');
            $pdf->SetTextColor($pr,$pg,$pb);
            $pdf->SetFont('dejavusans','B',9.5);
            $pdf->SetXY($x+5,$y+11.5);
            $pdf->MultiCell($w-10,7.5,$value,0,'L',false,1,$x+5,$y+11.5,true,0,false,true,7.5,'M');
        }
    }

    private static function attestationBottom(\TCPDF $pdf, array $brand, string $code, string $url, int $pr,int $pg,int $pb,int $ar,int $ag,int $ab): void
    {
        $y=241.0;
        $pdf->SetDrawColor($pr,$pg,$pb);
        $pdf->SetLineWidth(0.28);
        $pdf->Line(28,$y+16,78,$y+16);
        $pdf->SetTextColor(112,121,118);
        $pdf->SetFont('dejavusans','',6.8);
        $pdf->SetXY(28,$y+17.5);
        $pdf->Cell(50,4,'Le bénéficiaire',0,1,'C');

        if (!empty($brand['signature'])) {
            self::image($pdf, (string)$brand['signature'], 116, $y-2, 40, 16);
        }
        $pdf->SetDrawColor($pr,$pg,$pb);
        $pdf->Line(108,$y+16,164,$y+16);
        $pdf->SetTextColor($pr,$pg,$pb);
        $pdf->SetFont('dejavusans','B',7.2);
        $pdf->SetXY(108,$y+17.5);
        $pdf->Cell(56,4,'La Direction',0,1,'C');

        $style = ['border'=>0,'vpadding'=>'auto','hpadding'=>'auto','fgcolor'=>[$pr,$pg,$pb],'bgcolor'=>false,'module_width'=>1,'module_height'=>1];
        $pdf->write2DBarcode($url,'QRCODE,H',166,235,22,22,$style,'N');

        $pdf->SetTextColor(124,133,130);
        $pdf->SetFont('dejavusans','',5.9);
        $pdf->SetXY(20,274);
        $pdf->Cell(170,4,'Attestation vérifiable avec le code ' . $code,0,1,'C');
        $pdf->SetTextColor(150,156,154);
        $pdf->SetFont('dejavusans','',4.9);
        $pdf->SetXY(18,280);
        $pdf->Cell(174,3.5,$url,0,1,'C');
    }

    private static function image(\TCPDF $pdf, string $source, float $x,float $y,float $w,float $h): void
    {
        try {
            $resolved = self::imageSource($source);
            if ($resolved === null) return;
            $pdf->Image($resolved, $x, $y, $w, $h, '', '', '', true, 300, '', false, false, 0, true, false, false);
        } catch (\Throwable $e) {
            error_log('TCPDF image: ' . $e->getMessage());
        }
    }

    private static function imageSource(string $source): ?string
    {
        if ($source === '') return null;
        if (str_starts_with($source, 'data:image/')) {
            $comma = strpos($source, ',');
            if ($comma === false) return null;
            $meta = substr($source, 0, $comma);
            $payload = substr($source, $comma + 1);
            $binary = str_contains($meta, ';base64') ? base64_decode($payload, true) : urldecode($payload);
            return is_string($binary) && $binary !== '' ? '@' . $binary : null;
        }
        if (preg_match('~^https?://~i', $source)) return $source;
        $root = dirname(__DIR__, 2);
        $path = $root . '/' . ltrim($source, '/');
        return is_file($path) ? $path : null;
    }

    private static function verifyUrl(string $code): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $script = str_replace('\\','/',$_SERVER['SCRIPT_NAME'] ?? '/index.php');
        $base = rtrim(str_replace('/index.php','',$script),'/');
        return $scheme . '://' . $host . $base . '/certificats/verifier?code=' . rawurlencode($code);
    }

    private static function rgb(string $hex): array
    {
        $hex = ltrim(trim($hex), '#');
        if (!preg_match('/^[0-9a-f]{6}$/i', $hex)) $hex = '5547e8';
        return [hexdec(substr($hex,0,2)), hexdec(substr($hex,2,2)), hexdec(substr($hex,4,2))];
    }

    private static function assertTcpdf(): void
    {
        if (!class_exists(\TCPDF::class)) {
            throw new \RuntimeException('TCPDF n’est pas installé. Exécutez composer update tecnickcom/tcpdf puis composer install sur le serveur.');
        }
    }

    private static function send(\TCPDF $pdf, string $filename): never
    {
        while (ob_get_level() > 0) @ob_end_clean();
        $data = $pdf->Output($filename, 'S');
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($data));
        header('Cache-Control: private, no-store, max-age=0');
        header('X-Content-Type-Options: nosniff');
        echo $data;
        exit;
    }
}
