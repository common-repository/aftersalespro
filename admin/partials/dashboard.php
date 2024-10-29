<div class="card">
    <form action="" method="post">
        <?php
        wp_nonce_field('aftersalesprogr', '_wpnonce');
        submit_button('Αποθήκευση');
        ?>

        <p style="text-align: center;">
            <?php echo esc_html_e('To setup the plugin successfully, please refer to the documentation that can be found');?>
            <a href="https://aftersalespro.readme.io/reference/woocommerce" target="_blank">
                <?php echo esc_html_e('here', 'aftersalespro'); ?>
            </a>
        </p>
        <div class="col-md-6">
            <h3>
                <?php echo esc_html_e('Authorization Settings', 'aftersalespro'); ?>
                <br />
                <small><?php echo esc_html_e('You can create the API token for you account in the administration panel of your user profile at AfterSalesPro. You can access the page', 'aftersalespro'); ?>
                    <a href="https://aftersalespro.gr/panel/platform" target="_blank">
                        <?php echo esc_html_e('here', 'aftersalespro'); ?>
                    </a>
                </small>
                <br />
                <?php if ($validCredentials === true): ?>
                    <p style="color: #1a8300; font-weight: bold;">
                        <?php echo esc_html_e('Valid API token - Successful Connection', 'aftersalespro'); ?>
                    </p>
                <?php elseif ($validCredentials === false): ?>
                    <p style="color: #8c0615; font-weight: bold;">
                        <?php echo esc_html_e('Invalid API token', 'aftersalespro'); ?>
                    </p>
                <?php endif; ?>
            </h3>
        </div>
        <div class="col-md-6">
            <table>
                <tr>
                    <th><label for="aftersalesprogr_api_token">API Token</label></th>
                    <td>
                        <input type="text" name="aftersalesprogr_api_token" id="aftersalesprogr_api_token" value="<?php echo get_option('aftersalesprogr_api_token', ''); ?>" />
                    </td>
                </tr>
            </table>
        </div>

        <?php if ($validCredentials === true): ?>

            <?php if (!count($carriersResponse['carriers'])) : ?>
                <p style="color: #8c0615; font-weight: bold; text-align: center">
                    <?php echo esc_html_e('There are no carriers installed in your account. Please add a carrier in order to proceed. Visit AfterSalesPro panel to add a new carrier', 'aftersalespro'); ?>
                    <a href="https://aftersalespro.gr/panel/shipping_agency" target="_blank">
                        <?php echo esc_html_e('here', 'aftersalespro'); ?>
                    </a>
                </p>
            <?php else: ?>
                <div class="col-md-6">
                    <h3>
                        <?php echo esc_html_e('Settings for Shipping Methods at Checkout', 'aftersalespro'); ?>
                    </h3>
                </div>
                <div class="col-md-6">
                    <table>
                        <tr>
                            <th><label for="enabled"><?php echo esc_html_e('Activate new shipping methods at checkout', 'aftersalespro'); ?></label></th>
                            <td>
                                <select name="enabled" id="enabled">
                                    <option value="no"><?php echo esc_html_e('Disabled', 'aftersalespro'); ?></option>
                                    <option value="yes" <?php if ($settings['enabled'] == 'yes') : ?>selected="selected"<?php endif; ?>><?php echo  esc_html_e('Enabled', 'aftersalespro'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="freeShippingUpperLimit"><?php echo esc_html_e('Free shipping for order value grater than (€)', 'aftersalespro'); ?></label></th>
                            <td>
                                <input type="number" name="freeShippingUpperLimit" id="freeShippingUpperLimit" value="<?php echo intval($settings['freeShippingUpperLimit'] ?? ''); ?>" />
                            </td>
                        </tr>
                        <tr>
                            <th><label for="defaultWeight"><?php echo esc_html_e('Default order weight (if this is not provided from the system)', 'aftersalespro'); ?></label></th>
                            <td>
                                <input type="number" name="defaultWeight" id="defaultWeight" value="<?php echo intval($settings['defaultWeight'] ?? ''); ?>" />
                            </td>
                        </tr>
                    </table>

                    <table style="margin-top: 30px;">
                        <tr>
                            <th><label for="fallbackMethodEnabled"><?php echo esc_html_e('Activate FallBack*', 'aftersalespro'); ?></label></th>
                            <td>
                                <select name="fallbackMethodEnabled" id="fallbackMethodEnabled">
                                    <option value="no"><?php echo esc_html_e('Disabled', 'aftersalespro'); ?></option>
                                    <option value="yes" <?php if ($settings['fallbackMethodEnabled'] == 'yes') : ?>selected="selected"<?php endif; ?>><?php echo  esc_html_e('Enabled', 'aftersalespro'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="fallbackMethodTitle"><?php echo esc_html_e('Shipping Method Title for the FallBack', 'aftersalespro'); ?></label></th>
                            <td>
                                <input type="text" name="fallbackMethodTitle" id="fallbackMethodTitle" value="<?php echo esc_attr($settings['fallbackMethodTitle'] ?? ''); ?>" />
                            </td>
                        </tr>
                        <tr>
                            <th><label for="fallbackMethodCost"><?php echo esc_html_e('Shipping cost in case of FallBack', 'aftersalespro'); ?></label></th>
                            <td>
                                <input type="number" name="fallbackMethodCost" id="fallbackMethodCost" value="<?php echo intval($settings['fallbackMethodCost'] ?? ''); ?>" />
                            </td>
                        </tr>
                    </table>

                    <p>
                        <?php echo esc_html_e('*With the activation of FallBack, in case of failure, the system will show one shipping option with a flat/fixed cost. If FallBack service is disabled, the system will not show any new shipping method at checkout.', 'aftersalespro'); ?>
                    </p>
                </div>

                <div class="col-md-6">
                    <h3>
                        <?php echo esc_html_e('Tracking Widget Settings', 'aftersalespro'); ?>
                        <br />
                        <small>
                            <?php echo esc_html_e('You can setup your tracking widget and find your URL in your administration page at AfterSalesPro. Please click', 'aftersalespro'); ?>
                            <a href="https://aftersalespro.gr/panel/services/tracking-widget/edit">
                                 <?php echo esc_html_e('here', 'aftersalespro'); ?>
                            </a>
                        </small>
                    </h3>
                </div>
                <div class="col-md-6">
                    <table>
                        <tr>
                            <th><label for="aftersalesprogr_trackingwidget_status"><?php echo esc_html_e('Status', 'aftersalespro'); ?></label></th>
                            <td>
                                <select name="aftersalesprogr_trackingwidget_status" id="aftersalesprogr_trackingwidget_status">
                                    <option value="0"><?php echo esc_html_e('Disabled', 'aftersalespro'); ?></option>
                                    <option value="1" <?php if (get_option('aftersalesprogr_trackingwidget_status')) : ?>selected="selected"<?php endif; ?>><?php echo  esc_html_e('Enabled', 'aftersalespro'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="aftersalesprogr_trackingwidget_uuid">Url</label></th>
                            <td>
                                <input type="text" name="aftersalesprogr_trackingwidget_uuid" id="aftersalesprogr_trackingwidget_uuid" value="<?php echo get_option('aftersalesprogr_trackingwidget_uuid', ''); ?>" />
                                <small><?php echo esc_html_e('i.e.', 'aftersalespro'); ?> https://ext.aftersalespro.gr/tracking-widget/f492321f-2117-aaaa-855d-c4921321315b52</small>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <?php echo esc_html_e('Use shortcode', 'aftersalespro'); ?><pre>[aftersalesprogr_tracking_widget]</pre> <?php echo esc_html_e('to show tracking widget in any page or widget', 'aftersalespro'); ?>.
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="col-md-6">
                    <h3>
                        <?php echo esc_html_e('Advanced Settings', 'aftersalespro'); ?>
                        <br />
                        <small>
                            <?php echo esc_html_e('Please DO NOT change these settings unless you have received certain instructions by AfterSalesPro team.', 'aftersalespro'); ?>
                        </small>
                    </h3>
                </div>
                <div class="col-md-6">
                    <table>
                        <tr>
                            <th><label for="aftersalesprogr_order_data_mapper">Order Data Mapper fn</label></th>
                            <td>
                                <input type="text" name="aftersalesprogr_order_data_mapper" id="aftersalesprogr_order_data_mapper" value="<?php echo get_option('aftersalesprogr_order_data_mapper', ''); ?>" />
                            </td>
                        </tr>
                        <tr>
                            <th><label for="aftersalesprogr_product_mapper">Map Product fn</label></th>
                            <td>
                                <input type="text" name="aftersalesprogr_product_mapper" id="aftersalesprogr_product_mapper" value="<?php echo get_option('aftersalesprogr_product_mapper', ''); ?>" />
                            </td>
                        </tr>
                    </table>
                </div>
            <?php endif;?>
        <?php
        endif;
        submit_button('Αποθήκευση');
        ?>
    </form>
    <p>
        <a target="_blank" href="https://calendly.com/aftersalesprogr/suite-presentation">
            <img src="<?php echo plugin_dir_url(__FILE__).'../images/calendly-cta.png'; ?>"
                 alt="AfterSalesPro Presentation"
                 style="max-width: 891px; width: 60%; margin: 20px auto; display: block;" />
        </a>
    </p>
    <p>
        <strong><?php echo esc_html_e('Setup Information', 'aftersalespro'); ?></strong>
        <?php foreach ($setupInformation as $information): ?>
            | <?php echo($information['label']); ?> <?php echo($information['value']); ?>
        <?php endforeach; ?>
    </p>
</div>
