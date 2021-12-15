<?php

declare(strict_types=1);

namespace Web\Controls;

use Nette;

/**
 * Component widget.
 */
class Widget extends Nette\Application\UI\Component
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

	public function createComponentTextbox(): Nette\Application\UI\Multiplier
	{
		return new Nette\Application\UI\Multiplier(function ($id) {
			return $this->textboxFactory->create($id);
		});
	}

	public function createComponentGallery(): Nette\Application\UI\Multiplier
	{
		return new Nette\Application\UI\Multiplier(function ($id) {
			$galleryControl = $this->galleryFactory->create($id);
			$galleryControl->onAnchor[] = function (Gallery $galleryControl): void {
				if (\key_exists(Gallery::class, $this->defaultTemplates)) {
					$galleryControl->template->setFile($this->defaultTemplates[Gallery::class]);
				}
			};

			return $galleryControl;
		});
	}

	public function createComponentTestimonial(): Nette\Application\UI\Multiplier
	{
		return new Nette\Application\UI\Multiplier(function ($id) {
			$testimonial = $this->testimonialFactory->create($id);
			$testimonial->onAnchor[] = function (Testimonial $testimonial): void {
				if (\key_exists(Testimonial::class, $this->defaultTemplates)) {
					$testimonial->template->setFile($this->defaultTemplates[Testimonial::class]);
				}
			};

			return $testimonial;
		});
	}

	public function createComponentContact(): Nette\Application\UI\Multiplier
	{
		return new Nette\Application\UI\Multiplier(function ($id) {
			$contactControl = $this->contactFactory->create($id);
			$contactControl->onAnchor[] = function (Contact $contactControl): void {
				if (\key_exists(Contact::class, $this->defaultTemplates)) {
					$contactControl->template->setFile($this->defaultTemplates[Contact::class]);
				}
			};

			return $contactControl;
		});
	}

	public function createComponentCarousel(): Nette\Application\UI\Multiplier
	{
		return new Nette\Application\UI\Multiplier(function ($id) {
			$carouselControl = $this->carouselFactory->create($id);
			$carouselControl->onAnchor[] = function (Carousel $carouselControl): void {
				if (\key_exists(Carousel::class, $this->defaultTemplates)) {
					$carouselControl->template->setFile($this->defaultTemplates[Carousel::class]);
				}
			};

			return $carouselControl;
		});
	}

	public function createComponentFaq(): Nette\Application\UI\Multiplier
	{
		return new Nette\Application\UI\Multiplier(function ($id) {
			$faqControl = $this->faqFactory->create($id);
			$faqControl->onAnchor[] = function (Faq $faqControl): void {
				if (\key_exists(Faq::class, $this->defaultTemplates)) {
					$faqControl->template->setFile($this->defaultTemplates[Faq::class]);
				}
			};

			return $faqControl;
		});
	}

	public function createComponentTab(): Nette\Application\UI\Multiplier
	{
		return new Nette\Application\UI\Multiplier(function ($id) {
			$tabControl = $this->tabFactory->create($id);
			$tabControl->onAnchor[] = function (Tab $tabControl): void {
				if (\key_exists(Tab::class, $this->defaultTemplates)) {
					$tabControl->template->setFile($this->defaultTemplates[Tab::class]);
				}
			};

			return $tabControl;
		});
	}

	public function createComponentVideo(): Nette\Application\UI\Multiplier
	{
		return new Nette\Application\UI\Multiplier(function ($id) {
			$videoControl = $this->videoFactory->create($id);
			$videoControl->onAnchor[] = function (Video $videoControl): void {
				if (\key_exists(Video::class, $this->defaultTemplates)) {
					$videoControl->template->setFile($this->defaultTemplates[Video::class]);
				}
			};

			return $videoControl;
		});
	}

	public function createComponentBanner(): Nette\Application\UI\Multiplier
	{
		return new Nette\Application\UI\Multiplier(function ($id) {
			$bannerControl = $this->bannerFactory->create($id);
			$bannerControl->onAnchor[] = function (Banner $bannerControl): void {
				if (\key_exists(Banner::class, $this->defaultTemplates)) {
					$bannerControl->template->setFile($this->defaultTemplates[Banner::class]);
				}
			};

			return $bannerControl;
		});
	}

	public function createComponentMap(): Nette\Application\UI\Multiplier
	{
		return new Nette\Application\UI\Multiplier(function ($id) {
			$mapControl = $this->mapFactory->create($id);
			$mapControl->onAnchor[] = function (Map $mapControl): void {
				if (\key_exists(Map::class, $this->defaultTemplates)) {
					$mapControl->template->setFile($this->defaultTemplates[Map::class]);
				}
			};

			return $mapControl;
		});
	}

	public function createComponentHubspot(): Nette\Application\UI\Multiplier
	{
		return new Nette\Application\UI\Multiplier(function ($id) {
			$hubspotControl = $this->hubspotFactory->create($id);
			$hubspotControl->onAnchor[] = function (Hubspot $hubspotControl): void {
				if (\key_exists(Hubspot::class, $this->defaultTemplates)) {
					$hubspotControl->template->setFile($this->defaultTemplates[Hubspot::class]);
				}
			};

			return $hubspotControl;
		});
	}
}
