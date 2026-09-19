<?php
require_once __DIR__ . '/../../config/database.php'; 
include __DIR__ . '/../../layouts/header.php'; 

// ---------- Filter kategori & pagination ----------
$perPage = 6;

// Slug -> kategori asli. Field kategori bebas (free-text di admin), dislug-kan lowercase+dash.
$cats = [];
foreach ($pdo->query("SELECT kategori, COUNT(*) c FROM articles WHERE kategori <> '' AND kategori IS NOT NULL GROUP BY kategori ORDER BY kategori")->fetchAll() as $c) {
    $raw = trim($c['kategori']);
    if ($raw === '') continue;
    $cats[mb_strtolower(str_replace(' ', '-', $raw), 'UTF-8')] = ['name' => $raw, 'count' => (int) $c['c']];
}

// Slug aktif dari URL; hanya valid bila dikenali, selain itu dianggap "Semua".
$rawSlug = trim((string) ($_GET['kategori'] ?? ''));
$activeSlug = ($rawSlug !== '' && isset($cats[$rawSlug])) ? $rawSlug : null;
$activeKategori = $activeSlug !== null ? $cats[$activeSlug]['name'] : null;

// Nomor halaman: non-angka / <1 -> 1.
$page = max(1, (int) ($_GET['page'] ?? 1));

// Total artikel (terfilter) untuk pagination
$sqlCount = 'SELECT COUNT(*) FROM articles';
$params = [];
if ($activeKategori !== null) {
    $sqlCount .= ' WHERE kategori = ?';
    $params[] = $activeKategori;
}
$stmtCount = $pdo->prepare($sqlCount);
$stmtCount->execute($params);
$total = (int) $stmtCount->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));

// Clamp bila halaman di luar rentang
$page = min($page, $totalPages);

// Artikel halaman aktif
$sqlData = 'SELECT * FROM articles';
$params = [];
if ($activeKategori !== null) {
    $sqlData .= ' WHERE kategori = ?';
    $params[] = $activeKategori;
}
$sqlData .= ' ORDER BY id DESC LIMIT ' . $perPage . ' OFFSET ' . (($page - 1) * $perPage);
$stmtData = $pdo->prepare($sqlData);
$stmtData->execute($params);
$articles = $stmtData->fetchAll();

// Penyusun URL filter/pagination bersih.
// $s: slug kategori atau null untuk "Semua"; $p: nomor halaman atau null untuk meniadakan ?page
function artikel_filter_url(?string $s, ?int $p = null): string
{
    $qs = [];
    if ($s !== null && $s !== '') $qs['kategori'] = $s;
    if ($p !== null && $p > 1) $qs['page'] = $p;
    return empty($qs) ? '?' : '?' . http_build_query($qs);
}

function artikel_pagination_link(?string $s, int $p): string
{
    return artikel_filter_url($s, $p);
}
?>

<!-- HERO (Sama seperti halaman lain) -->
<section class="page-hero">
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <ol class="breadcrumbs__list">
                <li class="breadcrumbs__item"><a href="/" class="breadcrumbs__link">Beranda</a></li>
                <li class="breadcrumbs__item"><span class="breadcrumbs__current" aria-current="page">Artikel</span></li>
            </ol>
        </nav>
        <span class="section-label">Artikel & Kabar</span>
        <h1 class="page-hero__title">Catatan Perjalanan Almutqon</h1>
        <p class="page-hero__desc">Dari sebuah gagasan kecil, menuju langkah yang lebih besar.</p>
    </div>
</section>

