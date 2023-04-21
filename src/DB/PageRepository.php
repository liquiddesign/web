<?php

declare(strict_types=1);

namespace Web\DB;

use Common\DB\IGeneralRepository;
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
		$collection = $this->many()->join(['menu' => 'web_menuitem'], 'menu.fk_page = this.uuid')->where('menu.uuid IS NULL');
		
		$expression = new \StORM\Expression();
		
		foreach ($types as $type => $param) {
			$expression->add('OR', 'this.type=%s' . ($param !== null ? ' AND params LIKE %s' : ''), $param !== null ? [$type, $param === '' ? '' : "$param=%"] : [$type]);
		}
		
		$collection->where($expression->getSql(), $expression->getVars());
		
		return $collection;
	}
	
	/**
	 * Sitemaps format example:
	 * [$pagetype1 => $param1, $pagetype2, $pagetype3 => $param3 ....]
	 * @param array $sitemaps
	 */
	public function getPagesForSitemap(array $sitemaps): Collection
	{
		$expression = new \StORM\Expression();
		
		foreach ($sitemaps as $key => $value) {
			$type = \is_int($key) ? $value : $key;
			$param = \is_int($key) ? null : $value;
			
			$expression->add('OR', 'this.type=%s' . ($param !== null ? ' AND params LIKE %s' : ''), $param !== null ? [$type, "$param=%"] : [$type]);
		}
		
		$mutations = $this->getConnection()->getAvailableMutations();
		$urlExpression = new \StORM\Expression();
		
		foreach ($mutations as $lang) {
			$urlExpression->add('OR', "this.url$lang=pr.fromUrl", []);
		}
		
		return $this->many()->join(['pr' => 'pages_redirect'], $urlExpression->getSql())
			->where('pr.uuid IS NULL')
			->where('isOffline', false)->where($expression->getSql(), $expression->getVars());
	}
	
	/**
	 * @param bool $includeHidden
	 * @return array<string>
	 */
	public function getArrayForSelect(bool $includeHidden = true): array
	{
		return $this->getCollection($includeHidden)->toArrayOf('title');
	}
	
	public function getCollection(bool $includeHidden = false): Collection
	{
		unset($includeHidden);
		$suffix = $this->getConnection()->getMutationSuffix();
		
		return $this->many()->orderBy(['priority', "title$suffix"]);
	}
}
