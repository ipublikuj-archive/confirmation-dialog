# Custom templates

This extension come with three predefined templates:

* bootstrap if you are using [Twitter Bootstrap](http://getbootstrap.com/)
* uikit if you are using [YooTheme UIKit](http://getuikit.com/)
* default for custom styling (this template is loaded as default)

If you are using one of the front-end framework you can just define it:

```php
namespace Your\Coool\Namespace\Presenter;

class SomePresenter
{
    /**
     * Insert extension trait (only for PHP 5.4+)
     */
    use \IPub\ConfirmationDialog\TConfirmationDialog;

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
        $dialog->setTemplateFile('bootstrap');
        
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
}
```

With method **setTemplateFile** you can define one of the three predefined templates. This will define template globally for all confirmers.

## Different template for each confirmer

If you need to define custom different templates for specific confirmer, you can do it in the similar way. At first you have to create component and define confirmers.

```php
namespace Your\Coool\Namespace\Presenter;

class SomePresenter
{
    /**
     * Insert extension trait (only for PHP 5.4+)
     */
    use \IPub\ConfirmationDialog\TConfirmationDialog;

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

        // Define template for first confirmer
        $dialog->getConfirmer('confirmerName')->setTemplateFile('bootstrap');

        // Define template for second confirmer
        $dialog->getConfirmer('nextConfirmerName')->setTemplateFile('default');

        return $dialog;
    }
}
```

So now when you open first **confirmerName** confirmer, the bootstrap template will be used. But for the second confirmer **nextConfirmerName**, the default will be used instead.

## Custom templates

If you don't want to use one of the predefined template from extension, you can define your own custom template. The way how to handle is same as in predefined templates:

```php
namespace Your\Coool\Namespace\Presenter;

class SomePresenter
{
    /**
     * Insert extension trait (only for PHP 5.4+)
     */
    use \IPub\ConfirmationDialog\TConfirmationDialog;

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

class SomePresenter
{
    /**
     * Insert extension trait (only for PHP 5.4+)
     */
    use \IPub\ConfirmationDialog\TConfirmationDialog;

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

## Define template with factory

Factory which is creating component can pass info about what template should be used. So if you know which template use when you are creating confirmer container or confirmer component, you can pass it:

```php
namespace Your\Coool\Namespace\Presenter;

class SomePresenter
{
    /**
     * Insert extension trait (only for PHP 5.4+)
     */
    use \IPub\ConfirmationDialog\TConfirmationDialog;

    /**
     * Component for displaying messages
     *
     * @return ConfirmationDialog\Control
     */
    protected function createComponentConfirmAction()
    {
        // Create control
        $control = $this->confirmationDialogFactory->create('customlayout.latte', 'bootstrap');

        // or

        $control = $this->confirmationDialogFactory->create('path/to/your/custom/layout.latte', 'path/to/your/template.latte');

        // or
        
        $control = $this->confirmationDialogFactory->create('customlayout.latte');
        $control = $this->confirmationDialogFactory->create(NULL, 'bootstrap');

        $control = $this->confirmationDialogFactory->create('path/to/your/custom/layout.latte');
        $control = $this->confirmationDialogFactory->create(NULL, 'path/to/your/template.latte');
        ....
    }
}
```

## Define template in the configuration

Another way how to configure template is in extension configuration.

```neon
    confirmationDialog:
        layoutFile      : /path/to/your/layout/template.latte
        templateFile    : bootstrap // uikit // default // or/path/to/your/template.latte
```

System will automatically asset this template into components

## More

- [Read how to do default configuration](https://github.com/iPublikuj/confirmation-dialog/blob/master/docs/en/index.md)
- [Read more how to chain confirmers](https://github.com/iPublikuj/confirmation-dialog/blob/master/docs/en/chaining.md)
