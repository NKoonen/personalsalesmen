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
                'label' => $this->l('Partner offers', array())
            ),
        );
        $this->addRowAction('edit');
        $this->addRowAction('view');
        $this->addRowAction('delete');
        $this->bulk_actions = array(
            'delete' => array(
                'text' => $this->l('Delete selected', array()),
                'confirm' => $this->l('Delete selected items?', array()),
                'icon' => 'icon-trash'
            )
        );
        $this->default_form_language = $this->context->language->id;
        $titles_array = array();
        $genders = Gender::getGenders($this->context->language->id);
        foreach ($genders as $gender) {
            
            $titles_array[$gender->id_gender] = $gender->name;
        }
        $this->_join = 'LEFT JOIN '._DB_PREFIX_.'gender_lang gl ON (a.id_gender = gl.id_gender AND gl.id_lang = '.(int)$this->context->language->id.')';
        $this->_use_found_rows = false;
        $this->fields_list = array(
            'id_customer' => array(
                'title' => $this->l('ID', array()),
                'align' => 'text-center',
                'class' => 'fixed-width-xs'
            ),
            'title' => array(
                'title' => $this->l('Social title', array()),
                'filter_key' => 'a!id_gender',
                'type' => 'select',
                'list' => $titles_array,
                'filter_type' => 'int',
                'order_key' => 'gl!name'
            ),
            'firstname' => array(
                'title' => $this->l('First name', array())
            ),
            'lastname' => array(
                'title' => $this->l('Last name', array())
            ),
            'email' => array(
                'title' => $this->l('Email address', array())
            ),
        );
        if (Configuration::get('PS_B2B_ENABLE')) {
            $this->fields_list = array_merge($this->fields_list, array(
                'company' => array(
                    'title' => $this->l('Company', array())
                ),
            ));
        }
        $this->fields_list = array_merge($this->fields_list, array(
            'total_spent' => array(
                'title' => $this->l('Sales', array()),
                'type' => 'price',
                'search' => false,
                'havingFilter' => true,
                'align' => 'text-right',
                'badge_success' => true
            ),
            'active' => array(
                'title' => $this->l('Enabled', array()),
                'align' => 'text-center',
                'active' => 'status',
                'type' => 'bool',
                'orderby' => false,
                'filter_key' => 'a!active'
            ),
            'newsletter' => array(
                'title' => $this->l('Newsletter', array()),
                'align' => 'text-center',
                'callback' => 'printNewsIcon',
            ),
            'optin' => array(
                'title' => $this->l('Partner offers', array()),
                'align' => 'text-center',
                'callback' => 'printOptinIcon',
            ),
            'date_add' => array(
                'title' => $this->l('Registration', array()),
                'type' => 'date',
                'align' => 'text-right'
            ),
            'connect' => array(
                'title' => $this->l('Last visit', array()),
                'type' => 'datetime',
                'search' => false,
                'havingFilter' => true
            )
        ));
        $this->shopLinkType = 'shop';
        $this->shopShareDatas = Shop::SHARE_CUSTOMER;
        
        if($this->context->employee->id_profile != 1 && Configuration::get('ma_generalCEOptions') != 1)
        {
            $subQuery = new DbQuery();
            $subQuery
                ->select('pcg.`id_customer`');
            $subQuery->from('customer_group', 'pcg');
            $subQuery->innerJoin('personalsalesmen_Groups', 'psmg', 'psmg.`id_group` = pcg.`id_group`');
            $subQuery->where('psmg.`id_employee` = '.(int)$this->context->employee->id);

              $this->_select = '
                a.date_add,
                gl.name as title,
                (SELECT SUM(total_paid_real / conversion_rate) FROM '._DB_PREFIX_.'orders o WHERE o.id_customer = a.id_customer '.Shop::addSqlRestriction(Shop::SHARE_ORDER, 'o').' AND o.valid = 1) as total_spent,
                (SELECT c.date_add FROM '._DB_PREFIX_.'guest g LEFT JOIN '._DB_PREFIX_.'connections c ON c.id_guest = g.id_guest WHERE g.id_customer = a.id_customer ORDER BY c.date_add DESC LIMIT 1) as connect
            ';

            $this->_join ='LEFT JOIN '._DB_PREFIX_.'gender_lang gl ON (a.id_gender = gl.id_gender AND gl.id_lang = '.(int)$this->context->language->id.')
                LEFT JOIN `'._DB_PREFIX_.'personalsalesmen` psm  ON (psm.`id_customer` = a.`id_customer`) ';

            $this->_where = 'AND (psm.`id_employee`  = '.(int)$this->context->employee->id.' OR a.`id_customer` IN ('.$subQuery.'))';


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
        if (Shop::isFeatureActive() && (Shop::getContext() == Shop::CONTEXT_ALL || Shop::getContext() == Shop::CONTEXT_GROUP)) {
            $this->can_add_customer = false;
        }
        self::$meaning_status = array(
            'open' => $this->l('Open', array()),
            'closed' => $this->l('Closed', array()),
            'pending1' => $this->l('Pending 1', array()),
            'pending2' => $this->l('Pending 2', array())
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
            //PERSONAL FILTER
            

            $subQuery = new DbQuery();
            $subQuery
                ->select('pcg.`id_customer`');
            $subQuery->from('customer_group', 'pcg');
            $subQuery->innerJoin('personalsalesmen_Groups', 'psmg', 'psmg.`id_group` = pcg.`id_group`');
            $subQuery->where('psmg.`id_employee` = '.$this->context->employee->id_profile);

            $CustRay = array();
            $sql = 'SELECT c.* FROM `' . _DB_PREFIX_ . 'customer` c LEFT JOIN `' . _DB_PREFIX_ . 'personalsalesmen` psm ON c.id_customer = psm.id_customer WHERE (psm.`id_employee`  = '.$this->context->employee->id_profile.' OR c.`id_customer` IN ('.$subQuery.'))';
            if ($Listresults = Db::getInstance()->ExecuteS($sql))
            {
                foreach ($Listresults as $row)
                {
                    
                    array_push($CustRay, $row['id_customer']);
                }
            }
            foreach ($searches as $search) {
                if (!empty($search) && $results = Customer::searchByName($search, 50)) {
                    foreach ($results as $result) {
                        if ($result['active']) {
                            if(in_array($result['id_customer'], $CustRay)) {
                                    $result['fullname_and_email'] = $result['firstname'].' '.$result['lastname'].' - '.$result['email'];
                                    $customers[$result['id_customer']] = $result;
                            }
                        }
                    }
                }
            }

        }else{
            //DEFAULT PRESTA
            foreach ($searches as $search) {
                if (!empty($search) && $results = Customer::searchByName($search, 50)) {
                    foreach ($results as $result) {
                        if ($result['active']) {
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