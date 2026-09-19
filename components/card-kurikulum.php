<!-- =================== KURIKULUM =================== -->
<section class="kurikulum section section--surface" id="kurikulum">
    <div class="container">
        <div class="section__head reveal">
            <span class="section-label">Kurikulum Pembelajaran</span>
            <h2 class="section-title">Kurikulum Berjenjang</h2>
            <p class="section-subtitle">Empat tingkatan pembelajaran dari dasar hingga ahli, dirancang dengan pendekatan komunikatif-struktural.</p>
        </div>

        <div class="kurikulum-carousel">
            <div class="kurikulum__viewport">
                <div class="kurikulum-track">
                    
                    <?php foreach ($kurikulum_items as $item): ?>
                    <div class="kurikulum-card reveal" style="--theme-color: <?php echo $item['color']; ?>; transition-delay: <?php echo $item['delay']; ?>s;">
                        <div class="card-icon">
                            <span class="material-symbols-rounded"><?php echo $item['icon']; ?></span>
                        </div>
                        <div class="card-body">
                            <h3 class="card-title"><?php echo $item['title']; ?></h3>
                            <p class="card-desc"><?php echo $item['desc']; ?></p>
                            <div class="card-line"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>

                </div>
            </div>
        </div>
    </div>
</section>