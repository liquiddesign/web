<?php

declare(strict_types=1);

namespace Web\DB;

use StORM\Entity;
use StORM\RelationCollection;

/**
 * @table
 */
class Faq extends Entity
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
	 * @column{"mutations":true}
	 */
	public bool $active = false;
	
	/**
	 * Položky faq
	 * @relation
	 * @var \StORM\RelationCollection<\Web\DB\FaqItem>|\Web\DB\FaqItem[]
	 */
	public RelationCollection $items;
	
	public function getItems()
	{
		return $this->items->where('hidden', false)->orderBy(['priority']);
	}
}