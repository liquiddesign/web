<?php

declare(strict_types=1);

namespace Web\Controls;

interface ITextboxFactory
{
	public function create(string $id): Textbox;
}
