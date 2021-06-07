<?php

declare(strict_types=1);

namespace Web\DB;

use Common\DB\IGeneralRepository;
use Forms\Form;
use Nette\Http\Request;
use Nette\Utils\Strings;
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

	public function __construct(
		DIConnection $connection,
		SchemaManager $schemaManager,
		Request $request,
		MenuTypeRepository $menuTypeRepository,
		MenuAssignRepository $menuAssignRepository
	)
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
		$collection = $this->menuAssignRepository->many()
			->join(['item' => 'web_menuitem'], 'item.uuid = this.fk_menuitem')
			->where('LENGTH(path) <= 40')
			->orderBy(['item.priority']);

		if ($menuType) {
			if (!$menuType instanceof MenuType) {
				if (!$menuType = $this->one($menuType)) {
					return [];
				}
			}

			$collection->where('fk_menutype', $menuType->getPK());
		}

		return $this->buildTree($collection->toArray());
	}
	
	public function getFrontendTree($menuType = null): array
	{
		$collection = $this->menuAssignRepository->many()
			->join(['item' => 'web_menuitem'], 'item.uuid = this.fk_menuitem')
			->where('LENGTH(path) <= 40')
			->where('menuitem.page.isOffline', false)
			->where('menuitem.hidden', false)
			->where('menuitem.active_' . $this->getConnection()->getMutation(), true)
			->orderBy(['item.priority']);
		
		if ($menuType) {
			if (!$menuType instanceof MenuType) {
				if (!$menuType = $this->one($menuType)) {
					return [];
				}
			}
			
			$collection->where('fk_menutype', $menuType->getPK());
		}
		
		return $this->buildTree($collection->toArray());
	}
	
	public function getBreadcrumbStructure($menuItem): ?array
	{
		if (!$menuItem) {
			return [];
		}
		
		if ($menuItem) {
			if (!$menuItem instanceof MenuItem) {
				if (!$menuItem = $this->one($menuItem)) {
					return [];
				}
			}
		}
		
		$menuAssign = $this->menuAssignRepository->many()->where('fk_menuitem', $menuItem->getPK())->first();
		if (\strlen($menuAssign->path) / 4 === 1) {
			return [];
		}
		$ancestors = [];
		$parent = $menuAssign->ancestor;
		
		do {
			\array_push($ancestors, $parent->menuitem);
			$parent = $parent->ancestor;
		} while ($parent);
		
		\array_reverse($ancestors);
		return $ancestors;
	}

	/**
	 * @param \Web\DB\MenuAssign[] $elements
	 * @param string|null $ancestorId
	 * @return \Web\DB\MenuItem[]
	 */
	private function buildTree(array $elements, ?string $ancestorId = null): array
	{
		$branch = [];

		foreach ($elements as $element) {
			if ($element->getValue('ancestor') === $ancestorId) {
				if ($children = $this->buildTree($elements, $element->getPK())) {
					$element->menuitem->children = $children;
				}

				$element->menuitem->ancestor = $element->ancestor ? $element->ancestor->menuitem : null;
				$element->menuitem->path = $element->path;
				$branch[] = $element->menuitem;
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
		$menuTypes = $this->menuTypeRepository->getCollection($includeHidden)->toArray();

		if ($menuType) {
			if (!$menuType instanceof MenuType) {
				if (!$menuType = $this->menuTypeRepository->one($menuType)) {
					return [];
				}
			}

			$menuTypes = [$menuType];
		}

		$menuItemLevel = null;

		if ($menuItem) {
			$menuItem = $this->getCollection($includeHidden)
				->join(['nxn' => 'web_menuassign'], 'this.uuid = nxn.fk_menuitem')
				->where('nxn.fk_menuitem', $menuItem->getPK())
				->select(['path' => 'nxn.path'])
				->first();
		}

		$list = [];

		foreach ($menuTypes as $type) {
			$collection = $this->menuAssignRepository->many()
				->join(['type' => 'web_menutype'], 'this.fk_menutype = type.uuid')
				->where('LENGTH(path) <= 40')
				->where('(LENGTH(path)/4) < type.maxLevel')
				->where('fk_menutype', $type->getPK());

			if ($menuItem) {
				$maxDeep = $this->getMaxDeepLevel($menuItem, $type);
				$deep = $this->getDeepLevel($menuItem, $type);
				if ($menuItemDeep = $this->getMaxDeepLevel($menuItem, $type) - $this->getDeepLevel($menuItem, $type)) {
					$collection->where('(LENGTH(path)/4) + :deep < type.maxLevel', ['deep' => $menuItemDeep]);
				}

				$collection->whereNot('fk_menuitem', $menuItem->getPK());
				$collection->where('path NOT LIKE :path', ['path' => "$menuItem->path%"]);
			}

			$tempList = [];
			$this->buildTreeArrayForSelect($collection->toArray(), null, $tempList);
			$list['type_' . $type->getPK()] = $type->name;
			$list += $tempList;
		}

		return $list;
	}

	/**
	 * @param \Web\DB\MenuAssign[] $elements
	 * @param string|null $ancestorId
	 * @param array $list
	 * @return \Web\DB\MenuItem[]
	 */
	private function buildTreeArrayForSelect(array $elements, ?string $ancestorId = null, array &$list = []): array
	{
		$branch = [];

		foreach ($elements as $element) {
			if ($element->getValue('ancestor') === $ancestorId) {
				$list[$element->getPK()] = \str_repeat('—',
						\strlen($element->path) / 4) . " " . $element->menuitem->name;

				if ($children = $this->buildTreeArrayForSelect($elements, $element->getPK(), $list)) {
					$element->menuitem->children = $children;
				}

				$branch[] = $element->menuitem;
			}
		}

		return $branch;
	}

	public function getMenuItemPositions(MenuItem $menuItem): array
	{
		$items = $this->menuAssignRepository->many()
			->where('fk_menuitem', $menuItem->getPK())
			->where('fk_ancestor IS NOT NULL');

		$realItems = [];

		foreach ($items as $item) {
			if ($item->ancestor) {
				$realItems[] = $item->getValue('ancestor');
			}
		}

		$types = $this->menuTypeRepository->getCollection()
			->join(['nxn' => 'web_menuassign'], 'this.uuid = nxn.fk_menutype')
			->where('nxn.fk_menuitem', $menuItem->getPK())
			->where('nxn.fk_ancestor IS NULL')
			->toArray();

		$typesKeys = [];

		foreach ($types as $type) {
			$typesKeys[] = 'type_' . $type->getPK();
		}

		return \array_merge($realItems, $typesKeys);
	}

	public function getMaxDeepLevel($menuItem, $menuType): ?int
	{
		if ($menuItem) {
			if (!$menuItem instanceof MenuItem) {
				if (!$menuItem = $this->one($menuItem)) {
					return null;
				}
			}
		}

		if ($menuType) {
			if (!$menuType instanceof MenuType) {
				if (!$menuType = $this->menuTypeRepository->one($menuType)) {
					return null;
				}
			}
		}

		$menuItem = $this->getCollection()
			->join(['nxn' => 'web_menuassign'], 'this.uuid = nxn.fk_menuitem')
			->where('nxn.fk_menuitem', $menuItem->getPK())
			->where('nxn.fk_menutype', $menuType->getPK())
			->select(['path' => 'nxn.path'])
			->first();

		if (!$menuItem) {
			return null;
		}

		$item = $this->getCollection()
			->join(['nxn' => 'web_menuassign'], 'this.uuid = nxn.fk_menuitem')
			->where('nxn.fk_menutype', $menuType->getPK())
			->where('nxn.path LIKE :path', ['path' => "$menuItem->path%"])
			->select(['path' => 'nxn.path'])
			->setOrderBy(['LENGTH(path)' => 'DESC'])
			->first();

		return $item ? (\strlen($item->path) / 4) : (\strlen($menuItem->path) / 4);
	}

	public function hasChildren($menuItem): bool
	{
		if ($menuItem) {
			if (!$menuItem instanceof MenuItem) {
				if (!$menuItem = $this->one($menuItem)) {
					return false;
				}
			}
		}

		$menuItem = $this->getCollection()
			->join(['nxn' => 'web_menuassign'], 'this.uuid = nxn.fk_menuitem')
			->where('nxn.fk_menuitem', $menuItem->getPK())
			->select(['path' => 'nxn.path'])
			->first();

		return $this->getCollection()
				->join(['nxn' => 'web_menuassign'], 'this.uuid = nxn.fk_menuitem')
				->where('path LIKE :path', ['path' => "$menuItem->path%"])
				->where('LENGTH(path) > :pathLength', ['pathLength' => \strlen($menuItem->path)])
				->count() > 0;
	}

	public function getDeepLevel($menuItem, $menuType): ?int
	{
		if ($menuItem) {
			if (!$menuItem instanceof MenuItem) {
				if (!$menuItem = $this->one($menuItem)) {
					return null;
				}
			}
		}

		if ($menuType) {
			if (!$menuType instanceof MenuType) {
				if (!$menuType = $this->menuTypeRepository->one($menuType)) {
					return null;
				}
			}
		}

		$menuItem = $this->getCollection()
			->join(['nxn' => 'web_menuassign'], 'this.uuid = nxn.fk_menuitem')
			->where('nxn.fk_menuitem', $menuItem->getPK())
			->where('nxn.fk_menutype', $menuType->getPK())
			->select(['path' => 'nxn.path'])
			->first();

		return $menuItem ? (\strlen($menuItem->path) / 4) : null;
	}

	public function checkAncestors(Form $form, array &$selectedAncestors): void
	{
		// Take types and items and save to array
		// Check for multiple occurrences in same menu

		$selectedTypes = [];

		foreach ($form->getValues('array')['types'] as $typeItem) {
			if ($typePK = Strings::after($typeItem, 'type_')) {
				/** @var \Web\DB\MenuType $type */
				$type = $this->menuTypeRepository->one($typePK);

				if (isset($selectedAncestors[$type->getPK()])) {
					$form['types']->addError('Nelze vybrat více umístění stejného typu!');
				}

				$selectedAncestors[$type->getPK()] = [
					'type' => $type
				];

				$selectedTypes[] = $type->getPK();
			} else {
				/** @var MenuItem $selectedAncestor */
				$selectedAncestor = $this->many()
					->join(['nxn' => 'web_menuassign'], 'this.uuid = nxn.fk_menuitem')
					->select(['path' => 'nxn.path'])
					->select(['menutype' => 'nxn.fk_menutype'])
					->where('nxn.uuid', $typeItem)
					->first();

				/** @var \Web\DB\MenuType $type */
				$type = $this->menuTypeRepository->one($selectedAncestor->menutype);

				if (isset($selectedAncestors[$type->getPK()])) {
					$form['types']->addError('Nelze vybrat více umístění stejného typu!');
				}

				$selectedAncestors[$type->getPK()] = [
					'type' => $type,
					'item' => $selectedAncestor
				];

				$selectedTypes[] = $type->getPK();
			}
		}
	}
}
