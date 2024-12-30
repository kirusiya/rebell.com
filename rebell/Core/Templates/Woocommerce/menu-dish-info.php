<?php defined( 'ABSPATH' ) or die( '¯\_(ツ)_/¯' );

$randomID = bin2hex( random_bytes( ( 10 ) ) );

$dish = get_sub_field( 'plato' );
$dish = is_numeric( $dish )
        ? wc_get_product( $dish )
        : wc_get_product( $dish->ID );
?>

<li class="plato">
    <?= esc_html( $dish->get_title( ) ); ?> 

    <span
        data-open-modal="<?= $randomID ?>"
        class="ShowModal dashicons dashicons-info"
        title="<?= __( 'Más información', 'betheme' ) ?>"
    ></span>

    <?php if ( 
        ( $ingredients = get_field( 'ingrediente', $dish->get_ID( ) ) ) or
        $dish->has_attributes( )
    ) : ?>
    <div class="MenuModal" data-modal="<?= $randomID ?>">
        <header class="Header">
            <?= __( 'Ingredientes/Alérgenos', 'betheme' ) ?>
            <span
                class="CloseButton dashicons dashicons-dismiss"
                data-close-modal="<?= $randomID ?>"
            ></span>
        </header>
        <section class="Content">
            <?php if ( $ingredients ) :
                printf( __( '<strong>Ingredientes</strong>: %s', 'betheme' ), ucwords( $ingredients ) );
            endif; ?>
        </section>
        <footer class="Footer">
            <?php
            if ( !empty( $dish->get_attributes( )[ 'pa_alergenos' ] ) ) :
                foreach ( $dish->get_attributes( )[ 'pa_alergenos' ]->get_terms( ) as $attr ) {
                    if ( $icon = get_field( 'icon', "pa_alergenos_{$attr->term_id}" ) )
                        print "<img src='$icon' title='{$attr->name}'>";
                    else
                        print "{$attr->name} ";
                }
            endif;
            ?>
        </footer>
    </div>
    <?php endif; ?>
</li>