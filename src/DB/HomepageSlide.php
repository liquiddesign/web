<?php

declare(strict_types=1);

namespace Web\DB;

use Base\Entity\ShopEntity;

/**
 * @table
 */
class HomepageSlide extends ShopEntity
{
	public const IMAGE_DIR = 'homepage_slides';

	/**
	 * Text
	 * @column{"mutations":true,"type":"longtext"}
	 */
	public ?string $text = null;

	/**
	 * Cesta obrázku, pokud je typ video, tak cesta videa
	 * @column
	 */
	public ?string $image = null;

	/**
	 * Typ
	 * @column{"type":"enum","length":"'image','video'"}
	 */
	public string $type = 'image';

	/**
	 * Cesta obrázku (mobil)
	 * @column
	 */
	public ?string $imageMobile = null;

	/**
	 * URL
	 * @column{"mutations":true}
	 */
	public ?string $url = null;

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

	/**
	 * Animovat
	 * @column
	 */
	public bool $animate = false;
}
