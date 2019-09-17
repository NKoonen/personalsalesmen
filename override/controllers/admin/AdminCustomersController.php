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
 * @copyright 2007-2018 PrestaShop SA
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

class AdminCustomersController extends AdminCustomersControllerCore
{
    protected $delete_mode;

    protected $_defaultOrderBy = 'date_add';
    protected $_defaultOrderWay = 'DESC';
    protected $can_add_customer = true;
    protected static $meaning_status = array();

    public function __construct()
    {
        $this->bootstrap = true;
        $this->required_database = true;
        $this->table = 'customer';
        $this->className = 'Customer';
        $this->lang = false;
        $this->deleted = true;
        $this->explicitSelect = true;

        $this->allow_export = true;

        parent::__construct();

        $this->required_fields = array(
            array(
                'name' => 'optin',
                'label' => $this->trans('Partner offers', array(), 'Admin.Orderscustomers.Feature')
            ),
        );

        $this->addRowAction('edit');
        $this->addRowAction('view');
        $this->addRowAction('delete');
        $this->bulk_actions = array(
            'delete' => array(
                'text' => $this->trans('Delete selected', array(), 'Admin.Notifications.Info'),
                'confirm' => $this->trans('Delete selected items?', array(), 'Admin.Notifications.Info'),
                'icon' => 'icon-trash'
            )
        );

        $this->default_form_language = $this->context->language->id;

        $titles_array = array();
        $genders = Gender::getGenders($this->context->language->id);
        foreach ($genders as $gender) {
            /** @var Gender $gender */
            $titles_array[$gender->id_gender] = $gender->name;
        }

        $this->_join = 'LEFT JOIN '._DB_PREFIX_.'gender_lang gl ON (a.id_gender = gl.id_gender AND gl.id_lang = '.(int)$this->context->language->id.')';
        $this->_use_found_rows = false;
        $this->fields_list = array(
            'id_customer' => array(
                'title' => $this->trans('ID', array(), 'Admin.Global'),
                'align' => 'text-center',
                'class' => 'fixed-width-xs'
            ),
            'title' => array(
                'title' => $this->trans('Social title', array(), 'Admin.Global'),
                'filter_key' => 'a!id_gender',
                'type' => 'select',
                'list' => $titles_array,
                'filter_type' => 'int',
                'order_key' => 'gl!name'
            ),
            'firstname' => array(
                'title' => $this->trans('First name', array(), 'Admin.Global')
            ),
            'lastname' => array(
                'title' => $this->trans('Last name', array(), 'Admin.Global')
            ),
            'email' => array(
                'title' => $this->trans('Email address', array(), 'Admin.Global')
            ),
        );

        if (Configuration::get('PS_B2B_ENABLE')) {
            $this->fields_list = array_merge($this->fields_list, array(
                'company' => array(
                    'title' => $this->trans('Company', array(), 'Admin.Global')
                ),
            ));
        }

        $this->fields_list = array_merge($this->fields_list, array(
            'total_spent' => array(
                'title' => $this->trans('Sales', array(), 'Admin.Global'),
                'type' => 'price',
                'search' => false,
                'havingFilter' => true,
                'align' => 'text-right',
                'badge_success' => true
            ),
            'active' => array(
                'title' => $this->trans('Enabled', array(), 'Admin.Global'),
                'align' => 'text-center',
                'active' => 'status',
                'type' => 'bool',
                'orderby' => false,
                'filter_key' => 'a!active'
            ),
            'newsletter' => array(
                'title' => $this->trans('Newsletter', array(), 'Admin.Global'),
                'align' => 'text-center',
                'callback' => 'printNewsIcon',
            ),
            'optin' => array(
                'title' => $this->trans('Partner offers', array(), 'Admin.Orderscustomers.Feature'),
                'align' => 'text-center',
                'callback' => 'printOptinIcon',
            ),
            'date_add' => array(
                'title' => $this->trans('Registration', array(), 'Admin.Orderscustomers.Feature'),
                'type' => 'date',
                'align' => 'text-right'
            ),
            'connect' => array(
                'title' => $this->trans('Last visit', array(), 'Admin.Orderscustomers.Feature'),
                'type' => 'datetime',
                'search' => false,
                'havingFilter' => true
            )
        ));

        $this->shopLinkType = 'shop';
        $this->shopShareDatas = Shop::SHARE_CUSTOMER;
        
        if($this->context->employee->id_profile != 1 && Configuration::get('ma_generalCEOptions') != 1)
        {            
            $this->_select = '
                a.date_add,
                gl.name as title,
                (SELECT SUM(total_paid_real / conversion_rate) FROM '._DB_PREFIX_.'orders o WHERE o.id_customer = a.id_customer '.Shop::addSqlRestriction(Shop::SHARE_ORDER, 'o').' AND o.valid = 1) as total_spent,
                (SELECT c.date_add FROM '._DB_PREFIX_.'guest g LEFT JOIN '._DB_PREFIX_.'connections c ON c.id_guest = g.id_guest WHERE g.id_customer = a.id_customer ORDER BY c.date_add DESC LIMIT 1) as connect
            ';

            $this->_join ='LEFT JOIN '._DB_PREFIX_.'gender_lang gl ON (a.id_gender = gl.id_gender AND gl.id_lang = '.(int)$this->context->language->id.') LEFT JOIN `'._DB_PREFIX_.'personalsalesmen_Groups` psmg ON (psmg.`id_employee` = '.(int)$this->context->employee->id.' AND psmg.`id_group` = a.`id_default_group`)
                LEFT JOIN `'._DB_PREFIX_.'personalsalesmen` psm  ON (psm.`id_customer` = a.`id_customer` AND psm.`id_employee` = '.(int)$this->context->employee->id.' ) ';
            $this->_where = 'AND psmg.id IS NOT NULL OR psm.id IS NOT NULL';
        }else{
            $this->_select = '
            a.date_add, gl.name as title, (
                SELECT SUM(total_paid_real / conversion_rate)
                FROM '._DB_PREFIX_.'orders o
                WHERE o.id_customer = a.id_customer
                '.Shop::addSqlRestriction(Shop::SHARE_ORDER, 'o').'
                AND o.valid = 1
            ) as total_spent, (
                SELECT c.date_add FROM '._DB_PREFIX_.'guest g
                LEFT JOIN '._DB_PREFIX_.'connections c ON c.id_guest = g.id_guest
                WHERE g.id_customer = a.id_customer
                ORDER BY c.date_add DESC
                LIMIT 1
            ) as connect';
        }

        // Check if we can add a customer
        if (Shop::isFeatureActive() && (Shop::getContext() == Shop::CONTEXT_ALL || Shop::getContext() == Shop::CONTEXT_GROUP)) {
            $this->can_add_customer = false;
        }

        self::$meaning_status = array(
            'open' => $this->trans('Open', array(), 'Admin.Orderscustomers.Feature'),
            'closed' => $this->trans('Closed', array(), 'Admin.Orderscustomers.Feature'),
            'pending1' => $this->trans('Pending 1', array(), 'Admin.Orderscustomers.Feature'),
            'pending2' => $this->trans('Pending 2', array(), 'Admin.Orderscustomers.Feature')
        );
    }

