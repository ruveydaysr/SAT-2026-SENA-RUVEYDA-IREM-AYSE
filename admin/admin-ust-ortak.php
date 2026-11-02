<?php
require_once __DIR__ . '/admin-dil.php';

function admin_ortak_logo()
{
    ?>
    <a class="aris-brand" href="../index.php"
       onclick="return confirm(<?= admin_h(admin_json('Siteye gitmek istediğinize emin misiniz?')) ?>);">
        <img src="../images/aris-logoo.png" width="54" height="54"
             alt="<?= admin_h(admin_t('Aris Pati Yuvası logosu')) ?>">
        <span class="aris-brand-text">
            <strong>Aris Pati Yuvası</strong>
            <small><?= admin_h(admin_t('Her can dostuna bir yuva')) ?></small>
        </span>
    </a>
    <?php
}

function admin_ortak_diller()
{
    global $admin_dil;
    ?>
    <nav class="aris-language" aria-label="Language / Dil">
        <?php foreach (['tr' => 'TR', 'en' => 'EN'] as $code => $label): ?>
            <a href="<?= admin_h(admin_dil_linki($code)) ?>"
               lang="<?= $code ?>" hreflang="<?= $code ?>"
               aria-label="<?= $code === 'tr' ? 'Türkçe' : 'English' ?>"
               <?= $admin_dil === $code ? 'aria-current="true"' : '' ?>><?= $label ?></a>
        <?php endforeach; ?>
    </nav>
    <?php
}
