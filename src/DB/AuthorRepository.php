<?php

declare(strict_types=1);

namespace Web\DB;

use Common\DB\IGeneralRepository;
use StORM\Collection;
use StORM\Repository;

class AuthorRepository extends Repository implements IGeneralRepository
{
	public function getCollection(bool $includeHidden = false): Collection
	{
		unset($includeHidden);
		
		$suffix = $this->getConnection()->getMutationSuffix();
		$collection = $this->many();
		
		return $collection->orderBy(["name$suffix"]);
	}
	
	/**
	 * @param bool $includeHidden
	 * @return string[]
	 */
	public function getArrayForSelect(bool $includeHidden = true): array
	{
		return $this->getCollection($includeHidden)->toArrayOf('name');
	}
}
