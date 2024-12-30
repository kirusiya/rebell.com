<?php $data = require_once( get_stylesheet_directory() . '/Core/Admin/DeliverOrdersPageController.php' ); ?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?= __( 'Entregas pendientes', 'betheme' ) ?></h1>

    <div id="poststuff">
        <div id="post-body" class="metabox-holder">
            <div id="post-body-content">
                <div class="meta-box-sortables ui-sortable">

                    <?php if ( $_GET['order_marked_complete'] ) : ?>
                    <div style="background: white;padding: 1rem;margin: 0 auto 1rem auto;border-radius: .5rem;box-shadow: 0 0 10px -5px;max-width: 300px;text-align: center;">
                        <h3 style="margin-top: 0">Pedido <?= intval($_GET['order_marked_complete']) ?> marcado como completado</h3>
                        <a href="<?= admin_url('admin.php?page=deliver_orders') ?>" class="button button-primary">Descartar</a>
                    </div>
                    <?php endif; ?>
                
                    <form method="post">
                        <?php
                            $data->prepare_items();
                            $data->display(); 
                        ?>
                    </form>
                </div>
            </div>
        </div>

        <br class="clear">
    </div>
</div>