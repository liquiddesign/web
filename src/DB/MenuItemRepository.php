<?php

declare(strict_types=1);

namespace Web\DB;

use Common\DB\IGeneralRepository;
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

	private MenuTypeRepository $menuTypeRepository;

	private MenuAssignRepository $menuAssignRepository;

	public function __construct(DIConnection $connection, SchemaManager $schemaManager, Request $request, MenuTypeRepository $menuTypeRepository, MenuAssignRepository $menuAssignRepository)
	{
		parent::__construct($connection, $schemaManager);

		$this->request = $request;
		$this->menuTypeRepository = $menuTypeRepository;
		$this->menuAssignRepository = $menuAssignRepository;
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

	/** @deprecated use getTree($type) */
	public function getMenuItemsByType($type)
	{
		if (!$type instanceof MenuType) {
			if (!$type = $this->one($type)) {
				return [];
			}
		}

		return $this->getTree($type);
	}

	public function getTree($menuType = null): array
	{
		$collection = $this->getCollection()
			->join(['nxn' => 'web_menuassign'], 'this.uuid = nxn.fk_menuitem')
			->where('LENGTH(path) <= 40')
			->select(['ancestor' => 'nxn.fk_ancestor'])
			->select(['path' => 'nxn.path']);

		if ($menuType) {
			if (!$menuType instanceof MenuType) {
				if (!$menuType = $this->one($menuType)) {
					return [];
				}
			}

			$collection->where('nxn.fk_menuType', $menuType->getPK());
		}

		return $this->buildTree($collection->toArray());
	}

	/**
	 * @param \Web\DB\MenuItem[] $elements
	 * @param string|null $ancestorId
	 * @return \Web\DB\MenuItem[]
	 */
	private function buildTree(array $elements, ?string $ancestorId = null): array
	{
		$branch = [];

		foreach ($elements as $element) {
			if ($element->getValue('ancestor') === $ancestorId) {
				if ($children = $this->buildTree($elements, $element->getPK())) {
					$element->children = $children;
				}

				$branch[] = $element;
			}
		}

		return $branch;
	}

	/**
	 * Updates all items paths of menu type.
	 * @param \Web\DB\MenuType $element
	 * @throws \Throwable
	 */
	public function recalculatePaths(MenuType $menuType): void
	{
		foreach ($this->getTree($menuType) as $item) {
			$this->doRecalculatePaths($item, $menuType);
		}
	}

	private function doRecalculatePaths($item, MenuType $menuType): void
	{
		foreach ($item->children as $child) {
			$this->menuAssignRepository->syncOne([
				'menutype' => $menuType->getPK(),
				'menuitem' => $child->getPK(),
				'path' => $item->path . \substr($child->path, -4)
			]);

			if (\count($child->children) > 0) {
				$this->doRecalculatePaths($child, $menuType);
			}
		}
	}

	public function findElementInTree(MenuItem $targetMenuType, ?MenuItem $menuType = null): ?MenuItem
	{
		if (!$menuType) {
			$tree = $this->getTree();
			$children = [];

			foreach ($tree as $child) {
				$children[] = $child;
			}
		} else {
			$children = $menuType->children;
		}

		foreach ($children as $child) {
			if ($child->getPK() == $targetMenuType->getPK()) {
				return $child;
			}

			$returnElement = $this->findElementInTree($targetMenuType, $child);

			if ($returnElement) {
				return $returnElement;
			}
		}

		return null;
	}

	public function getTreeArrayForSelect(bool $includeHidden = true, $menuType = null, ?MenuItem $menuItem = null): array
	{
		$menuTypes = $this->menuTypeRepository->getCollection()->toArray();

		if ($menuType) {
			if (!$menuType instanceof MenuType) {
				if (!$menuType = $this->menuTypeRepository->one($menuType)) {
					return [];
				}
			}

			$menuTypes = [$menuType];
		}

		if ($menuItem) {
			$menuItem = $this->getCollection()
				->join(['nxn' => 'web_menuassign'], 'this.uuid = nxn.fk_menuitem')
				->where('nxn.fk_menuitem', $menuItem->getPK())
				->select(['path' => 'nxn.path'])
				->first();
		}

		$list = [];

		foreach ($menuTypes as $type) {
			$collection = $this->getCollection($includeHidden)
				->join(['nxn' => 'web_menuassign'], 'this.uuid = nxn.fk_menuitem')
				->where('LENGTH(nxn.path) <= 40')
				->where('nxn.fk_menutype', $type->getPK())
				->select(['ancestor' => 'nxn.fk_ancestor', 'path' => 'nxn.path']);

			if ($menuItem) {
				$collection->whereNot('nxn.fk_menuitem', $menuItem->getPK());
				$collection->where('nxn.path NOT LIKE :path', ['path' => "$menuItem->path%"]);
			}

			$tempList = [];
			$this->buildTreeArrayForSelect($collection->toArray(), null, $tempList);
			$list['type_' . $type->getPK()] = $type->name;
			$list += $tempList;
		}

		return $list;
	}

	/**
	 * @param \Web\DB\MenuItem[] $elements
	 * @param string|null $ancestorId
	 * @param array $list
	 * @return \Web\DB\MenuItem[]
	 */
	private function buildTreeArrayForSelect(array $elements, ?string $ancestorId = null, array &$list = []): array
	{
		$branch = [];

		foreach ($elements as $element) {
			if ($element->getValue('ancestor') === $ancestorId) {
				$list[$element->getPK()] = \str_repeat('--', \strlen($element->path) / 4) . " $element->name";

				if ($children = $this->buildTreeArrayForSelect($elements, $element->getPK(), $list)) {
					$element->children = $children;
				}

				$branch[] = $element;
			}
		}

		return $branch;
	}

	public function getMenuItemPositions(MenuItem $menuItem): array
	{
		$items = \array_values($this->menuAssignRepository->many()
			->where('fk_menuitem', $menuItem->getPK())
			->where('fk_ancestor IS NOT NULL')
			->toArrayOf('ancestor'));

//		$items = \array_values($this->getCollection()
//			->join(['nxn' => 'web_menuassign'], 'this.uuid = nxn.fk_menuitem')
//			->select(['path' => 'nxn.path', 'ancestor' => 'nxn.fk_ancestor'])

		$types = $this->menuTypeRepository->getCollection()
			->join(['nxn' => 'web_menuassign'], 'this.uuid = nxn.fk_menutype')
			->where('nxn.fk_menuitem', $menuItem->getPK())
			->where('nxn.fk_ancestor IS NULL')
			->setSelect(['this.uuid'])
			->toArray();

		$typesKeys = [];

		foreach ($types as $type) {
			$typesKeys[] = 'type_' . $type->getPK();
		}

		return \array_merge($items, $typesKeys);
	}

	public function getMaxDeepLevel(MenuItem $menuItem): int
	{
		$menuItem = $this->getCollection()
			->join(['nxn' => 'web_menuassign'], 'this.uuid = nxn.fk_menuitem')
			->where('nxn.fk_menuitem', $menuItem->getPK())
			->select(['path' => 'nxn.path'])
			->first();

		$item = $this->getCollection()
			->join(['nxn' => 'web_menuassign'], 'this.uuid = nxn.fk_menuitem')
			->where('nxn.fk_menuitem', $menuItem->getPK())
			->where('path LIKE :path', ['path' => "$menuItem->path%"])
			->where('LENGTH(path) > :pathLength', ['pathLength' => \strlen($menuItem->path)])
			->select(['pathLength' => 'LENGTH(path)'])
			->setOrderBy(['pathLength' => 'DESC'])
			->first();

		return $item ? (($item->pathLength / 4) - (\strlen($menuItem->path) / 4)) : (\strlen($menuItem->path) / 4);
	}
}
