<?php
/**
 * 2007-2015 PrestaShop
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
 * @copyright 2007-2015 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

class PersonalSalesMenExtra extends ObjectModel
{
	public $id_customer;

	public $customer_email;

	public $id_product;

	public $id_product_attribute;

	public $id_shop;

	public $id_lang;

	/**
	 * @see ObjectModel::$definition
	 */
	public static $definition = array(
		'table' => 'PersonalSalesMenExtra_customer_oos',
		'primary' => 'id_customer',
		'fields' => array(
			'id_customer' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true),
			'customer_email' => array('type' => self::TYPE_STRING, 'validate' => 'isEmail', 'required' => true),
			'id_product' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true),
			'id_product_attribute' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true),
			'id_shop' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true),
			'id_lang' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true)
		),
	);




	/*
	 * Get objects that will be viewed on "My alerts" page
	 */
	public static function getPersonalSalesMen($id_customer, $id_lang, Shop $shop = null)
	{
		if (!Validate::isUnsignedId($id_customer) || !Validate::isUnsignedId($id_lang))
			die (Tools::displayError());

		if (!$shop)
			$shop = Context::getContext()->shop;

		$customer = new Customer($id_customer);
		$products = PersonalSalesMenExtra::getProducts($customer, $id_lang);
		$products_number = count($products);

		if (empty($products) === true || !$products_number)
			return array();

		for ($i = 0; $i < $products_number; ++$i)
		{
			$obj = new Product((int)$products[$i]['id_product'], false, (int)$id_lang);
			if (!Validate::isLoadedObject($obj))
				continue;

			if (isset($products[$i]['id_product_attribute']) &&
				Validate::isUnsignedInt($products[$i]['id_product_attribute']))
			{
				$attributes = self::getProductAttributeCombination($products[$i]['id_product_attribute'], $id_lang);
				$products[$i]['attributes_small'] = '';

				if ($attributes)
				{
					foreach ($attributes as $row)
						$products[$i]['attributes_small'] .= $row['attribute_name'].', ';
				}

				$products[$i]['attributes_small'] = rtrim($products[$i]['attributes_small'], ', ');
				$products[$i]['id_shop'] = $shop->id;

				/* Get cover */
				$attrgrps = $obj->getAttributesGroups((int)$id_lang);
				foreach ($attrgrps as $attrgrp)
					if ($attrgrp['id_product_attribute'] == (int)$products[$i]['id_product_attribute']
						&& $images = Product::_getAttributeImageAssociations((int)$attrgrp['id_product_attribute']))
					{
						$products[$i]['cover'] = $obj->id.'-'.array_pop($images);
						break;
					}
			}

			if (!isset($products[$i]['cover']) || !$products[$i]['cover'])
			{
				$images = $obj->getImages((int)$id_lang);
				foreach ($images as $image)
					if ($image['cover'])
					{
						$products[$i]['cover'] = $obj->id.'-'.$image['id_image'];
						break;
					}
			}

			if (!isset($products[$i]['cover']))
				$products[$i]['cover'] = Language::getIsoById($id_lang).'-default';

			$products[$i]['link'] = $obj->getLink();
			$products[$i]['link_rewrite'] = $obj->link_rewrite;
		}

		return ($products);
	}

	/*
	 * Generate correctly the address for an email
	 */
	public static function getFormatedAddress(Address $address, $line_sep, $fields_style = array())
	{
		return AddressFormat::generateAddress($address, array('avoid' => array()), $line_sep, ' ', $fields_style);
	}

	/*
	 * Get products according to alerts
	 */
	public static function getProducts($customer, $id_lang)
	{
		$list_shop_ids = Shop::getContextListShopID(false);

		$sql = '
			SELECT ma.`id_product`, p.`quantity` AS product_quantity, pl.`name`, ma.`id_product_attribute`
			FROM `'._DB_PREFIX_.self::$definition['table'].'` ma
			JOIN `'._DB_PREFIX_.'product` p ON (p.`id_product` = ma.`id_product`)
			'.Shop::addSqlAssociation('product', 'p').'
			LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (pl.`id_product` = p.`id_product` AND pl.id_shop IN ('.implode(', ', $list_shop_ids).'))
			WHERE product_shop.`active` = 1
			AND (ma.`id_customer` = '.(int)$customer->id.' OR ma.`customer_email` = \''.pSQL($customer->email).'\')
			AND pl.`id_lang` = '.(int)$id_lang.Shop::addSqlRestriction(false, 'ma');

		return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
	}

	/*
	 * Get product combinations
	 */
	public static function getProductAttributeCombination($id_product_attribute, $id_lang)
	{
		$sql = '
			SELECT al.`name` AS attribute_name
			FROM `'._DB_PREFIX_.'product_attribute_combination` pac
			LEFT JOIN `'._DB_PREFIX_.'attribute` a ON (a.`id_attribute` = pac.`id_attribute`)
			LEFT JOIN `'._DB_PREFIX_.'attribute_group` ag ON (ag.`id_attribute_group` = a.`id_attribute_group`)
			LEFT JOIN `'._DB_PREFIX_.'attribute_lang` al ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = '.(int)$id_lang.')
			LEFT JOIN `'._DB_PREFIX_.'attribute_group_lang` agl ON (ag.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = '.(int)$id_lang.')
			LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa ON (pac.`id_product_attribute` = pa.`id_product_attribute`)
			'.Shop::addSqlAssociation('product_attribute', 'pa').'
			WHERE pac.`id_product_attribute` = '.(int)$id_product_attribute;

		return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
	}


}
