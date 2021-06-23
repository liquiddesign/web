<?php

declare(strict_types=1);

namespace Web\Controls;

interface ICarouselFactory
{
	public function create(string $id): Carousel;
}