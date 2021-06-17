<?php

declare(strict_types=1);

namespace Web\DB;

use StORM\Entity;

/**
 * @table
 */
class HomepageSlide extends Entity
{
	const IMAGE_DIR = 'homepage_slides';
	const DESKTOP_MIN_WIDTH = 820;
	const DESKTOP_MIN_HEIGHT = 410;
	const MOBILE_MIN_WIDTH = 700;
	const MOBILE_MIN_HEIGHT = 700;

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
