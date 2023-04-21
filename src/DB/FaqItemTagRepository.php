<?php

declare(strict_types=1);

namespace Web\DB;

use Common\DB\IGeneralRepository;
use StORM\Collection;
use StORM\Repository;

/**
 * @extends \StORM\Repository<\Web\DB\FaqItemTag>
 */
class FaqItemTagRepository extends Repository implements IGeneralRepository
{
	public function getCollection(bool $includeHidden = false): Collection
	{
		$suffix = $this->getConnection()->getMutationSuffix();
		$collection = $this->many();

		if (!$includeHidden) {
			$collection->where('this.hidden', false);
		}

		return $collection->orderBy(['this.priority', "this.name$suffix"]);
	}

	/**
	 * @param bool $includeHidden
	 * @return array<string>
	 */
	public function getArrayForSelect(bool $includeHidden = true): array
	{
		return $this->getCollection($includeHidden)->toArrayOf('name');
	}

	public function getActiveTags(): Collection
	{
		return $this->getCollection()->join(['tagsNxN' => 'web_faqitem_nxn_web_faqitemtag'], 'this.uuid = tagsNxN.fk_tag', [], 'INNER');
	}
}
