<?php

declare(strict_types=1);

namespace Web\DB;

use StORM\Collection;
use StORM\Entity;
use StORM\RelationCollection;

/**
 * @table
 */
class Carousel extends Entity
{
	public const IMAGE_DIR = 'carousel';
	
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
	public bool $hidden = false;
	
	/**
	 * @column{"mutations":true}
	 */
	public bool $active = false;
	
	/**
	 * Fotky carouselu
	 * @relation
	 * @var \StORM\RelationCollection<\Web\DB\CarouselSlide>|\Web\DB\CarouselSlide[]
	 */
	public RelationCollection $slides;

	public function getSlides(): Collection
	{
		$slides = $this->slides->clear();
		
		return $slides->where('hidden', false)->orderBy(['priority' => 'ASC']);
	}
}
