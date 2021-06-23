<?php

declare(strict_types=1);

namespace Web\Controls;

interface IGalleryFactory
{
	public function create(string $id): Gallery;
}
