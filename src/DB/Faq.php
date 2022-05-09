<?php

declare(strict_types=1);

namespace Web\DB;

use StORM\Collection;
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
	
	public function getItems(?FaqItemTag $faqItemTag = null): Collection
	{
		$collection = $this->items->where('this.hidden', false)->orderBy(['this.priority']);

		if ($faqItemTag) {
			$collection->setGroupBy(['this.uuid'])
				->join(['tagsNxN' => 'web_faqitem_nxn_web_faqitemtag'], 'this.uuid = tagsNxN.fk_item')
				->where('tagsNxN.fk_tag', $faqItemTag->getPK());
		}

		return $collection;
	}
}
