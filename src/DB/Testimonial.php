<?php

declare(strict_types=1);

namespace Web\DB;

use StORM\Entity;

/**
 * @table
 */
class Testimonial extends Entity
{
	public const IMAGE_DIR = 'testimonials';
	
	public const MIN_WIDTH = 300;
	
	public const MIN_HEIGHT = 300;
	
	/**
	 * @column{"mutations":true}
	 */
	public string $name;
	
	/**
	 * @column
	 */
	public string $fullName;
	
	/**
	 * @column{"mutations":true,"type":"longtext"}
	 */
	public ?string $text;
	
	/**
	 * @column
	 */
	public ?string $image;
	
	/**
	 * @column
	 */
	public ?string $logo;
	
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
