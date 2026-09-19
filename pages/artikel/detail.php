<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/sanitize.php'; 

$id = $_GET['id'] ?? 1; 
$stmt = $pdo->prepare("SELECT * FROM articles WHERE id = ?");
$stmt->execute([$id]);
$artikel = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$artikel) {
    die("Artikel tidak ditemukan.");
}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$base = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? '');
$shareUrl = $base . $_SERVER['REQUEST_URI'];
$shareTitle = $artikel['judul'] ?? 'Al-Mutqon Arabic Learning';
$shareText = 'Al-Mutqon: ' . $shareTitle;

include __DIR__ . '/../../layouts/header.php'; 
?>

<!-- =================== PAGE HERO =================== -->
<section class="page-hero">
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <ol class="breadcrumbs__list">
                <li class="breadcrumbs__item"><a href="/" class="breadcrumbs__link">Beranda</a></li>
                <li class="breadcrumbs__item"><a href="/pages/artikel/index.php" class="breadcrumbs__link">Artikel</a></li>
                <li class="breadcrumbs__item"><span class="breadcrumbs__current" aria-current="page"><?= htmlspecialchars($artikel['judul'] ?? 'Detail Artikel') ?></span></li>
            </ol>
        </nav>
        <span class="section-label">Artikel</span>
        
        <!-- Judul dinamis dari database -->
        <h1 class="page-hero__title"><?php echo $artikel['judul'] ?? 'Catatan Perjalanan Almutqon'; ?></h1>
        
        <!-- Meta Data: Author & Tanggal (Otomatis rapi) -->
        <div class="article-meta" style="margin-top: 20px; display: flex; align-items: center; gap: 15px; color: var(--muted); font-size: 15px;">
            <span style="display: flex; align-items: center; gap: 6px;">
                <span class="material-symbols-rounded" style="font-size: 20px;">person</span>
                <?php echo htmlspecialchars($artikel['author'] ?? 'Tim Almutqon'); ?>
            </span>
            <span>•</span>
            <span style="display: flex; align-items: center; gap: 6px;">
                <span class="material-symbols-rounded" style="font-size: 20px;">calendar_today</span>
                <?php 
                    $tgl = $artikel['tanggal'] ?? date('Y-m-d H:i:s');
                    echo date('d F Y', strtotime($tgl)); 
                ?>
            </span>
        </div>
    </div>
</section>

<main>
    <section class="section" style="padding-top: 60px; padding-bottom: 80px; min-height: 60vh;">
        <div class="container">
            
            <!-- INI KUNCINYA: Bungkus konten dengan class 'article-content' -->
            <div class="article-content">
                <?php 
                // Tampilkan konten HTML dari database. 
                // Jika belum ada data, tampilkan placeholder.
                if ($artikel && !empty($artikel['konten'])) {
                    echo sanitize_html($artikel['konten']); 
                } else {
                    echo '<p style="text-align: center; color: var(--muted);">Belum ada artikel yang diterbitkan. Silakan upload dari halaman admin.</p>';
                }
                ?>
            </div>

            <!-- Bagikan artikel -->
            <div class="article-share">
                <span class="article-share__label">Bagikan artikel ini:</span>
                <div class="article-share__btns">
                    <a class="article-share__btn article-share__btn--wa" href="https://wa.me/?text=<?= rawurlencode($shareText . ' ' . $shareUrl) ?>" target="_blank" rel="noopener" aria-label="Bagikan ke WhatsApp" title="WhatsApp">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17.6 6.32A7.85 7.85 0 0 0 12.05 4a7.94 7.94 0 0 0-6.88 11.9L4 20l4.2-1.1a7.9 7.9 0 0 0 3.8.97h.01a7.94 7.94 0 0 0 5.6-13.55zM12.05 18.5h-.01a6.6 6.6 0 0 1-3.36-.92l-.24-.14-2.5.65.67-2.43-.16-.25a6.6 6.6 0 1 1 5.6 3.09zm3.62-4.94c-.2-.1-1.17-.58-1.35-.64-.18-.07-.31-.1-.45.1-.13.2-.5.64-.62.77-.11.13-.23.15-.43.05-.2-.1-.84-.31-1.6-.99-.6-.53-1-1.18-1.12-1.38-.11-.2-.01-.31.09-.41.09-.09.2-.23.3-.35.1-.12.13-.2.2-.33.07-.13.03-.25-.02-.35-.05-.1-.45-1.08-.62-1.48-.16-.39-.33-.34-.45-.34h-.38c-.13 0-.34.05-.52.25-.18.2-.69.67-.69 1.64 0 .97.7 1.9.8 2.03.1.13 1.4 2.14 3.4 3 .47.2.84.33 1.14.42.48.15.92.13 1.26.08.39-.06 1.17-.48 1.34-.94.17-.46.17-.86.12-.94-.05-.08-.18-.13-.38-.23z"/></svg>
                    </a>
                    <a class="article-share__btn article-share__btn--fb" href="https://www.facebook.com/sharer/sharer.php?u=<?= rawurlencode($shareUrl) ?>" target="_blank" rel="noopener" aria-label="Bagikan ke Facebook" title="Facebook">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                    </a>
                    <a class="article-share__btn article-share__btn--x" href="https://twitter.com/intent/tweet?url=<?= rawurlencode($shareUrl) ?>&text=<?= rawurlencode($shareTitle) ?>" target="_blank" rel="noopener" aria-label="Bagikan ke X (Twitter)" title="X (Twitter)">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                    </a>
                    <a class="article-share__btn article-share__btn--tg" href="https://t.me/share/url?url=<?= rawurlencode($shareUrl) ?>&text=<?= rawurlencode($shareTitle) ?>" target="_blank" rel="noopener" aria-label="Bagikan ke Telegram" title="Telegram">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M21.94 3.36a1.42 1.42 0 0 0-1.5-.2L2.6 9.9c-.88.34-.84 1.6.06 1.9l4.66 1.5 1.8 5.74c.25.77 1.18 1.02 1.82.51l2.53-2.02 4.4 3.24c.69.5 1.65.17 1.87-.64l3-13.6c.14-.64-.29-1.25-.9-1.42z"/></svg>
                    </a>
                    <button type="button" class="article-share__btn article-share__btn--copy" data-copy="<?= htmlspecialchars($shareUrl, ENT_QUOTES) ?>" aria-label="Salin tautan artikel" title="Salin tautan">
                        <span class="material-symbols-rounded" style="font-size: 20px;" aria-hidden="true">link</span>
                    </button>
                </div>
            </div>

        </div>
    </section>
</main>

<?php 
// 4. Panggil Footer
include __DIR__ . '/../../layouts/footer.php'; 
?>