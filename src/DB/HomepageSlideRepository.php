<?php

declare(strict_types=1);

namespace Web\DB;

use Base\ShopsConfig;
use Common\DB\IGeneralRepository;
use StORM\Collection;
use StORM\DIConnection;
use StORM\Repository;
use StORM\SchemaManager;

/**
 * @extends \StORM\Repository<\Web\DB\HomepageSlide>
 */
class HomepageSlideRepository extends Repository implements IGeneralRepository
{
	public function __construct(DIConnection $connection, SchemaManager $schemaManager, protected readonly ShopsConfig $shopsConfig)
	{
		parent::__construct($connection, $schemaManager);
	}

	/**
	 * @param bool $includeHidden
	 * @return array<string>
	 */
	public function getArrayForSelect(bool $includeHidden = true): array
	{
		return $this->getCollection($includeHidden)->toArrayOf('text');
	}

	public function getCollection(bool $includeHidden = false, bool $filterShops = true): Collection
	{
		$collection = $this->many();

		if (!$includeHidden) {
			$collection->where('hidden', false);
		}

		if ($filterShops) {
			$this->shopsConfig->filterShopsInShopEntityCollection($collection);
		}

		return $collection->orderBy(['priority']);
	}
}
