<?php

declare(strict_types=1);

namespace Web\DB;

use Common\DB\IGeneralRepository;
use StORM\Collection;
use StORM\Repository;

/**
 * @extends \StORM\Repository<\Web\DB\MenuType>
 */
class MenuTypeRepository extends Repository implements IGeneralRepository
{
	public function getCollection(bool $includeHidden = false): Collection
	{
		$collection = $this->many();
		$suffix = $this->getConnection()->getMutationSuffix();

		return $collection->orderBy(['this.priority', "this.name$suffix"]);
	}

	public function getArrayForSelect(bool $includeHidden = true): array
	{
		return $this->getCollection()->toArrayOf('name');
	}
}
