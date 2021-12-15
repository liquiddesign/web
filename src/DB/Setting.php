<?php

declare(strict_types=1);

namespace Web\DB;

/**
 * @table
 * @index{"name":"setting_name","unique":true,"columns":["name"]}
 */
class Setting extends \StORM\Entity
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
