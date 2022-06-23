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

	/**
	 * @param bool $includeHidden
	 * @param \Web\DB\FaqItemTag|null $faqItemTag
	 * @return array<\Web\DB\Faq>
	 */
	public function getFaqsWithItems(bool $includeHidden = false, ?FaqItemTag $faqItemTag = null): array
	{
		$faqs = $this->getCollection($includeHidden);
		$result = [];

		/** @var \Web\DB\Faq $faq */
		foreach ($faqs as $faq) {
			$faq->itemsArray = $faq->getItems($faqItemTag)->toArray();

			$result[$faq->getPK()] = $faq;
		}

		return $result;
	}
}
