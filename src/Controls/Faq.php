<?php

declare(strict_types=1);

namespace Web\Controls;

use Nette\Application\UI\Control;
use StORM\Exception\NotFoundException;
use Web\DB\FaqRepository;

class Faq extends Control
{
	public FaqRepository $faqRepository;
	
	private string $id;
	
	public function __construct(string $id, FaqRepository $faqRepository)
	{
		$this->faqRepository = $faqRepository;
		$this->id = $id;
	}
	
	public function render(): void
	{
		try {
			$this->template->faq = $this->faqRepository->one(['id' => $this->id], true);
			$this->template->setFile($this->template->getFile() ?: __DIR__ . '/Faq.latte');
			$this->template->render();
		} catch (NotFoundException $x) {
			echo "*** widget #'$this->id' was deleted ***";
		}
	}
}
