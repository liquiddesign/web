<?php

declare(strict_types=1);

namespace Web\Controls;

interface ITabFactory
{
	public function create(string $id): Tab;
}
