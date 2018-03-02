# MyBB-Bot-Protection
Prevent automated registrations.

As automated scripts most likely don't parse javascript at all, this should ensure that the client is a full featured browser, and should be under control of a real human being.

# This is how it works:

The plugin creates a hidden form field on the registration page and fills it with a value of your choice using javascript.

After submission of the registration form, it checks the submitted value. If it matches the one that should have been set by javascript, everything is fine and the account gets created. 

If the value is not properly set (because the client did not parse the javascript) the user is redirected to the registration form with an error message.
