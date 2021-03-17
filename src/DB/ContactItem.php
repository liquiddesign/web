<?php

declare(strict_types=1);

namespace Web\DB;

use StORM\Entity;

/**
 * @table
 */
class ContactItem extends Entity
{
	/**
	 * Název
	 * @column{"mutations":true}
	 */
	public ?string $name = null;

	/**
	 * Telefon, více oddělené středníkem
	 * @column
	 */
	public ?string $phone = null;

	/**
	 * Email, více oddělené středníkem
	 * @column
	 */
	public ?string $email = null;

	/**
	 * Priorita
	 * @column
	 */
	public int $priority = 10;

	/**
	 * Skryto
	 * @column
	 */
	public bool $hidden = false;
}
