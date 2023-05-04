<?php

declare(strict_types=1);

namespace Web\DB;

use Base\Entity\ShopEntity;
use StORM\RelationCollection;

/**
 * @table{"name":"web_news"}
 */
class News extends ShopEntity
{
	public const IMAGE_DIR = 'news_images';
	
	/**
	 * @column{"mutations":true}
	 */
	public ?string $name = null;
	
	/**
	 * Perex
	 * @column{"type":"text","mutations":true}
	 */
	public ?string $perex;
	
	/**
	 * Obsah
	 * @column{"type":"longtext","mutations":true}
	 */
	public ?string $content;
	
	/**
	 * Publikováno
	 * @column{"type":"date"}
	 */
	public string $published;
	
	/**
	 * Doporučené
	 * @column
	 */
	public bool $recommended = false;
	
	/**
	 * Type
	 * @column
	 */
	public string $type = 'news';
	
	/**
	 * Skryto
	 * @column
	 */
	public bool $hidden = false;
	
	/**
	 * Název obrázku
	 * @column
	 */
	public ?string $imageFileName = null;
	
	/**
	 * Podobné tagy
	 * @relationNxN
	 * @var array<\Web\DB\Tag>|\StORM\RelationCollection<\Web\DB\Tag>
	 */
	public RelationCollection $tags;
	
	/**
	 * Author
	 * @constraint{"onUpdate":"CASCADE","onDelete":"SET NULL"}
	 * @relation
	 */
	public ?Author $author;
	
	/**
	 * Počet hodnocení
	 * @column
	 */
	public ?int $numberOfRatings;
	
	/**
	 * Průmerné hodnocení
	 * @column
	 */
	public ?float $ratingAverage;
	
	/**
	 * Podobné články
	 * @relationNxN{"sourceViaKey":"fk_news","targetViaKey":"fk_related"}
	 * @var \StORM\RelationCollection<\Web\DB\News>|array<\Web\DB\News>
	 */
	public RelationCollection $relatedNews;
}
