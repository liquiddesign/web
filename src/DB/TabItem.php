<?php

declare(strict_types=1);

namespace Web\DB;

use StORM\Entity;

/**
 * @table
 */
class TabItem extends Entity
{
	/**
	 * @column{"mutations":true}
	 */
	public ?string $name;
	
	/**
	 * @column{"mutations":true,"type":"longtext"}
	 */
	public ?string $text;
	
	/**
	 * Priorita
	 * @column
	 */
	public int $priority = 10;
	
	/**
	 * @column
	 */
	public bool $hidden = false;
	
	/**
	 * Tab
	 * @constraint{"onUpdate":"CASCADE","onDelete":"CASCADE"}
	 * @relation
	 */
	public Tab $tab;
	
	/**
	 * @column{"mutations":true}
	 */
	public bool $active = false;
}
