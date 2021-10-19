<?php

declare(strict_types=1);

namespace Web\DB;

use StORM\Entity;

/**
 * @table
 */
class Cover extends Entity
{
	public const IMAGE_DIR = 'cover';
	
	/**
	 * @column{"mutations":true}
	 */
	public ?string $name;
	
	/**
	 * @column{"type":"text","mutations":true}
	 */
	public ?string $text;
	
	/**
	 * @column
	 */
	public bool $hidden = false;
	
	/**
	 * Priorita
	 * @column
	 */
	public int $priority = 10;
	
	/**
	 * @column
	 */
	public ?string $heightDesktop = null;
	
	/**
	 * @column
	 */
	public ?string $heightMobile = null;
	
	/**
	 * @column
	 */
	public ?string $bgColor = null;
	
	/**
	 * @column
	 */
	public ?string $blend = null;
	
	/**
	 * @column{"type":"text"}
	 */
	public ?string $styles = null;
	
	/**
	 * @column
	 */
	public ?string $cssClass = null;
	
	/**
	 * @column{"mutations":true}
	 */
	public ?string $showOnPage;
	
	/**
	 * @column
	 */
	public ?string $imageDesktop = null;
	
	/**
	 * @column
	 */
	public ?string $imageTablet = null;

	/**
	 * @column
	 */
	public ?string $imageMobile = null;
	
	/**
	 * @column{"mutations":true}
	 */
	public bool $active = false;
}
