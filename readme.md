# Confirmation dialog

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

## Usage

### Create component in Presenter or Control

Extension create component factory which could be used to create confirmation dialog component like this:

```php
namespace Your\Coool\Namespace\Presenter;

use IPub\ConfirmationDialog\Components as ConfirmationDialog;

class SomePresenter
{
	/**
	 * Component for action confirmation
	 *
	 * @return ConfirmationDialog\Control
	 */
	protected function createComponentConfirmAction()
	{
		// Init action confirm
		$form = new ConfirmationDialog\Control;

		// Define confirm windows
		$form
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

		return $form;
	}


	/**
	 * Create question for confirmation dialog
	 *
	 * @param ConfirmationDialog\Control $dialog
	 * @param $params
	 *
	 * @return bool|string
	 */
	public function questionCallback(ConfirmationDialog\Control $dialog, $params)
	{
		// Set dialog icon
		$dialog->setIcon('trash');

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

The link signal is always created with prefix "confirm" like in example!