<?php

declare(strict_types=1);

namespace Web\DB;

use Common\DB\IGeneralRepository;
use StORM\Collection;
use StORM\Repository;

/**
 * @extends \StORM\Repository<\Web\DB\HomepageSlide>
 */
class HomepageSlideRepository extends Repository implements IGeneralRepository
{
	public function getArrayForSelect(bool $includeHidden = true):array
	{
		return $this->getCollection($includeHidden)->toArrayOf('text');
	}

	public function getCollection(bool $includeHidden = false): Collection
	{
		$collection = $this->many();

		if (!$includeHidden) {
			$collection->where('hidden', false);
		}

		return $collection->orderBy(['priority']);
	}
}
