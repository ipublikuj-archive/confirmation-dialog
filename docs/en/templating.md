# Custom templates

This extension come with three predefined templates:

* bootstrap.latte if you are using [Twitter Bootstrap](http://getbootstrap.com/)
* uikit.latte if you are using [YooTheme UIKit](http://getuikit.com/)
* default.latte for custom styling (this template is loaded as default)

If you are using one of the front-end framework you can just define it:

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
	 * @return ConfirmationDialog\Control
	 */
	protected function createComponentConfirmAction()
	{
		// Init action confirm
		$dialog = $this->confirmationDialogFactory->create();

        // Define template
        $dialog->setTemplateFile('bootstrap.latte');
        
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
}
```

With method **setTemplateFile** you can define one of the three predefined templates. This will define template globally for all confirmers.

## Different template for each confirmer

If you need to define custom different templates for specific confirmer, you can do it in the similar way. At first you have to create component and define confirmers.

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

        // Define template for first confirmer
        $dialog->getConfirmer('confirmerName')->setTemplateFile('bootstrap.latte');

        // Define template for second confirmer
        $dialog->getConfirmer('nextConfirmerName')->setTemplateFile('default.latte');

		return $dialog;
	}
}
```

So now when you open first **confirmerName** confirmer, the bootstrap.latte template will be used. But for the second confirmer **nextConfirmerName**, the default.latte will be used instead.

## Custom templates

If you don't want to use one of the predefined template from extension, you can define your own custom template. The way how to handle is same as in predefined templates:

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
	 * @return ConfirmationDialog\Control
	 */
	protected function createComponentConfirmAction()
	{
		// Init action confirm
		$dialog = $this->confirmationDialogFactory->create();
		
        $dialog->setTemplateFile('path/to/your/template.latte');
        
        ....
    }
}
```

## Global layout

As this extension is created from one control with sub-controls, you can define global layout template. This global layout is for creating a wrapper of the confirmers and can be defined similarly:

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
	 * @return ConfirmationDialog\Control
	 */
	protected function createComponentConfirmAction()
	{
		// Init action confirm
		$dialog = $this->confirmationDialogFactory->create();
		
        $dialog->setLayoutFile('path/to/your/layout.latte');
        
        ....
    }
}
```

Your custom layout file have to have structure as [default layout](https://github.com/iPublikuj/confirmation-dialog/blob/master/src/IPub/ConfirmationDialog/Components/template/layout.latte), check it out.