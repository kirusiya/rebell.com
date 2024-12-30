<?php

/**
 * The template for displaying the footer.
 *
 * @package Betheme
 * @author  Invbit
 * @link    https://invbit.com
 */

$footerLink = [
    [
        'name' => 'Aviso Legal',
        'url'  => '/aviso-legal'
    ], [
        'name' => 'Política de Privacidad',
        'url'  => '/politica-de-privacidad'
    ], [
        'name' => 'Términos y Condiciones',
        'url'  => '/terminos-y-condiciones'
    ], [
        'name' => 'Cookies',
        'url'  => '/politica-de-cookies'
    ], [
        'name' => 'Preguntas Frecuentes',
        'url'  => '/faq'
    ]
];

$back_to_top_class = mfn_opts_get('back-top-top');

if ($back_to_top_class == 'hide') {
    $back_to_top_position = false;
} elseif (strpos($back_to_top_class, 'sticky') !== false) {
    $back_to_top_position = 'body';
} elseif (mfn_opts_get('footer-hide') == 1) {
    $back_to_top_position = 'footer';
} else {
    $back_to_top_position = 'copyright';
}

do_action('mfn_hook_content_after');

if ('hide' != mfn_opts_get('footer-style')) : ?>

    <footer id="Footer" class="clearfix">

        <?php if ($footer_call_to_action = mfn_opts_get('footer-call-to-action')) : ?>
            <div class="footer_action">
                <div class="container">
                    <div class="column one column_column">
                        <?= do_shortcode($footer_call_to_action); ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php
        $sidebars_count = 0;
        for ($i = 1; $i <= 5; $i++) {
            if (is_active_sidebar('footer-area-' . $i)) {
                $sidebars_count++;
            }
        }

        if ($sidebars_count > 0) {

            echo '<div class="widgets_wrapper">';
            echo '<div class="container">';

            if ($footer_layout = mfn_opts_get('footer-layout')) {
                // Theme Options
                $footer_layout = explode(';', $footer_layout);
                $footer_cols   = $footer_layout[0];

                for ($i = 1; $i <= $footer_cols; $i++) {
                    if (is_active_sidebar('footer-area-' . $i)) {
                        echo '<div class="column ' . esc_attr($footer_layout[$i]) . '">';
                        dynamic_sidebar('footer-area-' . $i);
                        echo '</div>';
                    }
                }
            } else {
                // default with equal width
                $sidebar_class = '';
                if ($sidebars_count === 2) {
                    $sidebar_class = 'one-second';
                } elseif ($sidebars_count === 3) {
                    $sidebar_class = 'one-third';
                } elseif ($sidebars_count === 4) {
                    $sidebar_class = 'one-fourth';
                } elseif ($sidebars_count === 5) {
                    $sidebar_class = 'one-fifth';
                } else {
                    $sidebar_class = 'one';
                }

                for ($i = 1; $i <= 5; $i++) {
                    if (is_active_sidebar('footer-area-' . $i)) {
                        echo '<div class="column ' . esc_attr($sidebar_class) . '">';
                        dynamic_sidebar('footer-area-' . $i);
                        echo '</div>';
                    }
                }
            }

            echo '</div>';
            echo '</div>';
        }
        ?>

        <?php if (mfn_opts_get('footer-hide') != 1) : ?>

            <div class="footer_copy">
                <div class="container">
                    <div class="column one">

                        <?php if ($back_to_top_position == 'copyright') : ?>
                            <a id="back_to_top" class="button button_js" href=""><i class="icon-up-open-big"></i></a>
                        <?php endif; ?>

                        <ul class="FooterLinks">
                            <?php foreach ($footerLink as $link) : ?>
                            <li>
                                <a href="<?= $link['url'] ?>" target="_blank" rel="noopener noreferrer"><?= $link['name'] ?></a>
                            </li>
                            <?php endforeach; ?>
                        </ul>

                        <div class="copyright">
                            <?php if ($footerCopy = mfn_opts_get('footer-copy')) : ?>
                                <?= do_shortcode($footerCopy) ?>
                            <?php endif; ?>
                        </div>

                        <?php
                        if (has_nav_menu('social-menu-bottom')) {
                            mfn_wp_social_menu_bottom();
                        } else {
                            get_template_part('includes/include', 'social');
                        }
                        ?>

                    </div>
                </div>
            </div>

        <?php endif; ?>

        <?php if ($back_to_top_position == 'footer') : ?>
            <a id="back_to_top" class="button button_js in_footer" href=""><i class="icon-up-open-big"></i></a>
        <?php endif; ?>

    </footer>
<?php endif; ?>

</div>

<!-- Validation modal -->
<aside class="modal micromodal-slide" id="ExtrasValidation_Modal" aria-hidden="true">
	<div class="modal__overlay" tabindex="-1" data-micromodal-close>
		<div class="modal__container" role="dialog" aria-modal="true" aria-labelledby="modal-1-title">
			<header class="modal__header">
				<h2 class="modal__title">Aviso</h2>
			</header>
			<main class="modal__content">
				<div class="modal__description" id="ExtrasValidation_ModalContent">
					Estás a punto de eliminar todos los platos del pedido. ¿Deseas continuar?
				</div>
			</main>
			<footer class="modal__footer">
				<button class="modal__btn" data-micromodal-close aria-label="Cerrar información">
					Ok
				</button>
			</footer>
		</div>
	</div>
</aside>

<div class="loadingIcon opacity-0"><div></div></div>

<?php // side slide menu
if (mfn_opts_get('responsive-mobile-menu')) {
    get_template_part('includes/header', 'side-slide');
}

if ($back_to_top_position == 'body') {
    echo '<a id="back_to_top" class="button button_js ' . esc_attr($back_to_top_class) . '" href=""><i class="icon-up-open-big"></i></a>';
}

if (mfn_opts_get('popup-contact-form')) : ?>
    <div id="popup_contact">
        <a class="button button_js" href="#"><i class="<?= esc_attr(mfn_opts_get('popup-contact-form-icon', 'icon-mail-line')); ?>"></i></a>
        <div class="popup_contact_wrapper">
            <?= do_shortcode(mfn_opts_get('popup-contact-form')); ?>
            <span class="arrow"></span>
        </div>
    </div>
<?php endif;

do_action('mfn_hook_bottom');

wp_footer(); ?>

</body>

</html>