<?php
/**
 * 2007-2018 PrestaShop
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
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2019 PrestaShop SA
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

class AdminAddressesController extends AdminAddressesControllerCore
{
	/** @var array countries list */
    protected $countries_array = array();

    public function __construct()
    {
        $this->bootstrap = true;
        $this->required_database = true;
        $this->required_fields = array('company', 'address2', 'postcode', 'other', 'phone', 'phone_mobile', 'vat_number', 'dni');
        $this->table = 'address';
        $this->className = 'CustomerAddress';
        $this->lang = false;
        $this->addressType = 'customer';
        $this->explicitSelect = true;

        parent::__construct();

        $this->addRowAction('edit');
        $this->addRowAction('delete');
        $this->bulk_actions = array(
            'delete' => array(
                'text' => $this->trans('Delete selected', array(), 'Admin.Notifications.Info'),
                'confirm' => $this->trans('Delete selected items?', array(), 'Admin.Notifications.Info'),
                'icon' => 'icon-trash',
            ),
        );

        $this->allow_export = true;

        if (!Tools::getValue('realedit')) {
            $this->deleted = true;
        }

        $countries = Country::getCountries($this->context->language->id);
        foreach ($countries as $country) {
            $this->countries_array[$country['id_country']] = $country['name'];
        }

        $this->fields_list = array(
            'id_address' => array(
                'title' => $this->trans('ID', array(), 'Admin.Global'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ),
            'firstname' => array(
                'title' => $this->trans('First Name', array(), 'Admin.Global'),
                'filter_key' => 'a!firstname',
                'maxlength' => 30,
            ),
            'lastname' => array(
                'title' => $this->trans('Last Name', array(), 'Admin.Global'),
                'filter_key' => 'a!lastname',
                'maxlength' => 30,
            ),
            'address1' => array(
                'title' => $this->trans('Address', array(), 'Admin.Global'),
            ),
            'postcode' => array(
                'title' => $this->trans('Zip/postal code', array(), 'Admin.Global'),
                'align' => 'right',
            ),
            'city' => array(
                'title' => $this->trans('City', array(), 'Admin.Global'),
            ),
            'country' => array(
                'title' => $this->trans('Country', array(), 'Admin.Global'),
                'type' => 'select',
                'list' => $this->countries_array,
                'filter_key' => 'cl!id_country',
            ),
        );
        

        if($this->context->employee->id_profile != 1 && Configuration::get('ma_generalCEOptions') != 1)
        {
            $this->_select = 'cl.`name` as country';
            $this->_join = '
                LEFT JOIN `' . _DB_PREFIX_ . 'country_lang` cl ON (cl.`id_country` = a.`id_country` AND cl.`id_lang` = ' . (int) $this->context->language->id . ')
                LEFT JOIN `' . _DB_PREFIX_ . 'personalsalesmen` psm ON a.id_customer = psm.id_customer
                LEFT JOIN `' . _DB_PREFIX_ . 'customer` c ON a.id_customer = c.id_customer
                
            ';
            $this->_where = 'AND a.id_customer != 0 ' . Shop::addSqlRestriction(Shop::SHARE_CUSTOMER, 'c');

            $subQuery = new DbQuery();

            $subQuery
                ->select('pcg.`id_customer`');
            $subQuery->from('customer_group', 'pcg');
            $subQuery->innerJoin('personalsalesmen_Groups', 'psmg', 'psmg.`id_group` = pcg.`id_group`');
            $subQuery->where('psmg.`id_employee` = '.(int)$this->context->employee->id);

            $this->_where = 'AND (psm.`id_employee`  = '.(int)$this->context->employee->id.' OR c.`id_customer` IN ('.$subQuery.'))';

        }else{
            $this->_select = 'cl.`name` as country';
            $this->_join = '
                LEFT JOIN `' . _DB_PREFIX_ . 'country_lang` cl ON (cl.`id_country` = a.`id_country` AND cl.`id_lang` = ' . (int) $this->context->language->id . ')
                LEFT JOIN `' . _DB_PREFIX_ . 'customer` c ON a.id_customer = c.id_customer
                
            ';
            $this->_where = 'AND a.id_customer != 0 ' . Shop::addSqlRestriction(Shop::SHARE_CUSTOMER, 'c');
        }


        $this->_use_found_rows = false;



        
    }
    
}