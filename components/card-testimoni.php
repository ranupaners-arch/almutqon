<article class="story-card">
    <div class="story-card__quote-icon">
        <span class="material-symbols-rounded">format_quote</span>
    </div>
    <p class="story-card__quote"><?= $t['quote'] ?></p>
    <div class="story-card__author">
        <img src="<?= $t['img'] ?>" alt="Foto <?= $t['name'] ?>" loading="lazy">
        <div>
            <p class="story-card__name"><?= $t['name'] ?></p>
            <p class="story-card__role"><?= $t['role'] ?></p>
        </div>
    </div>
</article>