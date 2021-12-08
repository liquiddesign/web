<?php

declare(strict_types=1);

namespace Web\Controls;

interface ITestimonialFactory
{
	public function create(string $id): Testimonial;
}
