<?php

declare(strict_types=1);

namespace Web\Admin;

use Admin\BackendPresenter;
use Admin\Controls\AdminForm;
use Admin\Controls\AdminGrid;
use Nette\Forms\Controls\TextInput;
use Web\DB\Cover;
use Web\DB\CoverRepository;
use Nette\Utils\Random;

class CoverPresenter extends BackendPresenter
{
	/** @inject */
	public CoverRepository $coverRepo;
	
	public function renderDefault(): void
	{
		$this->template->headerLabel = "Covers";
		$this->template->headerTree = [
			['Covers'],
		];
		$this->template->displayButtons = [$this->createNewItemButton('new')];
		$this->template->displayControls = [$this->getComponent('grid')];
	}
	
	public function renderNew(): void
	{
		$tNewCover = $this->_('newCover', 'Nový cover');
		$this->template->headerLabel = $tNewCover;
		$this->template->headerTree = [
			['Covers', 'default'],
			[$tNewCover],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('form')];
	}
	
	public function renderDetail(): void
	{
		$tDetailCover = $this->_('detailCover', 'Detail coveru');
		$this->template->headerLabel = $tDetailCover;
		$this->template->headerTree = [
			['Covers', 'default'],
			[$tDetailCover],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('form')];
	}
	
	public function actionDetail(Cover $cover): void
	{
		/** @var \Admin\Controls\AdminForm $form */
		$form = $this->getComponent('form');
		$form->setDefaults($cover->toArray());
	}
	
	public function createComponentForm(): AdminForm
	{
		$form = $this->formFactory->create(true, true, true);
		$form->addLocaleText('name', $this->_('name', 'Název'));
		$form->addLocaleRichEdit('text', $this->_('content', 'Obsah'));
		$imagePicker = $form->addImagePicker('imageDesktop', $this->_('imageDesktop', 'Obrázek Desktop'), [
			Cover::IMAGE_DIR . '/desktop' => null,
		]);

		$tabletPicker = $form->addImagePicker('imageTablet', $this->_('imageTablet', 'Obrázek Tablet'), [
			Cover::IMAGE_DIR . '/tablet' => null,
		]);
		
		$mobilePicker = $form->addImagePicker('imageMobile', $this->_('imageMobile', 'Obrázek Mobil'), [
			Cover::IMAGE_DIR . '/mobile' => null,
		]);
		
		/** @var \Web\DB\Cover $cover */
		$cover = $this->getParameter('cover');
		
		if ($cover) {
			$imagePicker->onDelete[] = function () use ($cover): void {
				$cover->update(['imageDesktop' => null]);
				$this->redirect('this');
			};

			$tabletPicker->onDelete[] = function () use ($cover): void {
				$cover->update(['imageTablet' => null]);
				$this->redirect('this');
			};
			
			$mobilePicker->onDelete[] = function () use ($cover): void {
				$cover->update(['imageMobile' => null]);
				$this->redirect('this');
			};
		}
		
		$form->addText('heightDesktop', $this->_('heightDesktop', 'Výška Desktop'));
		$form->addText('bgColor', $this->_('bgColor', 'Barva pozadí'));
		$form->addText('styles', $this->_('styles', 'Styly'));
		$form->addText('blend', $this->_('blend', 'Blend mode'));
		$form->addText('cssClass', $this->_('cssClass', 'CSS třída'));
		$form->addLocaleText('showOnPage', $this->_('showOnPage', 'Zobrazit na URL'))->forAll(function (TextInput $input) {
			$input->setRequired(false)->addFilter(function ($value) {
				return \strpos($value, '/') !== false ? $value : '/' . $value;
			});
		});

		$form->addInteger('priority', $this->_('.priority', 'Pořadí'))->setRequired()->setDefaultValue(10);
		$form->addCheckbox('hidden', $this->_('.hidden', 'Skryto'));
		$form->addHidden('id')->setDefaultValue(Random::generate(4));
		$form->addSubmits(!$cover);
		
		$form->onSuccess[] = function (AdminForm $form) use ($cover): void {
			$values = $form->getValues('array');
			$this->generateDirectories([Cover::IMAGE_DIR], ['desktop', 'tablet', 'mobile']);
			
			$values['imageDesktop'] = $form['imageDesktop']->upload($values['uuid'] . '.%2$s');
			$values['imageTablet'] = $form['imageTablet']->upload($values['uuid'] . '.%2$s');
			$values['imageMobile'] = $form['imageMobile']->upload($values['uuid'] . '.%2$s');
			
			$cover = $this->coverRepo->syncOne($values, null, true);
			
			$this->flashMessage($this->_('.saved', 'Uloženo'), 'success');
			$form->processRedirect('detail', 'default', [$cover]);
		};
		
		return $form;
	}
	
	public function createComponentGrid(): AdminGrid
	{
		$grid = $this->gridFactory->create($this->coverRepo->many(), 200, 'priority', 'ASC', true);
		$grid->addColumnSelector();
		$grid->addColumnImage('imageMobile', Cover::IMAGE_DIR, 'mobile', $this->_('imageMobile', 'Obrázek mobil'));
		$grid->addColumnImage('imageDesktop', Cover::IMAGE_DIR, 'desktop', $this->_('imageDesktop', 'Obrázek desktop'));
		$grid->addColumnText($this->_('name', 'Název'), 'name', '%s', 'name');
		$grid->addColumnText('Url', 'showOnPage', '%s', 'showOnPage');
		$grid->addColumnText($this->_('heightDesktop', 'Výška desktop'), 'heightDesktop', '%s', 'heightDesktop');
		$grid->addColumnText($this->_('bgColor', 'Barva pozadí'), 'bgColor', '%s', 'bgColor');
		$grid->addColumnText('CSS class', 'cssClass', '%s', 'cssClass');
		$grid->addColumnInputCheckbox('<i title="'. $this->_('.hidden', 'Skryto') .'" class="far fa-eye-slash"></i>', 'hidden', '', '', 'hidden');
		$grid->addColumnPriority();
		$grid->addColumnMutations('active', false);
		$grid->addColumnLinkDetail();
		$grid->addColumnActionDelete();
		$grid->addButtonSaveAll();
		$grid->addButtonDeleteSelected();
		
		return $grid;
	}
}
