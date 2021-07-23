<?php

declare(strict_types=1);

namespace Web\Admin;

use Admin\BackendPresenter;
use Admin\Controls\AdminForm;
use Admin\Controls\AdminGrid;
use Web\DB\Testimonial;
use Web\DB\TestimonialRepository;
use Nette\Utils\Image;
use Nette\Utils\Random;

class TestimonialPresenter extends BackendPresenter
{
	/** @inject */
	public TestimonialRepository $testimonialRepo;
	
	public string $tTestimonials;
	
	public function beforeRender(): void
	{
		parent::beforeRender();
		
		$this->tTestimonials = $this->_('testimonials', 'Testimoniály');
	}
	
	public function renderDefault(): void
	{
		$this->template->headerLabel = $this->tTestimonials;
		$this->template->headerTree = [
			[$this->tTestimonials],
		];
		$this->template->displayButtons = [$this->createNewItemButton('new')];
		$this->template->displayControls = [$this->getComponent('grid')];
	}
	
	public function renderNew(): void
	{
		$tNewTestimonial = $this->_('newTestimonials', 'Nový testimonial');
		$this->template->headerLabel = $tNewTestimonial;
		$this->template->headerTree = [
			[$this->tTestimonials, 'default'],
			[$tNewTestimonial],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('form')];
	}
	
	public function renderDetail(): void
	{
		$tDetailTestimonial = $this->_('detailTestimonials', 'Detail testimonialu');
		$this->template->headerLabel = $tDetailTestimonial;
		$this->template->headerTree = [
			[$this->tTestimonials, 'default'],
			[$tDetailTestimonial],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('form')];
	}
	
	public function actionDetail(Testimonial $testimonial): void
	{
		/** @var \Admin\Controls\AdminForm $form */
		$form = $this->getComponent('form');
		$form->setDefaults($testimonial->toArray());
	}
	
	public function createComponentForm(): AdminForm
	{
		$form = $this->formFactory->create(true, true, true, true);
		$form->setLogging('testimonial');
		$form->addLocaleText('name', $this->_('name', 'Název'))->setRequired();
		$form->addText('fullName', $this->_('fullName', 'Jméno'));
		$form->addLocaleText('position', $this->_('position', 'Pozice ve společnosti'));
		$form->addLocaleTextArea('text', $this->_('text', 'Text'), null, null);
		$imagePicker = $form->addImagePicker('image', $this->_('picture', 'Fotka osoby'), [
			Testimonial::IMAGE_DIR . '/person' => static function (Image $image): void {
				$image->resize(Testimonial::MIN_WIDTH, Testimonial::MIN_HEIGHT, Image::EXACT);
			}], 'Obrázky vkládejte o velikosti %dx%d px', [Testimonial::MIN_WIDTH, Testimonial::MIN_HEIGHT]);
		
		$logoPicker = $form->addImagePicker('logo', $this->_('logo', 'Logo společnosti'), [
			Testimonial::IMAGE_DIR . '/logo' => null,
		]);
		
		/** @var \Web\DB\Testimonial $testimonial */
		$testimonial = $this->getParameter('testimonial');
		
		if ($testimonial) {
			$imagePicker->onDelete[] = function () use ($testimonial): void {
				$testimonial->update(['image' => null]);
				$this->redirect('this');
			};
			
			$logoPicker->onDelete[] = function () use ($testimonial): void {
				$testimonial->update(['logo' => null]);
				$this->redirect('this');
			};
		}
		
		$form->addHidden('id')->setDefaultValue(Random::generate(4));
		$form->addSubmits(!$testimonial);
		$form->onSuccess[] = function (AdminForm $form) use ($testimonial): void {
			$values = $form->getValues('array');
			$this->generateDirectories([Testimonial::IMAGE_DIR], ['person', 'logo']);
			
			$values['image'] = $form['image']->upload($values['uuid'] . '.%2$s');
			$values['logo'] = $form['logo']->upload($values['uuid'] . '.%2$s');
			
			$testimonial = $this->testimonialRepo->syncOne($values, null, true);
			
			$this->flashMessage($this->_('.saved', 'Uloženo'), 'success');
			$form->processRedirect('detail', 'default', [$testimonial]);
		};
		
		return $form;
	}
	
	public function createComponentGrid(): AdminGrid
	{
		$grid = $this->gridFactory->create($this->testimonialRepo->many(), 200, 'priority', 'ASC');
		$grid->addColumnSelector();
		$grid->setLogging('testimonial');
		$grid->addColumnImage('image', 'testimonials', 'person', $this->_('picture', 'Fotka osoby'));
		$grid->addColumnImage('logo', 'testimonials', 'logo', $this->_('logo', 'Logo společnosti'));
		$grid->addColumnText($this->_('fullName', 'Jméno'), 'fullName', '%s', 'fullName');
		$grid->addColumnText($this->_('.code', 'Kód'), 'id', '{control testimonial-%s}', 'id');
		$grid->addColumnText('Pozice', 'position', '%s', 'position');
		$grid->addColumnLinkDetail();
		$grid->addColumnActionDelete();
		$grid->addButtonSaveAll();
		$grid->addButtonDeleteSelected();
		
		return $grid;
	}
}
