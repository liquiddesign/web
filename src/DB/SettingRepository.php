<?php

declare(strict_types=1);

namespace Web\DB;

use Base\ShopsConfig;
use Common\DB\IGeneralRepository;
use Nette\Caching\Cache;
use Nette\Caching\Storage;
use StORM\Collection;
use StORM\DIConnection;
use StORM\Repository;
use StORM\SchemaManager;

/**
 * @extends \StORM\Repository<\Web\DB\Setting>
 */
class SettingRepository extends Repository implements IGeneralRepository
{
	private readonly Cache $cache;

	public function __construct(DIConnection $connection, SchemaManager $schemaManager, protected readonly ShopsConfig $shopsConfig, Storage $storage)
	{
		parent::__construct($connection, $schemaManager);

		$this->cache = new Cache($storage);
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
		$index = self::class . ':' . __FUNCTION__ . ((int) $checkShops) . '-' . ($shops ? \serialize(\array_keys($shops)) : null);

		return $this->cache->load($index, function (&$dependencies) use ($checkShops, $shops): array {
			$dependencies = [
				Cache::Tags => [self::class . ':' . __FUNCTION__],
				Cache::Expire => '1 week',
			];

			$query = $this->many()->setIndex('name');

			if ($checkShops) {
				$this->shopsConfig->filterShopsInShopEntityCollection($query, $shops);
			}

			return $query->toArrayOf('value');
		});
	}

	/**
	 * @param string $name
	 * @param bool $checkShops
	 * @param array<int|string, \Base\DB\Shop>|null $shops
	 * @throws \StORM\Exception\NotFoundException
	 */
	public function getValueByName(string $name, bool $checkShops = true, array|null $shops = null): ?string
	{
		$index = self::class . ':' . __FUNCTION__ . "-$name-$checkShops-" . ($shops ? \serialize(\array_keys($shops)) : null);

		$data = $this->cache->load($index, function (&$dependencies) use ($checkShops, $shops, $name): string|false {
			$dependencies = [
				Cache::Tags => [self::class . ':' . __FUNCTION__],
				Cache::Expire => '1 week',
			];

			$settingQuery = $this->many()->where('name', $name);

			if ($checkShops) {
				$this->shopsConfig->filterShopsInShopEntityCollection($settingQuery, $shops);
			}

			/** @var \Web\DB\Setting|null $setting */
			$setting = $settingQuery->first();

			if (!$setting || !$setting->value) {
				return false;
			}

			return $setting->value;
		});

		if ($data === false) {
			return null;
		}

		return $data;
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

	/**
	 * @param string $name
	 * @param string|null $shop
	 * @throws \StORM\Exception\NotFoundException
	 */
	public function getValueByNameWithShop(string $name, string|null $shop = null): string|null
	{
		$shop ??= $this->shopsConfig->getSelectedShop()?->getPK();

		if (!$shop) {
			return $this->getValueByName($name);
		}

		$index = self::class . ':' . __FUNCTION__ . "$name-$shop";

		$data = $this->cache->load($index, function (&$dependencies) use ($shop, $name): string|false {
			$dependencies = [
				Cache::Tags => [self::class . ':' . __FUNCTION__],
				Cache::Expire => '1 week',
			];

			$settingQuery = $this->many()->where('name', "{$name}_$shop");
			$setting = $settingQuery->first();

			if (!$setting || !$setting->value) {
				return false;
			}

			return $setting->value;
		});

		if ($data === false) {
			return null;
		}

		return $data;
	}
}