<main>
    <section class="section" style="padding-top: 60px; padding-bottom: 80px;">
        <div class="container">

            <?php if (count($cats) > 0): ?>
            <!-- Filter kategori -->
            <div class="articles__filters" role="group" aria-label="Filter kategori artikel" style="display:flex; flex-wrap:wrap; gap:10px; margin-bottom:32px;">
                <a href="<?= htmlspecialchars(artikel_filter_url(null)) ?>" class="btn btn-sm <?= $activeKategori === null ? 'btn-primary' : 'btn-outline' ?>">
                    Semua (<?= (int) $pdo->query('SELECT COUNT(*) FROM articles')->fetchColumn() ?>)
                </a>
                <?php foreach ($cats as $s => $c): ?>
                    <a href="<?= htmlspecialchars(artikel_filter_url($s)) ?>" class="btn btn-sm <?= $activeKategori === $c['name'] ? 'btn-primary' : 'btn-outline' ?>">
                        <?= htmlspecialchars($c['name']) ?> (<?= $c['count'] ?>)
                    </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if ($activeKategori !== null): ?>
                <p style="margin-bottom:24px; color:var(--muted); font-size:14px;">
                    Menampilkan kategori <strong style="color:var(--text-dark);"><?= htmlspecialchars($activeKategori) ?></strong>
                    (<?= $total ?> artikel) —
                    <a href="<?= htmlspecialchars(artikel_filter_url(null)) ?>" style="text-decoration:underline;">tampilkan semua</a>
                </p>
            <?php endif; ?>

            <div class="articles__grid">

                <?php if (count($articles) > 0): ?>
                    <?php foreach ($articles as $index => $art): ?>
                        <?php 
                        $delay = ($index * 0.1) . 's';
                        $link = "detail.php?id=" . $art['id'];

                        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                        $base = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? '') . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
                        $shareUrl = $base . '/' . $link;

                        $img = !empty($art['gambar']) ? '../../' . $art['gambar'] : 'https://images.unsplash.com/photo-1606326608606-aa0b62935f2b?auto=format&fit=crop&w=600&q=80';

                        $category = !empty($art['kategori']) ? htmlspecialchars($art['kategori']) : 'Umum';
                        $date = date('d F Y', strtotime($art['tanggal']));
                        $title = htmlspecialchars($art['judul']);

                        $clean_text = strip_tags($art['konten']);
                        $excerpt = mb_substr($clean_text, 0, 100);
                        if (mb_strlen($clean_text) > 100) {
                            $excerpt .= '...';
                        }
                        ?>

                        <article class="article-card reveal" style="transition-delay: <?= $delay ?>">
                            <a href="<?= $link ?>" class="article-card__link">
                                <div class="article-card__image">
                                    <img src="<?= $img ?>" alt="<?= $title ?>" loading="lazy">
                                    <span class="article-card__category"><?= $category ?></span>
                                </div>
                                <div class="article-card__body">
                                    <p class="article-card__date"><?= $date ?></p>
                                    <h3 class="article-card__title"><?= $title ?></h3>
                                    <p class="article-card__excerpt"><?= $excerpt ?></p>
                                    <span class="link-arrow">Baca Selengkapnya<span class="material-symbols-rounded">arrow_forward</span></span>
                                </div>
                            </a>
                            <button type="button" class="article-card__share" data-share-url="<?= htmlspecialchars($shareUrl, ENT_QUOTES) ?>" data-share-title="<?= htmlspecialchars($art['judul'] ?? '', ENT_QUOTES) ?>" aria-label="Bagikan artikel" aria-haspopup="true" aria-expanded="false">
                                <span class="material-symbols-rounded" aria-hidden="true">share</span>
                            </button>
                        </article>

                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; color: var(--muted); grid-column: 1 / -1;">Belum ada artikel yang diterbitkan.</p>
                <?php endif; ?>

            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <nav class="articles__pagination" aria-label="Paginasi artikel" style="display:flex; align-items:center; justify-content:center; gap:8px; margin-top:48px;">
                <?php if ($page > 1): ?>
                    <a href="<?= htmlspecialchars(artikel_pagination_link($activeSlug, $page - 1)) ?>" class="btn btn-outline btn-sm" aria-label="Halaman sebelumnya">&larr; Sebelumnya</a>
                <?php else: ?>
                    <span class="btn btn-outline btn-sm" aria-disabled="true" style="opacity:.4; pointer-events:none;">&larr; Sebelumnya</span>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($i === $page): ?>
                        <span class="btn btn-primary btn-sm" aria-current="page"><?= $i ?></span>
                    <?php else: ?>
                        <a href="<?= htmlspecialchars(artikel_pagination_link($activeSlug, $i)) ?>" class="btn btn-outline btn-sm" aria-label="Halaman <?= $i ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="<?= htmlspecialchars(artikel_pagination_link($activeSlug, $page + 1)) ?>" class="btn btn-outline btn-sm" aria-label="Halaman berikutnya">Selanjutnya &rarr;</a>
                <?php else: ?>
                    <span class="btn btn-outline btn-sm" aria-disabled="true" style="opacity:.4; pointer-events:none;">Selanjutnya &rarr;</span>
                <?php endif; ?>
            </nav>
            <?php endif; ?>

        </div>
    </section>
</main>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>