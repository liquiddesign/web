<?php

declare(strict_types=1);

namespace Web\DB;

use StORM\Entity;

/**
 * @table
 */
class TinyTemplate extends Entity
{
	/**
	 * @column{"mutations":true}
	 */
	public ?string $name;

	/**
	 * @column {"type":"longtext","mutations":true}
	 */
	public ?string $description;

	/**
	 * @column{"type":"text"}
	 */
	public string $html = '';

	/**
	 * @column{"type":"text"}
	 */
	public ?string $htmlTailwind;

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
	 * @column
	 */
	public bool $servis = false;

	/**
	 * @column{"mutations":true}
	 */
	public bool $active = false;
}