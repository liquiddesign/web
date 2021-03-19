<?php

declare(strict_types=1);

namespace Web\DB;

use App\IGeneralRepository;
use Nette\Utils\Strings;
use StORM\Collection;

class PageRepository extends \Pages\DB\PageRepository implements IGeneralRepository
{
	public const SUFFIX_SEPARATOR = '-';
	
	public function getUuidByValue(string $value): string
	{
		$uuid = Strings::webalize($value);
		$suffix = $this->many()->where('uuid LIKE :uuid', ['uuid' => "$uuid%"])->enum();
		
		return $suffix ? $uuid . self::SUFFIX_SEPARATOR . $suffix : $uuid;
	}
	
	public function getPagesWithoutMenu(?array $types = null): Collection
	{
		$collection = $this->many()->join(['menu' => 'web_menutem'], 'menu.fk_page = this.uuid')->where('menu.uuid IS NULL');
		
		if ($types !== null) {
			$collection->where('this.type', $types);
		}
		
		return $collection;
	}
	
	public function getArrayForSelect(bool $includeHidden = true):array
	{
		return $this->getCollection($includeHidden)->toArrayOf('title');
	}
	
	public function getCollection(bool $includeHidden = false): Collection
	{
		$suffix = $this->getConnection()->getMutationSuffix();
		
		return $this->many()->orderBy(['priority', "title$suffix"]);
	}
}