    /**
     * add to $this->content the result of Customer::SearchByName
     * (encoded in json)
     *
     * @return void
     */
    public function ajaxProcessSearchCustomers()
    {
        $searches = explode(' ', Tools::getValue('customer_search'));
        $customers = array();
        $searches = array_unique($searches);

        if($this->context->employee->id_profile != 1 && Configuration::get('ma_generalCEOptions') != 1){
            $profileID = 0;
        }else{
            $profileID = 1;
        }

        $CustRay = array();
        if ($profileID == 1){
            $sql = 'SELECT * FROM '._DB_PREFIX_.'personalsalesmen';
        }else{
            $sql = 'SELECT * FROM '._DB_PREFIX_.'personalsalesmen WHERE id_employee = '.(int)$this->context->employee->id.'';
        }
        if ($Listresults = Db::getInstance()->ExecuteS($sql))
            foreach ($Listresults as $row)
                array_push($CustRay, $row['id_customer']);

        foreach ($searches as $search) {
            if (!empty($search) && $results = Customer::searchByName($search, 50)) {
                foreach ($results as $result) {
                    if ($result['active']) {
                        if ($profileID == 1){
                            $result['fullname_and_email'] = $result['firstname'].' '.$result['lastname'].' - '.$result['email'];
                            $customers[$result['id_customer']] = $result;
                        }elseif(in_array($result['id_customer'], $CustRay)) {
                                $result['fullname_and_email'] = $result['firstname'].' '.$result['lastname'].' - '.$result['email'];
                                $customers[$result['id_customer']] = $result;
                        }
                    }
                }
            }
        }

        if (count($customers) && Tools::getValue('sf2')) {
            $to_return = $customers;
        } elseif (count($customers) && !Tools::getValue('sf2')) {
            $to_return = array(
                'customers' => $customers,
                'found' => true
            );
        } else {
            $to_return = Tools::getValue('sf2') ? array() : array('found' => false);
        }

        $this->content = json_encode($to_return);
    }
}