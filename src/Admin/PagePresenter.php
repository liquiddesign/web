<?php

declare(strict_types=1);

namespace Web\Admin;

use Admin\BackendPresenter;
use Admin\Controls\AdminForm;
use Admin\Controls\AdminGrid;
use Forms\Form;
use Pages\Pages;
use Web\DB\Page;
use Web\DB\PageRepository;

class PagePresenter extends BackendPresenter
{
	/**
	 * @inject
	 */
	public PageRepository $pageRepository;
	
	/**
	 * @inject
	 */
	public Pages $pages;
	
	public function createComponentGrid(): AdminGrid
	{
		$grid = $this->gridFactory->create($this->pageRepository->many(), 20, 'this.type');
		$grid->addColumnSelector();
		
		$baseUrl = $this->getHttpRequest()->getUrl()->getBaseUrl();
		
		$grid->addColumn('URL', static function ($item) use ($baseUrl) {
			return '<a href="' . $baseUrl . $item->url . '" target="_blank"><i class="fa fa-external-link-square-alt"></i>&nbsp;' . $baseUrl . $item->url . '</a>';
		}, '%s', 'this.url_cs');
		
		$grid->addColumnText('Titulek a popisek', ['title', 'description'], '%s<br><small>%s</small>', 'title');
		
		$pages = $this->pages;
		$grid->addColumn('Typ stránky', function (Page $page) use ($pages) {
			return $pages->getPageType($page->type) ? $pages->getPageType($page->type)->getName() : '';
		});
		
		$grid->addColumnMutations('url');
		
		$grid->addColumnInputCheckbox('Nedostupná', 'isOffline');
		$grid->addColumnLinkDetail('detail');
		
		$column = $grid->addColumnActionDelete(null, false, function (Page $page) {
			return $page->isDeletable();
		});
		
		$column->onRenderCell[] = function (\Nette\Utils\Html $td, Page $object): void {
			if ($object->isSystemic()) {
				$td[0] = "<button type='button' class='btn btn-sm btn-danger disabled' title='Systémová stránka'><i class='far fa-trash-alt'></i></button>";
			}
		};
		
		$grid->addButtonSaveAll([], [], 'this.uuid');
		
		$grid->addButtonDeleteSelected(null, false, function (Page $page) {
			return $page->isDeletable();
		}, 'this.uuid');
		
		//$inputs = ['page' => ['title', 'description', 'robots'], 'lastmod', 'changefreq', 'priority'];
		//$grid->addButtonBulkEdit('form', $inputs);
		
		$grid->addFilterTextInput('search', ["this.name_$this->lang", "this.url_$this->lang", "this.title_$this->lang"], null, 'Název, url, titulek');
		
		$types = [];

		foreach ($this->pages->getPageTypes() as $type => $pageType) {
			$types[$type] = $pageType->getName();
		}
		
		$grid->addFilterSelectInput('search2', 'type = :s', null, null, null, [null => 'Typ stránky - Vše'] + $types, 's');
		$grid->addFilterCheckboxInput('system', 'systemic=1', 'Systémové');
		
		
		$grid->addFilterButtons();
		
		return $grid;
	}
	
	public function createComponentForm(): Form
	{
		$form = $this->formFactory->create(true);
		$form->setPrettyPages(true);
		
		$page = $this->getParameter('page');
		
		if (!$page) {
			$pageTypes = [];

			foreach ($this->pages->getPageTypes() as $id => $pageType) {
				$pageTypes[$id] = $pageType->getName();
			}
			
			$select = $form->addSelect('type', 'Typ stránky', $pageTypes)->setRequired(true)->setPrompt('Žádný');

			foreach (\array_keys($this->pages->getPageTypes()) as $typeId) {
				$select->addCondition($form::EQUAL, $typeId)->toggle($typeId);
			}
			
			foreach ($this->pages->getPageTypes() as $typeId => $pageType) {
				$form->addGroup('Parametry - ' . $pageType->getName())
					->setOption('container', \Nette\Utils\Html::el('fieldset')->style('display:none;'))
					->setOption('id', $typeId);
				$container = $form->addContainer("_$typeId");

				foreach (\array_keys($pageType->getRequiredParameters()) as $name) {
					$container->addText($name, \ucfirst($name))->addConditionOn($select, $form::EQUAL, $typeId)->setRequired();
				}
				
				foreach (\array_keys($pageType->getOptionalParameters()) as $name) {
					$container->addText($name, \ucfirst($name))->setNullable();
				}
			}
		}
		
		$pageContainer = $form->addPageContainer($page ? $page->type : null, $page->getParsedParameters());
		$pageContainer->addText('robots', 'Robots');
		$pageContainer->addLocaleText('canonicalUrl', 'Canonická URL');
		
		$form->addGroup('Sitemap');
		$form->addDatetime('lastmod', 'Poslední změna')->setNullable();
		$frequency = ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'];
		$form->addSelect('changefreq', 'Frekvence', \array_combine($frequency, $frequency))->setPrompt('Žádná');
		$form->addText('priority', 'Priorita')->setHtmlType('number');
		
		$form->bind(null, ['page' => $this->pageRepository->getStructure()]);
		
		$form->addSubmits(!$this->getParameter('page'));
		
		if (!$page) {
			$form->onValidate[] = function (AdminForm $form): void {
				$values = $form->getValues('array');

				if (!$this->pageRepository->getPageByTypeAndParams($values['type'], null, $values["_$values[type]"])) {
					return;
				}

				$form['type']->addError($values["_$values[type]"] ? 'Stránka s těmito parametry již exituje' : 'Tento typ stránky již existuje');
			};
		}
		
		$form->onSuccess[] = function (AdminForm $form): void {
			$values = $form->getValues('array');
			
			if (isset($values['type'])) {
				$values['page']['type'] = $values['type'];
				$values['page']['params'] = \Pages\Helpers::serializeParameters($values["_$values[type]"]);
			}
			
			$values['priority'] = $values['priority'] === '' ? null : (float) $values['priority'];
			
			$page = $this->pageRepository->syncOne($values + $values['page'], null, true);
			
			$this->flashMessage('Uloženo', 'success');
			$form->processRedirect('detail', 'default', [$page]);
		};
		
		return $form;
	}
	
	public function renderDefault(): void
	{
		$this->template->headerLabel = 'Vstupní stránky';
		$this->template->headerTree = [
			['Vstupní stránky'],
		];
		//$this->template->displayButtons = [$this->createNewItemButton('new')];
		$this->template->displayControls = [$this->getComponent('grid')];
	}
	
	public function renderNew(): void
	{
		$this->template->headerLabel = 'Nová vstupní stránka';
		$this->template->headerTree = [
			['Vstupní stránky', 'default'],
			['Nová stránka'],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('form')];
	}
	
	public function renderDetail(): void
	{
		$this->template->headerLabel = 'Detail stránky';
		$this->template->headerTree = [
			['Vstupní stránky', 'default'],
			['Detail'],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('form')];
	}
	
	public function actionDetail(Page $page): void
	{
		/** @var \Forms\Form $form */
		$form = $this->getComponent('form');
		
		$form->setDefaults($page->toArray());
		$form['page']->setDefaults($page->toArray());
	}
}
