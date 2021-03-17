<?php

declare(strict_types=1);

namespace Web\DB;

use App\IGeneralRepository;
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

		return $collection->orderBy(['priority', "name"]);
	}

	public function getArrayForSelect(bool $includeHidden = true): array
	{
		return $this->getCollection()->toArrayOf('name');
	}
}
