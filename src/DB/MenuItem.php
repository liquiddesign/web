<?php

declare(strict_types=1);

namespace Web\DB;

use StORM\Entity;
use StORM\RelationCollection;

/**
 * @table
 */
class MenuItem extends Entity
{
	public const IMAGE_DIR = 'menuitem';
	
	/**
	 * @column{"mutations":true}
	 */
	public ?string $name = null;
	
	/**
	 * @column{"mutations":true}
	 */
	public ?string $description = null;

	/**
	 * Typ
	 * @column{"type":"enum","length":"'main','footer','support'"}
	 */
	public string $type;

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
	 * @column{"mutations":true}
	 */
	public bool $active = false;
	
	/**
	 * @column
	 */
	public ?string $icon;
	
	/**
	 * @column
	 */
	public ?string $iconImage;

	/**
	 * Absolutní URL
	 * @column
	 */
	public string $absoluteUrl = '#';

	/**
	 * Stránka
	 * @relation
	 * @constraint{"onUpdate":"CASCADE","onDelete":"CASCADE"}
	 */
	public ?Page $page;

	/**
	 * Pomocí repositářové metody getTree($type)
	 * @var array<\Web\DB\MenuItem>
	 */
	public $children = [];

	/**
	 * Zařazení do menu
	 * @relationNxN{"via":"web_menuassign"}
	 * @var array<\Web\DB\MenuType>|\StORM\RelationCollection<\Web\DB\MenuType>
	 */
	public RelationCollection $types;
	
	public function getUrl(?string $langPrefix = null): ?string
	{
		return $this->page ? $this->getRepository()->getBaseUrl() . ($langPrefix ? "$langPrefix/" : '') . $this->page->url : $this->absoluteUrl;
	}
	
	public function isSystemic(): bool
	{
		if ($this->page) {
			return $this->page->systemic;
		}

		return false;
	}

	public function isDeletable(): bool
	{
		return !$this->isSystemic();
	}
}
