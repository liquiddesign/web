<?php

declare(strict_types=1);

namespace Web\Controls;

interface IContactFactory
{
	public function create(string $id): Contact;
}
