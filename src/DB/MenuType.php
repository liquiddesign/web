<?php

declare(strict_types=1);

namespace Web\DB;

use StORM\Entity;

/**
 * @table
 */
class MenuType extends Entity
{
	/**
	 * @column{"mutations":true}
	 */
	public string $name;
	
	/**
	 * @column
	 */
	public int $priority = 10;

	/**
	 * Maximální úroven zanoření
	 * @column
	 */
	public int $maxLevel = 2;
}