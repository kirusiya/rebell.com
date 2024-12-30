<?php defined( 'ABSPATH' ) or die( '¯\_(ツ)_/¯' ); ?>

<div id="MyKitchen">
    <h1><?= esc_html( 'Mi Cocina', 'betheme' ) ?></h1>



        <div id="MyKitchenConfig">
                <h3 class="title">
                    <span>
                        <?= esc_html( 'Configura tu cocina.', 'betheme' ) ?>
                    </span>

                    <button type="button" class="toggler" aria-expanded="true" data-toggle="MyKitchenConfigContent">
                        <span class="screen-reader-text"><?= esc_html( 'Alternar panel: Configura tu cocina.', 'betheme' ) ?></span>
                        <span class="dashicons dashicons-arrow-up"></span>
                    </button>
                </h3>

            <div id="MyKitchenConfigContent" class="inside">

                <form method="POST" action="<?= esc_url( admin_url( 'admin-post.php' ) ) ?>">
                    <?php wp_nonce_field( 'rebell-form', 'rebell-form-nonce' ); ?>
                    <input type="hidden" name="action" value="rebell_update_my_kitchen">
                    <input type="hidden" name="kitchen" value="<?= $kitchen['kitchen_id'] ?>">

                    <table class="form-table" role="presentation">

                        <tbody>
                            <!-- Kitchen name -->
                            <tr>
                                <th scope="row">
                                    <label for="kitchen-name">Nombre de la cocina</label>
                                </th>
                                <td>
                                    <div class="acf-input">
                                        <div class="acf-input-wrap">
                                            <input type="text" id="kitchen-name" readonly value="<?= $kitchen['kitchen_name'] ?>">
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <!-- Kitchen Whatsapp -->
                            <tr>
                                <th scope="row">
                                    <label for="kitchen-whatsapp">Contacto de WhatsApp <span class="acf-required">*</span></label>
                                </th>
                                <td>
                                    <div class="acf-input">
                                        <div class="acf-input-prepend">34</div>
                                        <div class="acf-input-wrap">
                                            <input type="number" id="kitchen-whatsapp" name="kitchen_whatsapp" 
                                                class="acf-is-prepended" value="<?= $kitchen['kitchen_whatsapp'] ?>" 
                                                step="1" min="0" placeholder="E.g.: 679955232" required
                                            >
                                        </div>
                                        <p class="description">Se usará para el botón de contacto a través de Whatsapp en la app.</p>
                                    </div>
                                </td>
                            </tr>

                            <!-- Kitchen description -->
                            <tr>
                                <th scope="row">
                                    <label for="kitchen-description">Contacto de WhatsApp <span class="acf-required">*</span></label>
                                </th>
                                <td>
                                    <div class="acf-input">
                                        <?php wp_editor( $kitchen['kitchen_description'], 'mettaabox_ID', [
                                            'textarea_name' => 'kitchen_description',
                                            'teeny' => true
                                        ] ) ?>
                                    </div>
                                    <p class="description">
                                        Se mostrará en una ventana emergente al tocar sobre el nombre en el apartado Ubicaciones de la app
                                    </p>
                                </td>
                            </tr>


                            <tr>
                                <th colspan="2">
                                    <h3 class="m-0">Pedidos</h3>
                                </th>
                            </tr>

                            <!-- Kitchen minimum order -->
                            <tr>
                                <th scope="row">
                                    <label for="kitchen-min-order">Pedido mínimo <span class="acf-required">*</span></label>
                                </th>
                                <td>
                                    <div class="acf-input" style="display:flex">
                                        <div class="acf-input-wrap">
                                            <div class="acf-input-append">€</div>
                                            <input type="number" id="kitchen-min-order" name="kitchen_min_order" 
                                                class="acf-is-appended" value="<?= $kitchen['kitchen_min_order'] ?>" 
                                                step="1" min="0" placeholder="E.g.: 679955232" required
                                            >
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <!-- Kitchen status -->
                            <tr>
                                <th scope="row">
                                    <label for="kitchen-status">Estado <span class="acf-required">*</span></label>
                                </th>
                                <td>
                                    <div class="acf-field" data-name="kitchen_status" data-type="button_group">
                                        <div class="acf-input"><?php
                                            $group = new acf_field_button_group();
                                            $group->initialize();
                                            $group->render_field([
                                                'name' => 'kitchen_status',
                                                'allow_null' => false,
                                                'choices' => [
                                                    'blocked' => 'Saturada',
                                                    'closed'  => 'Cerrada',
                                                    'opened'  => 'Abierta',
                                                ],
                                                'value' => $kitchen['kitchen_status']
                                            ]);
                                        ?></div>
                                    </div>
                                </td>
                            </tr>

                            <!-- Kitchen blocked message -->
                            <tr>
                                <th scope="row">
                                    <label for="kitchen-message-blocked">Mensajes de aviso de cocina saturada <span class="acf-required">*</span></label>
                                </th>
                                <td>
                                    <div class="acf-input">
                                        <div class="acf-input-wrap">
                                            <input type="text" class="w-full m-0" id="kitchen-message-blocked" name="blocked_kitchen_message" value="<?= $kitchen['blocked_kitchen_message'] ?>">
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <!-- Kitchen closed message -->
                            <tr>
                                <th scope="row">
                                    <label for="kitchen-message-closed">Mensajes de aviso de cocina cerrada <span class="acf-required">*</span></label>
                                </th>
                                <td>
                                    <div class="acf-input">
                                        <div class="acf-input-wrap">
                                            <input type="text" class="w-full m-0" id="kitchen-message-closed" name="closed_kitchen_message" value="<?= $kitchen['closed_kitchen_message'] ?>">
                                        </div>
                                    </div>
                                </td>
                            </tr>


                            <tr>
                                <th colspan="2">
                                    <h3 class="m-0">Facturación</h3>
                                </th>
                            </tr>

                            <!-- Kitchen invoice name -->
                            <tr>
                                <th scope="row">
                                    <label for="kitchen-invoice-name">Razón social <span class="acf-required">*</span></label>
                                </th>
                                <td>
                                    <div class="acf-input">
                                        <div class="acf-input-wrap">
                                            <input type="text" class="w-full m-0" id="kitchen-invoice-name" name="invoiceName" value="<?= $kitchen['invoiceName'] ?>">
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <!-- Kitchen invoice vat number -->
                            <tr>
                                <th scope="row">
                                    <label for="kitchen-invoice-nif">NIF <span class="acf-required">*</span></label>
                                </th>
                                <td>
                                    <div class="acf-input">
                                        <div class="acf-input-wrap">
                                            <input type="text" class="w-full m-0" id="kitchen-invoice-nif" name="invoiceNIF" value="<?= $kitchen['invoiceNIF'] ?>">
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <!-- Kitchen invoice address -->
                            <tr>
                                <th scope="row">
                                    <label for="kitchen-invoice-address">Dirección <span class="acf-required">*</span></label>
                                </th>
                                <td>
                                    <div class="acf-input">
                                        <div class="acf-input-wrap">
                                            <textarea id="kitchen-invoice-address" name="invoiceAddress"
                                                class="w-full m-0" rows="8"
                                            ><?= $kitchen['invoiceAddress'] ?></textarea>
                                        </div>
                                    </div>
                                </td>
                            </tr>


                            <tr>
                                <th colspan="2">
                                    <h3 class="m-0">Pedidos en local</h3>
                                </th>
                            </tr>

                            <!-- Kitchen takeaway address -->
                            <tr>
                                <th scope="row">
                                    <label for="kitchen-takeaway-address">Dirección de recogida <span class="acf-required">*</span></label>
                                </th>
                                <td>
                                    <div class="acf-input">
                                        <div class="acf-input-wrap">
                                            <textarea id="kitchen-takeaway-address" name="takeaway_address"
                                                class="w-full m-0" rows="8"
                                            ><?= str_replace('<br />', '', $kitchen['takeaway_address']) ?></textarea>
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <!-- Kitchen takeaway max orders time -->
                            <tr>
                                <th scope="row">
                                    <label for="kitchen-takeaway-max-orders-time">Número máximo de pedidos <span class="acf-required">*</span></label>
                                </th>
                                <td>
                                    <div class="acf-input" style="display:flex">
                                        <div class="acf-input-wrap">
                                            <div class="acf-input-append">pedidos/hora</div>
                                            <input type="number" id="kitchen-takeaway-max-orders-time" name="takeaway_max_orders_time" 
                                                class="acf-is-appended" value="<?= $kitchen['takeaway_max_orders_time'] ?>" 
                                                step="1" min="1" required
                                            >
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <!-- Kitchen takeaway morning -->
                            <tr>
                                <th scope="row">
                                    <label for="kitchen-takeaway-max-orders-time">Recogidas de mañana <span class="acf-required">*</span></label>
                                </th>
                                <td>
                                    <div class="acf-input">
                                        <table class="acf-table">
                                            <thead>
                                                <tr>
                                                    <th class="acf-th" style="width: 20%;">
                                                        <label for="takeaway_start">Comienzo</label>
                                                    </th>
                                                    <th class="acf-th" style="width: 20%;">
                                                        <label for="takeaway_end">Fin</label>
                                                    </th>
                                                    <th class="acf-th" style="width: 20%;">
                                                        <label for="takeaway_interval">Intervalo</label>
                                                    </th>
                                                    <th class="acf-th" style="width: 20%;">
                                                        <label for="takeaway_limit">Límite de Pedidos</label>
                                                    </th>
                                                    <th class="acf-th" style="width: 20%;">
                                                        <label for="takeaway_open">Abierta</label>
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr class="acf-row">
                                                    <td class="acf-field">
                                                        <div class="acf-input">
                                                            <input type="time" id="takeaway_start" name="takeaway_morning_start"
                                                                value="<?= $kitchen['takeaway_morning']['start'] ?>">
                                                        </div>
                                                    </td>
                                                    <td class="acf-field">
                                                        <div class="acf-input">
                                                            <input type="time" id="takeaway_end" name="takeaway_morning_end"
                                                                value="<?= $kitchen['takeaway_morning']['end'] ?>">
                                                        </div>
                                                    </td>
                                                    <td class="acf-field">
                                                        <div class="acf-input" style="display:flex">
                                                            <input type="number" id="takeaway_interval" name="takeaway_morning_interval" 
                                                                class="acf-is-appended" step="1" min="1" required
                                                                style="padding:8px"
                                                                value="<?= $kitchen['takeaway_morning']['interval'] ?>" 
                                                            >
                                                        </div>
                                                    </td>
                                                    <td class="acf-field">
                                                        <div class="acf-input">
                                                            <input type="time" id="takeaway_limit" name="takeaway_morning_limit"
                                                                value="<?= $kitchen['takeaway_morning']['limit'] ?>">
                                                        </div>
                                                    </td>
                                                    <td class="acf-field">
                                                        <div class="acf-input" style="padding:9px">
                                                            <input type="checkbox" id="takeaway_open" name="takeaway_morning_open"
                                                                <?= $kitchen['takeaway_morning']['open'] ? 'checked' : '' ?>
                                                                autocomplete="off">
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>

                            <!-- Kitchen takeaway afternoon -->
                            <tr>
                                <th scope="row">
                                    <label for="kitchen-takeaway-max-orders-time">Recogidas de tarde <span class="acf-required">*</span></label>
                                </th>
                                <td>
                                    <div class="acf-input">
                                        <table class="acf-table">
                                            <thead>
                                                <tr>
                                                    <th class="acf-th" style="width: 20%;">
                                                        <label for="takeaway_start">Comienzo</label>
                                                    </th>
                                                    <th class="acf-th" style="width: 20%;">
                                                        <label for="takeaway_end">Fin</label>
                                                    </th>
                                                    <th class="acf-th" style="width: 20%;">
                                                        <label for="takeaway_interval">Intervalo</label>
                                                    </th>
                                                    <th class="acf-th" style="width: 20%;">
                                                        <label for="takeaway_limit">Límite de Pedidos</label>
                                                    </th>
                                                    <th class="acf-th" style="width: 20%;">
                                                        <label for="takeaway_open">Abierta</label>
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr class="acf-row">
                                                    <td class="acf-field">
                                                        <div class="acf-input">
                                                            <input type="time" id="takeaway_start" name="takeaway_afternoon_start"
                                                                value="<?= $kitchen['takeaway_afternoon']['start'] ?>">
                                                        </div>
                                                    </td>
                                                    <td class="acf-field">
                                                        <div class="acf-input">
                                                            <input type="time" id="takeaway_end" name="takeaway_afternoon_end"
                                                                value="<?= $kitchen['takeaway_afternoon']['end'] ?>">
                                                        </div>
                                                    </td>
                                                    <td class="acf-field">
                                                        <div class="acf-input" style="display:flex">
                                                            <input type="number" id="takeaway_interval" name="takeaway_afternoon_interval" 
                                                                class="acf-is-appended" step="1" min="1" required
                                                                style="padding:8px"
                                                                value="<?= $kitchen['takeaway_afternoon']['interval'] ?>" 
                                                            >
                                                        </div>
                                                    </td>
                                                    <td class="acf-field">
                                                        <div class="acf-input">
                                                            <input type="time" id="takeaway_limit" name="takeaway_afternoon_limit"
                                                                value="<?= $kitchen['takeaway_afternoon']['limit'] ?>">
                                                        </div>
                                                    </td>
                                                    <td class="acf-field">
                                                        <div class="acf-input" style="padding:9px">
                                                            <input type="checkbox" id="takeaway_open" name="takeaway_afternoon_open"
                                                                <?= $kitchen['takeaway_afternoon']['open'] ? 'checked' : '' ?>
                                                                autocomplete="off">
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>


                            <tr>
                                <th colspan="2">
                                    <h3 class="m-0">Pedidos a domicilio</h3>
                                </th>
                            </tr>

                            <!-- Kitchen delivery max orders time -->
                            <tr>
                                <th scope="row">
                                    <label for="kitchen-delivery-max-orders-time">Número máximo de pedidos <span class="acf-required">*</span></label>
                                </th>
                                <td>
                                    <div class="acf-input" style="display:flex">
                                        <div class="acf-input-wrap">
                                            <div class="acf-input-append">pedidos/hora</div>
                                            <input type="number" id="kitchen-delivery-max-orders-time" name="delivery_max_orders_time" 
                                                class="acf-is-appended" value="<?= $kitchen['delivery_max_orders_time'] ?>" 
                                                step="1" min="1" required
                                            >
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <!-- Kitchen delivery morning -->
                            <tr>
                                <th scope="row">
                                    <label for="kitchen-delivery-max-orders-time">Recogidas de mañana <span class="acf-required">*</span></label>
                                </th>
                                <td>
                                    <div class="acf-input">
                                        <table class="acf-table">
                                            <thead>
                                                <tr>
                                                    <th class="acf-th" style="width: 20%;">
                                                        <label for="delivery_start">Comienzo</label>
                                                    </th>
                                                    <th class="acf-th" style="width: 20%;">
                                                        <label for="delivery_end">Fin</label>
                                                    </th>
                                                    <th class="acf-th" style="width: 20%;">
                                                        <label for="delivery_interval">Intervalo</label>
                                                    </th>
                                                    <th class="acf-th" style="width: 20%;">
                                                        <label for="delivery_limit">Límite de Pedidos</label>
                                                    </th>
                                                    <th class="acf-th" style="width: 20%;">
                                                        <label for="delivery_open">Abierta</label>
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr class="acf-row">
                                                    <td class="acf-field">
                                                        <div class="acf-input">
                                                            <input type="time" id="delivery_start" name="delivery_morning_start"
                                                                value="<?= $kitchen['delivery_morning']['start'] ?>">
                                                        </div>
                                                    </td>
                                                    <td class="acf-field">
                                                        <div class="acf-input">
                                                            <input type="time" id="delivery_end" name="delivery_morning_end"
                                                                value="<?= $kitchen['delivery_morning']['end'] ?>">
                                                        </div>
                                                    </td>
                                                    <td class="acf-field">
                                                        <div class="acf-input" style="display:flex">
                                                            <input type="number" id="delivery_interval" name="delivery_morning_interval" 
                                                                class="acf-is-appended" step="1" min="1" required
                                                                style="padding:8px"
                                                                value="<?= $kitchen['delivery_morning']['interval'] ?>" 
                                                            >
                                                        </div>
                                                    </td>
                                                    <td class="acf-field">
                                                        <div class="acf-input">
                                                            <input type="time" id="delivery_limit" name="delivery_morning_limit"
                                                                value="<?= $kitchen['delivery_morning']['limit'] ?>">
                                                        </div>
                                                    </td>
                                                    <td class="acf-field">
                                                        <div class="acf-input" style="padding:9px">
                                                            <input type="checkbox" id="delivery_open" name="delivery_morning_open"
                                                                <?= $kitchen['delivery_morning']['open'] ? 'checked' : '' ?>
                                                                autocomplete="off">
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>

                            <!-- Kitchen delivery afternoon -->
                            <tr>
                                <th scope="row">
                                    <label for="kitchen-delivery-max-orders-time">Recogidas de tarde <span class="acf-required">*</span></label>
                                </th>
                                <td>
                                    <div class="acf-input">
                                        <table class="acf-table">
                                            <thead>
                                                <tr>
                                                    <th class="acf-th" style="width: 20%;">
                                                        <label for="delivery_start">Comienzo</label>
                                                    </th>
                                                    <th class="acf-th" style="width: 20%;">
                                                        <label for="delivery_end">Fin</label>
                                                    </th>
                                                    <th class="acf-th" style="width: 20%;">
                                                        <label for="delivery_interval">Intervalo</label>
                                                    </th>
                                                    <th class="acf-th" style="width: 20%;">
                                                        <label for="delivery_limit">Límite de Pedidos</label>
                                                    </th>
                                                    <th class="acf-th" style="width: 20%;">
                                                        <label for="delivery_open">Abierta</label>
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr class="acf-row">
                                                    <td class="acf-field">
                                                        <div class="acf-input">
                                                            <input type="time" id="delivery_start" name="delivery_afternoon_start"
                                                                value="<?= $kitchen['delivery_afternoon']['start'] ?>">
                                                        </div>
                                                    </td>
                                                    <td class="acf-field">
                                                        <div class="acf-input">
                                                            <input type="time" id="delivery_end" name="delivery_afternoon_end"
                                                                value="<?= $kitchen['delivery_afternoon']['end'] ?>">
                                                        </div>
                                                    </td>
                                                    <td class="acf-field">
                                                        <div class="acf-input" style="display:flex">
                                                            <input type="number" id="delivery_interval" name="delivery_afternoon_interval" 
                                                                class="acf-is-appended" step="1" min="1" required
                                                                style="padding:8px"
                                                                value="<?= $kitchen['delivery_afternoon']['interval'] ?>" 
                                                            >
                                                        </div>
                                                    </td>
                                                    <td class="acf-field">
                                                        <div class="acf-input">
                                                            <input type="time" id="delivery_limit" name="delivery_afternoon_limit"
                                                                value="<?= $kitchen['delivery_afternoon']['limit'] ?>">
                                                        </div>
                                                    </td>
                                                    <td class="acf-field">
                                                        <div class="acf-input" style="padding:9px">
                                                            <input type="checkbox" id="delivery_open" name="delivery_afternoon_open"
                                                                <?= $kitchen['delivery_afternoon']['open'] ? 'checked' : '' ?>
                                                                autocomplete="off">
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td></td>
                                <td>
                                    <button class="button button-primary">Guardar cambios</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </form>
            </div>
        </div>

</div>