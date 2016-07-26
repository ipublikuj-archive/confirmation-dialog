# Chanined confirmation

There could be special situations where you need to do some double check and made two questions to confirm. Especially when you are deleting user and this user have for example some articles.

So at first you create confirmation for deleting user, then you do the before delete check and if user has some articles you have to display another confirmation window to confirm hard deleting.

This component is created as usual, define two confirmers, one is for soft delete and the second is for force deleting.

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

        // First confirmation window
        $dialog->addConfirmer(
            'delete',
            [$this, 'deleteUser'],
            'Are you sure to delete selected user?',
            'Deleting of user'
        );

        // Second confirmation window
        $dialog->addConfirmer(
            'forceDelete',
            [$this, 'forceDeleteUser'],
            'Are you sure to delete selected user with all articles etc.?',
            'Deleting of user'
        );

        return $dialog;
    }
```

Now you have to define handler for each confirmer

```php
/**
 * @param int $id
 */
public function deleteUser(int $id)
{
    if (!$db->getUser($id)->delete()) {
        // Store message
        $this->flashMessage('User can not be deleted. Selected user have some articles.', 'error');

        $this->invalidateControl();

        // Open second confirm window
        // The second parameter must contain all method parameters
        $this['confirmAction']->showConfirm('forceDeleteUser', ['id' => $id]);

    } else {
        // Store message
        $this->flashMessage('User was successfully deleted.', 'success');
    }
}

/**
 * @param int $id
 */
public function forceDeleteUser(int $id)
{
    ....
}
```

In case user has some articles, the error message will be shown and also the second confirmer will be opened.
