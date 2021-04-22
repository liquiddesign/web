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

	public function __construct(DIConnection $connection, SchemaManager $schemaManager, Request $request, MenuTypeRepository $menuTypeRepository)
	{
		parent::__construct($connection, $schemaManager);

		$this->request = $request;
		$this->menuTypeRepository = $menuTypeRepository;
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
		$collection = $this->getCollection()->where('LENGTH(path) <= 40');

		if ($menuType) {
			if (!$menuType instanceof MenuType) {
				if (!$menuType = $this->one($menuType)) {
					return [];
				}
			}

			$collection->where('fk_menuType', $menuType->getPK());
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
	 * Updates all paths of children of element.
	 * @param \Web\DB\MenuItem $element
	 * @throws \Throwable
	 */
	public function updateElementChildrenPath(MenuItem $element): void
	{
		$tree = $this->getTree();

		foreach ($tree as $item) {
			if ($item->getPK() == $element->getPK()) {
				$startElement = $item;
				break;
			}

			if (\str_contains($element->path, $item->path)) {
				$startElement = $this->findElementInTree($element, $item);

				if ($startElement) {
					break;
				}
			}
		}

		if (isset($startElement)) {
			$startElement->setParent($this);
			$startElement->update(['path' => $element->path]);

			foreach ($startElement->children as $child) {
				$child->setParent($this);
				$child->update(['path' => $startElement->path . \substr($child->path, -4)]);

				if (\count($child->children) > 0) {
					$this->doUpdateElementChildrenPath($child);
				}
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

	private function doUpdateElementChildrenPath(MenuType $menuType): void
	{
		foreach ($menuType->children as $child) {
			$child->setParent($this);
			$child->update(['path' => $menuType->path . \substr($child->path, -4)]);

			if (\count($child->children) > 0) {
				$this->doUpdateElementChildrenPath($child);
			}
		}
	}

	public function getTreeArrayForSelect(bool $includeHidden = true, $menuType = null): array
	{
		$collection = $this->getCollection($includeHidden)->where('LENGTH(path) <= 40');

		if ($menuType) {
			if (!$menuType instanceof MenuType) {
				if (!$menuType = $this->menuTypeRepository->one($menuType)) {
					return [];
				}
			}

			$collection->join(['type' => 'web_menuitem_nxn_web_menutype'], 'this.uuid = type.fk_menutype');
			$collection->where('type.fk_menutype', $menuType->getPK());
		}

		$list = [];
		$this->buildTreeArrayForSelect($collection->toArray(), null, $list);

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
				$list[$element->getPK()] = \str_repeat('--', (\strlen($element->path) / 4) - 1) . " $element->name";

				if ($children = $this->buildTreeArrayForSelect($elements, $element->getPK(), $list)) {
					$element->children = $children;
				}

				$branch[] = $element;
			}
		}

		return $branch;
	}
}
