<?php

declare(strict_types=1);

namespace Web\DB;

use Admin\Controls\Menu;
use Common\DB\IGeneralRepository;
use StORM\Collection;
use StORM\Repository;
use Web\Admin\MenuPresenter;

/**
 * @extends \StORM\Repository<\Web\DB\MenuType>
 */
class MenuTypeRepository extends Repository implements IGeneralRepository
{
	public function getCollection(bool $includeHidden = false): Collection
	{
		$collection = $this->many();

		return $collection->orderBy(['priority', "name"]);
	}

	public function getArrayForSelect(bool $includeHidden = true): array
	{
		return $this->getCollection()->toArrayOf('name');
	}

	public function getTree(): array
	{
		return $this->buildTree($this->getCollection()->where('LENGTH(path) <= 40')->toArray());
	}

	/**
	 * @param \Web\DB\MenuType[] $elements
	 * @param string|null $ancestorId
	 * @return \Web\DB\MenuType[]
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
	 * @param \Web\DB\MenuType $element
	 * @throws \Throwable
	 */
	public function updateElementChildrenPath(MenuType $element): void
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

	public function findElementInTree(MenuType $targetMenuType, ?MenuType $menuType = null): ?MenuType
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

	public function getTreeArrayForSelect(bool $includeHidden = true, int $maxLevel = MenuPresenter::MAX_LEVEL): array
	{
		$collection = $this->getCollection($includeHidden)->where('LENGTH(path) <= ' . $maxLevel * 4);

		$list = [];
		$this->buildTreeArrayForSelect($collection->toArray(), null, $list);

		return $list;
	}

	/**
	 * @param \Web\DB\MenuType[] $elements
	 * @param string|null $ancestorId
	 * @param array $list
	 * @return \Web\DB\MenuType[]
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
