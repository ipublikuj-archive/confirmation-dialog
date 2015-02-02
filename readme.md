# Confirmation dialog

[![Build Status](https://img.shields.io/travis/iPublikuj/confirmation-dialog.svg?style=flat-square)](https://travis-ci.org/iPublikuj/confirmation-dialog)
[![Latest Stable Version](https://img.shields.io/packagist/v/ipub/confirmation-dialog.svg?style=flat-square)](https://packagist.org/packages/ipub/confirmation-dialog)
[![Composer Downloads](https://img.shields.io/packagist/dt/ipub/confirmation-dialog.svg?style=flat-square)](https://packagist.org/packages/ipub/confirmation-dialog)

Add confirm action dialog on various items for [Nette Framework](http://nette.org/)

## Installation

The best way to install ipub/confirmation-dialog is using  [Composer](http://getcomposer.org/):

```json
{
	"require": {
		"ipub/confirmation-dialog": "dev-master"
	}
}
```

or

```sh
$ composer require ipub/confirmation-dialog:@dev
```

After that you have to register extension in config.neon.

```neon
extensions:
	confirmationDialog: IPub\ConfirmationDialog\DI\ConfirmationDialogExtension
```

Package contains trait, which you will have to use in presenters or components to implement Confirmation Dialog component factory. This works only for PHP 5.4+, for older version you can simply copy trait content and paste it into class where you want to use it.

```php
<?php

class BasePresenter extends Nette\Application\UI\Presenter
{

	use IPub\ConfirmationDialog\TConfirmationDialog;

}
```

## Usage

### Create component in Presenter or Control

Extension create component factory which could be used to create confirmation dialog component like this:

```php
namespace Your\Coool\Namespace\Presenter;

use IPub\ConfirmationDialog;

class SomePresenter
{
	/**
	 * Insert extension trait (only for PHP 5.4+
	 */
	use ConfirmationDialog\TConfirmationDialog;

	/**
	 * Component for action confirmation
	 *
	 * @return ConfirmationDialog\Control
	 */
	protected function createComponentConfirmAction()
	{
		// Init action confirm
		$dialog = $this->confirmationDialogFactory->create();

		// Define confirm windows
		$dialog
			// First confirmation window
			->addConfirmer(
				'confirmerName',
				array($this, 'handleCallback'),
				array($this, 'questionCallback'),
				'Heading of the window'
			)
			// Second confirmation window
			->addConfirmer(
				'nextConfirmerName',
				array($this, 'handleCallbackTwo'),
				array($this, 'questionCallbackTwo'),
				'Heading of the second window'
			);

		return $dialog;
	}


	/**
	 * Create question for confirmation dialog
	 *
	 * @param ConfirmationDialog\Components\Confirmer $confirmer
	 * @param $params
	 *
	 * @return bool|string
	 */
	public function questionCallback(ConfirmationDialog\Components\Confirmer $confirmer, $params)
	{
		// Set dialog icon
		$confirmer->setIcon('trash');

		// Find item to do some action
		if ($item = $this->dataModel->findOneByIdentifier($params['id'])) {
			// Create question
			return 'Are you sure to do this action with: '. $item->title;

		// Item not exists
		} else {
			// Store info message
			$this->flashMessage("Error, item not found!");

			return FALSE;
		}
	}

	/**
	 * Handler for confirmed action
	 *
	 * @param int $id
	 */
	public function handleCallback($id)
	{
		// ...
		// Classic handle like without confirm dialog
	}
}
```

### Add confirmer to template

And finally you have to add confirmer to the template.

```html
{control confirmAction}

{foreach $items as $item}
    <a href="{link confirmAction:confirmConfirmerName! id => $item->id}">Do something with item {$item->title}</a>
    <a href="{link confirmAction:confirmNextConfirmerName! id => $item->id}">Do something else with item {$item->title}</a>
{/foreach}
```

The link for signal is always created with prefix "confirm" like in example!
