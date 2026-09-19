/* ===================================================================
ALFARAHIDI FLOATING WIDGET — STANDALONE MODULE
=================================================================== */
window.alfarahidi = {

    /* ============ DATA KOSAKATA ============ */
    data: [
        { ar: 'ضَرَبَ', tr: 'daraba', en: 'Dia (laki-laki) telah memukul', note: "Pola Fa'a'la (فَعَلَ). Fi'il Madhi (kata kerja lampau).", tags: ['Grammar'] },
        { ar: 'كَتَبَ', tr: 'kataba', en: 'Dia (laki-laki) telah menulis', note: "Pola Fa'a'la (فَعَلَ). Akar kata k-t-b.", tags: ['Grammar'] },
        { ar: 'الْعِلْمُ نُورٌ', tr: "Al-'Ilmu Nur", en: 'Ilmu adalah cahaya', note: 'Hikmah klasik: Ilmu menerangi jalan kebenaran dalam kegelapan.', tags: ['Wisdom', 'Random wisdom'] },
        { ar: 'اللُّغَةُ', tr: 'Al-Lughah', en: 'Bahasa', note: 'Bahasa bukan sekadar alat komunikasi, tapi cerminan pola pikir suatu peradaban.', tags: ['Philosophy'] },
        { ar: 'مَدْرَسَةٌ', tr: 'madrasatun', en: 'Sekolah', note: "Pola Maf'alun (مَفْعَلٌ). Isim Makan (kata benda tempat).", tags: ['Grammar', 'Vocabulary'] },
        { ar: 'يَضْرِبُ', tr: 'yadribu', en: 'Dia (laki-laki) sedang memukul', note: "Pola Yaf'ulu (يَفْعُلُ). Fi'il Mudhari' (kata kerja sekarang).", tags: ['Grammar'] },
        { ar: 'الصَّبْرُ مِفْتَاحُ الْفَرَجِ', tr: 'Ash-Shabr Miftahul Faraj', en: 'Kesabaran adalah kunci kebahagiaan', note: 'Mahfudzot populer untuk melatih ketenangan jiwa.', tags: ['Wisdom', 'Random wisdom'] },
        { ar: 'الْقَلْبُ', tr: 'Al-Qalb', en: 'Hati', note: 'Dalam falsafah Arab, Qalb adalah pusat pemahaman, bukan sekadar organ pemompa darah.', tags: ['Philosophy'] },
        { ar: 'كِتَابٌ', tr: 'kitabun', en: 'Buku', note: 'Isim Jamid (kata benda dasar). Benda yang digunakan untuk menulis.', tags: ['Vocabulary'] },
        { ar: 'فَهِمَ', tr: 'fahima', en: 'Dia (laki-laki) telah memahami', note: "Pola Fa'ila (فَعِلَ). Fi'il Madhi. Akar kata f-h-m.", tags: ['Grammar'] },
        { ar: 'مَنْ جَدَّ وَجَدَ', tr: 'Man Jadda Wajada', en: 'Siapa bersungguh-sungguh akan berhasil', note: 'Motivasi bahwa kesuksesan datang dari usaha yang nyata.', tags: ['Wisdom', 'Random wisdom'] },
        { ar: 'الْوَقْتُ كَالسَّيْفِ', tr: 'Al-Waqtu Kas-Saif', en: 'Waktu itu seperti pedang', note: 'Jika kamu tidak memotongnya (menggunakannya), ia akan memotongmu.', tags: ['Wisdom', 'Philosophy'] },
        { ar: 'قَلَمٌ', tr: 'qolamun', en: 'Pena', note: 'Alat tulis yang menggunakan tinta.', tags: ['Vocabulary'] },
        { ar: 'لَا تَأْسَفْ عَلَى مَا فَاتَ', tr: "La Ta'saf 'Ala Ma Fata", en: 'Jangan menyesal atas yang telah hilang', note: 'Menerima takdir dan melihat ke depan dengan positif.', tags: ['Wisdom', 'Random wisdom'] },
        { ar: 'عَلِمَ', tr: "'alima", en: 'Dia (laki-laki) telah mengetahui', note: "Pola Fa'ila (فَعِلَ). Akar kata '-l-m. Berhubungan dengan kata 'ilmu.", tags: ['Grammar'] }
        
    ],

    /* ============ STATE ============ */
    currentIndex: 0,

    /* ============ INIT ============ */
    init() {
        const widget = document.getElementById('alfarahidiWidget');
        const fab = document.getElementById('alfarahidiFab');
        const closeBtn = document.getElementById('alfarahidiClose');

        if (!widget || !fab) return;

        fab.addEventListener('click', () => {
            widget.classList.toggle('active');
        });

        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                widget.classList.remove('active');
            });
        }

        // Render kartu pertama saat halaman dimuat
        this.currentIndex = 0;
        this.render();
    },

    /* ============ NAVIGASI ============ */
    nav(direction) {
        const total = this.data.length;
        this.currentIndex += direction;
        if (this.currentIndex < 0) this.currentIndex = 0;
        if (this.currentIndex >= total) this.currentIndex = total - 1;
        this.render();
    },

    /* ============ RENDER ============ */
    render() {
        const item = this.data[this.currentIndex];
        const total = this.data.length;
        const currentNum = this.currentIndex + 1;

        // Update Progress Text (1 / 15)
        const progressText = document.getElementById('alfarahidiProgressText');
        if (progressText) progressText.innerText = `${currentNum} / ${total}`;

        // Update Tags di header (Grammar, Wisdom, dll)
        const tagsDiv = document.getElementById('alfarahidiTags');
        if (tagsDiv) {
            tagsDiv.innerHTML = item.tags.map((tag, i) =>
                `<span class="alfarahidi-tag ${i === 1 ? 'alt' : ''}">${tag}</span>`
            ).join('');
        }

        // Update Content dengan efek slide
        const contentDiv = document.getElementById('alfarahidiContent');
        if (!contentDiv) return;

        contentDiv.classList.remove('alfarahidi-card-slide');
        void contentDiv.offsetWidth; // Trigger reflow untuk restart animasi
        contentDiv.classList.add('alfarahidi-card-slide');

        contentDiv.innerHTML = `
            <div class="alfarahidi-card-box">
                <div class="alfarahidi-arabic">${item.ar}</div>
                <div class="alfarahidi-translit">${item.tr}</div>
                <div class="alfarahidi-meaning">${item.en}</div>
            </div>
            <div class="alfarahidi-note">
                <span class="material-symbols-rounded">info</span>
                <span>${item.note}</span>
            </div>
            <div class="alfarahidi-nav">
                <button onclick="alfarahidi.nav(-1)" ${this.currentIndex === 0 ? 'disabled' : ''}>
                    &larr; Prev
                </button>
                <button onclick="alfarahidi.nav(1)" ${this.currentIndex === total - 1 ? 'disabled' : ''}>
                    Next &rarr;
                </button>
            </div>
        `;
    }
};

// Auto-init saat DOM siap
document.addEventListener('DOMContentLoaded', () => {
    alfarahidi.init();
});