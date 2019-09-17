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

class Customer extends CustomerCore
{
     /**
     * Light back office search for customers.
     *
     * @param string $query Searched string
     * @param int|null $limit Limit query results
     *
     * @return array|false|mysqli_result|PDOStatement|resource|null Corresponding customers
     *
     * @throws PrestaShopDatabaseException
     */
    /*
    * module: personalsalesmen
    * date: 2019-09-16 16:48:31
    * version: 3.0.4
    */
    public static function searchByName($query, $limit = null)
    {
        $context = Context::getContext();
        if((int)$context->employee->id_profile != 1 && Configuration::get('ma_generalCEOptions') != 1)
        {

            $subQuery = new DbQuery();

            $subQuery
                ->select('pcg.`id_customer`');
            $subQuery->from('customer_group', 'pcg');
            $subQuery->innerJoin('personalsalesmen_Groups', 'psmg', 'psmg.`id_group` = pcg.`id_group`');
            $subQuery->where('psmg.`id_employee` = '.(int)$context->employee->id);
            ///


            $sql = 'SELECT c.* FROM `' . _DB_PREFIX_ . 'customer` c LEFT JOIN `' . _DB_PREFIX_ . 'personalsalesmen` psm ON c.id_customer = psm.id_customer WHERE 1';
            // WORKING $sql .= ' AND psm.`id_employee`  = '.(int)$context->employee->id.'';
            $sql .= ' AND (psm.`id_employee`  = '.(int)$context->employee->id.' OR c.`id_customer` IN ('.$subQuery.'))';


            $search_items = explode(' ', $query);
            $research_fields = array('id_customer', 'firstname', 'lastname', 'email');
            if (Configuration::get('PS_B2B_ENABLE')) {
                $research_fields[] = 'company';
            }
            $items = array();
            foreach ($research_fields as $field) {
                foreach ($search_items as $item) {
                    $items[$item][] = $field . ' LIKE \'%' . pSQL($item) . '%\' ';
                }
            }
            foreach ($items as $likes) {
                $sql .= ' AND (c.' . implode(' OR ', $likes) . ') ';
            }
            $sql .= Shop::addSqlRestriction(Shop::SHARE_CUSTOMER);
            if ($limit) {
                $sql .= ' LIMIT 0, ' . (int) $limit;
            }

            return  Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

        }else{
            $sql = 'SELECT *
                    FROM `' . _DB_PREFIX_ . 'customer`
                    WHERE 1';
            $search_items = explode(' ', $query);
            $research_fields = array('id_customer', 'firstname', 'lastname', 'email');
            if (Configuration::get('PS_B2B_ENABLE')) {
                $research_fields[] = 'company';
            }
            $items = array();
            foreach ($research_fields as $field) {
                foreach ($search_items as $item) {
                    $items[$item][] = $field . ' LIKE \'%' . pSQL($item) . '%\' ';
                }
            }
            foreach ($items as $likes) {
                $sql .= ' AND (' . implode(' OR ', $likes) . ') ';
            }
            $sql .= Shop::addSqlRestriction(Shop::SHARE_CUSTOMER);
            if ($limit) {
                $sql .= ' LIMIT 0, ' . (int) $limit;
            }
            return  Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
        }
    



    }
}