<?php

declare(strict_types=1);

namespace Web\Controls;

interface IMapFactory
{
	public function create(string $id): Map;
}
