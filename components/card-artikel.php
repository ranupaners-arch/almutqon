<?php
$shareScheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$shareHost = $_SERVER['HTTP_HOST'] ?? '';
$shareUrl = $shareScheme . '://' . $shareHost . $a['link'];
?>
<article class="article-card reveal" style="transition-delay:<?= $a['delay'] ?>">
    <a href="<?= $a['link'] ?>" class="article-card__link">
        <div class="article-card__image">
            <img src="<?= $a['img'] ?>" alt="<?= $a['title'] ?>" loading="lazy">
            <span class="article-card__category"><?= $a['category'] ?></span>
        </div>
        <div class="article-card__body">
            <p class="article-card__date"><?= $a['date'] ?></p>
            <h3 class="article-card__title"><?= $a['title'] ?></h3>
            <p class="article-card__excerpt"><?= $a['excerpt'] ?></p>
            <span class="link-arrow">Baca Selengkapnya<span class="material-symbols-rounded">arrow_forward</span></span>
        </div>
    </a>
    <button type="button" class="article-card__share" data-share-url="<?= htmlspecialchars($shareUrl, ENT_QUOTES) ?>" data-share-title="<?= htmlspecialchars($a['title'] ?? '', ENT_QUOTES) ?>" aria-label="Bagikan artikel" aria-haspopup="true" aria-expanded="false">
        <span class="material-symbols-rounded" aria-hidden="true">share</span>
    </button>
</article>