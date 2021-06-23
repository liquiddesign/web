<?php

declare(strict_types=1);

namespace Web\Controls;

interface IBannerFactory
{
	public function create(string $id): Banner;
}
