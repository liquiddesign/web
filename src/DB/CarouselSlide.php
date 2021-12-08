<?php

declare(strict_types=1);

namespace Web\DB;

use StORM\Entity;

/**
 * @table
 */
class CarouselSlide extends Entity
{
	/**
	 * @column{"mutations":true}
	 */
	public ?string $title;
	
	/**
	 * @column{"type":"text","mutations":true}
	 */
	public ?string $text;
	
	/**
	 * @column
	 */
	public ?string $image;
	
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
	
	/**
	 * Carousel
	 * @constraint{"onUpdate":"CASCADE","onDelete":"CASCADE"}
	 * @relation
	 */
	public Carousel $carousel;
}
