<?php
namespace Util;


class StoreUtil {

	private const TAX_WEIGHT = 0.1;


	public static function addTax(int $price): int{
		return $price+$price*self::TAX_WEIGHT;
	}
}
