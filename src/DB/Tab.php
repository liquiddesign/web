<?php

declare(strict_types=1);

namespace Web\DB;

use StORM\Collection;
use StORM\Entity;
use StORM\RelationCollection;

/**
 * @table
 */
class Tab extends Entity
{
	/**
	 * @column{"mutations":true}
	 */
	public ?string $name;
	
	/**
	 * @column{"unique":true}
	 */
	public string $id;
	
	/**
	 * @column
	 */
	public bool $firstMobile = true;
	
	/**
	 * Položky tab
	 * @relation
	 * @var \StORM\RelationCollection<\Web\DB\TabItem>|array<\Web\DB\TabItem>
	 */
	public RelationCollection $items;
	
	/**
	 * @column{"mutations":true}
	 */
	public bool $active = false;
	
	public function getItems(): Collection
	{
		$items = $this->items->clear();
		
		return $items->where('hidden', false)->orderBy(['priority' => 'ASC']);
	}
}
