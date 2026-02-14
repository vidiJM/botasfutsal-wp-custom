<?php
declare(strict_types=1);

namespace FS\ShortcodeSuite\Admin\Pages;

defined('ABSPATH') || exit;

final class Dashboard_Page {

    public function render(): void {

        $logo_post_id = 41;
        $logo_html = '';
        
        $post = get_post($logo_post_id);
        
        if ($post) {
        
            // Caso 1: Es un attachment
            if ($post->post_type === 'attachment') {
        
                $logo_html = wp_get_attachment_image(
                    $logo_post_id,
                    'medium',
                    false,
                    [
                        'class'   => 'fs-admin-logo',
                        'alt'     => 'FS Logo',
                        'loading' => 'lazy',
                    ]
                );
        
            }
            // Caso 2: Tiene imagen destacada
            elseif (has_post_thumbnail($logo_post_id)) {
        
                $logo_html = get_the_post_thumbnail(
                    $logo_post_id,
                    'medium',
                    [
                        'class'   => 'fs-admin-logo',
                        'alt'     => 'FS Logo',
                        'loading' => 'lazy',
                    ]
                );
            }
        }

    
        ?>
        <div class="wrap fs-admin-wrap">
    
            <div class="fs-admin-header fs-admin-header--brand">
    
                <div class="fs-admin-header__brand">
    
                    <?php if ($logo_html) : ?>
                        <div class="fs-admin-logo-wrapper">
                            <?php echo $logo_html; ?>
                        </div>
                    <?php endif; ?>
    
                    <div>
                        <div class="fs-admin-header__title">
                            <h1>FS Shortcode Suite</h1>
                            <span class="fs-badge fs-badge--brand">Admin</span>
                        </div>
                        <p>Arquitectura modular de shortcodes optimizados.</p>
                    </div>
    
                </div>
    
            </div>
    
            <div class="fs-admin-shell">
                <div class="fs-card">
                    <div class="fs-card__header">
                        <h2 class="fs-card__title">Shortcodes disponibles</h2>
                        <p class="fs-card__subtitle">Accede a cada generador y a su manual de uso.</p>
                    </div>
    
                    <div class="fs-tiles">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=fs-shortcode-suite-grid')); ?>" class="fs-tile">
                            <div class="fs-tile__top">
                                <h3>FS Grid</h3>
                                <span class="fs-pill">[fs_grid]</span>
                            </div>
                            <p>Grid offer-driven con sincronización dinámica.</p>
                        </a>
    
                        <a href="<?php echo esc_url(admin_url('admin.php?page=fs-shortcode-suite-search')); ?>" class="fs-tile">
                            <div class="fs-tile__top">
                                <h3>FS Search</h3>
                                <span class="fs-pill">[fs_search]</span>
                            </div>
                            <p>Búsqueda fullscreen con REST.</p>
                        </a>
                        
                        <a href="<?php echo esc_url(admin_url('admin.php?page=fs-shortcode-suite-size-guide')); ?>" class="fs-tile">
                            <div class="fs-tile__top">
                                <h3>FS Size Guide</h3>
                                <span class="fs-pill">[fs_size_guide]</span>
                            </div>
                            <p>Búsqueda fullscreen con REST.</p>
                        </a>
                    </div>
                </div>
            </div>
    
        </div>
        <?php
    }

}
