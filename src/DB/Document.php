<?php

namespace Web\DB;

use StORM\Entity;

/**
 * @table
 */
class Document extends Entity
{
	public const FILE_DIR = 'related_documents';
	
	/**
	 * @column{"mutations":true}
	 */
	public ?string $name;
	
	/**
	 * @column{"mutations":true}
	 */
	public ?string $filename;

	/**
	 * @column{"mutations":true}
	 */
	public ?int $fileSize;
	
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
}
