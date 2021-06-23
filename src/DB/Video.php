<?php

declare(strict_types=1);

namespace Web\DB;

use StORM\Entity;

/**
 * @table
 */
class Video extends Entity
{
	/**
	 * @column{"mutations":true}
	 */
	public ?string $name;
	
	/**
	 * @column
	 */
	public string $link;
	
	/**
	 * @column{"unique":true}
	 */
	public string $id;
	
	/**
	 * Priorita
	 * @column
	 */
	public int $priority = 10;
	
	/**
	 * @column{"mutations":true}
	 */
	public bool $active = false;
	
	public function getYoutubeCode(): string
	{
		return \preg_replace('/.+\/watch\?v=([\w\-]+)/i', '$1', $this->link);
	}
}
