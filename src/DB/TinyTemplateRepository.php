<?php

declare(strict_types=1);

namespace Web\DB;

use StORM\Repository;

/**
 * @extends \StORM\Repository<\Web\DB\TinyTemplate>
 */
class TinyTemplateRepository extends Repository
{
	/**
	 * @return array<\Web\DB\TinyTemplate>
	 */
	public function getTemplates(): array
	{
		$suffix = $this->getConnection()->getMutationSuffix();
		
		$collection = $this->many()->setSelect([
			'title' => "name$suffix",
			'description' => "description$suffix",
			'content' => 'html',
		]);
		
		$collection->orderBy(['priority'])->setIndex(null);
		
		return $collection->toArray();
	}
}
