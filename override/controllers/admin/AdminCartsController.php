<?php
/**
 * 2007-2019 PrestaShop and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2019 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

/**
 * @property Cart $object
 */
class AdminCartsController extends AdminCartsControllerCore
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'cart';
        $this->className = 'Cart';
        $this->lang = false;
        $this->explicitSelect = true;

        parent::__construct();

        $this->addRowAction('view');
        $this->addRowAction('delete');
        $this->allow_export = true;
        $this->_orderWay = 'DESC';

        if($this->context->employee->id_profile != 1 && Configuration::get('ma_generalCEOptions') != 1)
        {
            $this->_select = 'CONCAT(LEFT(c.`firstname`, 1), \'. \', c.`lastname`) `customer`, a.id_cart total, ca.name carrier, o.id_order,
              IF (IFNULL(o.id_order, \'' . $this->trans('Non ordered', array(), 'Admin.Orderscustomers.Feature') . '\') = \'' . $this->trans('Non ordered', array(), 'Admin.Orderscustomers.Feature') . '\', IF(TIME_TO_SEC(TIMEDIFF(\'' . pSQL(date('Y-m-d H:i:00', time())) . '\', a.`date_add`)) > 86400, \'' . $this->trans('Abandoned cart', array(), 'Admin.Orderscustomers.Feature') . '\', \'' . $this->trans('Non ordered', array(), 'Admin.Orderscustomers.Feature') . '\'), o.id_order) AS status, IF(o.id_order, 1, 0) badge_success, IF(o.id_order, 0, 1) badge_danger, IF(co.id_guest, 1, 0) id_guest';
            $this->_join = 'LEFT JOIN ' . _DB_PREFIX_ . 'customer c ON (c.id_customer = a.id_customer)
            LEFT JOIN ' . _DB_PREFIX_ . 'currency cu ON (cu.id_currency = a.id_currency)
            LEFT JOIN ' . _DB_PREFIX_ . 'carrier ca ON (ca.id_carrier = a.id_carrier)
            LEFT JOIN ' . _DB_PREFIX_ . 'orders o ON (o.id_cart = a.id_cart)
            LEFT JOIN `' . _DB_PREFIX_ . 'personalsalesmen` psm ON c.id_customer = psm.id_customer
            LEFT JOIN (
                SELECT `id_guest`
                FROM `' . _DB_PREFIX_ . 'connections`
                WHERE
                    TIME_TO_SEC(TIMEDIFF(\'' . pSQL(date('Y-m-d H:i:00', time())) . '\', `date_add`)) < 1800
                LIMIT 1
           ) AS co ON co.`id_guest` = a.`id_guest`';
            $subQuery = new DbQuery();
            $subQuery
                ->select('pcg.`id_customer`');
            $subQuery->from('customer_group', 'pcg');
            $subQuery->innerJoin('personalsalesmen_Groups', 'psmg', 'psmg.`id_group` = pcg.`id_group`');
            $subQuery->where('psmg.`id_employee` = '.(int)$this->context->employee->id);
            $this->_where = 'AND (psm.`id_employee`  = '.(int)$this->context->employee->id.' OR c.`id_customer` IN ('.$subQuery.'))';
        }else{
            $this->_select = 'CONCAT(LEFT(c.`firstname`, 1), \'. \', c.`lastname`) `customer`, a.id_cart total, ca.name carrier, o.id_order,
            IF (IFNULL(o.id_order, \'' . $this->trans('Non ordered', array(), 'Admin.Orderscustomers.Feature') . '\') = \'' . $this->trans('Non ordered', array(), 'Admin.Orderscustomers.Feature') . '\', IF(TIME_TO_SEC(TIMEDIFF(\'' . pSQL(date('Y-m-d H:i:00', time())) . '\', a.`date_add`)) > 86400, \'' . $this->trans('Abandoned cart', array(), 'Admin.Orderscustomers.Feature') . '\', \'' . $this->trans('Non ordered', array(), 'Admin.Orderscustomers.Feature') . '\'), o.id_order) AS status, IF(o.id_order, 1, 0) badge_success, IF(o.id_order, 0, 1) badge_danger, IF(co.id_guest, 1, 0) id_guest';
            $this->_join = 'LEFT JOIN ' . _DB_PREFIX_ . 'customer c ON (c.id_customer = a.id_customer)
            LEFT JOIN ' . _DB_PREFIX_ . 'currency cu ON (cu.id_currency = a.id_currency)
            LEFT JOIN ' . _DB_PREFIX_ . 'carrier ca ON (ca.id_carrier = a.id_carrier)
            LEFT JOIN ' . _DB_PREFIX_ . 'orders o ON (o.id_cart = a.id_cart)
            LEFT JOIN (
                SELECT `id_guest`
                FROM `' . _DB_PREFIX_ . 'connections`
                WHERE
                    TIME_TO_SEC(TIMEDIFF(\'' . pSQL(date('Y-m-d H:i:00', time())) . '\', `date_add`)) < 1800
                LIMIT 1
           ) AS co ON co.`id_guest` = a.`id_guest`';

        }

        if (Tools::getValue('action') && Tools::getValue('action') == 'filterOnlyAbandonedCarts') {
            $this->_having = 'status = \'' . $this->trans('Abandoned cart', array(), 'Admin.Orderscustomers.Feature') . '\'';
        } else {
            $this->_use_found_rows = false;
        }

        $this->fields_list = array(
            'id_cart' => array(
                'title' => $this->trans('ID', array(), 'Admin.Global'),
                'align' => 'text-center',
                'class' => 'fixed-width-xs',
            ),
            'status' => array(
                'title' => $this->trans('Order ID', array(), 'Admin.Orderscustomers.Feature'),
                'align' => 'text-center',
                'badge_danger' => true,
                'havingFilter' => true,
            ),
            'customer' => array(
                'title' => $this->trans('Customer', array(), 'Admin.Global'),
                'filter_key' => 'c!lastname',
            ),
            'total' => array(
                'title' => $this->trans('Total', array(), 'Admin.Global'),
                'callback' => 'getOrderTotalUsingTaxCalculationMethod',
                'orderby' => false,
                'search' => false,
                'align' => 'text-right',
                'badge_success' => true,
            ),
            'carrier' => array(
                'title' => $this->trans('Carrier', array(), 'Admin.Shipping.Feature'),
                'align' => 'text-left',
                'callback' => 'replaceZeroByShopName',
                'filter_key' => 'ca!name',
            ),
            'date_add' => array(
                'title' => $this->trans('Date', array(), 'Admin.Global'),
                'align' => 'text-left',
                'type' => 'datetime',
                'class' => 'fixed-width-lg',
                'filter_key' => 'a!date_add',
            ),
        );

        if (Configuration::get('PS_GUEST_CHECKOUT_ENABLED')) {
            $this->fields_list['id_guest'] = array(
                'title' => $this->trans('Online', array(), 'Admin.Global'),
                'align' => 'text-center',
                'type' => 'bool',
                'havingFilter' => true,
                'class' => 'fixed-width-xs',
            );
        }

        $this->shopLinkType = 'shop';

        $this->bulk_actions = array(
            'delete' => array(
                'text' => $this->trans('Delete selected', array(), 'Admin.Actions'),
                'confirm' => $this->trans('Delete selected items?', array(), 'Admin.Notifications.Warning'),
                'icon' => 'icon-trash',
            ),
        );
    }
}