<?php

/**
 * Login form
 *
 * @package BeTheme
 * @author  Invbit <info@invbit.com>
 * @link    https://www.invbit.com
 */

defined( 'ABSPATH' ) or die( '¯\_(ツ)_/¯' ); ?>

<?php $userCoupons = get_field( 'user_coupons', 'user_' . get_current_user_id( ) ) ?? [ ]; ?>

<section class="WCAccountCoupons">

    <div id="MessagesBox"></div>

    <input type="hidden" name="apply-coupon-nonce" value="<?= wp_create_nonce( 'apply-coupon' ) ?>">

    <?php if ( count( $userCoupons ) <= 0 ) : ?>
        <div class="empty">
            <img height="45" width="81" src="/wp-content/uploads/2020/09/coupon2-min-2020-09-02.png" alt="Icono">
            <h4>No tienes un cupón de descuento asociado a tu cuenta en estos momentos.</h4>
        </div>
    <?php else : ?>
        <ul class="coupons-list">
            <?php foreach ( $userCoupons as $coupon ) : ?>
                <?php $applied = WC( )->cart->has_discount( $coupon->post_title ); ?>
                <li <?= $applied ? 'class="applied"' : '' ?>>
                    <span class="name"><?= $coupon->post_title ?></span>
                    <button data-apply="<?= $coupon->post_title ?>" class="apply" <?= $applied ? 'disabled' : '' ?>>
                        <span class="used <?= $applied ? '' : 'hidden' ?>">EN USO</span>
                        <span class="use <?= $applied ? 'hidden' : '' ?>">USAR</span>
                    </button>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>