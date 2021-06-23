<?php

declare(strict_types=1);

namespace Web\Controls;

use Nette\Application\UI\Control;
use StORM\Exception\NotFoundException;
use Web\DB\TestimonialRepository;

class Testimonial extends Control
{
	private TestimonialRepository $testimonialRepository;
	
	private string $id;
	
	public function __construct(string $id, TestimonialRepository $testimonialRepository)
	{
		$this->testimonialRepository = $testimonialRepository;
		$this->id = $id;
	}
	
	public function render(): void
	{
		try {
			$this->template->testimonial = $this->testimonialRepository->one(['id' => $this->id], true);
			$this->template->setFile($this->template->getFile() ?: __DIR__ . '/Testimonial.latte');
			$this->template->render();
		} catch (NotFoundException $x) {
			echo "*** widget #'$this->id' was deleted ***";
		}
	}
}
