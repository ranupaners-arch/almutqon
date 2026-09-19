<article class="program-card">
    <div class="program-card__icon">
        <span class="material-symbols-rounded"><?= $p['icon'] ?></span>
    </div>
    <h3 class="program-card__title"><?= $p['title'] ?></h3>
    <p class="program-card__desc"><?= $p['desc'] ?></p>
    <div class="program-card__footer">
        <a href="<?= $p['link'] ?>" class="link-arrow">
            <?= $p['btn_text'] ?><span class="material-symbols-rounded">arrow_forward</span>
        </a>
    </div>
</article>