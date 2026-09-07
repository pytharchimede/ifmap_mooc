<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($title) ?></title>
<style>
:root{
    --doc-primary:<?= htmlspecialchars($brand['primary']??'#123f3a') ?>;
    --doc-accent:<?= htmlspecialchars($brand['accent']??'#d89b2b') ?>;
    --doc-ink:#173b35;
    --doc-muted:#6e7d78;
}
*{box-sizing:border-box}
html,body{margin:0;padding:0;font-family:DejaVu Sans,Arial,sans-serif;color:var(--doc-ink)}
body{background:#e9efec}

.document-tools{text-align:center;padding:18px}
.document-tools button,.document-tools a{
    display:inline-flex;align-items:center;justify-content:center;border:0;border-radius:9px;
    background:var(--doc-primary);color:#fff;padding:12px 18px;text-decoration:none;margin:4px;
    font-weight:700;cursor:pointer
}

.official-document{
    width:297mm;height:210mm;margin:0 auto 24px;background:#fff;position:relative;overflow:hidden;
    padding:14mm 18mm 13mm;box-shadow:0 24px 60px rgba(17,55,49,.15)
}
.document-frame{
    position:absolute;left:5mm;right:5mm;top:5mm;bottom:5mm;
    border:1.2mm solid var(--doc-primary);outline:.35mm solid var(--doc-accent);outline-offset:-3mm;
    pointer-events:none;z-index:0
}
.official-document:before,.official-document:after{
    content:'';position:absolute;background:var(--doc-accent);z-index:0
}
.official-document:before{width:42mm;height:2mm;left:0;top:0}
.official-document:after{width:42mm;height:2mm;right:0;bottom:0}

.official-document header{
    position:relative;z-index:2;display:flex;align-items:center;justify-content:space-between;
    min-height:18mm;padding:0 4mm
}
.document-brand{display:flex;align-items:center;gap:4mm;min-width:0}
.document-brand>span{
    width:17mm;height:17mm;border-radius:4mm;display:grid;place-items:center;
    background:var(--doc-primary);color:#fff;font-size:17pt;font-weight:800
}
.document-brand img{display:block;max-width:38mm;max-height:18mm;object-fit:contain}
.document-brand div{display:flex;flex-direction:column;gap:1mm}
.document-brand strong{font-size:15pt;line-height:1.05}
.document-brand small{font-size:6.5pt;letter-spacing:1.2pt;color:var(--doc-muted)}
.document-reference{font-size:7.5pt;color:var(--doc-muted);letter-spacing:.25pt;white-space:nowrap}

.document-title{position:relative;z-index:2;text-align:center;margin-top:10mm}
.document-title p{font-size:7pt;letter-spacing:3.2pt;color:var(--doc-accent);font-weight:800;margin:0 0 4mm}
.document-title h1{
    font-family:DejaVu Serif,Georgia,serif;font-size:31pt;line-height:1.05;letter-spacing:1.2pt;
    margin:0;color:var(--doc-ink);font-weight:800
}
.document-title i{display:block;width:23mm;height:1mm;margin:5mm auto 0;background:var(--doc-accent)}

.document-recipient{position:relative;z-index:2;text-align:center;margin-top:7mm}
.document-recipient p{color:var(--doc-muted);margin:2.5mm 0;font-size:8.5pt}
.document-recipient h2{
    font-family:DejaVu Serif,Georgia,serif;font-style:italic;font-size:22pt;line-height:1.1;
    margin:0;display:inline-block;border-bottom:.35mm solid var(--doc-accent);padding:0 10mm 2.5mm
}
.document-recipient h3{
    font-family:DejaVu Serif,Georgia,serif;font-size:14pt;line-height:1.2;
    margin:3mm auto 0;max-width:205mm;font-weight:700
}

.document-meta{
    position:relative;z-index:2;display:flex;justify-content:center;align-items:stretch;gap:0;
    width:195mm;margin:7mm auto 0;border-top:.25mm solid #dfe5e2;border-bottom:.25mm solid #dfe5e2
}
.document-meta div{width:65mm;text-align:center;padding:3.5mm 3mm}
.document-meta div+div{border-left:.25mm solid #dfe5e2}
.document-meta small{display:block;font-size:5.7pt;letter-spacing:.9pt;color:#7b8985;margin-bottom:1.2mm}
.document-meta strong{font-size:9.5pt;line-height:1.15}

.official-document footer{
    position:absolute;z-index:2;left:22mm;right:22mm;bottom:20mm;
    display:flex;align-items:flex-end;justify-content:space-between
}
.signature{width:73mm;display:flex;flex-direction:column;align-items:center;text-align:center;min-height:23mm;justify-content:flex-end}
.signature .digital-signature{display:block!important;width:42mm!important;height:15mm!important;object-fit:contain!important;margin:0 auto -1mm!important}
.signature i{display:block;width:52mm;border-top:.3mm solid #7d8a85;margin:0 auto 2mm}
.signature strong{font-size:8.5pt;line-height:1.2}
.signature small{font-size:6.2pt;color:#798681;margin-top:1mm}

.document-verification{display:flex;align-items:center;gap:4mm;text-align:left;max-width:92mm}
.document-verification img{width:21mm;height:21mm;display:block}
.document-verification div{display:flex;flex-direction:column;gap:.7mm}
.document-verification strong{font-size:7.2pt;letter-spacing:.3pt}
.document-verification small{font-size:5.7pt;color:#78847f;line-height:1.25}
.document-verification code{font-family:DejaVu Sans Mono,monospace;font-size:6.8pt;margin-top:1mm;color:var(--doc-ink)}

.document-bottom{
    position:absolute;z-index:2;bottom:8mm;left:20mm;right:20mm;text-align:center;
    font-size:4.8pt;line-height:1.2;color:#82908b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis
}

.specimen-notice{background:#fff}
@page{size:A4 landscape;margin:0}
@media print{
    html,body{width:297mm;height:210mm;background:#fff;overflow:hidden}
    .document-tools{display:none!important}
    .official-document{width:297mm;height:210mm;margin:0;box-shadow:none;print-color-adjust:exact;-webkit-print-color-adjust:exact}
}
@media screen and (max-width:1150px){
    body{overflow-x:auto}
    .official-document{transform-origin:top left;transform:scale(.78);margin-bottom:-42mm}
}
@media screen and (max-width:850px){
    .official-document{transform:scale(.5);margin-bottom:-104mm}
}
</style>
</head>
<body><?= $content ?></body>
</html>
