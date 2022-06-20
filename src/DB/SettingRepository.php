<?php

declare(strict_types=1);

namespace Web\DB;

use Common\DB\IGeneralRepository;
use StORM\Collection;
use StORM\Repository;

/**
 * @extends \StORM\Repository<\Web\DB\Setting>
 */
class SettingRepository extends Repository implements IGeneralRepository
{
	/**
	 * @param bool $includeHidden
	 * @return string[]
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
	 * @return string[]
	 */
	public function getValues(): array
	{
		return $this->many()->setIndex('name')->toArrayOf('value');
	}

	public function getValueByName(string $name): ?string
	{
		$setting = $this->one(['name' => $name]);

		if (!$setting || !$setting->value) {
			return null;
		}

		return $setting->value;
	}

	/**
	 * @param string $name
	 * @return array<string>|null
	 */
	public function getValuesByName(string $name): ?array
	{
		$value = $this->getValueByName($name);

		if (!$value) {
			return null;
		}

		return \explode(';', $value);
	}
}
