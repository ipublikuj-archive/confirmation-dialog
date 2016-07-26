# Quickstart

This extension brings you special component for creating system confirmation dialogs which could be used for e.g. for confirming delete action etc. This confirmation actions can be single or [chained](https://github.com/iPublikuj/confirmation-dialog/blob/master/docs/en/chaining.md).

## Installation

The best way to install ipub/confirmation-dialog is using [Composer](http://getcomposer.org/):

```sh
$ composer require ipub/confirmation-dialog
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
     * Insert extension trait (only for PHP 5.4+)
     */
    use ConfirmationDialog\TConfirmationDialog;

    /**
     * Component for action confirmation
     *
     * @return ConfirmationDialog\Components\Control
     */
    protected function createComponentConfirmAction()
    {
        // Init action confirm
        $dialog = $this->confirmationDialogFactory->create();

        // Define confirm windows

        // First confirmation window
        $dialog->addConfirmer(
            'confirmerName',
            [$this, 'handleCallback'],
            [$this, 'questionCallback'],
            'Heading of the window'
        );

        // Second confirmation window
        $dialog->addConfirmer(
            'nextConfirmerName',
            [$this, 'handleCallbackTwo'],
            [$this, 'questionCallbackTwo'],
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
        }

        // Item not exists

        // Store info message
        $this->flashMessage("Error, item not found!");

        return FALSE;
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

## More

- [Read more how to chain confirmers](https://github.com/iPublikuj/confirmation-dialog/blob/master/docs/en/chaining.md)
- [Read more about templating system](https://github.com/iPublikuj/confirmation-dialog/blob/master/docs/en/templating.md)
