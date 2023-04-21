<?php

declare(strict_types=1);

namespace Web\DB;

use StORM\Entity;
use StORM\RelationCollection;

/**
 * @table
 */
class FaqItemTag extends Entity
{
	/**
	 * @column{"mutations":true}
	 */
	public ?string $name;

	/**
	 * Priorita
	 * @column
	 */
	public int $priority = 10;

	/**
	 * @column
	 */
	public bool $hidden = false;

	/**
	 * Položky
	 * @relationNxN{"sourceViaKey":"fk_tag","targetViaKey":"fk_item","via":"web_faqitem_nxn_web_faqitemtag"}
	 * @var \StORM\RelationCollection<\Web\DB\FaqItem>|array<\Web\DB\FaqItem>
	 */
	public RelationCollection $tags;
}
