<?php

declare(strict_types=1);

namespace Web\DB;

use StORM\Collection;
use StORM\Repository;

/**
 * @extends \StORM\Repository<\Web\DB\Faq>
 */
class FaqRepository extends Repository
{
	public function getCollection(bool $includeHidden = false): Collection
	{
		$suffix = $this->getConnection()->getMutationSuffix();
		$collection = $this->many();

		if (!$includeHidden) {
			$collection->where("this.active$suffix", true);
		}

		return $collection->orderBy(["this.name$suffix",]);
	}
}
