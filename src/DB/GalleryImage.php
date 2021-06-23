<?php

declare(strict_types=1);

namespace Web\DB;

use StORM\Entity;

/**
 * @table{"name":"web_gallery_image"}
 */
class GalleryImage extends Entity
{
	/**
	 * @column{"mutations":true}
	 */
	public ?string $description;
	
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
	 * Galerie
	 * @constraint{"onUpdate":"CASCADE","onDelete":"CASCADE"}
	 * @relation
	 */
	public Gallery $gallery;
}
