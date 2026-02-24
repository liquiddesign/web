<?php

declare(strict_types=1);

namespace Web\Admin;

use Admin\BackendPresenter;
use Admin\Controls\AdminForm;
use Admin\Controls\AdminGrid;
use Base\DB\Shop;
use Forms\Form;
use Nette\Forms\Container;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Controls\TextInput;
use Nette\Utils\Html;
use Nette\Utils\Strings;
use Pages\Helpers;
use Pages\Pages;
use StORM\DIConnection;
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

		$availableShopPks = \array_keys($this->shopsConfig->getAvailableShops());
		$shopCount = \count($availableShopPks);

		if ($shopCount > 0) {
			$shopCoverage = [];

			foreach ($this->pageRepository->many()->where('this.fk_shop IS NOT NULL') as $p) {
				$shopCoverage[$p->type . '|' . $p->params] = ($shopCoverage[$p->type . '|' . $p->params] ?? 0) + 1;
			}

			$copyColumn = $grid->addColumn('', function (Page $page, $datagrid) {
				return $datagrid->getPresenter()->link('copy', $page);
			}, '<a class="btn btn-outline-primary btn-sm text-xs" style="white-space: nowrap" href="%s"><i class="far fa-copy"></i></a>', null, ['class' => 'minimal']);

			$copyColumn->onRenderCell[] = function (Html $td, Page $page) use ($shopCoverage, $shopCount): void {
				$key = $page->type . '|' . $page->params;

				if (!(($shopCoverage[$key] ?? 0) >= $shopCount)) {
					return;
				}

				$td[0] = '';
			};
		}

		$column = $grid->addColumnActionDelete(null, false, function (Page $page) {
			return $page->isDeletable();
		});

		$column->onRenderCell[] = function (Html $td, Page $object): void {
			if ($object->isSystemic()) {
				$td[0] = "<button type='button' class='btn btn-sm btn-danger disabled' title='Systémová stránka'><i class='far fa-trash-alt'></i></button>";
			}
		};

		$grid->addButtonSaveAll([], [], 'this.uuid');

		$grid->addButtonDeleteSelected(null, false, function (Page $page) {
			return $page->isDeletable();
		}, 'this.uuid');

		$grid->addFilterTextInput('search', ["this.name_$this->lang", "this.url_$this->lang", "this.title_$this->lang"], null, 'Název, url, titulek');

		$types = \array_map(function ($pageType) {
			return $pageType->getName();
		}, $this->pages->getPageTypes());

		$grid->addFilterSelectInput('search2', 'type = :s', null, null, null, ['' => 'Typ stránky - Vše'] + $types, 's');
		$grid->addFilterCheckboxInput('system', 'systemic=1', 'Systémové');


		$grid->addFilterButtons();

		return $grid;
	}

	public function createComponentForm(): Form
	{
		$form = $this->formFactory->create(true, useShops: true);
		$form->setPrettyPages(true);

		/** @var \Nette\Forms\Controls\SelectBox|null $shopInput */
		$shopInput = $form['shop'] ?? null;

		$page = $this->getParameter('page');

		/** @var \Web\DB\Page|null $sourcePage */
		$sourcePage = $this->getParameter('sourcePage');

		if ($shopInput) {
			$items = $shopInput->getItems();
			unset($items['']);
			$shopInput->setItems($items);
		}

		if ($page) {
			$shopInput?->setDisabled()->setDefaultValue($page->shop);
			$shops = $page->shop ? [$page->shop] : [];
		} elseif ($sourcePage) {
			// Remove shops that already have a page with this type+params
			if ($shopInput) {
				$takenShops = [];

				foreach ($this->pageRepository->many()->where('this.type', $sourcePage->type)->where('this.params', $sourcePage->params)->where('this.fk_shop IS NOT NULL') as $p) {
					$takenShops[] = $p->getValue('shop');
				}

				$items = $shopInput->getItems();

				foreach ($takenShops as $takenShopPk) {
					unset($items[$takenShopPk]);
				}

				$shopInput->setItems($items);
			}

			$shops = [];
		} else {
			$shops = [];
		}

		if (!$page && !$sourcePage) {
			$pageTypes = \array_map(function ($pageType) {
				return $pageType->getName();
			}, $this->pages->getPageTypes());

			$select = $form->addSelect('type', 'Typ stránky', $pageTypes)->setRequired(true)->setPrompt('Žádný');

			foreach (\array_keys($this->pages->getPageTypes()) as $typeId) {
				$select->addCondition($form::Equal, $typeId)->toggle($typeId);
			}

			foreach ($this->pages->getPageTypes() as $typeId => $pageType) {
				$form->addGroup('Parametry - ' . $pageType->getName())
					->setOption('container', Html::el('fieldset')->style('display:none;'))
					->setOption('id', $typeId);
				$container = $form->addContainer("_$typeId");

				foreach (\array_keys($pageType->getRequiredParameters()) as $name) {
					$container->addText($name, Strings::firstUpper($name))->addConditionOn($select, $form::Equal, $typeId)->setRequired();
				}

				foreach (\array_keys($pageType->getOptionalParameters()) as $name) {
					$container->addText($name, Strings::firstUpper($name))->setNullable();
				}
			}
		}

		if ($sourcePage && !$page) {
			$form->addHidden('type', $sourcePage->type);
		}

		$type = $page ? $page->type : $sourcePage?->type;
		$params = $page ? $page->getParsedParameters() : ($sourcePage ? $sourcePage->getParsedParameters() : []);
		// For copy mode, pass null type so addSubPageContainer won't look up the source page
		$pagesContainer = $form->addPageContainer($sourcePage ? null : $type, $sourcePage ? [] : $params, shops: $shops);

		foreach ($pagesContainer as $pageContainer) {
			// In copy mode, remove URL uniqueness rules — shop is not known at form build time
			// and the default validation checks globally across all shops
			if ($sourcePage) {
				/** @var \Forms\LocaleContainer $urlContainer */
				$urlContainer = $pageContainer['url'];

				foreach ($urlContainer->getComponents() as $urlInput) {
					if ($urlInput instanceof TextInput) {
						$urlInput->setRequired(false)->getRules()->reset();
						$urlInput->setNullable();
					}
				}
			}

			$pageContainer->addText('robots', 'Robots');
			$pageContainer->addLocaleText('canonicalUrl', 'Canonická URL');
		}

		$form->addGroup('Sitemap');
		$form->addPolyfillDatetime('lastmod', 'Poslední změna')->setNullable();
		$frequency = ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'];
		$form->addSelect('changefreq', 'Frekvence', \array_combine($frequency, $frequency))->setPrompt('Žádná');
		$form->addText('priority', 'Priorita')->setHtmlType('number');

		$this->insertBeforeSubmits($form, $page);

		$form->bind(null, ['page' => $this->pageRepository->getStructure()]);

		$form->addSubmits();

		if (!$page) {
			$form->onValidate[] = function (AdminForm $form) use ($sourcePage): void {
				$values = $form->getUntrustedValues('array');

				$type = $sourcePage ? $sourcePage->type : $values['type'];
				$params = $sourcePage ? $sourcePage->params : Helpers::serializeParameters($values["_$type"] ?? []);
				$shopPk = $values['shop'] ?? null;

				$existing = $this->pageRepository->many()
					->where('this.type', $type)
					->where('this.params', $params)
					->where('this.fk_shop', $shopPk ?: null)
					->first();

				if (!$existing) {
					return;
				}

				if ($sourcePage) {
					\assert($form['shop'] instanceof BaseControl);
					$form['shop']->addError('Stránka s tímto typem a parametry pro zvolený obchod již existuje');
				} else {
					\assert($form['type'] instanceof BaseControl);
					$form['type']->addError($params ? 'Stránka s těmito parametry již exituje' : 'Tento typ stránky již existuje');
				}
			};
		}

		$form->onSuccess[] = function (AdminForm $form) use ($sourcePage): void {
			$values = $form->getValues('array');
			$savedPage = null;

			if (isset($values['type'])) {
				$pageType = $values['type'];
				$pageParams = Helpers::serializeParameters($values["_$pageType"] ?? []);
			} elseif ($sourcePage) {
				$pageType = $sourcePage->type;
				$pageParams = $sourcePage->params;
			} else {
				$pageType = null;
				$pageParams = null;
			}

			$values['priority'] = $values['priority'] === '' ? null : (float) $values['priority'];

			$form->syncPages(function (array $pageValues, Shop|null $shop, string $containerIndex) use (&$savedPage, $values, $pageType, $pageParams): void {
				if ($pageType !== null) {
					$pageValues['type'] = $pageType;
					$pageValues['params'] = $pageParams;
				}

				if (isset($pageValues['uuid']) === false || Strings::length($pageValues['uuid']) === 0) {
					$pageValues['uuid'] = DIConnection::generateUuid();
				}

				$pageValues['lastmod'] = $values['lastmod'];
				$pageValues['changefreq'] = $values['changefreq'];
				$pageValues['priority'] = $values['priority'];

				if (!$shop && isset($values['shop'])) {
					$pageValues['shop'] = $values['shop'];
				}

				$this->beforeSync($pageValues, $values);

				$savedPage = $this->pageRepository->syncOne($pageValues, null, true);
			});

			$this->formFactory->cleanPagesCache();
			$this->flashMessage('Uloženo', 'success');
			$form->processRedirect('detail', 'default', [$savedPage]);
		};

		return $form;
	}

	public function renderDefault(): void
	{
		$this->template->headerLabel = 'Vstupní stránky';
		$this->template->headerTree = [
			['Vstupní stránky'],
		];
		$this->template->displayButtons = [$this->createNewItemButton('new')];
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
	}

	public function actionCopy(Page $sourcePage): void
	{
		/** @var \Forms\Form $form */
		$form = $this->getComponent('form');

		$defaults = $sourcePage->toArray();
		unset($defaults['uuid'], $defaults['shop']);
		$form->setDefaults($defaults);

		$pageDefaults = $sourcePage->toArray();
		$pageDefaults['uuid'] = null;

		\assert($form['page'] instanceof Container);

		foreach ($form['page']->getComponents() as $pageSubContainer) {
			\assert($pageSubContainer instanceof Container);
			$pageSubContainer->setDefaults($pageDefaults);
		}
	}

	public function renderCopy(): void
	{
		$this->template->headerLabel = 'Kopie stránky pro jiný obchod';
		$this->template->headerTree = [
			['Vstupní stránky', 'default'],
			['Kopie stránky'],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('form')];
	}

	protected function insertBeforeSubmits(Form $form, ?Page $page): void
	{
		unset($form, $page);
	}

	protected function beforeSync(array &$pageValues, array $formValues): void
	{
		unset($pageValues, $formValues);
	}
}
