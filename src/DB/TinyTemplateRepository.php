<?php

declare(strict_types=1);

namespace Web\DB;

use StORM\Repository;

class TinyTemplateRepository extends Repository
{
	public function getTemplates()
	{
		$suffix = $this->getConnection()->getMutationSuffix();
		
		$collection  = $this->many()->setSelect([
			'title' => "name$suffix",
			'description' => "description$suffix",
			'content' => "html",
		]);
		
		$collection->orderBy(['priority'])->setIndex(null)->setFetchClass(\StdClass::class);
		
		return $collection->toArray();
	}
}
