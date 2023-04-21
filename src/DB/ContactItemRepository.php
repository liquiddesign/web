<?php

declare(strict_types=1);

namespace Web\DB;

use Common\DB\IGeneralRepository;
use StORM\Collection;
use StORM\Repository;

/**
 * @extends \StORM\Repository<\Web\DB\ContactItem>
 */
class ContactItemRepository extends Repository implements IGeneralRepository
{
	/**
	 * @param bool $includeHidden
	 * @return array<string>
	 */
	public function getArrayForSelect(bool $includeHidden = true): array
	{
		return $this->getCollection($includeHidden)->toArrayOf('name');
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
