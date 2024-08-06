<?php

namespace Web\Controls;

use Nette\Application\UI\Control;

/**
 * @property \Nette\Application\UI\Template $template
 */
class Breadcrumb extends Control
{
	/**
	 * @var array<\stdClass>
	 */
	private array $items = [];
	
	public function addItem(string $name, ?string $link = null): void
	{
		$this->items[] = (object) ['name' => $name, 'link' => $link];
	}
	
	/**
	 * @return array<\stdClass>
	 */
	public function getItems(): array
	{
		return $this->items;
	}
	
	public function render(): void
	{
		/** @var \Nette\Bridges\ApplicationLatte\Template $template */
		$template = $this->template;

		$this->template->items = $this->items;
		$template->render($this->template->getFile() ?: __DIR__ . '/Breadcrumb.latte');
	}
}
