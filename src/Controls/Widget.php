<?php

declare(strict_types=1);

namespace Web\Controls;

use Nette;

/**
 * Component widget.
 */
final class Widget extends Nette\Application\UI\Component
{
	private ITextboxFactory $textboxFactory;

	private IGalleryFactory $galleryFactory;

	private ITestimonialFactory $testimonialFactory;

	private IContactFactory $contactFactory;

	private ICarouselFactory $carouselFactory;

	private IFaqFactory $faqFactory;

	private ITabFactory $tabFactory;

	private IVideoFactory $videoFactory;

	private IBannerFactory $bannerFactory;

	private IMapFactory $mapFactory;

	private IHubspotFactory $hubspotFactory;

	/**
	 * @var string[]|null[]
	 */
	private array $defaultTemplates = [];

	public function __construct(
		ITextboxFactory $textboxFactory,
		IGalleryFactory $galleryFactory,
		ITestimonialFactory $testimonialFactory,
		IContactFactory $contactFactory,
		ICarouselFactory $carouselFactory,
		IFaqFactory $faqFactory,
		ITabFactory $tabFactory,
		IVideoFactory $videoFactory,
		IBannerFactory $bannerFactory,
		IMapFactory $mapFactory,
		IHubspotFactory $hubspotFactory
	) {
		$this->textboxFactory = $textboxFactory;
		$this->galleryFactory = $galleryFactory;
		$this->testimonialFactory = $testimonialFactory;
		$this->contactFactory = $contactFactory;
		$this->carouselFactory = $carouselFactory;
		$this->faqFactory = $faqFactory;
		$this->tabFactory = $tabFactory;
		$this->videoFactory = $videoFactory;
		$this->bannerFactory = $bannerFactory;
		$this->mapFactory = $mapFactory;
		$this->hubspotFactory = $hubspotFactory;
	}

	public function setDefaultTemplate(string $class, ?string $template): void
	{
		$this->defaultTemplates[$class] = $template;
	}

	public function createComponentTextbox()
	{
		return new Nette\Application\UI\Multiplier(function ($id) {
			return $this->textboxFactory->create($id);
		});
	}

	public function createComponentGallery()
	{
		return new Nette\Application\UI\Multiplier(function ($id) {
			$galleryControl = $this->galleryFactory->create($id);
			$galleryControl->onAnchor[] = function (Gallery $galleryControl): void {
				$galleryControl->template->setFile($this->defaultTemplates[Gallery::class] ?? null);
			};

			return $galleryControl;
		});
	}

	public function createComponentTestimonial()
	{
		return new Nette\Application\UI\Multiplier(function ($id) {
			$testimonial = $this->testimonialFactory->create($id);
			$testimonial->onAnchor[] = function (Testimonial $testimonial): void {
				$testimonial->template->setFile($this->defaultTemplates[Testimonial::class] ?? null);
			};

			return $testimonial;
		});
	}

	public function createComponentContact()
	{
		return new Nette\Application\UI\Multiplier(function ($id) {
			$contactControl = $this->contactFactory->create($id);
			$contactControl->onAnchor[] = function (Contact $contactControl): void {
				$contactControl->template->setFile($this->defaultTemplates[Contact::class] ?? null);
			};

			return $contactControl;
		});
	}

	public function createComponentCarousel()
	{
		return new Nette\Application\UI\Multiplier(function ($id) {
			$carouselControl = $this->carouselFactory->create($id);
			$carouselControl->onAnchor[] = function (Carousel $carouselControl): void {
				$carouselControl->template->setFile($this->defaultTemplates[Carousel::class] ?? null);
			};

			return $carouselControl;
		});
	}

	public function createComponentFaq()
	{
		return new Nette\Application\UI\Multiplier(function ($id) {
			$faqControl = $this->faqFactory->create($id);
			$faqControl->onAnchor[] = function (Faq $faqControl): void {
				$faqControl->template->setFile($this->defaultTemplates[Faq::class] ?? null);
			};

			return $faqControl;
		});
	}

	public function createComponentTab()
	{
		return new Nette\Application\UI\Multiplier(function ($id) {
			$tabControl = $this->tabFactory->create($id);
			$tabControl->onAnchor[] = function (Tab $tabControl): void {
				$tabControl->template->setFile($this->defaultTemplates[Tab::class] ?? null);
			};

			return $tabControl;
		});
	}

	public function createComponentVideo()
	{
		return new Nette\Application\UI\Multiplier(function ($id) {
			$videoControl = $this->videoFactory->create($id);
			$videoControl->onAnchor[] = function (Video $videoControl): void {
				$videoControl->template->setFile($this->defaultTemplates[Video::class] ?? null);
			};

			return $videoControl;
		});
	}

	public function createComponentBanner()
	{
		return new Nette\Application\UI\Multiplier(function ($id) {
			$bannerControl = $this->bannerFactory->create($id);
			$bannerControl->onAnchor[] = function (Banner $bannerControl): void {
				$bannerControl->template->setFile($this->defaultTemplates[Banner::class] ?? null);
			};

			return $bannerControl;
		});
	}

	public function createComponentMap()
	{
		return new Nette\Application\UI\Multiplier(function ($id) {
			$mapControl = $this->mapFactory->create($id);
			$mapControl->onAnchor[] = function (Map $mapControl): void {
				$mapControl->template->setFile($this->defaultTemplates[Map::class] ?? null);
			};

			return $mapControl;
		});
	}

	public function createComponentHubspot()
	{
		return new Nette\Application\UI\Multiplier(function ($id) {
			$hubspotControl = $this->hubspotFactory->create($id);
			$hubspotControl->onAnchor[] = function (Hubspot $hubspotControl): void {
				$hubspotControl->template->setFile($this->defaultTemplates[Hubspot::class] ?? null);
			};

			return $hubspotControl;
		});
	}
}
