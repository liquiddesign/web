<?php

declare(strict_types=1);

namespace Web\DB;

use StORM\Entity;

/**
 * @table
 */
class Map extends Entity
{
	/**
	 * @column
	 */
	public string $name;
	
	/**
	 * @column{"unique":true}
	 */
	public string $id;
	
	/**
	 * @column
	 */
	public string $gpsx;
	
	/**
	 * @column
	 */
	public string $gpsy;
	
	/**
	 * @column
	 */
	public string $width;
	
	/**
	 * @column
	 */
	public string $height;
	
	/**
	 * @column
	 */
	public int $zoom = 15;
	
	/**
	 * @column
	 */
	public string $address;
	
	/**
	 * @column{"mutations":true}
	 */
	public bool $active = false;
}
