# MyBB-Bot-Protection
Prevent automated registrations.

As automated scripts most likely don't parse javascript at all, this should ensure that the client is a full featured browser, and should be under control of a real human being.

# This is how it works:

The plugin creates a hidden form field on the registration page and fills it with a value of your choice using javascript.

After submission of the registration form, it checks the submitted value. If it matches the one that should have been set by javascript, everything is fine and the account gets created. 

If the value is not properly set (because the client did not parse the javascript) the user is redirected to the registration form with an error message.

# How To Install:
• Upload "inc/plugins/botprotection.php" to your plugins folder.

• Upload "inc/languages/english/botprotection.lang.php" to your languages folder.

• Upload "inc/languages/english/admin/forum_botprotection.lang.php" to your admin language folder.

• Click "install" in the admin panel plugin section.



Any person who is using a browser that does not evaluate javascript, will not be able to register.

This might be the case for blind people using a text only browser with a screen reader like jaws, or or other handicapped users.

In case you want to provide your service for them, too, you should not use this plugin, or provide another way of registering.

You might for example offer to create an account manually when requested via email.
