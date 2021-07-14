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
			return $this->galleryFactory->create($id);
		});
	}

	public function createComponentTestimonial()
	{
		return new Nette\Application\UI\Multiplier(function ($id) {
			return $this->testimonialFactory->create($id);
		});
	}

	public function createComponentContact()
	{
		return new Nette\Application\UI\Multiplier(function ($id) {
			return $this->contactFactory->create($id);
		});
	}

	public function createComponentCarousel()
	{
		return new Nette\Application\UI\Multiplier(function ($id) {
			return $this->carouselFactory->create($id);
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
			return $this->tabFactory->create($id);
		});
	}

	public function createComponentVideo()
	{
		return new Nette\Application\UI\Multiplier(function ($id) {
			return $this->videoFactory->create($id);
		});
	}

	public function createComponentBanner()
	{
		return new Nette\Application\UI\Multiplier(function ($id) {
			return $this->bannerFactory->create($id);
		});
	}

	public function createComponentMap()
	{
		return new Nette\Application\UI\Multiplier(function ($id) {
			return $this->mapFactory->create($id);
		});
	}

	public function createComponentHubspot()
	{
		return new Nette\Application\UI\Multiplier(function ($id) {
			return $this->hubspotFactory->create($id);
		});
	}
}
