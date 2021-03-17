<?php

namespace Web\Controls;

use Nette\Application\UI\Control;

class Breadcrumb extends Control
{
	/**
	 * @var \stdClass[]
	 */
	private array $items = [];
	
	public function addItem(string $name, ?string $link = null)
	{
		$this->items[] = (object) ['name' => $name, 'link' => $link];
	}
	
	public function render(): void
	{
		$this->template->items = $this->items;
		$this->template->render(__DIR__ .'/Breadcrumb.latte');
	}
}