<?php

declare(strict_types=1);

namespace Web\DB;

use StORM\Entity;
use StORM\RelationCollection;

/**
 * @table{"name":"web_news"}
 */
class News extends Entity
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
	 * @column{"type":"enum","length":"'news','article'"}
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
	 * @var \Web\DB\Tag[]|\StORM\RelationCollection<\Web\DB\Tag>
	 */
	public RelationCollection $tags;
}
