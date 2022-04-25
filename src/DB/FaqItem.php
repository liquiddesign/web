<?php

declare(strict_types=1);

namespace Web\DB;

use StORM\Entity;

/**
 * @table
 */
class FaqItem extends Entity
{
	/**
	 * @column{"mutations":true}
	 */
	public ?string $question;
	
	/**
	 * @column{"mutations":true,"type":"longtext"}
	 */
	public ?string $answer;
	
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
	 * @column{"mutations":true}
	 */
	public bool $active = false;
	
	/**
	 * Faq
	 * @constraint{"onUpdate":"CASCADE","onDelete":"CASCADE"}
	 * @relation
	 */
	public Faq $faq;
	
	/**
	 * Author
	 * @constraint{"onUpdate":"CASCADE","onDelete":"SET NULL"}
	 * @relation
	 */
	public ?Author $author;
}
