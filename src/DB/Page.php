<?php

declare(strict_types=1);

namespace Web\DB;

/**
 * @table
 * @index{"name":"pages_page_type_params","unique":true,"columns":["type","params"]}
 */
class Page extends \Pages\DB\Page
{
	/**
	 * @column{"mutations":true}
	 */
	public ?string $name = null;

	/**
	 * @column{"type":"longtext","mutations":true}
	 */
	public ?string $content = null;

	/**
	 * @column{"type":"datetime"}
	 */
	public ?string $lastmod = null;
	
	/**
	 * @column
	 */
	public ?string $changefreq = null;
	
	/**
	 * @column
	 */
	public ?float $priority = null;

	/**
	 * @column
	 */
	public bool $systemic = false;
	
	public function isSystemic(): bool
	{
		return $this->systemic;
	}

	public function isDeletable(): bool
	{
		return !$this->isSystemic();
	}
}
