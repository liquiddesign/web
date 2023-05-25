<?php

declare(strict_types=1);

namespace Web\DB;

use Base\Entity\ShopEntity;

/**
 * @table
 * @index{"name":"setting_name","unique":true,"columns":["name", "fk_shop"]}
 */
class Setting extends ShopEntity
{
	/**
	 * Jméno
	 * @unique
	 * @column
	 */
	public string $name;

	/**
	 * Hodnota
	 * @column{"type":"longtext"}
	 */
	public ?string $value = null;
}
