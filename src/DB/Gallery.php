<?php

declare(strict_types=1);

namespace Web\DB;

use StORM\Entity;
use StORM\RelationCollection;

/**
 * @table
 */
class Gallery extends Entity
{
	public const IMAGE_DIR = 'gallery';
	
	public const SUBDIRS = ['origin', 'thumb', 'upload'];
	
	public const RESIZE_METHODS = [
		'EXACT' => 'EXACT - Vyplnění cílové plochy a oříznutí přesahu',
		'FIT' => 'FIT - Přispůsobit zadaným rozměrům',
		'FILL' => 'FILL - Vyplnit s případným přesáhnutím jednoho rozměru',
	];
	
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
	public ?int $originWidth;
	
	/**
	 * @column
	 */
	public ?int $originHeight;
	
	/**
	 * @column
	 */
	public ?int $thumbWidth;
	
	/**
	 * @column
	 */
	public ?int $thumbHeight;
	
	/**
	 * @column
	 */
	public string $ratio = '4/4/3';
	
	/**
	 * @column
	 */
	public bool $hidden = false;
	
	/**
	 * @column
	 */
	public ?string $classes = '';
	
	/**
	 * @column{"type":"enum","length":"'FIT','FILL','EXACT'"}
	 */
	public string $resizeMethod = 'EXACT';
	
	/**
	 * @column{"mutations":true}
	 */
	public bool $active = false;
	
	/**
	 * Fotky galerie
	 * @relation
	 * @var \StORM\RelationCollection<\Web\DB\GalleryImage>|array<\Web\DB\GalleryImage>
	 */
	public RelationCollection $images;
	
	/**
	 * @return array<mixed>
	 */
	public function getRatios(): array
	{
		$ratios = \explode('/', $this->ratio);
		
		return [
			'mobile' => $ratios[0],
			'tablet' => $ratios[1],
			'desktop' => $ratios[2],
		];
	}
}
