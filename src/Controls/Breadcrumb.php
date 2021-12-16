<?php

namespace Web\Controls;

use Nette\Application\UI\Control;

class Breadcrumb extends Control
{
	/** @var array<callable(static): void> Occurs when component is attached to presenter */
	public $onAnchor = [];

	/**
	 * @var \stdClass[]
	 */
	private array $items = [];
	
	public function addItem(string $name, ?string $link = null): void
	{
		$this->items[] = (object) ['name' => $name, 'link' => $link];
	}
	
	/**
	 * @return \stdClass[]
	 */
	public function getItems(): array
	{
		return $this->items;
	}
	
	public function render(): void
	{
		$this->template->items = $this->items;
		$this->template->render($this->template->getFile() ?: __DIR__ .'/Breadcrumb.latte');
	}
}
