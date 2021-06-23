<?php

declare(strict_types=1);

namespace Web\Controls;

use Nette\Application\UI\Control;
use StORM\Exception\NotFoundException;
use Web\DB\HubspotRepository;

class Hubspot extends Control
{
	private HubspotRepository $hubspotRepository;
	
	private string $id;
	
	public function __construct(string $id, HubspotRepository $hubspotRepository)
	{
		$this->hubspotRepository = $hubspotRepository;
		$this->id = $id;
	}
	
	public function render(): void
	{
		try {
			$this->template->hubspot = $this->hubspotRepository->one(['id' => $this->id], true);
			$this->template->setFile($this->template->getFile() ?: __DIR__ . '/Hubspot.latte');
			$this->template->render();
		} catch (NotFoundException $x) {
			echo "*** widget #'$this->id' was deleted ***";
		}
	}
}
