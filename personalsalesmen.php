<?php
/**
 * 2007-2018 PrestaShop
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
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2018 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

include_once(dirname(__FILE__).'/PersonalSalesMenExtra.php');

class PersonalSalesMen extends Module
{
    protected $html = '';
    protected $merchant_mails;
    protected $merchant_order;

    const __MA_MAIL_DELIMITOR__ = "\n";

    public function __construct()
    {
        $this->name = 'personalsalesmen';
        $this->tab = 'administration';
        $this->version = '3.0.5';
        $this->author = 'Inform-all';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_); 
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Personal Salesmen');
        $this->description = $this->l('Link customers to employees. And have employees responsible for their orders.');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall, and remove all the current links?');
        if (!Configuration::get('PERSONALSALES_NAME')) {
            $this->warning = $this->l('No name provided.');
        }
        $this->module_key = 'adc8f5cfac749f8b03c631861e9dc996';

    }

    protected function init()
    {
        $this->merchant_mails = str_replace(',', self::__MA_MAIL_DELIMITOR__, (string)Configuration::get('MA_MERCHANT_MAILS'));
        $this->merchant_order = (int)Configuration::get('MA_MERCHANT_ORDER');
        $this->generalCEOptions = (int)Configuration::get('ma_generalCEOptions');
        $this->SendMailOption = (int)Configuration::get('SendMailOption');
    }

    public function install($delete_params = true)
    {
        if (!parent::install() ||
            !$this->registerHook('actionValidateOrder'))
            return false;

        if ($delete_params)
        {
        
            Configuration::updateValue('MA_MERCHANT_ORDER', 1);
            Configuration::updateValue('MA_MERCHANT_MAILS', Configuration::get('PS_SHOP_EMAIL'));
            Configuration::updateValue('ma_generalCEOptions', 1);
            Configuration::updateValue('SendMailOption', 1);

            $sql = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'personalsalesmen'.'`
                (
                    `id` int(6) NOT NULL AUTO_INCREMENT,
                    `id_employee` INTEGER UNSIGNED NOT NULL DEFAULT \'1\',
                    `id_customer` INTEGER UNSIGNED NOT NULL,
                    PRIMARY KEY(`id`)
                ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci';

            if (!Db::getInstance()->execute($sql))
                return false;


            $sql2 = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'personalsalesmen_Groups'.'`
                (
                    `id` int(6) NOT NULL AUTO_INCREMENT,
                    `id_employee` INTEGER UNSIGNED NOT NULL DEFAULT \'1\',
                    `id_group` INTEGER UNSIGNED NOT NULL,
                    PRIMARY KEY(`id`)
                ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci';

            if (!Db::getInstance()->execute($sql2))
                return false;

        }
        
        return true;
    }

    public function uninstall($delete_params = true)
    {
        if ($delete_params)
        {
            Configuration::deleteByName('MA_MERCHANT_ORDER');
            Configuration::deleteByName('MA_MERCHANT_MAILS');
            Configuration::deleteByName('ma_generalCEOptions');
            Configuration::deleteByName('SendMailOption');

            if (!Db::getInstance()->execute('DROP TABLE IF EXISTS '._DB_PREFIX_.PersonalSalesMenExtra::$definition['table']))
                return false;
        }

        return parent::uninstall();
    }

    public function reset()
    {
        if (!$this->uninstall(false))
            return false;
        if (!$this->install(false))
            return false;

        return true;
    }

    public function getContent()
    {
        $this->html = '';

        $this->postProcess();

        if (Tools::isSubmit('submit'.$this->name)){
            $myModuleName = str(Tools::getValue('PERSONALSALES_NAME'));
            if (
                !$myModuleName ||
                empty($myModuleName) ||
                !Validate::isGenericName($myModuleName)
            ) {
                $this->html .= $this->displayError($this->l('Invalid Configuration value'));
            } 
        }
        $this->html .= $this->displayForm();
        return $this->html;
    }


    protected function postProcess()
    {
        $errors = array();

        if (Tools::isSubmit('submitNewEmpCustomerLink'))
        {
            $employee = Tools::getValue('MA_EMPTOCUS_SELECTION');
            $customer = Tools::getValue('MA_CUSTOMER_SELECTION');

            if ($employee != 0 && $customer != 0){
                $q= Db::getInstance()->insert('personalsalesmen', array(
                    'id_employee' => (int)$employee,
                    'id_customer'      => (int)$customer
                )); 
            }
        }
        else if (Tools::isSubmit('submitNewGrpCustomerLink'))
        {
            $employee = Tools::getValue('MA_EMP_SELECTION');
            $group = Tools::getValue('MA_GRP_SELECTION');

            if ($employee != 0 && $group != 0){
                $q= Db::getInstance()->insert('personalsalesmen_Groups', array(
                    'id_employee' => (int)$employee,
                    'id_group'      => (int)$group
                )); 
            }
        }
        else if (Tools::isSubmit('DeleteEmpLink'))
        {
            $employee = (string)Tools::getValue('MA_EMP_DELETION');
            
            if ($employee != 0 ){
                $empcust= 'DELETE FROM `'._DB_PREFIX_.'personalsalesmen` WHERE id_employee = ("'.pSQL($employee).'")';
                if(!Db::getInstance()->Execute($empcust)){$this->errorlog[] = $this->l("ERROR");}
                $empgrp= 'DELETE FROM `'._DB_PREFIX_.'personalsalesmen_Groups` WHERE id_employee = ("'.pSQL($employee).'")';
                if(!Db::getInstance()->Execute($empgrp)){$this->errorlog[] = $this->l("ERROR");}    
            }
            
            
        }
        else if (Tools::isSubmit('ma_generalCEOptionsSubmit'))
        {
            if (!Configuration::updateValue('ma_generalCEOptions', (int)Tools::getValue('ma_generalCEOptions')))
                $errors[] = $this->l('Cannot update settings');
        }
        else if (Tools::isSubmit('SendMailOptionSubmit'))
        {
            if (!Configuration::updateValue('SendMailOption', (int)Tools::getValue('SendMailOption')))
                $errors[] = $this->l('Cannot update settings');
        }

        if (count($errors) > 0)
            $this->html .= $this->displayError(implode('<br />', $errors));
        else
            $this->html .= $this->displayConfirmation($this->l('Settings updated successfully'));

    }

    public function getAllMessages($id)
    {
        $messages = Db::getInstance()->executeS('
            SELECT `message`
            FROM `'._DB_PREFIX_.'message`
            WHERE `id_order` = '.(int)$id.'
            ORDER BY `id_message` ASC');
        $result = array();
        foreach ($messages as $message)
            $result[] = $message['message'];

        return implode('<br/>', $result);
    }

    public function hookActionValidateOrder($params)
    {
        if (Configuration::get('SendMailOption') == 0) {
            return;
        }
        
        // Getting differents vars
        $context = Context::getContext();
        $id_lang = (int)$context->language->id;
        $id_shop = (int)$context->shop->id;
        $currency = $params['currency'];
        $order = $params['order'];
        $customer = $params['customer'];
        $configuration = Configuration::getMultiple(
            array(
                'PS_SHOP_EMAIL',
                'PS_MAIL_METHOD',
                'PS_MAIL_SERVER',
                'PS_MAIL_USER',
                'PS_MAIL_PASSWD',
                'PS_SHOP_NAME',
                'PS_MAIL_COLOR'
            ), $id_lang, null, $id_shop
        );
        $delivery = new Address((int)$order->id_address_delivery);
        $invoice = new Address((int)$order->id_address_invoice);
        $order_date_text = Tools::displayDate($order->date_add);
        $carrier = new Carrier((int)$order->id_carrier);
        $message = $this->getAllMessages($order->id);

        if (!$message || empty($message))
            $message = $this->l('No message');

        $items_table = '';

        $products = $params['order']->getProducts();
        $customized_datas = Product::getAllCustomizedDatas((int)$params['cart']->id);
        Product::addCustomizationPrice($products, $customized_datas);
        foreach ($products as $key => $product)
        {
            $unit_price = Product::getTaxCalculationMethod($customer->id) == PS_TAX_EXC ? $product['product_price'] : $product['product_price_wt'];

            $customization_text = '';
            if (isset($customized_datas[$product['product_id']][$product['product_attribute_id']]))
            {
                foreach ($customized_datas[$product['product_id']][$product['product_attribute_id']][$order->id_address_delivery] as $customization)
                {
                    if (isset($customization['datas'][Product::CUSTOMIZE_TEXTFIELD]))
                        foreach ($customization['datas'][Product::CUSTOMIZE_TEXTFIELD] as $text)
                            $customization_text .= $text['name'].': '.$text['value'].'<br />';

                    if (isset($customization['datas'][Product::CUSTOMIZE_FILE]))
                        $customization_text .= count($customization['datas'][Product::CUSTOMIZE_FILE]).' '.$this->l('image(s)').'<br />';

                    $customization_text .= '---<br />';
                }
                if (method_exists('Tools', 'rtrimString'))
                    $customization_text = Tools::rtrimString($customization_text, '---<br />');
                else
                    $customization_text = preg_replace('/---<br \/>$/', '', $customization_text);
            }

            $url = $context->link->getProductLink($product['product_id']);
            $items_table .=
                '<tr style="background-color:'.($key % 2 ? '#DDE2E6' : '#EBECEE').';">
                    <td style="padding:0.6em 0.4em;">'.$product['product_reference'].'</td>
                    <td style="padding:0.6em 0.4em;">
                        <strong><a href="'.$url.'">'.$product['product_name'].'</a>'
                            .(isset($product['attributes_small']) ? ' '.$product['attributes_small'] : '')
                            .(!empty($customization_text) ? '<br />'.$customization_text : '')
                        .'</strong>
                    </td>
                    <td style="padding:0.6em 0.4em; text-align:right;">'.Tools::displayPrice($unit_price, $currency, false).'</td>
                    <td style="padding:0.6em 0.4em; text-align:center;">'.(int)$product['product_quantity'].'</td>
                    <td style="padding:0.6em 0.4em; text-align:right;">'
                        .Tools::displayPrice(($unit_price * $product['product_quantity']), $currency, false)
                    .'</td>
                </tr>';
        }
        foreach ($params['order']->getCartRules() as $discount)
        {
            $items_table .=
                '<tr style="background-color:#EBECEE;">
                        <td colspan="4" style="padding:0.6em 0.4em; text-align:right;">'.$this->l('Voucher code:').' '.$discount['name'].'</td>
                    <td style="padding:0.6em 0.4em; text-align:right;">-'.Tools::displayPrice($discount['value'], $currency, false).'</td>
            </tr>';
        }
        if ($delivery->id_state)
            $delivery_state = new State((int)$delivery->id_state);
        if ($invoice->id_state)
            $invoice_state = new State((int)$invoice->id_state);

        if (Product::getTaxCalculationMethod($customer->id) == PS_TAX_EXC)
            $total_products = $order->getTotalProductsWithoutTaxes();
        else
            $total_products = $order->getTotalProductsWithTaxes();

        $order_state = $params['orderStatus'];

        // Filling-in vars for email
        $template_vars = array(
            '{firstname}' => $customer->firstname,
            '{lastname}' => $customer->lastname,
            '{email}' => $customer->email,
            '{delivery_block_txt}' => PersonalSalesMenExtra::getFormatedAddress($delivery, "\n"),
            '{invoice_block_txt}' => PersonalSalesMenExtra::getFormatedAddress($invoice, "\n"),
            '{delivery_block_html}' => PersonalSalesMenExtra::getFormatedAddress(
                $delivery, '<br />', array(
                    'firstname' => '<span style="color:'.$configuration['PS_MAIL_COLOR'].'; font-weight:bold;">%s</span>',
                    'lastname' => '<span style="color:'.$configuration['PS_MAIL_COLOR'].'; font-weight:bold;">%s</span>'
                )
            ),
            '{invoice_block_html}' => PersonalSalesMenExtra::getFormatedAddress(
                $invoice, '<br />', array(
                    'firstname' => '<span style="color:'.$configuration['PS_MAIL_COLOR'].'; font-weight:bold;">%s</span>',
                    'lastname' => '<span style="color:'.$configuration['PS_MAIL_COLOR'].'; font-weight:bold;">%s</span>'
                )
            ),
            '{delivery_company}' => $delivery->company,
            '{delivery_firstname}' => $delivery->firstname,
            '{delivery_lastname}' => $delivery->lastname,
            '{delivery_address1}' => $delivery->address1,
            '{delivery_address2}' => $delivery->address2,
            '{delivery_city}' => $delivery->city,
            '{delivery_postal_code}' => $delivery->postcode,
            '{delivery_country}' => $delivery->country,
            '{delivery_state}' => $delivery->id_state ? $delivery_state->name : '',
            '{delivery_phone}' => $delivery->phone ? $delivery->phone : $delivery->phone_mobile,
            '{delivery_other}' => $delivery->other,
            '{invoice_company}' => $invoice->company,
            '{invoice_firstname}' => $invoice->firstname,
            '{invoice_lastname}' => $invoice->lastname,
            '{invoice_address2}' => $invoice->address2,
            '{invoice_address1}' => $invoice->address1,
            '{invoice_city}' => $invoice->city,
            '{invoice_postal_code}' => $invoice->postcode,
            '{invoice_country}' => $invoice->country,
            '{invoice_state}' => $invoice->id_state ? $invoice_state->name : '',
            '{invoice_phone}' => $invoice->phone ? $invoice->phone : $invoice->phone_mobile,
            '{invoice_other}' => $invoice->other,
            '{order_name}' => $order->reference,
            '{order_status}' => $order_state->name,
            '{shop_name}' => $configuration['PS_SHOP_NAME'],
            '{date}' => $order_date_text,
            '{carrier}' => (($carrier->name == '0') ? $configuration['PS_SHOP_NAME'] : $carrier->name),
            '{payment}' => Tools::substr($order->payment, 0, 32),
            '{items}' => $items_table,
            '{total_paid}' => Tools::displayPrice($order->total_paid, $currency),
            '{total_products}' => Tools::displayPrice($total_products, $currency),
            '{total_discounts}' => Tools::displayPrice($order->total_discounts, $currency),
            '{total_shipping}' => Tools::displayPrice($order->total_shipping, $currency),
            '{total_tax_paid}' => Tools::displayPrice(
                ($order->total_products_wt - $order->total_products) + ($order->total_shipping_tax_incl - $order->total_shipping_tax_excl),
                $currency,
                false
            ),
            '{total_wrapping}' => Tools::displayPrice($order->total_wrapping, $currency),
            '{currency}' => $currency->sign,
            '{gift}' => (bool)$order->gift,
            '{gift_message}' => $order->gift_message,
            '{message}' => $message
        );

        // Shop iso
        $iso = Language::getIsoById((int)Configuration::get('PS_LANG_DEFAULT'));

        $sql = 'SELECT `id_employee` FROM '._DB_PREFIX_.'personalsalesmen WHERE id_customer = '.(int)$customer->id.'';
        $empl_ids = Db::getInstance()->executeS($sql);

        $sql2 = 'SELECT `id_employee` FROM '._DB_PREFIX_.'personalsalesmen_Groups WHERE id_group = '.(int)$customer->id_default_group.'';
        $emplgrp_ids = Db::getInstance()->executeS($sql2);

        if (!is_null($empl_ids) && !is_null($emplgrp_ids))
        {
            $all_employees = array_merge($emplgrp_ids, $empl_ids);
        }else{
            $all_employees = $empl_ids;
        }

        $merchant_mailsb = array();


        foreach ($all_employees as $empl_array)
        {
            foreach($empl_array as $empl_id)
            {
                $personal_emp_mail = Db::getInstance()->getValue('SELECT `email` FROM `'._DB_PREFIX_.'employee` WHERE `id_employee` = '.(int)$empl_id.' ');
                array_push($merchant_mailsb, explode(self::__MA_MAIL_DELIMITOR__, $personal_emp_mail));
            }
        }


        $merchant_mailsa = explode(self::__MA_MAIL_DELIMITOR__, $this->merchant_mails);
        $merchant_mails = array_merge($merchant_mailsa, $merchant_mailsb);

        // Send 1 email by merchant mail, because Mail::Send doesn't work with an array of recipients
        foreach ($merchant_mails as $merchant_mail)
        {
            // Default language
            $mail_id_lang = $id_lang;
            $mail_iso = $iso;

            // Use the merchant lang if he exists as an employee
            $results = Db::getInstance()->executeS('
                SELECT `id_lang` FROM `'._DB_PREFIX_.'employee`
                WHERE `email` = \''.pSQL($merchant_mail).'\'
            ');
            if ($results)
            {
                $user_iso = Language::getIsoById((int)$results[0]['id_lang']);
                if ($user_iso)
                {
                    $mail_id_lang = (int)$results[0]['id_lang'];
                    $mail_iso = $user_iso;
                }
            }

            $dir_mail = false;
            if (file_exists(dirname(__FILE__).'/mails/'.$mail_iso.'/new_order.txt') &&
                file_exists(dirname(__FILE__).'/mails/'.$mail_iso.'/new_order.html'))
                $dir_mail = dirname(__FILE__).'/mails/';

            if (file_exists(_PS_MAIL_DIR_.$mail_iso.'/new_order.txt') &&
                file_exists(_PS_MAIL_DIR_.$mail_iso.'/new_order.html'))
                $dir_mail = _PS_MAIL_DIR_;

            if ($dir_mail)
                Mail::Send(
                    $mail_id_lang,
                    'new_order',
                    sprintf(Mail::l('New order : #%d - %s', $mail_id_lang), $order->id, $order->reference),
                    $template_vars,
                    $merchant_mail,
                    null,
                    $configuration['PS_SHOP_EMAIL'],
                    $configuration['PS_SHOP_NAME'],
                    null,
                    null,
                    $dir_mail,
                    null,
                    $id_shop
                );
        }
    }

    public function displayForm()
    {
        $GO = array(
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
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right',
                    'name' => 'ma_generalCEOptionsSubmit',
                )
            ),
        );

        $SMO = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Sending mail options'),
                    'icon' => 'icon-briefcase'
                ),
                'input' => array(
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
                    'name' => 'SendMailOptionSubmit',
                )
            ),
        );

        $grplink = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Group & Employee links'),
                    'icon' => 'icon-bell'
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
                            'query' => Group::getGroups(true),
                            'id' => 'id_group',
                            'name'=>'name',
                            'default' => array(
                                'value' => '',
                                'label' => $this->l('Select Group')
                            )
                        )
                    ),
                ),
                'submit' => array(
                        'title' => $this->l('Save'),
                        'class' => 'btn btn-success pull-right',
                        'name' => 'submitNewGrpCustomerLink',
                )
            )
        );

        $ret = array(
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
                        'title' => $this->l('Save'),
                        'class' => 'btn btn-success pull-right',
                        'name' => 'submitNewEmpCustomerLink',
                )
            )
        );

        $DeleteEmpLink = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Delete all the links from this employee'),
                    'icon' => 'icon-bell-slash'
                ),
                'input' => array(
                    array(
                        'type' => 'select',
                        'label' => $this->l('Select employee to delete links from'),
                        'name' => 'MA_EMP_DELETION',
                        'desc' => $this->l('This wont delete the employee, only his/her links with a customer'),
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
                ),
                'submit' => array(
                        'title' => $this->l('Save'),
                        'class' => 'btn btn-danger pull-right',
                        'name' => 'DeleteEmpLink',
                )
            )
        );

        $emplistquery = Db::getInstance()->ExecuteS('SELECT *,CONCAT(OG.lastname," - ",EG.email) as display FROM `'._DB_PREFIX_.'personalsalesmen` C INNER JOIN '._DB_PREFIX_.'employee OG 
            ON OG.id_employee = C.id_employee INNER JOIN '._DB_PREFIX_.'customer EG ON EG.id_customer = C.id_customer ORDER BY C.id_employee');

        $emplist = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Current linked customers and employee'),
                    'icon' => 'icon-check-square'
                ),
                'input' => array(
                    array(
                        'type' => 'checkbox',
                        'desc' => $this->l('This are the currently active links between employee and customers.'),
                        'label' => $this->l('Linked'),
                        'name' => 'deleteId[]',
                        'values' => array(
                            'query' => $emplistquery,
                            'id' => 'id',
                            'name' => 'display',
                            'default' => array(
                                'value' => '',
                                'label' => $this->l('None')
                            )
                        )
                    ),
                ),
            )
        );


        $grplistquery = Db::getInstance()->ExecuteS('SELECT *,CONCAT(OG.lastname," - ",EG.name) as display FROM `'._DB_PREFIX_.'personalsalesmen_Groups` C INNER JOIN '._DB_PREFIX_.'employee OG 
            ON OG.id_employee = C.id_employee INNER JOIN '._DB_PREFIX_.'group_lang EG ON EG.id_group = C.id_group AND EG.id_lang = '.(int)$this->context->language->id.' ORDER BY C.id_employee');

        $grplist = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Current linked groups and employee'),
                    'icon' => 'icon-check-square'
                ),
                'input' => array(
                    array(
                        'type' => 'checkbox',
                        'desc' => $this->l('This are the currently active links between employee and groups.'),
                        'label' => $this->l('Linked'),
                        'name' => 'deleteId[]',
                        'values' => array(
                            'query' => $grplistquery,
                            'id' => 'id',
                            'name' => 'display',
                            'default' => array(
                                'value' => '',
                                'label' => $this->l('None')
                            )
                        )
                    ),
                ),
            )
        );


        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->module = $this;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitPersonalSalesMenExtraConfiguration';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name
            .'&tab_module='.$this->tab
            .'&module_name='.$this->name;
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        return $helper->generateForm(array($GO, $SMO, $ret, $grplink, $DeleteEmpLink, $emplist, $grplist));
    }

    public function getConfigFieldsValues()
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

}