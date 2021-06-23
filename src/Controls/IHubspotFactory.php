<?php

declare(strict_types=1);

namespace Web\Controls;

interface IHubspotFactory
{
	public function create(string $id): Hubspot;
}
