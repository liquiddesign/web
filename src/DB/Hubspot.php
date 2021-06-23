<?php

declare(strict_types=1);

namespace Web\DB;

use StORM\Entity;

/**
 * @table
 */
class Hubspot extends Entity
{
	/**
	 * @column{"mutations":true}
	 */
	public ?string $name;
	
	/**
	 * @column{"type":"longtext"}
	 */
	public ?string $script;
	
	/**
	 * @column{"unique":true}
	 */
	public string $id;
	
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
	 * @column{"mutations":true}
	 */
	public bool $active = false;
}
