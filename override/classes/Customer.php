<?php

class Customer extends CustomerCore
{
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