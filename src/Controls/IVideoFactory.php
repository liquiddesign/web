<?php

declare(strict_types=1);

namespace Web\Controls;

interface IVideoFactory
{
	public function create(string $id): Video;
}
