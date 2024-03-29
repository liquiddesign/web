<?php

declare(strict_types=1);

namespace Web\Controls;

interface IWidgetFactory
{
	public function create(): Widget;
}
