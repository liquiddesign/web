<?php

declare(strict_types=1);

namespace Web\DB;

use StORM\Entity;

/**
 * @table
 */
class Banner extends Entity
{
	public const IMAGE_DIR = 'banner';
	
	/**
	 * @column
	 */
	public ?string $image;
	
	/**
	 * @column
	 */
	public ?string $background;
	
	/**
	 * @column{"mutations":true}
	 */
	public string $headline;
	
	/**
	 * @column{"mutations":true,"type":"text"}
	 */
	public string $text;
	
	/**
	 * @column{"unique":true}
	 */
	public string $id;
	
	/**
	 * @column{"mutations":true}
	 */
	public bool $active = false;
	
	/**
	 * Systémový
	 * @column
	 */
	public bool $systemic = false;
}
