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
	/**
	 * @column{"mutations":true}
	 */
	public ?string $name = null;

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
	 * @var \Web\DB\MenuItem[]
	 */
	public array $children = [];

	/**
	 * Zařazení do menu
	 * @relationNxN{"via":"web_menuassign"}
	 * @var \Web\DB\MenuType[]|\StORM\RelationCollection<\Web\DB\MenuType>
	 */
	public RelationCollection $types;

	public function getUrl()
	{
		return $this->page ? $this->getRepository()->getBaseUrl() . $this->page->url : $this->absoluteUrl;
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
