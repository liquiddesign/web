<?php

declare(strict_types=1);

namespace Web\DB;

use Base\ShopsConfig;
use StORM\DIConnection;
use StORM\Repository;
use StORM\SchemaManager;

class BannerRepository extends Repository
{
	public function __construct(DIConnection $connection, SchemaManager $schemaManager, protected readonly ShopsConfig $shopsConfig)
	{
		parent::__construct($connection, $schemaManager);
	}

	public function getShopsFilteredBannerById(string|int $id): Banner|null
	{
		$collection = $this->many()->where('this.uuid', $id);

		$this->shopsConfig->filterShopsInShopEntityCollection($collection);

		return $collection->first();
	}
}
