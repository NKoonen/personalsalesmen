<?php
/**
* 2007-2020 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
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
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2020 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class Personalsalesmen extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'personalsalesmen';
        $this->tab = 'administration';
        $this->version = '4.0.0';
        $this->author = 'Inform-all';
        $this->need_instance = 1;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;


        parent::__construct();

        $this->displayName = $this->l('Personal Salesmen');
        $this->description = $this->l('Link customers to employees. And have employees responsible for their orders.');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall, and remove all the current links?');

        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('MA_MERCHANT_ORDER', 1);
        Configuration::updateValue('ma_generalCEOptions', 0);
        Configuration::updateValue('SendMailOption', 1);

        include(dirname(__FILE__).'/sql/install.php');

        return parent::install() &&
            $this->registerHook('actionCustomerGridQueryBuilderModifier') &&
            $this->registerHook('actionOrderGridQueryBuilderModifier') &&
            $this->registerHook('actionAddressGridQueryBuilderModifier') &&
            $this->registerHook('actionValidateOrder');
    }

    public function uninstall()
    {
        Configuration::deleteByName('MA_MERCHANT_ORDER');
        Configuration::deleteByName('ma_generalCEOptions');
        Configuration::deleteByName('SendMailOption');

        include(dirname(__FILE__).'/sql/uninstall.php');

        return parent::uninstall();
    }

    /**
     * @param array $params
     */
    public function hookActionOrderGridQueryBuilderModifier(array $params)
    {
        if ($this->context->employee->id_profile != 1 && Configuration::get('ma_generalCEOptions') != 1) {
            var_dump("Filter active");
            /** @var CustomerFilters $searchCriteria */
            $searchCriteria = $params['search_criteria'];

            /** @var QueryBuilder $searchQueryBuilder */
            $searchQueryBuilder = $params['search_query_builder'];
            $searchQueryBuilder->leftJoin(
                'cu',
                '`' . pSQL(_DB_PREFIX_) . 'personalsalesmen`',
                'psm',
                'psm.`id_customer` = cu.`id_customer`'
            );
            $subQuery = new DbQuery();
            $subQuery
                ->select('pcg.`id_customer`');
            $subQuery->from('customer_group', 'pcg');
            $subQuery->innerJoin('personalsalesmen_Groups', 'psmg', 'psmg.`id_group` = pcg.`id_group`');
            $subQuery->where('psmg.`id_employee` = ' . (int)$this->context->employee->id);
            $searchQueryBuilder->andWhere('(psm.`id_employee`  = ' . (int)$this->context->employee->id . ' OR cu.`id_customer` IN (' . $subQuery . '))');
        }

    }

    /**
     * @param array $params
     */
    public function hookActionAddressGridQueryBuilderModifier(array $params)
    {
        var_dump("AddressHooked");
        if ($this->context->employee->id_profile != 1 && Configuration::get('ma_generalCEOptions') != 1) {
            var_dump("Filter active");
            /** @var CustomerFilters $searchCriteria */
            $searchCriteria = $params['search_criteria'];

            /** @var QueryBuilder $searchQueryBuilder */
            $searchQueryBuilder = $params['search_query_builder'];
            $searchQueryBuilder->leftJoin(
                'a',
                '`' . pSQL(_DB_PREFIX_) . 'personalsalesmen`',
                'psm',
                'psm.`id_customer` = a.`id_customer`'
            );
            $subQuery = new DbQuery();
            $subQuery
                ->select('pcg.`id_customer`');
            $subQuery->from('customer_group', 'pcg');
            $subQuery->innerJoin('personalsalesmen_Groups', 'psmg', 'psmg.`id_group` = pcg.`id_group`');
            $subQuery->where('psmg.`id_employee` = ' . (int)$this->context->employee->id);
            $searchQueryBuilder->andWhere('(psm.`id_employee`  = ' . (int)$this->context->employee->id . ' OR a.`id_customer` IN (' . $subQuery . '))');
        }

    }


    /**
     * @param array $params
     */
    public function hookActionCustomerGridQueryBuilderModifier(array $params)
    {
        if ($this->context->employee->id_profile != 1 && Configuration::get('ma_generalCEOptions') != 1) {
            /** @var CustomerFilters $searchCriteria */
            $searchCriteria = $params['search_criteria'];

            /** @var QueryBuilder $searchQueryBuilder */
            $searchQueryBuilder = $params['search_query_builder'];
            $searchQueryBuilder->leftJoin(
                'c',
                '`' . pSQL(_DB_PREFIX_) . 'personalsalesmen`',
                'psm',
                'psm.`id_customer` = c.`id_customer`'
            );
            $subQuery = new DbQuery();
            $subQuery
                ->select('pcg.`id_customer`');
            $subQuery->from('customer_group', 'pcg');
            $subQuery->innerJoin('personalsalesmen_Groups', 'psmg', 'psmg.`id_group` = pcg.`id_group`');
            $subQuery->where('psmg.`id_employee` = ' . (int)$this->context->employee->id);
            $searchQueryBuilder->andWhere('(psm.`id_employee`  = ' . (int)$this->context->employee->id . ' OR c.`id_customer` IN (' . $subQuery . '))');
        }

    }

    public function hookActionValidateOrder($params)
    {
        if (Configuration::get('SendMailOption') == 0) {
            return;
        }

        $customer = $params['customer'];
        $sql = 'SELECT `id_employee` FROM ' . _DB_PREFIX_ . 'personalsalesmen WHERE id_customer = ' . (int)$customer->id . '';
        $empl_ids = Db::getInstance()->executeS($sql);

        $sql2 = 'SELECT `id_employee` FROM ' . _DB_PREFIX_ . 'personalsalesmen_Groups WHERE id_group = ' . (int)$customer->id_default_group . '';
        $emplgrp_ids = Db::getInstance()->executeS($sql2);

        if (!is_null($empl_ids) && !is_null($emplgrp_ids)) {
            $all_employees = array_merge($emplgrp_ids, $empl_ids);
        } else {
            $all_employees = $empl_ids;
        }

        foreach ($all_employees as $empl_array) {
            foreach ($empl_array as $empl_id) {
                $emp = new Employee((int) $empl_id);
                Mail::Send(
                    (int)(Configuration::get('PS_LANG_DEFAULT')), // defaut language id
                    'contact', // email template file to be use
                    ' New Order', // email subject
                    array(
                        '{email}' => Configuration::get('PS_SHOP_EMAIL'), // sender email address
                        '{message}' => 'A order has been placed' // email content
                    ),
                    $emp->email(), // receiver email address
                    $emp->lastname(), //receiver name
                    Configuration::get('PS_SHOP_EMAIL'), //from email address
                    NULL  //from name
                );
            }
        }
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitPersonalsalesmenModule')) == true) {
            $this->postProcess();
        }

        $cstmr_links = Db::getInstance()->ExecuteS('SELECT *,CONCAT(OG.lastname," - ",EG.email) as display FROM `' . _DB_PREFIX_ . 'personalsalesmen` C INNER JOIN ' . _DB_PREFIX_ . 'employee OG 
            ON OG.id_employee = C.id_employee INNER JOIN ' . _DB_PREFIX_ . 'customer EG ON EG.id_customer = C.id_customer ORDER BY C.id_employee');
        $this->context->smarty->assign('cstmr_links', $cstmr_links);

        $grp_links = Db::getInstance()->ExecuteS('SELECT *,CONCAT(OG.lastname," - ",EG.name) as display FROM `' . _DB_PREFIX_ . 'personalsalesmen_Groups` C INNER JOIN ' . _DB_PREFIX_ . 'employee OG 
            ON OG.id_employee = C.id_employee INNER JOIN ' . _DB_PREFIX_ . 'group_lang EG ON EG.id_group = C.id_group AND EG.id_lang = ' . (int)$this->context->language->id . ' ORDER BY C.id_employee');
        $this->context->smarty->assign('grp_links', $grp_links);

        $baselink = $this->context->link->getAdminLink('AdminModules', true)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name.'&submitPersonalsalesmenModule=1';

        $this->context->smarty->assign('removeGrouplink', $baselink."&removeGrouplink=");
        $this->context->smarty->assign('removeCstmrlink', $baselink."&removeCstmrlink=");


        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output.$this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitPersonalsalesmenModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm($this->getConfigForm());
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        $options_form =  array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('General options'),
                    'icon' => 'icon-cogs'
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'is_bool' => true, //retro compat 1.5
                        'label' => $this->l('Employee can see all orders and customers'),
                        'name' => 'ma_generalCEOptions',
                        'desc' => $this->l('When this option is enabled all the employees can see ALL the orders, and not just the orders from his/her linked customers.'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'is_bool' => true, //retro compat 1.5
                        'label' => $this->l('Employees recieve an email when a order is placed'),
                        'name' => 'SendMailOption',
                        'desc' => $this->l('Employees recieve and email with the order from his/her linked customers.'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right',
                    'name' => 'ma_generalCEOptionsSubmit',
                )
            ),
        );

        $group_link_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Group & Employee links'),
                    'icon' => 'icon-bullhorn'
                ),
                'input' => array(
                    array(
                        'type' => 'select',
                        'label' => $this->l('Select employee'),
                        'name' => 'MA_EMP_SELECTION',
                        'desc' => $this->l('This employee can see the order from the linked Groups'),
                        'options' => array(
                            'query' => Employee::getEmployees(true),
                            'id' => 'id_employee',
                            'name' => 'lastname',
                            'default' => array(
                                'value' => '',
                                'label' => $this->l('Select Employee')
                            )
                        )
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Select Group'),
                        'name' => 'MA_GRP_SELECTION',
                        'options' => array(
                            'query' => Group::getGroups(Context::getContext()->language->id, true),
                            'id' => 'id_group',
                            'name' => 'name',
                            'default' => array(
                                'value' => '',
                                'label' => $this->l('Select Group')
                            )
                        )
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Create link'),
                    'class' => 'btn btn-success pull-right',
                    'name' => 'submitNewGrpCustomerLink',
                )
            )
        );

        $customer_link_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Customer & Employee links'),
                    'icon' => 'icon-bell'
                ),
                'input' => array(
                    array(
                        'type' => 'select',
                        'label' => $this->l('Select employee'),
                        'name' => 'MA_EMPTOCUS_SELECTION',
                        'desc' => $this->l('This employee can see the order from the linked customer'),
                        'options' => array(
                            'query' => Employee::getEmployees(true),
                            'id' => 'id_employee',
                            'name' => 'lastname',
                            'default' => array(
                                'value' => '',
                                'label' => $this->l('Select Employee')
                            )
                        )
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Select Customer'),
                        'name' => 'MA_CUSTOMER_SELECTION',
                        'options' => array(
                            'query' => Customer::getCustomers(true),
                            'id' => 'id_customer',
                            'name' => 'email',
                            'default' => array(
                                'value' => '',
                                'label' => $this->l('Select Customer')
                            )
                        )
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Create link'),
                    'class' => 'btn btn-success pull-right',
                    'name' => 'submitNewEmpCustomerLink',
                )
            )
        );

        return array($group_link_form, $customer_link_form, $options_form);

    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'ma_generalCEOptions' => Tools::getValue('ma_generalCEOptions', Configuration::get('ma_generalCEOptions')),
            'MA_EMP_SELECTION' => Tools::getValue('MA_EMP_SELECTION', Configuration::get('MA_EMP_SELECTION')),
            'MA_EMPTOCUS_SELECTION' => Tools::getValue('MA_EMPTOCUS_SELECTION', Configuration::get('MA_EMPTOCUS_SELECTION')),
            'MA_GRP_SELECTION' => Tools::getValue('MA_GRP_SELECTION', Configuration::get('MA_GRP_SELECTION')),
            'MA_CUSTOMER_SELECTION' => Tools::getValue('MA_CUSTOMER_SELECTION', Configuration::get('MA_CUSTOMER_SELECTION')),
            'MA_EMP_DELETION' => Tools::getValue('MA_EMP_DELETION', Configuration::get('MA_EMP_DELETION')),
            'SendMailOption' => Tools::getValue('SendMailOption', Configuration::get('SendMailOption'))
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
        if (Tools::isSubmit('submitNewEmpCustomerLink')) {
            $employee = Tools::getValue('MA_EMPTOCUS_SELECTION');
            $customer = Tools::getValue('MA_CUSTOMER_SELECTION');

            if ($employee != 0 && $customer != 0) {
                $q = Db::getInstance()->insert('personalsalesmen', array(
                    'id_employee' => (int)$employee,
                    'id_customer' => (int)$customer
                ));
            }
        } else if (Tools::isSubmit('submitNewGrpCustomerLink')) {
            $employee = Tools::getValue('MA_EMP_SELECTION');
            $group = Tools::getValue('MA_GRP_SELECTION');

            if ($employee != 0 && $group != 0) {
                $q = Db::getInstance()->insert('personalsalesmen_Groups', array(
                    'id_employee' => (int)$employee,
                    'id_group' => (int)$group
                ));
            }
        }
        if (Tools::isSubmit('removeGrouplink')) {
            $IDToDelete = Tools::getValue('removeGrouplink');
            $empgrp = 'DELETE FROM `' . _DB_PREFIX_ . 'personalsalesmen_Groups` WHERE id = ("' . pSQL($IDToDelete) . '")';
            if (!Db::getInstance()->Execute($empgrp)) {
                $this->errorlog[] = $this->l("ERROR");
            }
        }
        if (Tools::isSubmit('removeCstmrlink')) {
            $IDToDelete = Tools::getValue('removeCstmrlink');
            $delcmstrlink = 'DELETE FROM `' . _DB_PREFIX_ . 'personalsalesmen` WHERE id = ("' . pSQL($IDToDelete) . '")';
            if (!Db::getInstance()->Execute($delcmstrlink)) {
                $this->errorlog[] = $this->l("ERROR");
            }
        }
    }
}
