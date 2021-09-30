<?php

declare(strict_types=1);

namespace Web\DB;

use StORM\RelationCollection;

/**
 * @table
 * @index{"name":"pages_page_type_params","unique":true,"columns":["type","params"]}
 */
class Page extends \Pages\DB\Page
{
	public const IMAGE_DIR = 'page';

	public const SUBDIRS = ['background', 'opengraph'];
	
	/**
	 * @column{"mutations":true}
	 */
	public ?string $name = null;

	/**
	 * @column{"type":"longtext","mutations":true}
	 */
	public ?string $content = null;

	/**
	 * @column
	 */
	public ?string $opengraph;
	
	/**
	 * @column
	 */
	public ?string $image;
	
	/**
	 * @column
	 */
	public ?string $mobileImage;

	/**
	 * @column
	 */
	public ?string $icon = null;

	/**
	 * @column
	 */
	public ?string $secret = null;

	/**
	 * @column
	 */
	public ?string $layout = null;

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
	
	/**
	 * @column{"mutations":true}
	 */
	public ?bool $deploy = true;
	
	/**
	 * @column{"mutations":true}
	 */
	public bool $active = false;
	
	/**
	 * Dokumenty stránky
	 * @relationNxN
	 * @var \Web\DB\Document[]|\StORM\RelationCollection<\Web\DB\Document>
	 */
	public RelationCollection $documents;
	
	public function isSystemic(): bool
	{
		return $this->systemic;
	}

	public function isDeletable(): bool
	{
		return !$this->isSystemic();
	}
}
