<?php

declare(strict_types=1);

namespace Web\DB;

use StORM\Entity;

/**
 * @table
 */
class Textbox extends Entity
{
	/**
	 * @column{"type":"varchar","mutations":true}
	 */
	public ?string $name;
	
	/**
	 * @column{"type":"text","mutations":true}
	 */
	public ?string $text;
	
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
	 * @column{"mutations":true}
	 */
	public bool $active = false;
	
	/**
	 * Systémový
	 * @column
	 */
	public bool $systemic = false;
	
	public function isSystemic(): bool
	{
		return $this->systemic;
	}
}
