<?php

declare(strict_types=1);

namespace Web\Controls;

interface IFaqFactory
{
	public function create(string $id): Faq;
}
