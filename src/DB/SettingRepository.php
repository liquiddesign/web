<?php

declare(strict_types=1);

namespace Web\DB;

use Base\ShopsConfig;
use Common\DB\IGeneralRepository;
use StORM\Collection;
use StORM\DIConnection;
use StORM\Repository;
use StORM\SchemaManager;

/**
 * @extends \StORM\Repository<\Web\DB\Setting>
 */
class SettingRepository extends Repository implements IGeneralRepository
{
	public function __construct(DIConnection $connection, SchemaManager $schemaManager, protected readonly ShopsConfig $shopsConfig)
	{
		parent::__construct($connection, $schemaManager);
	}

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
		unset($includeHidden);
		
		return $this->many();
	}
	
	/**
	 * @param array<int|string, \Base\DB\Shop>|null $shops
	 * @return array<string>
	 */
	public function getValues(bool $checkShops = true, array|null $shops = null): array
	{
		$query = $this->many()->setIndex('name');

		if ($checkShops) {
			$this->shopsConfig->filterShopsInShopEntityCollection($query, $shops);
		}

		return $query->toArrayOf('value');
	}

	/**
	 * @param string $name
	 * @param bool $checkShops
	 * @param array<int|string, \Base\DB\Shop>|null $shops
	 * @throws \StORM\Exception\NotFoundException
	 */
	public function getValueByName(string $name, bool $checkShops = true, array|null $shops = null): ?string
	{
		$settingQuery = $this->many()->where('name', $name);

		if ($checkShops) {
			$this->shopsConfig->filterShopsInShopEntityCollection($settingQuery, $shops);
		}

		$setting = $settingQuery->first();

		if (!$setting || !$setting->value) {
			return null;
		}

		return $setting->value;
	}

	/**
	 * @param string $name
	 * @param bool $checkShops
	 * @param array<int|string, \Base\DB\Shop>|null $shops
	 * @return array<string>|null
	 * @throws \StORM\Exception\NotFoundException
	 */
	public function getValuesByName(string $name, bool $checkShops = true, array|null $shops = null): ?array
	{
		$value = $this->getValueByName($name, $checkShops, $shops);

		if (!$value) {
			return null;
		}

		return \explode(';', $value);
	}
}
