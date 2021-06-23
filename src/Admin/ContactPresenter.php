<?php

declare(strict_types=1);

namespace Web\Admin;

use Admin\BackendPresenter;
use Admin\Controls\AdminForm;
use Admin\Controls\AdminGrid;
use Web\DB\Contact;
use Web\DB\ContactRepository;
use Nette\Utils\Random;

class ContactPresenter extends BackendPresenter
{
	/** @inject */
	public ContactRepository $contactRepo;
	
	public string $tContacts;
	
	public function beforeRender(): void
	{
		parent::beforeRender();
		
		$this->tContacts = $this->_('contacts', 'Kontakty');
	}
	
	public function renderDefault(): void
	{
		$this->template->headerLabel = $this->tContacts;
		$this->template->headerTree = [
			[$this->tContacts],
		];
		$this->template->displayButtons = [$this->createNewItemButton('new')];
		$this->template->displayControls = [$this->getComponent('grid')];
	}
	
	public function renderNew(): void
	{
		$tNew = $this->_('newContact', 'Nový kontakt');
		$this->template->headerLabel = $tNew;
		$this->template->headerTree = [
			[$this->tContacts, 'default'],
			[$tNew],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('form')];
	}
	
	public function renderDetail(): void
	{
		$tDetail = $this->_('detailContact', 'Detail kontaktu');
		$this->template->headerLabel = $tDetail;
		$this->template->headerTree = [
			[$this->tContacts, 'default'],
			[$tDetail],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('form')];
	}
	
	public function actionDetail(Contact $contact): void
	{
		/** @var \Admin\Controls\AdminForm $form */
		$form = $this->getComponent('form');
		$form->setDefaults($contact->toArray());
	}
	
	public function createComponentForm(): AdminForm
	{
		$form = $this->formFactory->create(true, true, true, true);
		$form->setLogging('contact');
		$form->addText('fullName', $this->_('name', 'Jméno'));
		$form->addLocaleText('position', $this->_('position', 'Pozice'));
		$form->addText('email', 'E-mail');
		$form->addText('phone', $this->_('phone', 'Telefon'));
		$imagePicker = $form->addImagePicker('image', $this->_('picture', 'Obrázek'), [
			Contact::IMAGE_DIR . '/' => null,
		]);
		
		/** @var \Web\DB\Contact $contact */
		$contact = $this->getParameter('contact');
		
		$imagePicker->onDelete[] = function () use ($contact): void {
			if ($contact) {
				$contact->update(['image' => '']);
				$this->redirect('this');
			}
		};
		
		$form->addHidden('id')->setDefaultValue(Random::generate(4));
		$form->addSubmits(!$contact);
		$form->onSuccess[] = function (AdminForm $form) use ($contact): void {
			$values = $form->getValues('array');
			$this->generateDirectories([Contact::IMAGE_DIR]);
			
			$values['image'] = $form['image']->upload($values['uuid'] . '.%2$s');
			$contact = $this->contactRepo->syncOne($values, $form->getChangedProperties(), true, true);
			
			$this->flashMessage($this->_('.saved', 'Uloženo'), 'success');
			$form->processRedirect('detail', 'default', [$contact]);
		};
		
		return $form;
	}
	
	public function createComponentGrid(): AdminGrid
	{
		$grid = $this->gridFactory->create($this->contactRepo->many(), 200, 'uuid', 'ASC');
		$grid->addColumnSelector();
		$grid->setLogging('contact');
		$grid->addColumnImage('image', 'contacts', '', $this->_('picture', 'Obrázek'));
		$grid->addColumnText($this->_('name', 'Jméno'), 'fullName', '%s', 'fullName');
		$grid->addColumnText($this->_('position', 'Pozice'), 'position', '%s', 'position');
		$grid->addColumnText('E-mail', 'email', '%s', 'email');
		$grid->addColumnText($this->_('phone', 'Telefon'), 'phone', '%s', 'phone');
		$grid->addColumnText($this->_('.code', 'Kód'), 'id', '{control contact-%s}', 'id');
		$grid->addColumnLinkDetail();
		$grid->addColumnActionDelete();
		$grid->addButtonSaveAll();
		$grid->addButtonDeleteSelected();
		
		return $grid;
	}
}
