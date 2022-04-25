<?php

declare(strict_types=1);

namespace Web\DB;

use StORM\Entity;
use StORM\RelationCollection;

/**
 * @table
 */
class Author extends Entity
{
	public const IMAGE_DIR = 'authors';
	
	public const MIN_WIDTH = 300;
	
	public const MIN_HEIGHT = 300;
	
	/**
	 * @column{"mutations":true}
	 */
	public ?string $name;
	
	/**
	 * @column{"mutations":true,"type":"longtext"}
	 */
	public ?string $text;
	
	/**
	 * @column
	 */
	public ?string $image;
	
	/**
	 * @column{"mutations":true}
	 */
	public ?string $position;
	
	/**
	 * @column
	 */
	public ?string $linkedInUrl;
	
	/**
	 * @column{"mutations":true}
	 */
	public bool $active = false;
	
	/**
	 * Články
	 * @relation
	 * @var \StORM\RelationCollection<\Web\DB\News>|\Web\DB\News[]
	 */
	public RelationCollection $articles;
}
