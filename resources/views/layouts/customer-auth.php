<?php
$brand=$_SESSION['brand']??['name'=>'IFMAP Learning','primary'=>'#5547e8','accent'=>'#f59e0b','logo'=>null,'favicon'=>null];
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="theme-color" content="<?= htmlspecialchars($brand['primary']) ?>">
    <title><?= htmlspecialchars($title) ?> — <?= htmlspecialchars($brand['name']) ?></title>
    <?php if(!empty($brand['favicon'])||!empty($brand['logo'])): ?>
        <link rel="icon" href="<?= htmlspecialchars($brand['favicon']?:$brand['logo']) ?>">
    <?php endif ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/public/assets/css/app.css">
    <link rel="stylesheet" href="/public/assets/css/auth.css">
    <link rel="stylesheet" href="/public/assets/css/customer-auth.css">
    <style>
        :root{
            --primary:<?= htmlspecialchars($brand['primary']) ?>;
            --accent:<?= htmlspecialchars($brand['accent']) ?>;
            --site-primary:<?= htmlspecialchars($brand['primary']) ?>;
            --site-accent:<?= htmlspecialchars($brand['accent']) ?>;
        }
        .customer-auth-body{
            background:
                radial-gradient(circle at 12% 14%,color-mix(in srgb,var(--accent) 20%,transparent),transparent 28%),
                radial-gradient(circle at 88% 82%,color-mix(in srgb,var(--accent) 12%,transparent),transparent 30%),
                var(--primary)!important;
        }
        .auth-submit{background:var(--primary)!important;color:#fff!important}
        .auth-submit:hover{background:color-mix(in srgb,var(--primary) 88%,#000)!important}
        .customer-form input:focus{border-color:var(--primary)!important;box-shadow:0 0 0 3px color-mix(in srgb,var(--primary) 12%,transparent)!important}
        .customer-form .eyebrow,.auth-switch a,.back-site{color:var(--accent)!important}
        .password-field button{color:var(--primary)!important}
        .customer-logo>span{background:#fff!important;color:var(--primary)!important}
        .customer-logo>span img{width:100%;height:100%;object-fit:contain;border-radius:inherit}
        .customer-logo strong,.customer-logo small{color:#fff!important}
    </style>
</head>
<body class="auth-body customer-auth-body">
<?= $content ?>
<script>
document.querySelectorAll('[data-password]').forEach(function(button){
    button.addEventListener('click',function(){
        const input=this.previousElementSibling;
        input.type=input.type==='password'?'text':'password';
        this.textContent=input.type==='password'?'Afficher':'Masquer';
    });
});
</script>
</body>
</html>
