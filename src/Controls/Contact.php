<?php

declare(strict_types=1);

namespace Web\Controls;

use Nette\Application\UI\Control;
use StORM\Exception\NotFoundException;
use Web\DB\ContactRepository;

class Contact extends Control
{
	private ContactRepository $contactRepository;
	
	private string $id;
	
	public function __construct(string $id, ContactRepository $contactRepository)
	{
		$this->contactRepository = $contactRepository;
		$this->id = $id;
	}
	
	public function render(): void
	{
		try {
			$this->template->contact = $this->contactRepository->one(['id' => $this->id], true);
			$this->template->setFile($this->template->getFile() ?: __DIR__ . '/Contact.latte');
			$this->template->render();
		} catch (NotFoundException $x) {
			echo "*** widget #'$this->id' was deleted ***";
		}
	}
}
