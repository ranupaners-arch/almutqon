<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/sanitize.php';
require_once __DIR__ . '/../config/csrf.php';

require_admin();

$admin_quill = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $judul   = trim($_POST['judul'] ?? '');
    $author  = trim($_POST['author'] ?? '');
    $kategori = trim($_POST['kategori'] ?? '');
    $konten  = sanitize_html($_POST['konten'] ?? '');

    // CEK DUPLIKAT
    $stmt_check = $pdo->prepare("SELECT id FROM articles WHERE judul = ?");
    $stmt_check->execute([$judul]);
    if ($stmt_check->fetch()) {
        flash_error("Artikel dengan judul '" . htmlspecialchars($judul) . "' sudah ada!");
        header("Location: tambah-artikel.php");
        exit;
    }

    // HANDLE UPLOAD GAMBAR
    $gambar_path = '';
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === 0) {
        $target_dir = __DIR__ . '/../assets/uploads/';
        if (!file_exists($target_dir)) mkdir($target_dir, 0755, true);

        $max_size = 2 * 1024 * 1024;
        $allowed_mime = ['image/jpeg', 'image/png', 'image/webp'];
        $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];

        $file_extension = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
        $file_mime = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $_FILES['gambar']['tmp_name']);
        $file_size = (int) $_FILES['gambar']['size'];

        if (!in_array($file_extension, $allowed_ext, true) || !in_array($file_mime, $allowed_mime, true)) {
            flash_error("Format gambar tidak didukung. Gunakan JPG, PNG, atau WebP.");
            header("Location: tambah-artikel.php");
            exit;
        }
        if ($file_size <= 0 || $file_size > $max_size) {
            flash_error("Ukuran gambar maksimal 2 MB.");
            header("Location: tambah-artikel.php");
            exit;
        }
        $new_filename = 'art_' . bin2hex(random_bytes(16)) . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;
        if (move_uploaded_file($_FILES['gambar']['tmp_name'], $target_file)) {
            $gambar_path = 'assets/uploads/' . $new_filename;
        }
    }

    if (!empty($judul) && !empty($author) && !empty($konten)) {
        $stmt = $pdo->prepare("INSERT INTO articles (judul, author, kategori, gambar, konten, tanggal) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$judul, $author, $kategori, $gambar_path, $konten]);
        flash('Artikel berhasil diterbitkan!');
        header("Location: tambah-artikel.php");
        exit;
    } else {
        flash_error("Mohon lengkapi semua data yang wajib diisi.");
        header("Location: tambah-artikel.php");
        exit;
    }
}

pesan_flash();

$admin_title = 'Tulis Artikel';
$admin_aktif = 'tulis';
include __DIR__ . '/../layouts/admin-header.php';
?>

<?php if ($pesan_sukses): ?>
    <div class="admin__alert admin__alert--<?= $jenis_pesan === 'success' ? 'success' : 'error' ?>">
        <?= htmlspecialchars($pesan_sukses) ?>
    </div>
<?php endif; ?>

<div class="admin__card">
    <form method="POST" enctype="multipart/form-data" id="formArtikel">
        <?= csrf_field() ?>

        <div class="admin__form-group">
            <label class="admin__form-label" for="judul">Judul Artikel *</label>
            <input type="text" id="judul" name="judul" class="admin__input" required>
        </div>

        <div class="admin__form-group">
            <label class="admin__form-label" for="author">Nama Author *</label>
            <input type="text" id="author" name="author" class="admin__input" required>
        </div>

        <div class="admin__form-group">
            <label class="admin__form-label" for="kategori">Kategori *</label>
            <input type="text" id="kategori" name="kategori" class="admin__input" required>
        </div>

        <div class="admin__form-group">
            <label class="admin__form-label" for="gambar">Gambar Cover (Opsional)</label>
            <input type="file" id="gambar" name="gambar" accept="image/*" class="admin__input-file">
            <p class="admin__form-hint">Format: JPG, PNG, atau WebP. Maks. 2 MB.</p>
        </div>

        <div class="admin__form-group">
            <label class="admin__form-label">Isi Artikel *</label>
            <div class="admin__editor">
                <div id="editor"></div>
            </div>
            <input type="hidden" name="konten" id="konten">
        </div>

        <button type="submit" id="submitBtn" class="admin__btn admin__btn--primary" style="padding:12px 28px;font-size:14px;">
            <span class="material-symbols-rounded">send</span> Terbitkan Artikel
        </button>
    </form>
</div>

<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
<script>
    var quill = new Quill('#editor', {
        theme: 'snow',
        placeholder: 'Mulai menulis artikel di sini...',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'color': [] }, { 'background': [] }],
                [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                [{ 'align': [] }],
                ['link', 'image'],
                ['clean']
            ]
        }
    });

    var isPosted = <?php echo ($jenis_pesan === 'success') ? 'true' : 'false'; ?>;
    if (isPosted) {
        localStorage.removeItem('artikel_draft');
        quill.setContents([]);
    } else {
        var draft = localStorage.getItem('artikel_draft');
        if (draft && quill.root.innerText.trim().length === 0) quill.root.innerHTML = draft;
    }
    quill.on('text-change', function() { localStorage.setItem('artikel_draft', quill.root.innerHTML); });

    var isSubmitting = false;
    document.getElementById('formArtikel').onsubmit = function(e) {
        if (isSubmitting) { e.preventDefault(); return false; }
        isSubmitting = true;
        var kontenInput = document.getElementById('konten');
        kontenInput.value = quill.root.innerHTML;
        if (quill.root.innerText.trim().length === 0) {
            alert('Isi artikel tidak boleh kosong!');
            isSubmitting = false;
            e.preventDefault();
            return false;
        }
        var btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerHTML = 'Memproses...';
    };
</script>

<?php include __DIR__ . '/../layouts/admin-footer.php'; ?>
