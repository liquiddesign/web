<?php

declare(strict_types=1);

namespace Web\DB;

use App\IGeneralRepository;
use Nette\Http\Request;
use StORM\Collection;
use StORM\DIConnection;
use StORM\Repository;
use StORM\SchemaManager;

/**
 * @extends \StORM\Repository<\Web\DB\MenuItem>
 */
class MenuItemRepository extends Repository implements IGeneralRepository
{
	private Request $request;

	public function __construct(DIConnection $connection, SchemaManager $schemaManager, Request $request)
	{
		parent::__construct($connection, $schemaManager);

		$this->request = $request;
	}

	public function getBaseUrl(): string
	{
		return $this->request->getUrl()->getBaseUrl();
	}

	public function getArrayForSelect(bool $includeHidden = true): array
	{
		return $this->getCollection($includeHidden)->toArrayOf('name');
	}

	public function getCollection(bool $includeHidden = false): Collection
	{
		$suffix = $this->getConnection()->getMutationSuffix();
		$collection = $this->many();

		if (!$includeHidden) {
			$collection->where('hidden', false);
		}

		return $collection->orderBy(['priority', "name$suffix"]);
	}

	public function getMenuItemsByType($type)
	{
		if (!$type instanceof MenuType) {
			if (!$type = $this->one($type)) {
				return [];
			}
		}

		return ;
	}
}
