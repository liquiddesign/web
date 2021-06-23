<?php

declare(strict_types=1);

namespace Web\DB;

use StORM\Entity;

/**
 * @table
 */
class Contact extends Entity
{
	public const IMAGE_DIR = 'contacts';
	
	/**
	 * @column
	 */
	public string $fullName;
	
	/**
	 * @column
	 */
	public ?string $phone;
	
	/**
	 * @column
	 */
	public ?string $email;
	
	/**
	 * @column
	 */
	public string $image = '';
	
	/**
	 * @column{"mutations":true}
	 */
	public ?string $position;
	
	/**
	 * @column{"unique":true}
	 */
	public string $id;
	
	/**
	 * @column{"mutations":true}
	 */
	public bool $active = false;
}
