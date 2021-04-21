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
	 * @column
	 */
	public string $name;
	
	/**
	 * @column
	 */
	public int $priority = 10;

	/**
	 * @column
	 */
	public ?string $path;

	/**
	 * Pomocí repositářové metody getTree(array $orderBy)
	 * @var \Web\DB\MenuType[]
	 */
	public array $children = [];

	/**
	 * Nadřazený
	 * @relation
	 * @constraint{"onUpdate":"CASCADE","onDelete":"CASCADE"}
	 */
	public ?MenuType $ancestor;
}